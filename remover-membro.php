<?php
/**
 * remover-membro.php – Endpoint para remover um membro da comunidade (DELETE físico)
 * 
 * Método: POST
 * Parâmetros: comunidade_id, usuario_id, csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório (hash_equals)
 * - Prepared statements
 * - Apenas admin/criador podem remover
 * - Criador NÃO pode ser removido (nem por ele mesmo)
 * - Usuário NÃO pode remover a si mesmo
 * - Rate limiting persistente (fenda_rate_limits)
 * - Logs via fenda_log()
 * 
 * 📌 Nota: Remoção é DELETE físico da tabela comunidade_membros.
 * O usuário poderá solicitar entrada novamente no futuro.
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 Rate limit corrigido: array de timestamps (5 ações/minuto)
 *
 * 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
 *    - Rate limit trocado de $_SESSION (efêmera em serverless — nunca disparava
 *      em produção Vercel) por tabela `fenda_rate_limits`.
 *    - COTA COMPARTILHADA com promover-membro.php e banir-membro.php:
 *      todos usam o mesmo endpoint `gerenciar_membros`.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÕES INICIAIS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

// ============================================================
// 2. RATE LIMITING PERSISTENTE (cota compartilhada — 5 ações/min)
// 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
//    ⚠️ COTA COMPARTILHADA: mesmo endpoint de promover/banir.
// ============================================================
$usuario_id = (int)$_SESSION['usuario_id'];
$ip = function_exists('obterIPReal')
    ? obterIPReal()
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

$endpoint = 'gerenciar_membros';

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
        (SELECT COUNT(*) FROM fenda_rate_limits
         WHERE endpoint = ? AND usuario_id = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS user_total,
        (SELECT COUNT(*) FROM fenda_rate_limits
         WHERE endpoint = ? AND ip_address = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS ip_total
");
$stmt_rate->bind_param('siis', $endpoint, $usuario_id, $endpoint, $ip);
$stmt_rate->execute();
$result_rate = $stmt_rate->get_result()->fetch_assoc();
$stmt_rate->close();

if ((int)($result_rate['user_total'] ?? 0) >= 5 || (int)($result_rate['ip_total'] ?? 0) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um momento antes de realizar outra ação.']);
    exit;
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint, $usuario_id, $ip);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ============================================================
// 3. CAPTURA DOS PARÂMETROS
// ============================================================
$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$usuario_alvo_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;

if ($comunidade_id <= 0 || $usuario_alvo_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

// ============================================================
// 4. VERIFICA PERMISSÃO DO ADMIN
// ============================================================
$stmt = $conn->prepare("SELECT papel FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin || !in_array($admin['papel'], ['criador', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para remover membros.']);
    exit;
}

// ============================================================
// 5. VERIFICA SE O ALVO EXISTE E NÃO É O CRIADOR
// ============================================================
$stmt = $conn->prepare("SELECT papel, status FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $comunidade_id, $usuario_alvo_id);
$stmt->execute();
$res = $stmt->get_result();
$alvo = $res->fetch_assoc();
$stmt->close();

if (!$alvo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado nesta comunidade.']);
    exit;
}

if ($alvo['papel'] === 'criador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Não é possível remover o criador da comunidade.']);
    exit;
}

if ($usuario_alvo_id == $usuario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não pode remover a si mesmo.']);
    exit;
}

// ============================================================
// 6. EXECUTA A REMOÇÃO (DELETE FÍSICO)
// ============================================================
$stmt = $conn->prepare("DELETE FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $comunidade_id, $usuario_alvo_id);

if ($stmt->execute()) {
    $deletados = $stmt->affected_rows;
    $stmt->close();
    
    if ($deletados > 0) {
        fenda_log("🟢 [REMOVER] Usuário $usuario_alvo_id removido da comunidade $comunidade_id");
        echo json_encode([
            'success' => true,
            'message' => 'Membro removido com sucesso.'
        ]);
    } else {
        fenda_log("⚠️ [REMOVER] Nenhuma linha afetada para usuário $usuario_alvo_id na comunidade $comunidade_id");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Membro não encontrado ou já removido.']);
    }
} else {
    fenda_log("🔴 Erro ao remover: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao remover membro.']);
    $stmt->close();
}
exit;