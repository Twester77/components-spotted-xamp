<?php
// 🔧 CORREÇÃO FINAL: Logout com parâmetros exatos da sessão
ob_start();

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO logout.php');

if (session_status() === PHP_SESSION_ACTIVE) {
    fenda_log('🔵 Sessão ativa. Destruindo...');

    // Obtém os parâmetros exatos do cookie atual
    $params = session_get_cookie_params();

    // Remove o cookie com os mesmos parâmetros da sessão
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

    // Limpa as variáveis da sessão
    $_SESSION = array();

    // Destrói a sessão
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