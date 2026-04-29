<?php
/** @var mysqli $conn */
include '../conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['tem' => false]);
    exit();
}

$user_id = $_SESSION['usuario_id'];

// 1. Busca se existe alguma notificação não lida (lida = 0)
$sql = "SELECT id, mensagem FROM notificacoes WHERE usuario_id = ? AND lida = 0 ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($notif = $res->fetch_assoc()) {
    $id_notif = $notif['id'];
    
    // 2. LOGICA DA SESSÃO: Só apita se for uma notificação que ainda não "avisamos" sonoramente
    // Isso impede que o som fique tocando infinitamente, mas mantém a lida = 0 no banco.
    if (!isset($_SESSION['ultima_notif_avisada']) || $_SESSION['ultima_notif_avisada'] != $id_notif) {
        $_SESSION['ultima_notif_avisada'] = $id_notif;
        
        echo json_encode([
            'tem' => true, 
            'msg' => $notif['mensagem']
        ]);
    } else {
        echo json_encode(['tem' => false]);
    }
} else {
    echo json_encode(['tem' => false]);
}
?>