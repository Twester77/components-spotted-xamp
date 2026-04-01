<?php
include 'conexao.php';
session_start();

// 1. SÓ EXECUTA SE VIER DO FORMULÁRIO (Isso mata o erro do print anterior!)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    
    // 2. Limpeza total (O trim que a gente lutou pra colocar!)
    $email_bruto = trim($_POST['email']);
    $email = mysqli_real_escape_string($conn, $email_bruto);
    $senha = $_POST['senha']; 

    // 3. Busca no Banco
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = mysqli_query($conn, $sql);

    if ($usuario = mysqli_fetch_assoc($resultado)) {
        
        // 4. Verifica a senha (Hash ou Texto Puro)
        // Se você ainda não usou password_hash, use: if ($senha == $usuario['senha'])
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_username'] = $usuario['username'];
            
            header("Location: feed.php");
            exit();
        } else {
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        // Se cair aqui, o e-mail digitado não existe no banco
        header("Location: index.php?erro=usuario");
        exit();
    }
} else {
    // Se tentarem acessar o arquivo direto pela URL, manda de volta pro login
    header("Location: index.php");
    exit();
}
?>