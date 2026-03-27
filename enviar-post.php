<?php
include 'conexao.php';

// Verifica se os dados vieram via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Captura os dados do formulário
    // O nome dentro do [''] deve ser igual ao 'name' do seu HTML
    $mensagem = $_POST['mensagem'];
    $categoria = $_POST['categoria'];

    // Prepara o SQL para evitar SQL Injection (Segurança em 1º lugar!)
    $sql = "INSERT INTO mensagens (mensagem, categoria) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    
    // "ss" significa que estamos enviando duas Strings
    $stmt->bind_param("ss", $mensagem, $categoria);

    if ($stmt->execute()) {
        // Se deu certo, volta para o feed para ver o post novo
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