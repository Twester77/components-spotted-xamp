<?php
/**
 * solicitar-entrada.php – Endpoint para usuário solicitar entrada em comunidade privada
 * 
 * Método: POST
 * Parâmetros: comunidade_id, csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token
 * - Prepared statements
 * - Verificação de comunidade existente e tipo privada
 * - Verificação de duplicidade (usuário já é membro ou já solicitou)
 * - Notificação para todos os admins/criadores da comunidade (tipo = 'solicitacao')
 * - Rate limiting por USUÁRIO (não por IP, para evitar bloqueios em redes compartilhadas)
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-11 (estrutura)
 * 🌙 LUZ – ATUALIZAÇÃO 2026-08-13: adicionado campo `tipo` nas notificações.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÕES INICIAIS
// ============================================================
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

// ============================================================
// 2. CAPTURA DOS PARÂMETROS E RATE LIMITING POR USUÁRIO
// ============================================================
$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$usuario_id = $_SESSION['usuario_id'];

if ($comunidade_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da comunidade inválido.']);
    exit;
}

// 🔥 RATE LIMITING: agora usa o ID do usuário, não o IP (evita bloqueios em redes compartilhadas)
$chave_rate = 'solicitar_entrada_' . $usuario_id;
if (isset($_SESSION[$chave_rate]) && $_SESSION[$chave_rate] > time() - 60) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um minuto antes de solicitar novamente.']);
    exit;
}
$_SESSION[$chave_rate] = time();

// ============================================================
// 3. VERIFICA SE A COMUNIDADE EXISTE E É PRIVADA
// ============================================================
$stmt = $conn->prepare("SELECT id, nome, criador_id, tipo FROM comunidades WHERE id = ?");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$res = $stmt->get_result();
$comunidade = $res->fetch_assoc();
$stmt->close();

if (!$comunidade) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Comunidade não encontrada.']);
    exit;
}

if ($comunidade['tipo'] !== 'privada') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Esta comunidade é pública. Você pode entrar diretamente.']);
    exit;
}

// ============================================================
// 4. VERIFICA SE O USUÁRIO JÁ É MEMBRO OU JÁ SOLICITOU
// ============================================================
$stmt = $conn->prepare("SELECT status FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $comunidade_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$membro = $res->fetch_assoc();
$stmt->close();

if ($membro) {
    if ($membro['status'] === 'ativo') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Você já é membro desta comunidade.']);
        exit;
    } elseif ($membro['status'] === 'pendente') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sua solicitação já está pendente de aprovação.']);
        exit;
    } elseif ($membro['status'] === 'banido') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Você foi banido desta comunidade.']);
        exit;
    }
}

// ============================================================
// 5. INSERE SOLICITAÇÃO (status = 'pendente')
// ============================================================
$stmt = $conn->prepare("INSERT INTO comunidade_membros (comunidade_id, usuario_id, papel, status, data_entrada) 
                         VALUES (?, ?, 'membro', 'pendente', NOW())");
$stmt->bind_param("ii", $comunidade_id, $usuario_id);

if (!$stmt->execute()) {
    fenda_log("🔴 Erro ao inserir solicitação: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação.']);
    exit;
}
$stmt->close();

fenda_log("🟢 Solicitação de entrada registrada: usuário $usuario_id → comunidade $comunidade_id");

// ============================================================
// 6. NOTIFICAÇÃO PARA ADMINS E CRIADOR DA COMUNIDADE (com tipo = 'solicitacao')
// ============================================================
$nome_usuario = $_SESSION['usuario_username'] ?? 'Alguém';
$nome_comunidade = $comunidade['nome'];
$mensagem_notif = "@$nome_usuario solicitou entrada em \"$nome_comunidade\" — veja na Central";

// Busca todos os admins e criadores da comunidade
$stmt = $conn->prepare("SELECT usuario_id FROM comunidade_membros 
                         WHERE comunidade_id = ? AND papel IN ('criador', 'admin') AND status = 'ativo'");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$res = $stmt->get_result();

while ($admin = $res->fetch_assoc()) {
    $admin_id = $admin['usuario_id'];
    // Não notifica o próprio solicitante (se ele for admin – improvável, mas previne)
    if ($admin_id == $usuario_id) continue;

    // 🔥 INSERE COM TIPO 'solicitacao' (post_id = NULL)
    $stmt_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida, data_criacao) 
                                   VALUES (?, NULL, 'solicitacao', ?, 0, NOW())");
    $stmt_notif->bind_param("is", $admin_id, $mensagem_notif);
    $stmt_notif->execute();
    $stmt_notif->close();
    fenda_log("🔔 Notificação enviada para admin $admin_id sobre solicitação de entrada");
}
$stmt->close();

// ============================================================
// 7. RESPOSTA DE SUCESSO
// ============================================================
echo json_encode([
    'success' => true,
    'message' => 'Solicitação enviada! Aguarde a aprovação de um administrador.'
]);
exit;