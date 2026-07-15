<?php
/**
 * post-detalhe.php – Endpoint para Lightbox Universal
 * 
 * Retorna HTML com o conteúdo completo de um post (texto, imagens, reações,
 * prévia dos 3 comentários mais recentes) para ser exibido em um modal.
 * 
 * @package A Fenda
 * @author DeepSeek (Novo Marretor)
 * @version 1.1
 * 
 * 🔒 SEGURANÇA:
 * - Sanitização rigorosa com htmlspecialchars() + fallback para null
 * - Prepared statements para evitar SQL Injection
 * - Validação de ID numérico
 */

// ============================================================
// 1. CONFIGURAÇÃO INICIAL
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desliga exibição de erros em produção
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

// ============================================================
// 2. VALIDAÇÃO DO ID
// ============================================================
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo '<div class="lightbox-erro">❌ ID inválido.</div>';
    exit();
}

// ============================================================
// 3. BUSCA OS DADOS DO POST (COM JOIN PARA AUTOR)
// ============================================================
try {
    $sql = "SELECT 
                m.id, 
                m.mensagem, 
                m.categoria, 
                m.imagem_url, 
                m.anexos, 
                m.data_post,
                m.status,
                u.id as usuario_id,
                u.username, 
                u.foto,
                u.pref_cor_padrao,
                u.pref_vibe_padrao
            FROM mensagens m
            INNER JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.id = ? AND m.status = 'ativo'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $post = $resultado->fetch_assoc();
    $stmt->close();

    if (!$post) {
        http_response_code(404);
        echo '<div class="lightbox-erro">❌ Post não encontrado ou foi removido.</div>';
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="lightbox-erro">❌ Erro ao buscar o post. Tente novamente.</div>';
    error_log('[post-detalhe] Erro no banco: ' . $e->getMessage());
    exit();
}

// ============================================================
// 4. BUSCA AS REAÇÕES (CONTAGEM)
// ============================================================
$reacoes = [];
try {
    $sql_react = "SELECT tipo_reacao, COUNT(*) as total 
                  FROM curtidas 
                  WHERE mensagem_id = ? 
                  GROUP BY tipo_reacao";
    $stmt_react = $conn->prepare($sql_react);
    $stmt_react->bind_param("i", $id);
    $stmt_react->execute();
    $res_react = $stmt_react->get_result();
    while ($row = $res_react->fetch_assoc()) {
        $reacoes[$row['tipo_reacao']] = $row['total'];
    }
    $stmt_react->close();
} catch (Exception $e) {
    error_log('[post-detalhe] Erro ao buscar reações: ' . $e->getMessage());
}

// ============================================================
// 5. BUSCA OS COMENTÁRIOS (ÚLTIMOS 3)
// ============================================================
$comentarios = [];
try {
    $sql_com = "SELECT 
                    c.id,
                    c.comentario,
                    c.data_comentario,
                    c.usuario_nome,
                    c.usuario_id,
                    c.pref_vibe_comentario,
                    c.pref_cor_borda,
                    u.foto as autor_foto,
                    u.username as autor_username
                FROM comentarios c
                LEFT JOIN usuarios u ON c.usuario_id = u.id
                WHERE c.id_mensagem = ? AND c.status = 'ativo'
                ORDER BY c.id DESC
                LIMIT 3";
    $stmt_com = $conn->prepare($sql_com);
    $stmt_com->bind_param("i", $id);
    $stmt_com->execute();
    $res_com = $stmt_com->get_result();
    while ($row = $res_com->fetch_assoc()) {
        $comentarios[] = $row;
    }
    $stmt_com->close();
} catch (Exception $e) {
    error_log('[post-detalhe] Erro ao buscar comentários: ' . $e->getMessage());
}

// ============================================================
// 6. PREPARA O B2 PARA GERAR URLs DAS IMAGENS
// ============================================================
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
    error_log('[post-detalhe] Falha ao instanciar B2: ' . $e->getMessage());
}

// ============================================================
// 7. FUNÇÃO AUXILIAR: FORMATA ANEXOS EM HTML
// ============================================================
function renderizarAnexos($post, $b2) {
    $anexos_exibicao = null;
    $html = '';

    if (!empty($post['anexos'])) {
        $anexos_exibicao = json_decode($post['anexos'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($anexos_exibicao)) {
            $anexos_exibicao = null;
        }
    }

    if (!empty($anexos_exibicao) && is_array($anexos_exibicao)) {
        $html = '<div class="feed-anexos-grid">';
        foreach ($anexos_exibicao as $anexo) {
            if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                $img_url = obterUrlImagem($anexo['caminho'], $b2, true) ?? 'postagens/' . htmlspecialchars($anexo['caminho']);
                $html .= '<div class="feed-anexo-item"><img src="' . htmlspecialchars($img_url) . '" loading="lazy" onerror="this.style.display=\'none\'" alt="Imagem do post"></div>';
            } elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])) {
                $html .= '<div class="feed-anexo-item"><img src="' . htmlspecialchars($anexo['url']) . '" loading="lazy" alt="GIF do post"></div>';
            }
        }
        $html .= '</div>';
    } elseif (!empty($post['imagem_url'])) {
        $nome_imagem = $post['imagem_url'];
        $defaults = ['default_feminino.jpg', 'default_masculino.jpg', 'default_capa_feminino.webp', 'default_capa_masculino.webp'];
        if (in_array($nome_imagem, $defaults)) {
            $img_url = 'uploads/ui/' . $nome_imagem;
        } else {
            $img_url = obterUrlImagem($nome_imagem, $b2, true) ?? htmlspecialchars($nome_imagem);
        }
        $html = '<div class="container-img-post"><img src="' . htmlspecialchars($img_url) . '" loading="lazy" onerror="this.src=\'uploads/ui/fallback-post.webp\'" alt="Imagem do post"></div>';
    }

    return $html;
}

// ============================================================
// 8. FUNÇÃO AUXILIAR: FORMATA REAÇÕES
// ============================================================
function renderizarReacoes($reacoes) {
    $tradutor = [
        'amei' => '💖',
        'perplecto' => '😲',
        'haha' => '😂',
        'ranco' => '🙄',
        'forca' => '🫂',
        'triste' => '😢',
        'tendi-nada' => '🤔'
    ];

    if (empty($reacoes)) {
        return '<span class="reacao-placeholder">Ninguém reagiu ainda.</span>';
    }

    $html = '';
    foreach ($reacoes as $tipo => $total) {
        $emoji = $tradutor[$tipo] ?? '👍';
        $html .= '<span class="reacao-item">' . $emoji . ' ' . intval($total) . '</span>';
    }
    return $html;
}

// ============================================================
// 9. FUNÇÃO AUXILIAR: FORMATA COMENTÁRIOS (CORRIGIDA)
// ============================================================
function renderizarComentarios($comentarios, $b2) {
    if (empty($comentarios)) {
        return '<p class="sem-comentarios">Ninguém comentou ainda. Seja o primeiro!</p>';
    }

    $html = '';
    foreach ($comentarios as $c) {
        // 🔥 Sanitização com fallback para null
        $nome = !empty($c['usuario_nome']) ? '@' . htmlspecialchars($c['usuario_nome'], ENT_QUOTES, 'UTF-8') : '👤 Anônimo';
        $texto = nl2br(htmlspecialchars($c['comentario'] ?? '', ENT_QUOTES, 'UTF-8'));
        $data = date('H:i', strtotime($c['data_comentario']));
        $cor = !empty($c['pref_cor_borda']) ? htmlspecialchars($c['pref_cor_borda'], ENT_QUOTES, 'UTF-8') : '#70cde4';
        $vibe = !empty($c['pref_vibe_comentario']) ? htmlspecialchars($c['pref_vibe_comentario'], ENT_QUOTES, 'UTF-8') : 'vibe-glass';

        $avatar = 'uploads/ui/default_masculino.jpg';
        if (!empty($c['autor_foto'])) {
            $avatar_temp = obterUrlImagem($c['autor_foto'], $b2, true) ?? 'uploads/ui/default_masculino.jpg';
            $avatar = htmlspecialchars($avatar_temp, ENT_QUOTES, 'UTF-8');
        }

        $html .= '
        <div class="comentario-item ' . $vibe . '" style="--cor-borda-glow: ' . $cor . ';">
            <div class="comentario-meta">
                <img src="' . $avatar . '" class="avatar-p" style="border-radius:50%; margin-right:8px;" onerror="this.src=\'uploads/ui/default_masculino.jpg\'">
                <strong class="comentario-autor" style="color: ' . $cor . ';">' . $nome . '</strong>
                <span class="comentario-data">' . $data . '</span>
            </div>
            <p class="comentario-texto">' . $texto . '</p>
        </div>
        ';
    }
    return $html;
}

// ============================================================
// 10. CONSTRÓI O HTML FINAL
// ============================================================

// 🔥 Dados do autor (sanitizados com fallback)
$categoria = strtoupper(htmlspecialchars($post['categoria'] ?? '', ENT_QUOTES, 'UTF-8'));
$mensagem = nl2br(htmlspecialchars($post['mensagem'] ?? '', ENT_QUOTES, 'UTF-8'));
$data_post = date('d/m H:i', strtotime($post['data_post']));
$username = htmlspecialchars($post['username'] ?? '', ENT_QUOTES, 'UTF-8');
$cor_autor = htmlspecialchars($post['pref_cor_padrao'] ?? '#70cde4', ENT_QUOTES, 'UTF-8');

// Avatar do autor
$avatar_autor = 'uploads/ui/default_masculino.jpg';
if (!empty($post['foto'])) {
    $avatar_temp = obterUrlImagem($post['foto'], $b2, true) ?? 'uploads/ui/default_masculino.jpg';
    $avatar_autor = htmlspecialchars($avatar_temp, ENT_QUOTES, 'UTF-8');
}

// Anexos
$anexos_html = renderizarAnexos($post, $b2);

// Reações
$reacoes_html = renderizarReacoes($reacoes);

// Comentários
$comentarios_html = renderizarComentarios($comentarios, $b2);

// Contagem total de comentários
$total_comentarios = count($comentarios);

?>

<!-- ============================================================
     HTML FINAL DO LIGHTBOX
     ============================================================ -->
<div class="lightbox-post-container">

    <!-- Cabeçalho do post -->
    <div class="lightbox-post-header">
        <div class="lightbox-autor">
            <img src="<?php echo $avatar_autor; ?>" class="avatar-p" onerror="this.src='uploads/ui/default_masculino.jpg'">
            <div class="lightbox-autor-info">
                <span class="lightbox-autor-nome" style="color: <?php echo $cor_autor; ?>;">@<?php echo $username; ?></span>
                <span class="lightbox-autor-data"><?php echo $data_post; ?></span>
            </div>
            <span class="lightbox-categoria">#<?php echo $categoria; ?></span>
        </div>
    </div>

    <!-- Corpo do post -->
    <div class="lightbox-post-body">
        <div class="lightbox-post-texto">
            <?php echo $mensagem; ?>
        </div>
        <?php echo $anexos_html; ?>
    </div>

    <!-- Reações -->
    <div class="lightbox-reacoes">
        <?php echo $reacoes_html; ?>
    </div>

    <!-- Comentários (prévia) -->
    <div class="lightbox-comentarios">
        <h4 class="lightbox-comentarios-titulo">💬 Comentários</h4>
        <div class="lightbox-comentarios-lista">
            <?php echo $comentarios_html; ?>
        </div>
        <?php if ($total_comentarios > 3): ?>
            <p class="lightbox-ver-todos">
                <a href="comentarios-post.php?id=<?php echo intval($id); ?>#fofocar" target="_top">
                    Ver todos os <?php echo $total_comentarios; ?> comentários →
                </a>
            </p>
        <?php elseif ($total_comentarios > 0): ?>
            <p class="lightbox-ver-todos">
                <a href="comentarios-post.php?id=<?php echo intval($id); ?>#fofocar" target="_top">
                    Ver todos os comentários →
                </a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Rodapé com ação -->
    <div class="lightbox-footer">
        <button class="btn-fofocar lightbox-btn-comentar" onclick="window.location.href='comentarios-post.php?id=<?php echo intval($id); ?>#fofocar'">
            <i class="fas fa-comment"></i> Comentar
        </button>
    </div>

</div>