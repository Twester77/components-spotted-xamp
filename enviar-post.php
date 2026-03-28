<?php
include 'conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $mensagem = $_POST['mensagem'];
    $categoria = $_POST['categoria'];
    
    // Pega a subcategoria (se não existir no post, fica nula)
    $subcategoria = isset($_POST['subcategoria']) ? $_POST['subcategoria'] : null;
    
    $usuario_id = $_SESSION['usuario_id']; 

    // 3. Adicionamos 'subcategoria' no INSERT e um "?" a mais
    $sql = "INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, data_post) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    
    // "sssi" -> string (msg), string (cat), string (subcat), integer (id)
    $stmt->bind_param("sssi", $mensagem, $categoria, $subcategoria, $usuario_id);

    if ($stmt->execute()) {
        // Se postou de dentro do perdidos.php, pode até voltar pra lá
        // Mas o feed.php é o destino padrão seguro
        header("Location: feed.php");
        exit();
    } else {
        echo "Erro ao lançar ao mar: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: novo-post.php");
    exit();
}
?>