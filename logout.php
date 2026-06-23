<?php
// 🔧 CORREÇÃO: garante que não haja saída antes do header
ob_start();

include_once __DIR__ . '/conexao.php';

// 🔧 CORREÇÃO: verifica se a sessão está ativa antes de destruir
if (session_status() === PHP_SESSION_ACTIVE) {
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
    }

    // Destrói a sessão
    session_destroy();
}

// 🔧 CORREÇÃO: limpa o buffer antes do redirecionamento
ob_end_clean();

// Volta para a home limpo
header("Location: index.php");
exit();
?>