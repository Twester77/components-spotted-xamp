<?php
/**
 * promover-membro.php – Endpoint para promover/rebaixar administradores de uma comunidade
 * 
 * Método: POST
 * Parâmetros: comunidade_id, usuario_id, acao (promover/rebaixar), csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório
 * - Prepared statements
 * - APENAS O CRIADOR pode promover/rebaixar admins
 * - Criador NÃO pode ser rebaixado
 * - Usuário NÃO pode promover/rebaixar a si mesmo
 * - Rate limiting: 5 ações por minuto (array de timestamps)
 * - Logs via fenda_log()
 * 
 * 📌 Regras:
 * - Promover: membro → admin (apenas criador)
 * - Rebaixar: admin → membro (apenas criador)
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 Rate limit corrigido: array de timestamps (5 ações/minuto)
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

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

// ============================================================
// 2. RATE LIMITING (5 ações por minuto com array de timestamps)
// ============================================================
$usuario_id = $_SESSION['usuario_id'];
$chave_rate = 'acoes_membro_' . $usuario_id;
$agora = time();

if (!isset($_SESSION[$chave_rate]) || !is_array($_SESSION[$chave_rate])) {
    $_SESSION[$chave_rate] = [];
}

// Remove timestamps com mais de 60 segundos
$_SESSION[$chave_rate] = array_filter($_SESSION[$chave_rate], function($t) use ($agora) {
    return ($agora - $t) < 60;
});

if (count($_SESSION[$chave_rate]) >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um momento antes de realizar outra ação.']);
    exit;
}

$_SESSION[$chave_rate][] = $agora;

// ============================================================
// 3. CAPTURA DOS PARÂMETROS
// ============================================================
$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$usuario_alvo_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if ($comunidade_id <= 0 || $usuario_alvo_id <= 0 || !in_array($acao, ['promover', 'rebaixar'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

// ============================================================
// 4. VERIFICA SE O USUÁRIO LOGADO É O CRIADOR
// ============================================================
$stmt = $conn->prepare("SELECT papel FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin || $admin['papel'] !== 'criador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Apenas o criador da comunidade pode promover ou rebaixar administradores.']);
    exit;
}

// ============================================================
// 5. VERIFICA SE O ALVO EXISTE E NÃO É O CRIADOR
// ============================================================
$stmt = $conn->prepare("SELECT papel, status FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $comunidade_id, $usuario_alvo_id);
$stmt->execute();
$res = $stmt->get_result();
$alvo = $res->fetch_assoc();
$stmt->close();

if (!$alvo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado nesta comunidade.']);
    exit;
}

if ($alvo['papel'] === 'criador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Não é possível promover ou rebaixar o criador da comunidade.']);
    exit;
}

if ($usuario_alvo_id == $usuario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não pode promover ou rebaixar a si mesmo.']);
    exit;
}

// ============================================================
// 6. VALIDA A AÇÃO COM BASE NO PAPEL ATUAL
// ============================================================
$papel_atual = $alvo['papel'];
$status_atual = $alvo['status'];

if ($status_atual !== 'ativo') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este usuário não está ativo na comunidade.']);
    exit;
}

if ($acao === 'promover' && $papel_atual === 'admin') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este usuário já é um administrador.']);
    exit;
}

if ($acao === 'rebaixar' && $papel_atual !== 'admin') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este usuário não é um administrador.']);
    exit;
}

// ============================================================
// 7. EXECUTA A AÇÃO (PROMOVER OU REBAIXAR)
// ============================================================
$novo_papel = ($acao === 'promover') ? 'admin' : 'membro';

$stmt = $conn->prepare("UPDATE comunidade_membros SET papel = ? WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("sii", $novo_papel, $comunidade_id, $usuario_alvo_id);

if ($stmt->execute()) {
    $stmt->close();
    $acao_texto = ($acao === 'promover') ? 'promovido a administrador' : 'rebaixado a membro';
    fenda_log("🟢 [$acao] Usuário $usuario_alvo_id $acao_texto na comunidade $comunidade_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuário ' . $acao_texto . ' com sucesso.',
        'novo_papel' => $novo_papel
    ]);
} else {
    fenda_log("🔴 Erro ao $acao: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao executar ação.']);
    $stmt->close();
}
exit;