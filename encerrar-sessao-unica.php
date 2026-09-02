<?php
/**
 * encerrar-sessao-unica.php – Encerra uma sessão ativa específica (individual)
 * 
 * Método: POST (AJAX)
 * Parâmetros: sessao_id (int), csrf_token (obrigatório)
 * Retorno: JSON com success, error (opcional) e message
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório
 * - Apenas usuário logado
 * - Verifica se a sessão pertence ao usuário
 * - Impede que o usuário encerre a própria sessão atual (com bloqueio rígido)
 * - Rate limiting: 5 ações por minuto
 * - Logs estruturados via error_log (compatível com Vercel)
 * 
 * 🐚 BRISA – 2026-09-02 (v5 – com log de diagnóstico em arquivo)
 *    - Adicionado log em arquivo (encerrar_log.log) para rastrear o fluxo
 *    - Mantidos logs via error_log para compatibilidade com Vercel
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO encerrar-sessao-unica.php');

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
    fenda_log('🔴 CSRF inválido');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'csrf_invalid', 'message' => 'Token de segurança inválido.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$sessao_id = isset($_POST['sessao_id']) ? (int)$_POST['sessao_id'] : 0;

if ($sessao_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_id', 'message' => 'ID da sessão inválido.']);
    exit;
}

fenda_log("🔵 Recebida solicitação para encerrar sessão ID $sessao_id do usuário $usuario_id");

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
// 3. OBTÉM O TOKEN DA SESSÃO ATUAL (do cookie) – COM BLOQUEIO RÍGIDO
// ============================================================
$token_atual = null;
if (!empty($_COOKIE['fenda_state_token'])) {
    fenda_log('🔵 Cookie fenda_state_token encontrado. Tentando decriptar...');
    $decrypted = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    if ($decrypted) {
        $payload = json_decode($decrypted, true);
        if (isset($payload['token_sessao'])) {
            $token_atual = $payload['token_sessao'];
            fenda_log('🔵 Token atual obtido: ' . substr($token_atual, 0, 16) . '...');
        } else {
            fenda_log('⚠️ Payload decriptado não contém token_sessao');
        }
    } else {
        fenda_log('⚠️ Falha na decriptação do cookie');
    }
} else {
    fenda_log('🔵 Nenhum cookie fenda_state_token encontrado');
}

// 🔥 BLOQUEIO RÍGIDO: se o token atual é nulo, NENHUMA ação de encerramento é permitida
if ($token_atual === null) {
    fenda_log('🔴 [SESSAO] TOKEN NULO – bloqueando todas as ações de encerramento para usuário ' . $usuario_id);
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'unauthorized',
        'message' => 'Token de autenticação inválido. Faça login novamente.'
    ]);
    exit;
}

// ============================================================
// 4. VERIFICA SE A SESSÃO EXISTE E PERTENCE AO USUÁRIO
// ============================================================
$stmt_check = $conn->prepare("
    SELECT id, token, ativo, usuario_id 
    FROM sessoes_ativas 
    WHERE id = ? AND usuario_id = ?
");
$stmt_check->bind_param("ii", $sessao_id, $usuario_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$sessao = $result->fetch_assoc();
$stmt_check->close();

if (!$sessao) {
    fenda_log("🔴 Sessão ID $sessao_id não encontrada para usuário $usuario_id");
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'Sessão não encontrada ou não pertence a você.']);
    exit;
}

if ($sessao['ativo'] == 0) {
    fenda_log("🔴 Sessão ID $sessao_id já está inativa");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'already_inactive', 'message' => 'Esta sessão já foi encerrada.']);
    exit;
}

// ============================================================
// 5. IMPEDE QUE O USUÁRIO ENCERRE A PRÓPRIA SESSÃO ATUAL (com logs)
// ============================================================
if ($sessao['token'] === $token_atual) {
    fenda_log("🔴 Tentativa de encerrar a sessão atual (token coincide) – BLOQUEADA");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'current_session',
        'message' => 'Você não pode encerrar a sessão atual. Use "Encerrar todas" ou faça logout.'
    ]);
    exit;
}


// ============================================================
// 6. ENCERRA A SESSÃO ESPECÍFICA (com verificação dupla e logs)
// ============================================================
$stmt_update = $conn->prepare("
    UPDATE sessoes_ativas 
    SET ativo = 0 
    WHERE id = ? AND usuario_id = ? AND ativo = 1
");
$stmt_update->bind_param("ii", $sessao_id, $usuario_id);

if ($stmt_update->execute()) {
    $afetadas = $stmt_update->affected_rows;
    $stmt_update->close();

    fenda_log("🟢 UPDATE executado para sessão ID $sessao_id. affected_rows: $afetadas");

    if ($afetadas > 0) {
        fenda_log('🟢 Sessão ID ' . $sessao_id . ' encerrada para usuário ' . $usuario_id);
        echo json_encode([
            'success' => true,
            'message' => 'Sessão encerrada com sucesso.'
        ]);
    } else {
        // Isso pode acontecer se a sessão já foi encerrada por outro processo
        fenda_log('⚠️ Nenhuma alteração na sessão ID ' . $sessao_id . ' (já encerrada ou ID inválido)');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'already_inactive',
            'message' => 'Sessão não pôde ser encerrada (já estava inativa).'
        ]);
    }
    exit;
} else {
    $erro = $stmt_update->error;
    $stmt_update->close();
    fenda_log('🔴 Erro ao encerrar sessão ID ' . $sessao_id . ': ' . $erro);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'internal_error',
        'message' => 'Erro ao encerrar sessão. Tente novamente.'
    ]);
    exit;
}