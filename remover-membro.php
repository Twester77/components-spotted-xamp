<?php
/**
 * remover-membro.php – Endpoint para remover um membro da comunidade (DELETE físico)
 * 
 * Método: POST
 * Parâmetros: comunidade_id, usuario_id, csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório
 * - Prepared statements
 * - Apenas admin/criador podem remover
 * - Criador NÃO pode ser removido (nem por ele mesmo)
 * - Usuário NÃO pode remover a si mesmo
 * - Rate limiting: 5 ações por minuto (array de timestamps)
 * - Logs via fenda_log()
 * 
 * 📌 Nota: Remoção é DELETE físico da tabela comunidade_membros.
 * O usuário poderá solicitar entrada novamente no futuro.
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

if ($comunidade_id <= 0 || $usuario_alvo_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

// ============================================================
// 4. VERIFICA PERMISSÃO DO ADMIN
// ============================================================
$stmt = $conn->prepare("SELECT papel FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin || !in_array($admin['papel'], ['criador', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não tem permissão para remover membros.']);
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
    echo json_encode(['success' => false, 'message' => 'Não é possível remover o criador da comunidade.']);
    exit;
}

if ($usuario_alvo_id == $usuario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não pode remover a si mesmo.']);
    exit;
}

// ============================================================
// 6. EXECUTA A REMOÇÃO (DELETE FÍSICO)
// ============================================================
$stmt = $conn->prepare("DELETE FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $comunidade_id, $usuario_alvo_id);

if ($stmt->execute()) {
    $deletados = $stmt->affected_rows;
    $stmt->close();
    
    if ($deletados > 0) {
        fenda_log("🟢 [REMOVER] Usuário $usuario_alvo_id removido da comunidade $comunidade_id");
        echo json_encode([
            'success' => true,
            'message' => 'Membro removido com sucesso.'
        ]);
    } else {
        fenda_log("⚠️ [REMOVER] Nenhuma linha afetada para usuário $usuario_alvo_id na comunidade $comunidade_id");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Membro não encontrado ou já removido.']);
    }
} else {
    fenda_log("🔴 Erro ao remover: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao remover membro.']);
    $stmt->close();
}
exit;