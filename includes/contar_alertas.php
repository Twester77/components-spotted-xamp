<?php
include '../conexao.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['total' => 0]);
    exit();
}

$user_id = $_SESSION['usuario_id'];

// Conta apenas notificações NÃO LIDAS (lida = 0)
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();

// Retorna o JSON para o seu JavaScript no footer
echo json_encode(['total' => $resultado['total']]);
?>