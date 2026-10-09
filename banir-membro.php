<?php
/**
 * banir-membro.php – Endpoint para banir/desbanir um membro da comunidade
 * 
 * Método: POST
 * Parâmetros: comunidade_id, usuario_id, acao (banir/desbanir), csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório (hash_equals)
 * - Prepared statements
 * - Apenas admin/criador podem banir
 * - Criador NÃO pode ser banido (nem por ele mesmo)
 * - Usuário NÃO pode banir a si mesmo
 * - Rate limiting persistente (fenda_rate_limits)
 * - Logs via fenda_log()
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 Rate limit corrigido: array de timestamps (5 ações/minuto)
 *
 * 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
 *    - Rate limit trocado de $_SESSION (efêmera em serverless — nunca disparava
 *      em produção Vercel) por tabela `fenda_rate_limits`.
 *    - COTA COMPARTILHADA com promover-membro.php e remover-membro.php:
 *      todos usam o mesmo endpoint `gerenciar_membros`.
 *
 * ⚠️ NOTA HERDADA (não corrigida aqui):
 *    O desbanir seta status = 'ativo', mesmo se o usuário estava 'pendente'
 *    antes de ser banido. Isso efetivamente "aprova" uma solicitação de
 *    entrada que nunca foi aprovada formalmente. Edge case conhecido,
 *    anotado no relatório da Corrente para o Sprint 3.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/conexao.php';

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
//    ⚠️ COTA COMPARTILHADA: mesmo endpoint de promover/remover.
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
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if ($comunidade_id <= 0 || $usuario_alvo_id <= 0 || !in_array($acao, ['banir', 'desbanir'])) {
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
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para banir membros.']);
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
    echo json_encode(['success' => false, 'message' => 'Não é possível banir o criador da comunidade.']);
    exit;
}

if ($usuario_alvo_id == $usuario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não pode banir a si mesmo.']);
    exit;
}

// ============================================================
// 6. EXECUTA A AÇÃO (BANIR OU DESBANIR)
// ============================================================
$novo_status = ($acao === 'banir') ? 'banido' : 'ativo';

$stmt = $conn->prepare("UPDATE comunidade_membros SET status = ? WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("sii", $novo_status, $comunidade_id, $usuario_alvo_id);

if ($stmt->execute()) {
    $stmt->close();
    fenda_log("🟢 [$acao] Usuário $usuario_alvo_id na comunidade $comunidade_id");
    
    echo json_encode([
        'success' => true,
        'message' => ($acao === 'banir') ? 'Usuário banido com sucesso.' : 'Usuário desbanido com sucesso.'
    ]);
} else {
    fenda_log("🔴 Erro ao $acao: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao executar ação.']);
    $stmt->close();
}
exit;