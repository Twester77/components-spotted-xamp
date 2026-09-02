<?php
// logout.php – Destrói a sessão PHP e encerra a sessão ativa na tabela (Vercel)
ob_start();

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO logout.php (Vercel)');

// ============================================================
// 1. OBTÉM O TOKEN DA SESSÃO ATUAL (para encerrar na tabela)
// ============================================================
$token_atual = null;
if (!empty($_COOKIE['fenda_state_token'])) {
    $decrypted = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    if ($decrypted) {
        $payload = json_decode($decrypted, true);
        if (isset($payload['token_sessao'])) {
            $token_atual = $payload['token_sessao'];
        }
    }
}

// ============================================================
// 2. ENCERRA A SESSÃO ATIVA NA TABELA (se encontrada)
// ============================================================
if ($token_atual && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $stmt = $conn->prepare("UPDATE sessoes_ativas SET ativo = 0 WHERE usuario_id = ? AND token = ?");
    $stmt->bind_param("is", $usuario_id, $token_atual);
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    $stmt->close();
    fenda_log("🟢 Sessão ativa encerrada na tabela para usuário $usuario_id (afetadas: $afetadas)");
} else {
    fenda_log("⚠️ Nenhum token de sessão encontrado para encerrar na tabela.");
}

// ============================================================
// 3. DEFINE DOMÍNIO DO COOKIE (mesma lógica do cookie)
// ============================================================
$current_host = $_SERVER['HTTP_HOST'] ?? '';
$is_real_production = ($is_production ?? false) || str_ends_with($current_host, 'fendauniversity.com.br');
$cookieDomain = $is_real_production ? '.fendauniversity.com.br' : null;

// ============================================================
// 4. DESTRÓI O COOKIE PERSONALIZADO DA FENDA
// ============================================================
if (isset($_COOKIE['fenda_state_token'])) {
    setcookie('fenda_state_token', '', [
        'expires' => time() - 86400,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => $is_real_production,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    fenda_log('🔵 Cookie fenda_state_token invalidado');
}

// ============================================================
// 5. DESTRÓI A SESSÃO PHP
// ============================================================
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
    fenda_log('🔵 Cookie de sessão removido');

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