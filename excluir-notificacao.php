<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
$csrf_sessao = $_SESSION['csrf_token'] ?? '';
if (!is_string($csrf_token) || $csrf_token === '' || !is_string($csrf_sessao) || !hash_equals($csrf_sessao, $csrf_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token de segurança inválido.']);
    exit;
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$raw_notif_id = $_POST['id'] ?? null;
if (!is_string($raw_notif_id) || !ctype_digit($raw_notif_id) || (int)$raw_notif_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID da notificação inválido.']);
    exit;
}
$notif_id = (int)$raw_notif_id;

$ip = function_exists('obterIPReal')
    ? obterIPReal()
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$endpoint = 'excluir_notificacao';

try {
    $transactionStarted = false;

    $conn->query("CREATE TABLE IF NOT EXISTS fenda_rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        endpoint VARCHAR(64) NOT NULL,
        usuario_id INT NULL,
        ip_address VARCHAR(45) NOT NULL,
        tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_endpoint (endpoint),
        INDEX idx_usuario (usuario_id),
        INDEX idx_ip (ip_address),
        INDEX idx_tentativa (tentativa)
    )");

    $stmt_rate = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM fenda_rate_limits
             WHERE endpoint = ? AND usuario_id = ? AND tentativa > NOW() - INTERVAL 5 MINUTE) AS user_total,
            (SELECT COUNT(*) FROM fenda_rate_limits
             WHERE endpoint = ? AND ip_address = ? AND tentativa > NOW() - INTERVAL 5 MINUTE) AS ip_total
    ");
    $stmt_rate->bind_param('siis', $endpoint, $usuario_id, $endpoint, $ip);
    $stmt_rate->execute();
    $result_rate = $stmt_rate->get_result()->fetch_assoc();
    $stmt_rate->close();

    if ((int)($result_rate['user_total'] ?? 0) >= 20 || (int)($result_rate['ip_total'] ?? 0) >= 100) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Aguarde um momento antes de excluir outra notificação.']);
        exit;
    }

    $stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
    $stmt_log_rate->bind_param('sis', $endpoint, $usuario_id, $ip);
    $stmt_log_rate->execute();
    $stmt_log_rate->close();

    $conn->query("CREATE TABLE IF NOT EXISTS logs_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        acao VARCHAR(50) NOT NULL,
        detalhes TEXT NULL,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_usuario (usuario_id),
        INDEX idx_acao (acao),
        INDEX idx_data (data_hora)
    )");

    $conn->begin_transaction();
    $transactionStarted = true;

    $stmt_check = $conn->prepare("SELECT id, mensagem, tipo, lida FROM notificacoes WHERE id = ? AND usuario_id = ? FOR UPDATE");
    $stmt_check->bind_param('ii', $notif_id, $usuario_id);
    $stmt_check->execute();
    $row = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$row) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Notificação não encontrada.']);
        exit;
    }

    $stmt_delete = $conn->prepare("DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?");
    $stmt_delete->bind_param('ii', $notif_id, $usuario_id);
    $stmt_delete->execute();
    $excluida = $stmt_delete->affected_rows === 1;
    $stmt_delete->close();

    if (!$excluida) {
        $conn->rollback();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'A notificação já foi removida. Atualize a lista e tente novamente.']);
        exit;
    }

    $stmt_auditoria = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, acao, detalhes, data_hora) VALUES (?, 'excluir_notificacao', ?, NOW())");
    $detalhes = 'notif_id=' . $notif_id . ', tipo=' . ($row['tipo'] ?? 'desconhecido') . ', mensagem=' . mb_substr(strip_tags($row['mensagem'] ?? ''), 0, 255, 'UTF-8');
    $stmt_auditoria->bind_param('is', $usuario_id, $detalhes);
    $stmt_auditoria->execute();
    $stmt_auditoria->close();

    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM notificacoes WHERE usuario_id = ? AND lida = 0");
    $stmt_count->bind_param('i', $usuario_id);
    $stmt_count->execute();
    $unread_count = (int)($stmt_count->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt_count->close();

    $conn->commit();
    $transactionStarted = false;

    echo json_encode([
        'status' => 'success',
        'message' => 'Notificação removida.',
        'id' => $notif_id,
        'unread_count' => $unread_count,
    ]);
    exit;
} catch (Throwable $e) {
    if (!empty($transactionStarted)) {
        $conn->rollback();
    }
    error_log('[EXCLUIR_NOTIFICACAO] Falha ao processar exclusão: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível excluir a notificação. Tente novamente.']);
    exit;
}
