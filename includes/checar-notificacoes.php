<?php
include '../conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['tem' => false]);
    exit();
}

$user_id = $_SESSION['usuario_id'];

// Busca a última notificação não lida
$sql = "SELECT id, mensagem FROM notificacoes WHERE usuario_id = ? AND lida = 0 ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($notif = $res->fetch_assoc()) {
    // Marca como lida para não aparecer de novo no próximo ciclo
    $id_notif = $notif['id'];
    $conn->query("UPDATE notificacoes SET lida = 1 WHERE id = '$id_notif'");
    
    echo json_encode(['tem' => true, 'msg' => $notif['mensagem']]);
} else {
    echo json_encode(['tem' => false]);
}
?>