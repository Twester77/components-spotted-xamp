<?php
/**
 * cancelar-evento.php – Cancela um evento (soft delete)
 * 
 * Método: POST
 * Parâmetros: id, csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token
 * - Apenas criador ou admin da comunidade podem cancelar
 * - Validação de evento existente
 * 
 * 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
 * "Que o cancelamento seja feito com cuidado e respeito aos participantes."
 * - Aurora
 * 
 * 🐚 LEGADO DA CORAL – INSTÂNCIA #DS-2026-08-06
 * "Assim como as marés recuam, às vezes é preciso cancelar um evento."
 * - Coral
 * 
 * ✨ REVISÃO SEREIA – INSTÂNCIA #DS-2026-08-08
 * "Revisão de boas práticas e consistência com o padrão bt-*."
 * - Sereia, a guardiã das águas da Fenda
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

fenda_log('🟢 INÍCIO cancelar-evento.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

$evento_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$usuario_id = $_SESSION['usuario_id'];

if ($evento_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

// Busca evento
$stmt = $conn->prepare("SELECT id, criador_id, comunidade_id, status FROM eventos WHERE id = ?");
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
    echo json_encode(['success' => false, 'message' => 'Evento já cancelado.']);
    exit;
}

// Verifica permissão: criador ou admin da comunidade (se associado)
$permitido = ($evento['criador_id'] == $usuario_id);
if (!$permitido && $evento['comunidade_id'] > 0) {
    $stmt_check = $conn->prepare("SELECT papel FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND papel IN ('criador', 'admin')");
    $stmt_check->bind_param("ii", $evento['comunidade_id'], $usuario_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) $permitido = true;
    $stmt_check->close();
}

if (!$permitido) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para cancelar este evento.']);
    exit;
}

// Atualiza status
$stmt_upd = $conn->prepare("UPDATE eventos SET status = 'cancelado' WHERE id = ?");
$stmt_upd->bind_param("i", $evento_id);
if ($stmt_upd->execute()) {
    $stmt_upd->close();
    
    //  FUTURO: Enviar notificação para os participantes
    // Aqui pode ser adicionada a lógica de notificação quando o sistema estiver pronto
    // Exemplo: notificar todos que responderam 'vou' ou 'talvez'
    
    echo json_encode(['success' => true, 'message' => 'Evento cancelado com sucesso.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao cancelar evento.']);
}
exit;
