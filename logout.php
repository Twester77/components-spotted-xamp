<?php
// 🔧 CORREÇÃO: garante que não haja saída antes do header
ob_start();

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO logout.php');

// 🔧 CORREÇÃO: verifica se a sessão está ativa antes de destruir
if (session_status() === PHP_SESSION_ACTIVE) {
    fenda_log('🔵 Sessão ativa. Destruindo...');
    
    // Limpa todas as variáveis da sessão
    $_SESSION = array();

    // Remove o cookie da sessão no navegador
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
        fenda_log('🔵 Cookie de sessão removido');
    }

    // Destrói a sessão
    session_destroy();
    fenda_log('🔵 Sessão destruída');
} else {
    fenda_log('🔵 Nenhuma sessão ativa para destruir');
}

// 🔧 CORREÇÃO: limpa o buffer antes do redirecionamento
ob_end_clean();

fenda_log('🔴 REDIRECIONANDO para index.php (logout)');
header("Location: index.php");
exit();
?>