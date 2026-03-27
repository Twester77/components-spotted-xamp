<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_mensagem = $_POST['id_mensagem'];
    $comentario = $_POST['comentario'];

    // Insere o comentário ligado ao post certo
    $sql = "INSERT INTO comentarios (id_mensagem, comentario) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_mensagem, $comentario);

    if ($stmt->execute()) {
        // Volta para a página do post específico
        header("Location: post.php?id=" . $id_mensagem);
        exit();
    } else {
        echo "Erro ao comentar: " . $conn->error;
    }
}
?>