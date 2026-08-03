<?php
/**
 * marcar-todas-lidas.php – Marca todas as notificações do usuário como lidas (AJAX)
 * 
 * Método: POST
 * Parâmetros: csrf_token (para segurança)
 * Retorno: JSON { success: true/false, message: string }
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Faça login para continuar.']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// 🔥 CSRF TOKEN (reutiliza o token da sessão)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Marca todas as notificações como lidas
$sql = "UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

// affected_rows pode ser 0 (se já estavam todas lidas) – ainda é sucesso
$stmt->close();

//  Atualiza o badge (opcional – o front-end vai recarregar a lista e o badge)
echo json_encode([
    'success' => true,
    'message' => 'Todas as notificações foram marcadas como lidas.'
]);
exit;
?>