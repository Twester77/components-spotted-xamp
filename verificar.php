<?php
include 'conexao.php';

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);

    // Busca o usuário com esse token
    $sql = "SELECT id FROM usuarios WHERE token = '$token' AND ativo = 0";
    $res = mysqli_query($conn, $sql);

    if (mysqli_num_rows($res) > 0) {
        // ATIVA O CARA!
        $sql_update = "UPDATE usuarios SET ativo = 1, token = NULL WHERE token = '$token'";
        if (mysqli_query($conn, $sql_update)) {
        header("Location: /spotted-unifev/index.php?msg=conta_ativada");
      exit();        }
    } else {
        echo "Ops! Esse link é inválido ou você já ativou sua conta.";
    }
}
?>