<?php
include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO confirma-login.php');

// 1. SÓ EXECUTA SE VIER DO FORMULÁRIO 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    
    fenda_log('🔵 POST recebido para login: ' . $_POST['email']);
    
    // 2. Limpeza total (Trim e Escape para evitar SQL Injection)
    $email_bruto = trim($_POST['email']);
    $email = mysqli_real_escape_string($conn, $email_bruto);
    $senha = $_POST['senha']; 

    // 3. Busca no Banco considerando apenas usuários ativos
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = mysqli_query($conn, $sql);

    if ($usuario = mysqli_fetch_assoc($resultado)) {
        fenda_log('🔵 Usuário encontrado: ID ' . $usuario['id']);
        
        // --- TRAVA DE ATIVAÇÃO ---
        // Se a conta não estiver ativa (ativo = 0), barra o login
        if ($usuario['ativo'] == 0) {
            fenda_log('🔴 REDIRECIONANDO para index.php?erro=pendente (conta inativa)');
            header("Location: index.php?erro=pendente");
            exit();
        }
        
        // 4. Verifica a senha via Hash
        if (password_verify($senha, $usuario['senha'])) {
            // Define as sessões para uso no feed e perfil
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_username'] = $usuario['username'];
            
            fenda_log('🔴 REDIRECIONANDO para feed.php (login bem-sucedido)');
            header("Location: feed.php");
            exit();
        } else {
            fenda_log('🔴 REDIRECIONANDO para index.php?erro=senha (senha incorreta)');
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        // E-mail não encontrado no banco
        fenda_log('🔴 REDIRECIONANDO para index.php?erro=usuario (e-mail não encontrado)');
        header("Location: index.php?erro=usuario");
        exit();
    }
} else {
    // Acesso direto via URL
    fenda_log('🔴 REDIRECIONANDO para index.php (acesso direto)');
    header("Location: index.php");
    exit();
}
?>