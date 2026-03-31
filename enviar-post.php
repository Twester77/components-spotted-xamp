<?php
include 'conexao.php';
session_start();

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 2. Higienização básica
    $mensagem = mysqli_real_escape_string($conn, $_POST['mensagem']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    $usuario_id = $_SESSION['usuario_id']; 

    // 3. Preparação do SQL (Segurança Máxima)
    // Note que se você não usa subcategoria aqui, tiramos do INSERT para não dar erro
    $sql = "INSERT INTO mensagens (mensagem, categoria, usuario_id, data_post) VALUES (?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    // "ssi" -> string (msg), string (cat), integer (id)
    $stmt->bind_param("ssi", $mensagem, $categoria, $usuario_id);

    if ($stmt->execute()) {
        // Sucesso! Volta para o feed
        header("Location: feed.php");
        exit();
    } else {
        // Se der erro, ele te avisa o que é (ex: coluna faltando)
        die("ERRO AO SALVAR: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: novo-post.php");
    exit();
}
?>