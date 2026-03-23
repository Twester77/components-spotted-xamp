<?php
include 'conexao.php';
session_start();

// Checa: Se já estiver logado, tchau!
if(isset($_SESSION['usuario_id'])) {
    header("Location: feed.php");
    exit();
}

// Processa : 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Agora batendo com os 'names' do formulário que você copiou!
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha']; 

    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = mysqli_query($conn, $sql);

    if ($usuario = mysqli_fetch_assoc($resultado)) {
        // Verifica a senha criptografada
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header("Location: feed.php");
            exit();
        } else {
            // Se errar a senha, volta pro index com um alerta
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        // Se não achar o e-mail, volta pro index com outro alerta
        header("Location: index.php?erro=usuario");
        exit();
    }
} else {
    // Se tentarem acessar o logar.php direto pela URL, manda pro index
    header("Location: index.php");
    exit();
}
?>