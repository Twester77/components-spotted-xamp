<?php
session_start();
include 'includes/conexao.php'; // Não esquece de incluir a conexão!

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $arquivo = $_FILES['foto'];
    $usuario_id = $_SESSION['usuario_id']; // Pegando o ID da sessão
    $limite_bytes = 15 * 1024 * 1024; // 15MB

    // 1. Validação de tamanho
    if ($arquivo['size'] > $limite_bytes) {
        die("Opa! Essa foto passou dos 15MB. Tenta uma um pouco menor?");
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    // 2. AJUSTE DE CAMINHO
    $nome_final = "user_" . $usuario_id . ".jpg";
    $destino = "uploads/" . $nome_final; 

    // Criar imagem na memória para compressão
    $img = null;
    if ($extensao == 'jpg' || $extensao == 'jpeg') {
        $img = @imagecreatefromjpeg($arquivo['tmp_name']);
    } elseif ($extensao == 'png') {
        $img = @imagecreatefrompng($arquivo['tmp_name']);
    }

    if ($img) {
        // 3. Salva comprimindo (Qualidade 75) direto na pasta uploads/
        imagejpeg($img, $destino, 75); 
        imagedestroy($img);
        
        // UPDATE NO BANCO
        $sql = "UPDATE usuarios SET foto = '$nome_final' WHERE id = '$usuario_id'";
        mysqli_query($conn, $sql);
        
        header("Location: perfil.php?sucesso=1");
        exit();
    } else {
        die("Erro ao processar a imagem. Verifique se o formato é válido!");
    }
}
?>