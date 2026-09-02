<?php
// auth-bridge.php – Ponte entre Supabase Auth e Sessão PHP (Vercel)
// 🔧 v1.3 – Adicionado "Manter-me conectado" + registro em sessoes_ativas
// 🐚 Íris – 2026-08-28

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO auth-bridge.php (Vercel)');

// 🔥 Polyfill para str_ends_with (caso PHP < 8.0)
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') return true;
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
$supabase_token = $input['token'] ?? null;
$manter = isset($input['manter_conectado']) && $input['manter_conectado'] === true;

if (!$supabase_token) {
    fenda_log('🔴 Token não fornecido');
    http_response_code(400);
    echo json_encode(['error' => 'Token não fornecido']);
    exit();
}

$supabase_url = getenv('SUPABASE_URL');
$supabase_anon_key = getenv('SUPABASE_ANON_KEY');

if (empty($supabase_url) || empty($supabase_anon_key)) {
    fenda_log('🔴 SUPABASE_URL ou SUPABASE_ANON_KEY não configuradas');
    http_response_code(500);
    echo json_encode(['error' => 'Configuração do servidor incompleta']);
    exit();
}

// ============================================================
// 1. VALIDA O TOKEN NO SUPABASE
// ============================================================
$ch = curl_init($supabase_url . '/auth/v1/user');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $supabase_token,
    'apikey: ' . $supabase_anon_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// 🔥 curl_close removido – PHP 8.5+ gerencia automaticamente

if ($http_code !== 200) {
    fenda_log('🔴 Token inválido ou expirado (HTTP ' . $http_code . ')');
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido']);
    exit();
}

$user_data = json_decode($response, true);
$user_email = $user_data['email'] ?? null;

if (!$user_email) {
    fenda_log('🔴 E-mail não encontrado no token');
    http_response_code(400);
    echo json_encode(['error' => 'E-mail não encontrado']);
    exit();
}

// ============================================================
// 2. BUSCA O USUÁRIO NO BANCO
// ============================================================
$sql = "SELECT id, nome, username, email FROM usuarios WHERE email = ? AND ativo = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    fenda_log('🔴 Usuário não encontrado ou inativo: ' . $user_email);
    http_response_code(404);
    echo json_encode(['error' => 'Usuário não encontrado ou inativo']);
    exit();
}

// ============================================================
// 3. CRIA SESSÃO PHP
// ============================================================
session_start();
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['usuario_username'] = $usuario['username'];
$_SESSION['usuario_email'] = $usuario['email'];

// ============================================================
// 4. GERA TOKEN ÚNICO PARA A SESSÃO ATIVA
// ============================================================
$session_token = bin2hex(random_bytes(32)); // 64 caracteres hex

// ============================================================
// 5. REGISTRA A SESSÃO NA TABELA `sessoes_ativas`
// ============================================================
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$stmt_insert = $conn->prepare("
    INSERT INTO sessoes_ativas (usuario_id, token, user_agent, ip, ativo, data_criacao, ultima_atividade)
    VALUES (?, ?, ?, ?, 1, NOW(), NOW())
");
$stmt_insert->bind_param("isss", $usuario['id'], $session_token, $user_agent, $ip);
$stmt_insert->execute();
$stmt_insert->close();

fenda_log('🟢 Sessão ativa registrada para usuário ' . $usuario['id'] . ' com token ' . substr($session_token, 0, 16) . '...');

// ============================================================
// 6. CRIA COOKIE PERSISTENTE COM O TOKEN NO PAYLOAD
// ============================================================
$expires_in = $manter ? time() + (86400 * 30) : 0;

$cookie_payload = json_encode([
    'id' => $usuario['id'],
    'nome' => $usuario['nome'],
    'username' => $usuario['username'],
    'email' => $usuario['email'],
    'persistente' => $manter,
    'token_sessao' => $session_token, // 🔥 chave para validar na tabela
    'exp' => $expires_in
]);

$encrypted_payload = fenda_encrypt_state($cookie_payload);

// 🔥 Definição de domínio dinâmico (DJÊ: compatível com previews Vercel)
$current_host = $_SERVER['HTTP_HOST'] ?? '';
$is_real_production = ($is_production ?? false) || str_ends_with($current_host, 'fendauniversity.com.br');
$cookieDomain = $is_real_production ? '.fendauniversity.com.br' : null;

setcookie('fenda_state_token', $encrypted_payload, [
    'expires' => $expires_in ?: 0,
    'path' => '/',
    'domain' => $cookieDomain,
    'secure' => $is_real_production,
    'httponly' => true,
    'samesite' => 'Lax'
]);

fenda_log('🟢 Sessão e Token de persistência criados para usuário: ' . $usuario['id'] . 
          ' (' . $usuario['email'] . ') persistente=' . ($manter ? 'sim' : 'não') .
          ' token_sessao=' . substr($session_token, 0, 16) . '...');

// ============================================================
// 7. RESPOSTA
// ============================================================
echo json_encode(['success' => true, 'redirect' => 'feed.php']);
exit();