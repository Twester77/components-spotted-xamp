<?php
session_start(); // OBRIGATÓRIO: Sem isso o PHP não lê o nome de quem logou!
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_mensagem = $_POST['id_mensagem'];
    $comentario = $_POST['comentario'];

    // Pegamos o nome da sessão se existir, senão fica vazio (para o banco tratar como anônimo)
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;

    // IMPORTANTE: Sua tabela 'comentarios' PRECISA ter a coluna 'usuario_nome'
    $sql = "INSERT INTO comentarios (id_mensagem, comentario, usuario_nome) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // "iss" -> i (inteiro), s (string comentário), s (string nome)
    $stmt->bind_param("iss", $id_mensagem, $comentario, $usuario_nome);

    if ($stmt->execute()) {
        header("Location: post.php?id=" . $id_mensagem);
        exit();
    } else {
        echo "Erro ao comentar: " . $conn->error;
    }
}
?>