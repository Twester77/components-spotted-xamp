<?php
require_once __DIR__ . '/auth_check.php';

// ============================================================
// 1. VALIDAÇÃO DOS PARÂMETROS
// ============================================================
if (!isset($_GET['id']) || !isset($_GET['user']) || empty($_GET['id']) || empty($_GET['user'])) {
    header("Location: feed.php");
    exit();
}

$seguidor_id = (int)$_SESSION['usuario_id']; // Quem está logado
$seguido_id  = (int)$_GET['id'];             // Quem será seguido/desseguido
$username    = trim($_GET['user']);           // Usado apenas para redirecionamento

// ============================================================
// 2. IMPEDE AUTO-SEGUIR
// ============================================================
if ($seguidor_id == $seguido_id) {
    header("Location: ver-perfil.php?user=" . urlencode($username));
    exit();
}

// ============================================================
// 3. VERIFICA SE JÁ SEGUE (PREPARED STATEMENT)
// ============================================================
$check_sql = "SELECT 1 FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
$check_stmt = $conn->prepare($check_sql);
if (!$check_stmt) {
    error_log("[SEGUIR] Erro ao preparar SELECT: " . $conn->error);
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'feed.php'));
    exit();
}
$check_stmt->bind_param("ii", $seguidor_id, $seguido_id);
$check_stmt->execute();
$check_stmt->store_result();
$ja_segue = ($check_stmt->num_rows > 0);
$check_stmt->close();

// ============================================================
// 4. EXECUTA A AÇÃO (INSERT OU DELETE) COM PREPARED STATEMENT
// ============================================================
if ($ja_segue) {
    // UNFOLLOW (DELETE)
    $sql = "DELETE FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("[SEGUIR] Erro ao preparar DELETE: " . $conn->error);
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'feed.php'));
        exit();
    }
    $stmt->bind_param("ii", $seguidor_id, $seguido_id);
    $executou = $stmt->execute();
    $stmt->close();
} else {
    // FOLLOW (INSERT)
    $sql = "INSERT INTO seguidores (id_seguidor, id_seguido) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("[SEGUIR] Erro ao preparar INSERT: " . $conn->error);
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'feed.php'));
        exit();
    }
    $stmt->bind_param("ii", $seguidor_id, $seguido_id);
    $executou = $stmt->execute();
    $stmt->close();
}

// ============================================================
// 5. REDIRECIONAMENTO SEGURO
// ============================================================
if (!$executou) {
    error_log("[SEGUIR] Falha ao executar ação para seguidor=$seguidor_id, seguido=$seguido_id");
}

// Se houver referer, volta para lá; senão, vai para o perfil do usuário
$redirect = $_SERVER['HTTP_REFERER'] ?? "ver-perfil.php?user=" . urlencode($username);
header("Location: " . $redirect);
exit();
?>