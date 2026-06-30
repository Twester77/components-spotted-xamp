<?php
include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO confirma-login.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    
    fenda_log('🔵 POST recebido para login: ' . $_POST['email']);
    
    $email_bruto = trim($_POST['email']);
    $email = mysqli_real_escape_string($conn, $email_bruto);
    $senha = $_POST['senha']; 

    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $resultado = mysqli_query($conn, $sql);

    if ($usuario = mysqli_fetch_assoc($resultado)) {
        fenda_log('🔵 Usuário encontrado: ID ' . $usuario['id']);
        
        if ($usuario['ativo'] == 0) {
            fenda_log('🔴 REDIRECIONANDO para index.php?erro=pendente (conta inativa)');
            header("Location: index.php?erro=pendente");
            exit();
        }
        
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_username'] = $usuario['username'];
            
            // 🛡️ GERAÇÃO DO TOKEN DE ESTADO PERSISTENTE (30 DIAS) COM EXPIRAÇÃO INTERNA
            $expires_in = time() + (86400 * 30); // 30 dias a partir de agora
            $cookie_payload = json_encode([
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'username' => $usuario['username'],
                'exp' => $expires_in // 🔥 Campo de expiração para validação no servidor
            ]);
            
            $encrypted_payload = fenda_encrypt_state($cookie_payload);
            
            // Define o domínio do cookie baseado no ambiente
            $cookieDomain = $is_production ? '.fendauniversity.com.br' : null;
            
            setcookie('fenda_state_token', $encrypted_payload, [
                'expires' => $expires_in,
                'path' => '/',
                'domain' => $cookieDomain,
                'secure' => $is_production,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            fenda_log('🔵 Token de estado persistente injetado via Cookie para ID: ' . $usuario['id'] . ' (expira em 30 dias)');
            
            fenda_log('🔴 REDIRECIONANDO para feed.php (login bem-sucedido)');
            header("Location: feed.php");
            exit();
        } else {
            fenda_log('🔴 REDIRECIONANDO para index.php?erro=senha (senha incorreta)');
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        fenda_log('🔴 REDIRECIONANDO para index.php?erro=usuario (e-mail não encontrado)');
        header("Location: index.php?erro=usuario");
        exit();
    }
} else {
    fenda_log('🔴 REDIRECIONANDO para index.php (acesso direto)');
    header("Location: index.php");
    exit();
}
?>