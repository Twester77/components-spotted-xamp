<?php
/**
 * encerrar-sessoes.php – Encerra todas as sessões ativas (exceto a atual)
 * 
 * Método: POST (AJAX)
 * Parâmetros: csrf_token (obrigatório)
 * Retorno: JSON com success, message e forcar_logout (se a atual foi encerrada)
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório
 * - Apenas usuário logado
 * - Rate limiting: 5 ações por minuto
 * - Logs estruturados via fenda_log()
 * - Se a sessão atual for encerrada acidentalmente, retorna forcar_logout: true
 * 
 * 🐚 BRISA – 2026-09-01 (v3 – com logs e contrato JSON refinado)
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

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    fenda_log('🔴 CSRF inválido em encerrar-sessoes.php');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'csrf_invalid', 'message' => 'Token de segurança inválido.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

fenda_log("🔵 Recebida solicitação para encerrar todas as sessões do usuário $usuario_id");

// ============================================================
// 2. RATE LIMITING (5 ações por minuto)
// ============================================================
$chave_rate = 'encerrar_sessoes_' . $usuario_id;
$agora = time();

if (!isset($_SESSION[$chave_rate]) || !is_array($_SESSION[$chave_rate])) {
    $_SESSION[$chave_rate] = [];
}

$_SESSION[$chave_rate] = array_filter($_SESSION[$chave_rate], function($t) use ($agora) {
    return ($agora - $t) < 60;
});

if (count($_SESSION[$chave_rate]) >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'rate_limited', 'message' => 'Aguarde um momento antes de realizar outra ação.']);
    exit;
}
$_SESSION[$chave_rate][] = $agora;

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