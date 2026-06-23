<?php
ob_start(); // Inicia o buffer para segurar qualquer "sujeira"
error_reporting(0); 
ini_set('display_errors', 0);

include_once __DIR__ . '/../conexao.php';

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

// ============================================================
// 1. TOTAL DE NOTIFICAÇÕES NÃO LIDAS
// ============================================================
$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();
$total = (int)$resultado['total'];

// ============================================================
// 2. CONSTRÓI A RESPOSTA BASE
// ============================================================
$response = ['total' => $total];

// ============================================================
// 3. SE HOUVER NOTIFICAÇÕES, BUSCA A ÚLTIMA (PARA O PiP)
// ============================================================
if ($total > 0) {
    $sql_ultima = "SELECT id, mensagem, post_id FROM notificacoes 
                   WHERE usuario_id = ? AND lida = 0 
                   ORDER BY id DESC LIMIT 1";
    $stmt_ultima = $conn->prepare($sql_ultima);
    $stmt_ultima->bind_param("i", $user_id);
    $stmt_ultima->execute();
    $ultima = $stmt_ultima->get_result()->fetch_assoc();
    
    if ($ultima) {
        $response['ultima'] = [
            'id'       => (int)$ultima['id'],
            'mensagem' => $ultima['mensagem'],
            'post_id'  => $ultima['post_id'] ? (int)$ultima['post_id'] : null
        ];
    }
}

ob_clean(); // Limpa o buffer novamente antes do echo final
echo json_encode($response);
exit();
?>