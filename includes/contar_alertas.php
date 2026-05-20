<?php
ob_start(); // Inicia o buffer para segurar qualquer "sujeira"
error_reporting(0); 
ini_set('display_errors', 0);

include '../conexao.php';


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    ob_clean(); // Limpa o que tiver no buffer
    echo json_encode(['total' => 0]);
    exit();
}

$user_id = $_SESSION['usuario_id'];
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();

ob_clean(); // Limpa o buffer novamente antes do echo final
echo json_encode(['total' => (int)$resultado['total']]);
exit();