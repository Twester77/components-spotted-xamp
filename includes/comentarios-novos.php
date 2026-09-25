<?php
/**
 * comentarios-novos.php – Endpoint de polling para comentários em tempo real
 *
 * Chamado pelo comentarios-post.php a cada 4 segundos.
 * Retorna APENAS os comentários novos (id > ultimo_id) com status 'ativo'.
 *
 * 🔒 Segurança:
 * - Não escreve nada no banco (só SELECT).
 * - Respeita a regra do post: se for de comunidade, exige login do mesmo jeito
 *   que o comentarios-post.php exige. Se for perdidos, aceita anônimo.
 * - Prepared statements.
 * - Rate limiting por IP (60 req/min — bem generoso pra não atrapalhar).
 *
 * 🐚 IARA – 2026-09-25 (auditoria v5.0)
 *    - Endpoint criado para polling em tempo real do comentarios-post.php.
 *    - Pausa em background é responsabilidade do front (document.hidden).
 *
 * @package A Fenda
 */

error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/upload_engine.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ============================================================
// 1. VALIDAÇÃO DE PARÂMETROS
// ============================================================
$id_mensagem = isset($_GET['id_mensagem']) ? (int)$_GET['id_mensagem'] : 0;
$ultimo_id   = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : 0;

if ($id_mensagem <= 0) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'ID da mensagem inválido.']);
    exit;
}

// ============================================================
// 2. RATE LIMITING por IP (60 req/min — generoso)
// ============================================================
$ip = function_exists('obterIPReal') ? obterIPReal() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

$conn->query("CREATE TABLE IF NOT EXISTS rate_limiter_polling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_tentativa (tentativa)
)");

$stmt_rate = $conn->prepare("SELECT COUNT(*) as total FROM rate_limiter_polling WHERE ip_address = ? AND tentativa > NOW() - INTERVAL 1 MINUTE");
$stmt_rate->bind_param("s", $ip);
$stmt_rate->execute();
$row_rate = $stmt_rate->get_result()->fetch_assoc();
$stmt_rate->close();

if ($row_rate['total'] >= 60) {
    ob_clean();
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Muitas requisições. Aguarde.']);
    exit;
}

$stmt_log = $conn->prepare("INSERT INTO rate_limiter_polling (ip_address) VALUES (?)");
$stmt_log->bind_param("s", $ip);
$stmt_log->execute();
$stmt_log->close();

// ============================================================
// 3. VERIFICA SE O POST EXISTE E É PÚBLICO/PRIVADO
// ============================================================
$stmt_post = $conn->prepare("SELECT id, categoria, status FROM mensagens WHERE id = ?");
$stmt_post->bind_param("i", $id_mensagem);
$stmt_post->execute();
$post = $stmt_post->get_result()->fetch_assoc();
$stmt_post->close();

if (!$post) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Post não encontrado.']);
    exit;
}

$is_perdidos = ($post['categoria'] === 'perdidos');

// Se não for perdidos, exige login (mesma regra do comentarios-post.php)
if (!$is_perdidos && !isset($_SESSION['usuario_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Login necessário.']);
    exit;
}

// Se o post não está mais ativo, para de pollar
if ($post['status'] !== 'ativo') {
    ob_clean();
    echo json_encode(['status' => 'success', 'novos' => [], 'post_inativo' => true]);
    exit;
}

// ============================================================
// 4. BUSCA COMENTÁRIOS NOVOS (id > ultimo_id)
// ============================================================
$sql = "SELECT c.*, 
        (SELECT comentario FROM comentarios WHERE id = c.parent_id) as parent_comentario
        FROM comentarios c 
        WHERE c.id_mensagem = ? AND c.id > ? AND c.status = 'ativo'
        ORDER BY c.id ASC
        LIMIT 30";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_mensagem, $ultimo_id);
$stmt->execute();
$res = $stmt->get_result();

$novos = [];
$novo_ultimo_id = $ultimo_id;

while ($c = $res->fetch_assoc()) {
    // 🔒 Sanitização (mesma do comentarios-post.php — defesa em profundidade)
    $vibe = 'vibe-glass';
    if (!empty($c['pref_vibe_comentario'])) {
        $vibes_validas = ['vibe-glass', 'vibe-neon', 'vibe-dark', 'vibe-light', 'vibe-ads'];
        if (in_array($c['pref_vibe_comentario'], $vibes_validas, true)) {
            $vibe = $c['pref_vibe_comentario'];
        }
    }
    $cor_borda = '#70cde4';
    if (!empty($c['pref_cor_borda']) && preg_match('/^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/', $c['pref_cor_borda'])) {
        $cor_borda = $c['pref_cor_borda'];
    }

    $classe_filho = !empty($c['parent_id']) ? "comentario-filho" : "";
    $id_autor_comentario = $c['id_usuario'] ?? $c['usuario_id'] ?? 0;
    $sou_eu = (isset($_SESSION['usuario_id']) && $id_autor_comentario == $_SESSION['usuario_id']) ? 'meu-comentario' : '';
    $estilo_filho = $classe_filho ? "var(--cor-borda-glow);" : "";

    // Trecho de resposta (se for filho)
    $trecho_resposta = '';
    if (!empty($c['parent_id']) && !empty($c['parent_comentario'])) {
        $texto_puro = strip_tags($c['parent_comentario']);
        $texto_cortado = mb_substr($texto_puro, 0, 50);
        $trecho_resposta = mb_strlen($texto_puro) > 50 ? $texto_cortado . '...' : $texto_cortado;
    }

    // Nome do autor (respeitando anônimo)
    $nome_autor = !empty($c['usuario_nome'])
        ? '@' . htmlspecialchars($c['usuario_nome'], ENT_QUOTES, 'UTF-8')
        : '👤 Anônimo';

    // Corpo do comentário (com menções formatadas)
    $texto_html = nl2br(formatarMencoes($c['comentario']));

    // Anexos
    $anexos_html = '';
    if (!empty($c['anexos'])) {
        $anexos_arr = json_decode($c['anexos'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($anexos_arr) && count($anexos_arr) > 0) {
            $anexos_html .= '<div class="comentario-media-wrapper-grid">';
            foreach ($anexos_arr as $anexo) {
                if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                    $img_url = obterUrlComFallback($anexo['caminho'], 'uploads/ui/fallback-post.webp', null, true);
                    $anexos_html .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($img_url) . '" class="comentario-img" alt="Imagem do comentário" loading="lazy" onerror="this.style.display=\'none\'"></div>';
                } elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])) {
                    $anexos_html .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($anexo['url']) . '" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy"></div>';
                }
            }
            $anexos_html .= '</div>';
        }
    } elseif (!empty($c['imagem_url'])) {
        if (filter_var($c['imagem_url'], FILTER_VALIDATE_URL)) {
            $anexos_html = '<div class="comentario-media-wrapper"><img src="' . htmlspecialchars($c['imagem_url']) . '" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy"></div>';
        } else {
            $img_url = obterUrlComFallback($c['imagem_url'], 'uploads/ui/fallback-post.webp', null, true);
            $anexos_html = '<div class="comentario-media-wrapper"><img src="' . htmlspecialchars($img_url) . '" class="comentario-img" alt="Imagem do comentário" loading="lazy" onerror="this.style.display=\'none\'"></div>';
        }
    }

    // Monta HTML do comentário (mesma estrutura do comentarios-post.php)
    $comentario_html = '
    <div class="comentario-item ' . $vibe . ' ' . $classe_filho . ' ' . $sou_eu . '" id="comentario-' . (int)$c['id'] . '" style="--cor-borda-glow: ' . $cor_borda . '; ' . $estilo_filho . '">
        <div class="comentario-meta">
            <strong class="comentario-autor" style="color: var(--cor-borda-glow);">' . $nome_autor . '</strong>
        </div>';

    if (!empty($c['parent_id'])) {
        $comentario_html .= '
        <div class="indicador-resposta" onclick="irParaMensagem(' . (int)$c['parent_id'] . ')">
            <i class="fas fa-reply"></i> <small>' . htmlspecialchars($trecho_resposta, ENT_QUOTES, 'UTF-8') . '</small>
        </div>';
    }

    $comentario_html .= '
        <p class="comentario-texto">' . $texto_html . '</p>
        ' . $anexos_html . '
        <div class="comentario-rodape">
            <span class="comentario-data">' . exibirDataHoraBrasil($c['data_comentario'], 'H:i') . '</span>
        </div>
    </div>';

    $novos[] = [
        'id'     => (int)$c['id'],
        'html'   => $comentario_html,
        'autor'  => $nome_autor,
    ];

    $novo_ultimo_id = max($novo_ultimo_id, (int)$c['id']);
}

$stmt->close();

// ============================================================
// 5. RESPOSTA
// ============================================================
ob_clean();
echo json_encode([
    'status'       => 'success',
    'novos'        => $novos,
    'ultimo_id'    => $novo_ultimo_id,
    'total_novos'  => count($novos),
]);
exit;