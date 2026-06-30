<?php
// auth-bridge.php – Ponte entre Supabase Auth e Sessão PHP (Vercel)

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO auth-bridge.php (Vercel)');

$input = json_decode(file_get_contents('php://input'), true);
$supabase_token = $input['token'] ?? null;

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

$ch = curl_init($supabase_url . '/auth/v1/user');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $supabase_token,
    'apikey: ' . $supabase_anon_key
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

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

session_start();
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['usuario_username'] = $usuario['username'];
$_SESSION['usuario_email'] = $usuario['email'];

// 🛡️ GERAÇÃO DO TOKEN DE ESTADO PERSISTENTE VIA BRIDGE (30 DIAS) COM EXPIRAÇÃO INTERNA
$expires_in = time() + (86400 * 30);
$cookie_payload = json_encode([
    'id' => $usuario['id'],
    'nome' => $usuario['nome'],
    'username' => $usuario['username'],
    'email' => $usuario['email'],
    'exp' => $expires_in
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

fenda_log('🟢 Sessão e Token de persistência criados para usuário: ' . $usuario['id'] . ' (' . $usuario['email'] . ')');

echo json_encode(['success' => true, 'redirect' => 'feed.php']);
exit();
?>