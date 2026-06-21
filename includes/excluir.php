<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth_check.php';

// ============================================================
// 1. DETECTA SE É REQUISIÇÃO AJAX (POST com X-Requested-With)
// ============================================================
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Pega o ID (via GET ou POST)
$post_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $post_id = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
}

// ============================================================
// FUNÇÃO AUXILIAR PARA RESPONDER JSON (garante cabeçalho e exit)
// ============================================================
function responderJSON($status, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// ============================================================
// 2. VALIDAÇÃO DO ID
// ============================================================
if ($post_id <= 0) {
    if ($is_ajax) {
        responderJSON('error', 'ID inválido.');
    } else {
        header("Location: ../feed.php");
        exit;
    }
}

// ============================================================
// 3. VERIFICA PERMISSÃO E EXECUTA SOFT DELETE
// ============================================================
$usuario_id = (int)$_SESSION['usuario_id'];

$check = $conn->prepare("SELECT usuario_id, imagem_url FROM mensagens WHERE id = ? AND status = 'ativo'");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if (!$dados_post || $dados_post['usuario_id'] != $usuario_id) {
    if ($is_ajax) {
        responderJSON('error', 'Você não tem permissão para excluir este post.');
    } else {
        header("Location: ../feed.php");
        exit;
    }
}

// Soft delete
$soft_delete = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ?");
$soft_delete->bind_param("i", $post_id);
$executou = $soft_delete->execute();

if ($executou) {
    // Move a imagem (se existir)
    if (!empty($dados_post['imagem_url'])) {
        $origem = "../postagens/" . $dados_post['imagem_url'];
        $destino = "../lixeira_postagens/" . $dados_post['imagem_url'];
        if (file_exists($origem)) {
            rename($origem, $destino);
        }
    }
}

$soft_delete->close();
$check->close();

// ============================================================
// 4. RESPOSTA FINAL
// ============================================================
if ($is_ajax) {
    if ($executou) {
        responderJSON('success', 'Post excluído com sucesso.');
    } else {
        responderJSON('error', 'Falha ao excluir no banco de dados.');
    }
} else {
    // Modo tradicional (GET) – fallback para navegadores sem JS
    if ($executou) {
        header("Location: ../feed.php?msg=deletado");
    } else {
        header("Location: ../feed.php?erro=delete_fail");
    }
    exit;
}
?>