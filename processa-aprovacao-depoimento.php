<?php
/**
 * processa-aprovacao-depoimento.php – Aprova ou rejeita um depoimento (AJAX)
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Faça login para continuar.']);
    exit();
}

// Verifica se os dados foram enviados via GET
if (!isset($_GET['id']) || !isset($_GET['acao'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit();
}

$depoimento_id = (int)$_GET['id'];
$acao = $_GET['acao'];
$usuario_id = $_SESSION['usuario_id'];

// Valida a ação
if (!in_array($acao, ['aprovar', 'rejeitar'])) {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
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
    echo json_encode(['success' => false, 'message' => 'Depoimento não encontrado.']);
    exit();
}

if ($depoimento['destinatario_id'] != $usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para modificar este depoimento.']);
    exit();
}

if ($depoimento['status'] !== 'pendente') {
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
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o depoimento.']);
}

$stmt_update->close();
?>