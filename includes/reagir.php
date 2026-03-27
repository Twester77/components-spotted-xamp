<?php
include 'conexao.php';
session_start();

// 1. Verificamos se o usuário está logado 
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php?erro=login");
    exit();
}

// 2. Proteção básica contra SQL Injection (ADS safe)
$post_id = mysqli_real_escape_string($conn, $_GET['id']);
$tipo = mysqli_real_escape_string($conn, $_GET['tipo']);
$usuario_id = $_SESSION['usuario_id'];

// 3. Verifica se o usuário já reagiu a esse post
$check = "SELECT * FROM curtidas WHERE mensagem_id = '$post_id' AND usuario_id = '$usuario_id'";
$res_check = mysqli_query($conn, $check);
$dados_reacao = mysqli_fetch_assoc($res_check);

if (mysqli_num_rows($res_check) == 0) {
    // Se não reagiu ainda, insere a nova reação
    $sql = "INSERT INTO curtidas (mensagem_id, usuario_id, tipo_reacao) 
            VALUES ('$post_id', '$usuario_id', '$tipo')";
    mysqli_query($conn, $sql);
} else {
    // LÓGICA DE TOGGLE: 
    // Se ele clicou no MESMO emoji que já estava lá, a gente REMOVE (descurtir)
    if ($dados_reacao['tipo_reacao'] == $tipo) {
        $sql = "DELETE FROM curtidas WHERE mensagem_id = '$post_id' AND usuario_id = '$usuario_id'";
    } else {
        // Se ele clicou num emoji DIFERENTE, a gente atualiza a escolha dele
        $sql = "UPDATE curtidas SET tipo_reacao = '$tipo' 
                WHERE mensagem_id = '$post_id' AND usuario_id = '$usuario_id'";
    }
    mysqli_query($conn, $sql);
}

// 4. Volta para a página de onde o usuário veio (Feed ou Post Detalhes)
if(isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: feed.php");
}
exit();
?>