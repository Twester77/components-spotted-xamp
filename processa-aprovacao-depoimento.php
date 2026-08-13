<?php
/**
 * processa-aprovacao-depoimento.php – Aprova ou rejeita um depoimento (AJAX)
 * 🔥 CORRIGIDO: Agora aceita POST e valida CSRF
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Faça login para continuar.']);
    exit();
}

// 🔥 Muda para POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

// 🔥 CSRF Token (obrigatório)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit();
}

// Valida parâmetros
$depoimento_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';
$usuario_id = $_SESSION['usuario_id'];

if ($depoimento_id <= 0 || !in_array($acao, ['aprovar', 'rejeitar'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit();
}

// Verifica se o depoimento existe e pertence ao usuário logado (como destinatário)
$sql_check = "SELECT id, destinatario_id, status FROM depoimentos WHERE id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $depoimento_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$depoimento = $res_check->fetch_assoc();
$stmt_check->close();

if (!$depoimento) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Depoimento não encontrado.']);
    exit();
}

if ($depoimento['destinatario_id'] != $usuario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para modificar este depoimento.']);
    exit();
}

if ($depoimento['status'] !== 'pendente') {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Este depoimento já foi processado.']);
    exit();
}

// Atualiza o status
$novo_status = ($acao === 'aprovar') ? 'aprovado' : 'rejeitado';
$sql_update = "UPDATE depoimentos SET status = ?, data_aprovacao = NOW() WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $novo_status, $depoimento_id);
$stmt_update->execute();

if ($stmt_update->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Depoimento ' . ($acao === 'aprovar' ? 'aprovado' : 'rejeitado') . ' com sucesso.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o depoimento.']);
}
$stmt_update->close();
?>