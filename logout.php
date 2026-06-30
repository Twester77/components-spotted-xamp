<?php
// logout.php – Destrói a sessão PHP (Vercel)
ob_start();

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO logout.php (Vercel)');

// Define domínio do cookie para limpeza (mesma lógica do cookie)
$cookieDomain = $is_production ? '.fendauniversity.com.br' : null;

if (session_status() === PHP_SESSION_ACTIVE) {
    fenda_log('🔵 Sessão ativa. Destruindo...');

    // 🛡️ DESTRUIÇÃO DO COOKIE CUSTOMIZADO DA FENDA
    setcookie('fenda_state_token', '', [
        'expires' => time() - 86400,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => $is_production,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    fenda_log('🔵 Cookie fenda_state_token explicitamente invalidado');

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