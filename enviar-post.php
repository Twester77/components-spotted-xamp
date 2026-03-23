<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $mensagem = mysqli_real_escape_string($conn, $_POST['mensagem']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    
    $sql = "INSERT INTO mensagens (mensagem, categoria) VALUES ('$mensagem', '$categoria')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: feed.php");
    } else {
        echo "Erro ao enviar: " . mysqli_error($conn);
    }
}
?>