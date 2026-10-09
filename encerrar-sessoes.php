<?php
/**
 * encerrar-sessoes.php – Encerra todas as sessões ativas (exceto a atual)
 * 
 * Método: POST (AJAX)
 * Parâmetros: csrf_token (obrigatório)
 * Retorno: JSON com success, message e forcar_logout (se a atual foi encerrada)
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório (hash_equals)
 * - Apenas usuário logado
 * - Rate limiting persistente (fenda_rate_limits)
 * - Logs estruturados via fenda_log()
 * - Se a sessão atual for encerrada acidentalmente, retorna forcar_logout: true
 * 
 * 🐚 BRISA – 2026-09-01 (v3 – com logs e contrato JSON refinado)
 *
 * 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
 *    - Rate limit trocado de $_SESSION (efêmera em serverless — nunca disparava
 *      em produção Vercel) por tabela `fenda_rate_limits`. Mesmo padrão do Snap
 *      (excluir-notificacao.php) e do solicitar-entrada.php (Calmaria).
 *    - Duas camadas:
 *        • por usuário: 3 por 60s (ação destrutiva em lote — mais restritiva)
 *        • por IP: 6 por 60s (protege contra multi-conta)
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO encerrar-sessoes.php');

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÕES INICIAIS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed', 'message' => 'Método não permitido.']);
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    fenda_log('🔴 CSRF inválido em encerrar-sessoes.php');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'csrf_invalid', 'message' => 'Token de segurança inválido.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

fenda_log("🔵 Recebida solicitação para encerrar todas as sessões do usuário $usuario_id");

// ============================================================
// 2. RATE LIMITING PERSISTENTE (3 ações por minuto)
// 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
// ============================================================
$ip = function_exists('obterIPReal')
    ? obterIPReal()
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$endpoint = 'encerrar_sessoes';

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

if ((int)($result_rate['user_total'] ?? 0) >= 3 || (int)($result_rate['ip_total'] ?? 0) >= 6) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'rate_limited', 'message' => 'Aguarde um momento antes de realizar outra ação.']);
    exit;
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint, $usuario_id, $ip);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ============================================================
// 3. OBTÉM O TOKEN DA SESSÃO ATUAL (para preservá-la)
// ============================================================
$token_atual = null;
if (!empty($_COOKIE['fenda_state_token'])) {
    $decrypted = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    if ($decrypted) {
        $payload = json_decode($decrypted, true);
        if (isset($payload['token_sessao'])) {
            $token_atual = $payload['token_sessao'];
            fenda_log('🔵 Token atual obtido: ' . substr($token_atual, 0, 16) . '...');
        }
    }
}

// ============================================================
// 4. ENCERRA TODAS AS SESSÕES (exceto a atual, se identificada)
// ============================================================
if ($token_atual !== null) {
    // Preserva a sessão atual
    $sql = "UPDATE sessoes_ativas 
            SET ativo = 0 
            WHERE usuario_id = ? AND token != ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $token_atual);
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    $stmt->close();
    fenda_log("🟢 Sessões encerradas (preservando a atual): $afetadas afetadas");
    $forcar_logout = false;
} else {
    // Se não conseguimos identificar a sessão atual, encerra todas (medida de segurança)
    // Mas avisamos o front-end para forçar logout
    fenda_log("⚠️ Token atual não identificado. Encerrando todas as sessões (forçando logout).");
    $sql = "UPDATE sessoes_ativas 
            SET ativo = 0 
            WHERE usuario_id = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    $stmt->close();
    fenda_log("🟢 Sessões encerradas (todas): $afetadas afetadas");
    $forcar_logout = true;
}

// ============================================================
// 5. RESPOSTA
// ============================================================
if ($afetadas > 0 || $forcar_logout) {
    fenda_log('🟢 Todas as sessões encerradas para usuário ' . $usuario_id . ($forcar_logout ? ' (incluindo a atual)' : ''));
    echo json_encode([
        'success' => true,
        'message' => $forcar_logout 
            ? 'Todas as sessões foram encerradas, incluindo a atual. Faça login novamente.'
            : 'Todas as outras sessões foram encerradas. A sessão atual foi mantida.',
        'forcar_logout' => $forcar_logout
    ]);
} else {
    fenda_log('⚠️ Nenhuma sessão ativa encontrada para usuário ' . $usuario_id);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'no_active_sessions',
        'message' => 'Nenhuma sessão ativa encontrada.'
    ]);
}
exit;