<?php
/**
 * rejeitar-entrada.php – Endpoint para admin rejeitar solicitação
 * 
 * 🔔 Notificação: tipo = 'solicitacao' (adicionado pela Lua)
 * 
 * 🔧 CORRIGIDO: removida referência à coluna 'id' (tabela tem chave composta)
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

fenda_log('🔵 [REJEITAR] Iniciando');

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

$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$usuario_solicitante_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
$admin_id = $_SESSION['usuario_id'];

if ($comunidade_id <= 0 || $usuario_solicitante_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

// Verifica permissão do admin
$stmt = $conn->prepare("SELECT papel FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $admin_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin || !in_array($admin['papel'], ['criador', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
    exit;
}

// 🔥 CORREÇÃO: verifica se existe solicitação pendente (sem usar 'id')
$stmt = $conn->prepare("SELECT 1 FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'pendente'");
$stmt->bind_param("ii", $comunidade_id, $usuario_solicitante_id);
$stmt->execute();
$res = $stmt->get_result();
$existe = $res->num_rows > 0;
$stmt->close();

if (!$existe) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Nenhuma solicitação pendente.']);
    exit;
}

// Remove a solicitação (DELETE)
$stmt = $conn->prepare("DELETE FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'pendente'");
$stmt->bind_param("ii", $comunidade_id, $usuario_solicitante_id);

if (!$stmt->execute()) {
    fenda_log('🔴 [REJEITAR] Erro no DELETE: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar.']);
    exit;
}
$stmt->close();

fenda_log('🟢 [REJEITAR] Sucesso!');

// ============================================================
// 🔔 NOTIFICAÇÃO PARA O SOLICITANTE (com tipo = 'solicitacao')
// ============================================================
$nome_comunidade = $conn->query("SELECT nome FROM comunidades WHERE id = $comunidade_id")->fetch_assoc()['nome'] ?? 'Comunidade';
$mensagem_notif = "Sua solicitação para entrar em \"$nome_comunidade\" foi rejeitada. ";

$stmt_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida, data_criacao) 
                               VALUES (?, NULL, 'solicitacao', ?, 0, NOW())");
$stmt_notif->bind_param("is", $usuario_solicitante_id, $mensagem_notif);
$stmt_notif->execute();
$stmt_notif->close();

fenda_log("🔔 Notificação de rejeição enviada para $usuario_solicitante_id");

echo json_encode([
    'success' => true,
    'message' => 'Solicitação rejeitada com sucesso!'
]);
exit;