<?php
include 'conexao.php';
session_start(); // 1. IMPORTANTE: Inicie a sessão para pegar o ID!

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $mensagem = $_POST['mensagem'];
    $categoria = $_POST['categoria'];
    $usuario_id = $_SESSION['usuario_id']; // 2. Pegue o ID de quem está logado

    // 3. Adicione o usuario_id no seu INSERT
    $sql = "INSERT INTO mensagens (mensagem, categoria, usuario_id, data_post) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    // "ssi" -> string, string, integer (o ID é número)
    $stmt->bind_param("ssi", $mensagem, $categoria, $usuario_id);

    if ($stmt->execute()) {
        header("Location: feed.php");
        exit();
    } else {
        echo "Erro ao lançar ao mar: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Se tentarem acessar esse arquivo direto, manda de volta pro formulário
    header("Location: novo-post.php");
    exit();
}
?>