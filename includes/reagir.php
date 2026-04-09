<?php
include '../conexao.php';
session_start();

// 1. Verifica se o usuário está logado 
if (!isset($_SESSION['usuario_id'])) {
    header("Location:../index.php?erro=login");
    exit();
}

// 2. Proteção básica contra SQL Injection 
$post_id = mysqli_real_escape_string($conn, $_GET['id']);
$tipo = mysqli_real_escape_string($conn, $_GET['tipo']);
$usuario_id = $_SESSION['usuario_id'];

// 3. Verifica se o usuário já reagiu a esse post
$check = "SELECT * FROM curtidas WHERE mensagem_id = '$post_id' AND usuario_id = '$usuario_id'";
$res_check = mysqli_query($conn, $check);
$dados_reacao = mysqli_fetch_assoc($res_check);

if (mysqli_num_rows($res_check) == 0) {
    // Se ele não reagiu ainda, insere a nova reação
    $sql = "INSERT INTO curtidas (mensagem_id, usuario_id, tipo_reacao) 
            VALUES ('$post_id', '$usuario_id', '$tipo')";
    mysqli_query($conn, $sql);
} else {
    // LÓGICA DE TOGGLE: 
    // Se ele clicou no MESMO emoji que já estava lá, o botão REMOVE a reação 
    if ($dados_reacao['tipo_reacao'] == $tipo) {
        $sql = "DELETE FROM curtidas WHERE mensagem_id = '$post_id' AND usuario_id = '$usuario_id'";
    } else {
        // Se ele clicou num emoji DIFERENTE, a gente atualiza a escolha dele
        $sql = "UPDATE curtidas SET tipo_reacao = '$tipo' 
                WHERE mensagem_id = '$post_id' AND usuario_id = '$usuario_id'";
    }
    mysqli_query($conn, $sql);
}

/* 4. Volta para a página de onde o usuário veio (Feed). 
Se o  usuário  tiver filtrado o resultado também volta pra categoria e o post que foi reagido. */ 

$cat_retorno = isset($_GET['cat']) ? $_GET['cat'] : '';
$categoria = $_GET['categoria'] ?? '';
$referencia = $_GET['ref'] ?? '';

if (!empty($categoria)) {
    header("Location:../feed.php?categoria=" . urlencode($categoria) . "#" . $referencia);
} else {
    header("Location:../feed.php#" . $referencia);
}
exit();