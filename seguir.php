<?php
/**
 * seguir.php – Follow/Unfollow de usuários (endpoint protegido)
 *
 * 🔒 SEGURANÇA (auditoria Pérola – 2026-09-18):
 *    - Mudou de GET para POST (impede CSRF via <img src> em sites terceiros).
 *    - Validação obrigatória de csrf_token.
 *    - Retorna JSON para o front-end. Mantém redirect para GET antigo
 *      (bookmarks/links) para não quebrar UX.
 *
 * @package A Fenda
 */

require_once __DIR__ . '/auth_check.php';

// ============================================================
// 1. SÓ ACEITA POST (proteção CSRF)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET antigo (bookmark, link): redireciona pra não quebrar
    $username = isset($_GET['user']) ? trim($_GET['user']) : '';
    if ($username !== '') {
        header("Location: ver-perfil.php?user=" . urlencode($username));
    } else {
        header("Location: feed.php");
    }
    exit();
}

header('Content-Type: application/json');

// ============================================================
// 2. VALIDAÇÃO CSRF (obrigatória)
// ============================================================
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || $csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit();
}

// ============================================================
// 3. VALIDAÇÃO DOS PARÂMETROS
// ============================================================
$seguidor_id = (int)($_SESSION['usuario_id'] ?? 0);
$seguido_id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$username    = isset($_POST['user']) ? trim($_POST['user']) : '';

if ($seguidor_id <= 0 || $seguido_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit();
}

// ============================================================
// 4. IMPEDE AUTO-SEGUIR
// ============================================================
if ($seguidor_id == $seguido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Você não pode seguir a si mesmo.']);
    exit();
}

// ============================================================
// 5. VERIFICA SE JÁ SEGUE (PREPARED STATEMENT)
// ============================================================
$check_sql = "SELECT 1 FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    error_log("[SEGUIR] Erro ao preparar SELECT: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno.']);
    exit();
}
$check_stmt->bind_param("ii", $seguidor_id, $seguido_id);
$check_stmt->execute();
$check_stmt->store_result();
$ja_segue = ($check_stmt->num_rows > 0);
$check_stmt->close();

// ============================================================
// 6. EXECUTA A AÇÃO (INSERT OU DELETE)
// ============================================================
$executou = false;
$novo_estado = !$ja_segue; // Se já seguia → deixa de seguir. Se não seguia → passa a seguir.

if ($ja_segue) {
    // UNFOLLOW (DELETE)
    $stmt = $conn->prepare("DELETE FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $seguidor_id, $seguido_id);
        $executou = $stmt->execute();
        $stmt->close();
    }
} else {
    // FOLLOW (INSERT)
    $stmt = $conn->prepare("INSERT INTO seguidores (id_seguidor, id_seguido) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $seguidor_id, $seguido_id);
        $executou = $stmt->execute();
        $stmt->close();
    }
}

if (!$executou) {
    error_log("[SEGUIR] Falha ao executar ação para seguidor=$seguidor_id, seguido=$seguido_id");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar. Tente novamente.']);
    exit();
}

// ============================================================
// 7. BUSCA CONTAGEM ATUALIZADA DE SEGUIDORES
// ============================================================
$total_seguidores = 0;
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = ?");
if ($count_stmt) {
    $count_stmt->bind_param("i", $seguido_id);
    $count_stmt->execute();
    $row = $count_stmt->get_result()->fetch_assoc();
    $total_seguidores = (int)($row['total'] ?? 0);
    $count_stmt->close();
}

// ============================================================
// 8. RESPOSTA JSON
// ============================================================
echo json_encode([
    'success' => true,
    'seguindo' => $novo_estado,
    'total_seguidores' => $total_seguidores,
    'message' => $novo_estado ? 'Seguindo!' : 'Deixou de seguir.'
]);
exit;