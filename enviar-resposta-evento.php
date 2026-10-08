<?php
/**
 * enviar-resposta-evento.php – Processa a resposta do usuário a um evento
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 CORREÇÃO: Verificação de banimento para eventos de comunidades privadas.
 * 
 * 🐚 BRISA – 2026-09-16 (v2 – rolling CSRF token)
 *    - Após validar o token atual, geramos um novo CSRF token e atualizamos
 *      $_SESSION['csrf_token']. O novo token é devolvido no JSON de sucesso
 *      para o front-end atualizar o <input id="csrf_token"> dinamicamente.
 *      Isso resolve o "CSRF stale" em sessões contínuas de swipe.
 *      (Recomendação da Djê na auditoria do bt-swipe.js.)
 *
 * 🐚 CALMARIA – 2026-10-08 (Sprint 2, item #5)
 *    - Reordenada a rotação do CSRF: agora acontece APÓS todas as validações
 *      (evento existe, não cancelado, não expirado, não banido) e ANTES do
 *      INSERT. Antes, rotacionava imediatamente após validar o token antigo,
 *      o que fazia com que erros de negócio devolvessem um token novo mas
 *      o front (evento.php inline, modo grid) não o lesse — a próxima
 *      tentativa usava o token antigo e recebia 403 "Token inválido".
 *      Bug visível como "segunda resposta sem recarregar a página falha".
 *      O `bt-swipe.js` já lê `data.csrf_token`, então não era afetado.
 *    - Em caso de falha de validação, NÃO rotaciona (token antigo continua
 *      válido) e devolve o token ATUAL (não um novo) pra consistência.
 *    - Docblock do sucesso mantém `csrf_token` no JSON para o bt-swipe.js.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Faça login para responder.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// ============================================================
// VALIDAÇÃO DO CSRF (com hash_equals para timing-safe)
// ============================================================
$csrf_enviado = $_POST['csrf_token'] ?? '';
$csrf_sessao  = $_SESSION['csrf_token'] ?? '';

if (!is_string($csrf_enviado) || $csrf_enviado === '' ||
    !is_string($csrf_sessao)  || $csrf_sessao === '' ||
    !hash_equals($csrf_sessao, $csrf_enviado)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

$evento_id = isset($_POST['evento_id']) ? (int)$_POST['evento_id'] : 0;
$opcao = isset($_POST['opcao']) ? $_POST['opcao'] : '';
$usuario_id = $_SESSION['usuario_id'];

// 🐚 CALMARIA – 2026-10-08 (item #5): em erros de validação, devolve o
//    token ATUAL (não rotacionado). O front pode tentar novamente sem 403.
$csrf_atual = $csrf_sessao;

if ($evento_id <= 0 || !in_array($opcao, ['vou', 'nao_vou', 'talvez'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Dados inválidos.',
        'csrf_token' => $csrf_atual
    ]);
    exit;
}

// ============================================================
// 1. VERIFICA SE O EVENTO EXISTE E NÃO ESTÁ ENCERRADO
// ============================================================
$stmt = $conn->prepare("SELECT id, data_evento, status, comunidade_id FROM eventos WHERE id = ?");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$res = $stmt->get_result();
$evento = $res->fetch_assoc();
$stmt->close();

if (!$evento) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Evento não encontrado.',
        'csrf_token' => $csrf_atual
    ]);
    exit;
}

if ($evento['status'] === 'cancelado') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Este evento foi cancelado.',
        'csrf_token' => $csrf_atual
    ]);
    exit;
}

// Verifica se o evento já passou
$data_evento = strtotime($evento['data_evento']);
if ($data_evento < time() && $evento['status'] !== 'encerrado') {
    $stmt_upd = $conn->prepare("UPDATE eventos SET status = 'encerrado' WHERE id = ?");
    $stmt_upd->bind_param("i", $evento_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Este evento já foi encerrado.',
        'csrf_token' => $csrf_atual
    ]);
    exit;
}

// 🔥 VERIFICA BANIMENTO (se o evento pertence a uma comunidade)
if ($evento['comunidade_id'] > 0) {
    $comunidade_id = (int)$evento['comunidade_id'];
    $stmt_ban = $conn->prepare("SELECT status FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt_ban->bind_param("ii", $comunidade_id, $usuario_id);
    $stmt_ban->execute();
    $res_ban = $stmt_ban->get_result();
    $membro = $res_ban->fetch_assoc();
    $stmt_ban->close();

    if (!$membro || $membro['status'] !== 'ativo') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Você não tem permissão para responder a este evento porque foi banido da comunidade.',
            'csrf_token' => $csrf_atual
        ]);
        exit;
    }
}

// ============================================================
// 2. INSERE OU ATUALIZA A RESPOSTA (IDEMPOTÊNCIA)
// ============================================================
$stmt = $conn->prepare("INSERT INTO evento_respostas (evento_id, usuario_id, resposta) 
                         VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE resposta = VALUES(resposta), data_resposta = NOW()");
$stmt->bind_param("iis", $evento_id, $usuario_id, $opcao);
$stmt->execute();

if ($stmt->affected_rows >= 0) {
    $stmt->close();

    // ============================================================
    // 🔥 ROTACIONA O CSRF TOKEN (rolling token) — SÓ AGORA, NO SUCESSO
    // 🐚 CALMARIA – 2026-10-08 (item #5): movida de cima pra cá.
    //    Se qualquer validação acima tivesse falhado, o token antigo
    //    continuaria válido — o front pode tentar de novo sem 403.
    // ============================================================
    $novo_csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $novo_csrf_token;

    // Busca contagens atualizadas
    $stmt_count = $conn->prepare("SELECT 
        COUNT(CASE WHEN resposta = 'vou' THEN 1 END) as vou,
        COUNT(CASE WHEN resposta = 'nao_vou' THEN 1 END) as nao_vou,
        COUNT(CASE WHEN resposta = 'talvez' THEN 1 END) as talvez
        FROM evento_respostas WHERE evento_id = ?");
    $stmt_count->bind_param("i", $evento_id);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();
    $counts = $res_count->fetch_assoc();
    $stmt_count->close();

    echo json_encode([
        'success' => true,
        'message' => 'Resposta registrada!',
        'contagens' => $counts,
        'csrf_token' => $novo_csrf_token
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar resposta.',
        'csrf_token' => $csrf_atual
    ]);
}

exit;