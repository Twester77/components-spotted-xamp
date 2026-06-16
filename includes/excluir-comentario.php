<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once __DIR__ . '/../conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado e se o ID do comentário foi enviado
if (!isset($_SESSION['usuario_id']) || !isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit();
}

$comentario_id = (int)$_POST['id'];
$usuario_id = (int)$_SESSION['usuario_id'];

// Verifica se o comentário existe, está ativo e pertence ao usuário
$check = $conn->prepare("SELECT id, usuario_id, status FROM comentarios WHERE id = ?");
$check->bind_param("i", $comentario_id);
$check->execute();
$res = $check->get_result();
$comentario = $res->fetch_assoc();

if (!$comentario || $comentario['status'] !== 'ativo') {
    echo json_encode(['status' => 'error', 'message' => 'Comentário não encontrado ou já removido.']);
    exit();
}

if ($comentario['usuario_id'] != $usuario_id) {
    echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para excluir este comentário.']);
    exit();
}

// Soft delete
$update = $conn->prepare("UPDATE comentarios SET status = 'deletado' WHERE id = ?");
$update->bind_param("i", $comentario_id);

if ($update->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Comentário removido.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao remover comentário.']);
}

$check->close();
$update->close();
$conn->close();
?>