<?php
include 'conexao.php';
session_start();

// 1. Checa: Se já estiver logado, tchau!
if(isset($_SESSION['usuario_id'])) {
    header("Location: feed.php");
    exit();
}

// 2. Processa o envio do formulário: 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha']; 

    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = mysqli_query($conn, $sql);

    // ESSA LINHA ABAIXO É ESSENCIAL: Transforma o resultado do banco em dados reais
    if ($usuario = mysqli_fetch_assoc($resultado)) {
        
        // 3. Verifica a senha e já sabe qual foto carregar
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_foto'] = $usuario['foto']; 
            $_SESSION['usuario_username'] = $usuario['username'];
            
            header("Location: feed.php");
            exit();
        } else {
            // Senha errada
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        // E-mail não encontrado
        header("Location: index.php?erro=usuario");
        exit();
    }
} else {
    // Acesso direto pela URL
    header("Location: index.php");
    exit();
}
?>