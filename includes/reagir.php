<?php
include '../conexao.php';
session_start();

// 1. Verifica se o usuário está logado 
if (!isset($_SESSION['usuario_id'])) {
    header("Location:../index.php?erro=login");
    exit();
}

// 2. Recebe os dados (Sem escape_string, o prepare cuida disso!)
$post_id = $_GET['id'] ?? 0;
$tipo = $_GET['tipo'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

// 3. Verifica se o usuário já reagiu a esse post (PREPARED STATEMENT)
$check = $conn->prepare("SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
$check->bind_param("ii", $post_id, $usuario_id); // "ii" = dois inteiros
$check->execute();
$res_check = $check->get_result();
$dados_reacao = $res_check->fetch_assoc();

if ($res_check->num_rows == 0) {
    // Se não reagiu, INSERE
    $stmt = $conn->prepare("INSERT INTO curtidas (mensagem_id, usuario_id, tipo_reacao) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $usuario_id, $tipo); // "iis" = inteiro, inteiro, string
    $stmt->execute();
} else {
    // LÓGICA DE TOGGLE
    if ($dados_reacao['tipo_reacao'] == $tipo) {
        // REMOVE se for igual
        $stmt = $conn->prepare("DELETE FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $post_id, $usuario_id);
    } else {
        // ATUALIZA se for diferente
        $stmt = $conn->prepare("UPDATE curtidas SET tipo_reacao = ? WHERE mensagem_id = ? AND usuario_id = ?");
        $stmt->bind_param("sii", $tipo, $post_id, $usuario_id);
    }
    $stmt->execute();
}

// 4. Lógica de Retorno (Igual a sua, mas organizada)
$categoria = $_GET['categoria'] ?? '';
$referencia = $_GET['ref'] ?? '';

if (!empty($categoria)) {
    header("Location:../feed.php?categoria=" . urlencode($categoria) . "#" . $referencia);
} else {
    header("Location:../feed.php#" . $referencia);
}
exit();