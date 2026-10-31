<?php
/**
 * excluir-post.php – Exclusão de posts com permissões de comunidade
 * 
 * Permissões:
 * - Autor do post (sempre pode excluir o próprio post)
 * - Admin ou Criador da comunidade (posts dentro da comunidade)
 * 
 * Regras de ouro:
 * - NUNCA confia em comunidade_id vindo do frontend (IDOR protection).
 *   O comunidade_id é SEMPRE descoberto a partir do post_id no banco.
 * - Se o B2 falhar ao deletar anexos, o soft delete AINDA acontece.
 *   Os arquivos órfãos são registrados no error_log para limpeza futura.
 * - Toda exclusão é registrada na tabela logs_auditoria (quem, quando, motivo).
 * - Rate limiting: 5 ações por minuto por usuário.
 * 
 * 🐚 BRISA – 2026-09-16
 *    - Endpoint criado após aprovação da Djê (brainstorming v2.2).
 *    - Isolado do excluir.php antigo para não regredir o feed principal.
 * 
 * 🔧 BRISA – 2026-09-16 (v2 – patch defensivo)
 *    - Seção 8 (logs_auditoria) agora envolve o prepare em if ($stmt_log),
 *      evitando Fatal Error em PHP 8+ caso a tabela não exista/permissão negada.
 *      Auditoria aprovada pela Djê.
 */

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';
require_once __DIR__ . '/fenda_debug.php';

header('Content-Type: application/json');

// ============================================================
// 1. VERIFICAÇÃO DE SESSÃO
// ============================================================
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

// ============================================================
// 2. MÉTODO E CSRF
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
$csrf_sessao = $_SESSION['csrf_token'] ?? '';

if (!is_string($csrf_token) || $csrf_token === '' || !is_string($csrf_sessao) || !hash_equals($csrf_sessao, $csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token de segurança inválido.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$post_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID do post inválido.']);
    exit;
}

fenda_log("[EXCLUIR_POST] 🔵 Início: post_id=$post_id, usuario_id=$usuario_id");

// ============================================================
// 3. RATE LIMITING PERSISTENTE (F2 — Porto 2026-10-30)
//    🔴 Antes: $_SESSION[$chave_rate], que NÃO persiste em serverless
//       (Vercel). Rate limit nunca disparava em produção.
//    ✅ Agora: tabela `fenda_rate_limits` (padrão consolidado da Fenda,
//       mesmo do enviar-post.php, reagir.php, solicitar-entrada.php).
//    Cota: 10 exclusões/min por usuário, 20/min por IP.
// ============================================================
$usuario_id_rate = (int)$_SESSION['usuario_id'];
$ip_rate = function_exists('obterIPReal')
    ? obterIPReal()
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$endpoint_rate = 'excluir_post';

$conn->query("CREATE TABLE IF NOT EXISTS fenda_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(64) NOT NULL,
    usuario_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint (endpoint),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ip (ip_address),
    INDEX idx_tentativa (tentativa)
)");

$stmt_rate = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM fenda_rate_limits WHERE endpoint = ? AND usuario_id = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS user_total,
        (SELECT COUNT(*) FROM fenda_rate_limits WHERE endpoint = ? AND ip_address = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS ip_total
");
$stmt_rate->bind_param('siis', $endpoint_rate, $usuario_id_rate, $endpoint_rate, $ip_rate);
$stmt_rate->execute();
$result_rate = $stmt_rate->get_result()->fetch_assoc();
$stmt_rate->close();

if ((int)($result_rate['user_total'] ?? 0) >= 10 || (int)($result_rate['ip_total'] ?? 0) >= 20) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Aguarde um momento antes de excluir outro post.']);
    exit;
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint_rate, $usuario_id_rate, $ip_rate);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ============================================================
// 4. BUSCA O POST (FONTE DA VERDADE)
// ============================================================
$stmt = $conn->prepare("
    SELECT id, usuario_id, comunidade_id, imagem_url, anexos, status
    FROM mensagens
    WHERE id = ?
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Post não encontrado.']);
    exit;
}

if ($post['status'] !== 'ativo') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Este post já foi removido.']);
    exit;
}

// 🔥 comunidade_id REAL (do banco, não do frontend) – proteção IDOR
$comunidade_id_real = (int)($post['comunidade_id'] ?? 0);
$autor_original = (int)$post['usuario_id'];

// ============================================================
// 5. VERIFICAÇÃO DE PERMISSÃO
// ============================================================
$permitido = false;
$motivo = '';

// 5.1 Autor do post SEMPRE pode excluir o próprio post
if ($autor_original === $usuario_id) {
    $permitido = true;
    $motivo = 'autor';
}

// 5.2 Admin ou Criador da comunidade pode excluir posts dentro dela
if (!$permitido && $comunidade_id_real > 0) {
    $stmt_papel = $conn->prepare("
        SELECT papel
        FROM comunidade_membros
        WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'
    ");
    $stmt_papel->bind_param("ii", $comunidade_id_real, $usuario_id);
    $stmt_papel->execute();
    $membro = $stmt_papel->get_result()->fetch_assoc();
    $stmt_papel->close();

    if ($membro && in_array($membro['papel'], ['criador', 'admin'])) {
        $permitido = true;
        $motivo = 'admin_comunidade';
        fenda_log("[EXCLUIR_POST] 🛡️ Admin/Criador autorizado: papel={$membro['papel']}");
    }
}

if (!$permitido) {
    fenda_log("[EXCLUIR_POST] 🚫 Permissão negada: usuario=$usuario_id, post=$post_id, comunidade=$comunidade_id_real");
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para excluir este post.']);
    exit;
}

// ============================================================
// 6. EXCLUSÃO DOS ARQUIVOS NO B2 (RESILIENTE)
// ============================================================
$arquivosDeletados = 0;
$errosB2 = [];
$anexos = null;

if (!empty($post['anexos'])) {
    $anexos = json_decode($post['anexos'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if (($anexo['tipo'] ?? '') === 'imagem' && !empty($anexo['caminho'])) {
                try {
                    if (excluirArquivoB2($anexo['caminho'], $usuario_id)) {
                        $arquivosDeletados++;
                    } else {
                        $errosB2[] = $anexo['caminho'];
                    }
                } catch (Exception $e) {
                    $errosB2[] = $anexo['caminho'];
                    fenda_log("[EXCLUIR_POST] ⚠️ Erro B2 em {$anexo['caminho']}: " . $e->getMessage());
                }
            }
        }
    }
}

// 6.2 Fallback legado: campo imagem_url (compatibilidade)
if (!empty($post['imagem_url'])) {
    $jaDeletada = false;
    if (!empty($anexos) && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if (($anexo['tipo'] ?? '') === 'imagem' && ($anexo['caminho'] ?? '') === $post['imagem_url']) {
                $jaDeletada = true;
                break;
            }
        }
    }
    if (!$jaDeletada) {
        try {
            if (excluirArquivoB2($post['imagem_url'], $usuario_id)) {
                $arquivosDeletados++;
            } else {
                $errosB2[] = $post['imagem_url'];
            }
        } catch (Exception $e) {
            $errosB2[] = $post['imagem_url'];
            fenda_log("[EXCLUIR_POST] ⚠️ Erro B2 em {$post['imagem_url']}: " . $e->getMessage());
        }
    }
}

if (!empty($errosB2)) {
    fenda_log("[EXCLUIR_POST] ⚠️ Arquivos órfãos (soft delete continuou): " . implode(', ', $errosB2));
}

// ============================================================
// 7. SOFT DELETE NO BANCO
// ============================================================
$stmt_del = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ? AND status = 'ativo'");
$stmt_del->bind_param("i", $post_id);
$executou = $stmt_del->execute();
$afetadas = $stmt_del->affected_rows;
$stmt_del->close();

if (!$executou || $afetadas === 0) {
    fenda_log("[EXCLUIR_POST] ❌ Falha no UPDATE: " . $conn->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir no banco de dados.']);
    exit;
}

// ============================================================
// 8. REGISTRO NO LOG DE AUDITORIA (BLINDAGEM DEFENSIVA)
// ============================================================
// 🔧 PATCH DEFENSIVO (Djê): Se a tabela não existir ou a criação falhar,
// o prepare pode retornar false. O if evita Fatal Error em PHP 8+.

$conn->query("
    CREATE TABLE IF NOT EXISTS logs_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        acao VARCHAR(50) NOT NULL,
        detalhes TEXT,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_usuario (usuario_id),
        INDEX idx_acao (acao),
        INDEX idx_data (data_hora)
    )
");

$stmt_log = $conn->prepare("
    INSERT INTO logs_auditoria (usuario_id, acao, detalhes, data_hora)
    VALUES (?, 'excluir_post', ?, NOW())
");

if ($stmt_log) {
    $detalhes = "post_id=$post_id, comunidade_id=$comunidade_id_real, autor_original=$autor_original, motivo=$motivo";
    $stmt_log->bind_param("is", $usuario_id, $detalhes);
    $stmt_log->execute();
    $stmt_log->close();
} else {
    error_log("[EXCLUIR_POST] ⚠️ Não foi possível gravar log_auditoria: " . $conn->error);
}

// ============================================================
// 9. LIMPA NOTIFICAÇÕES RELACIONADAS
// ============================================================
$stmt_notif = $conn->prepare("UPDATE notificacoes SET post_id = NULL WHERE post_id = ?");
$stmt_notif->bind_param("i", $post_id);
$stmt_notif->execute();
$stmt_notif->close();

// ============================================================
// 10. RESPOSTA
// ============================================================
fenda_log("[EXCLUIR_POST] ✅ Post $post_id excluído por $motivo. B2: $arquivosDeletados arquivos.");

echo json_encode([
    'status' => 'success',
    'message' => 'Post excluído com sucesso.',
    'motivo' => $motivo,
    'arquivos_deletados' => $arquivosDeletados,
    'avisos_b2' => count($errosB2) > 0 ? 'Alguns arquivos não puderam ser deletados do storage (log registrado).' : null
]);
exit;