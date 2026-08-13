<?php
/**
 * salvar-pref-swipe-balanga.php – Endpoint para salvar a preferência de swipe do Balanga Teras
 * 
 * Método: POST
 * Parâmetros: pref_swipe_balanga (0 ou 1), csrf_token
 * Retorno: JSON { success: true/false, message: string }
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

fenda_log('🟢 INÍCIO salvar-pref-swipe-balanga.php');

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

$usuario_id = $_SESSION['usuario_id'];
$pref = isset($_POST['pref_swipe_balanga']) ? (int)$_POST['pref_swipe_balanga'] : 0;
$pref = $pref ? 1 : 0; // Garante que seja 0 ou 1

$stmt = $conn->prepare("UPDATE usuarios SET pref_swipe_balanga = ? WHERE id = ?");
$stmt->bind_param("ii", $pref, $usuario_id);

if ($stmt->execute()) {
    $stmt->close();
    fenda_log("🟢 Preferência de swipe Balanga Teras atualizada para usuário $usuario_id: $pref");
    echo json_encode(['success' => true, 'message' => 'Preferência salva!']);
} else {
    $erro = $stmt->error;
    $stmt->close();
    fenda_log("❌ Erro ao salvar preferência: $erro");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar preferência.']);
}
exit;
?>