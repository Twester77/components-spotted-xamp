<?php
include_once __DIR__ . '/conexao.php';

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Se desejar matar o cookie da sessão no navegador do usuário também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão de vez
session_destroy();

// Volta para a home limpo
header("Location: index.php");
exit();
?>