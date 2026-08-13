<?php
/**
 * enviar-resposta-evento.php – Processa a resposta do usuário a um evento
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 CORREÇÃO: Verificação de banimento para eventos de comunidades privadas.
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

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

$evento_id = isset($_POST['evento_id']) ? (int)$_POST['evento_id'] : 0;
$opcao = isset($_POST['opcao']) ? $_POST['opcao'] : '';
$usuario_id = $_SESSION['usuario_id'];

if ($evento_id <= 0 || !in_array($opcao, ['vou', 'nao_vou', 'talvez'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
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
    echo json_encode(['success' => false, 'message' => 'Evento não encontrado.']);
    exit;
}

if ($evento['status'] === 'cancelado') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este evento foi cancelado.']);
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
    echo json_encode(['success' => false, 'message' => 'Este evento já foi encerrado.']);
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
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para responder a este evento (banido da comunidade).']);
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
        'contagens' => $counts
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar resposta.']);
}

exit;