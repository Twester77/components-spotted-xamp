<?php
// logout.php – Destrói a sessão PHP (Vercel)
ob_start();

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO logout.php (Vercel)');

if (session_status() === PHP_SESSION_ACTIVE) {
    fenda_log('🔵 Sessão ativa. Destruindo...');

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );

    fenda_log('🔵 Cookie de sessão removido com parâmetros: path=' . $params['path'] . ', domain=' . $params['domain'] . ', secure=' . ($params['secure'] ? 'true' : 'false'));

    $_SESSION = array();
    session_destroy();
    fenda_log('🔵 Sessão destruída');
} else {
    fenda_log('🔵 Nenhuma sessão ativa para destruir');
}

ob_end_clean();

fenda_log('🔴 REDIRECIONANDO para index.php (logout)');
header("Location: index.php");
exit();
?>