<?php
session_start();
// Conexão com banco aqui...

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $arquivo = $_FILES['foto'];
    $limite_bytes = 15 * 1024 * 1024; // 15MB

    // Validação de tamanho
    if ($arquivo['size'] > $limite_bytes) {
        die("Opa! Essa foto passou dos 15MB. Tenta uma um pouco menor?");
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nome_final = "user_" . $_SESSION['usuario_id'] . ".jpg";
    $destino = "uploads/perfil/" . $nome_final;

    // Criar imagem na memória
    if ($extensao == 'jpg' || $extensao == 'jpeg') {
        $img = imagecreatefromjpeg($arquivo['tmp_name']);
    } elseif ($extensao == 'png') {
        $img = imagecreatefrompng($arquivo['tmp_name']);
    }

    if ($img) {
        // Salva comprimindo para ~1.5MB (Qualidade 75)
        imagejpeg($img, $destino, 75); 
        imagedestroy($img);
        
        // Aqui você faz o UPDATE no seu Banco de Dados
        // mysqli_query($conn, "UPDATE usuarios SET foto='$nome_final' WHERE id=...");
        
        header("Location: perfil.php?sucesso=1");
    }
}
?>