<?php
// ============================================================
// 🔒 CARREGAMENTO HÍBRIDO DO AMBIENTE (LOCAL + PRODUÇÃO)
// ============================================================
// PRIMEIRO: carrega o .env.php se existir (ambiente local)
if (file_exists(__DIR__ . '/.env.php')) {
    include_once __DIR__ . '/.env.php';
}

// ============================================================
// 🔥 AJUSTADO PARA VERCEL – caminhos absolutos com ROOT_PATH
// ============================================================
define('ROOT_PATH', dirname(__DIR__));

if (ob_get_level() == 0) ob_start();
include_once __DIR__ . '/fenda_debug.php';
fenda_log('🔵 INÍCIO conexao.php (Vercel/Local)');

// ============================================================
// 🔍 SONDA DIAGNÓSTICA COMPLETA (AUDITORIA DE VARIÁVEIS)
// ============================================================
fenda_log('DEBUG: [AUDITORIA] DB_HOST: ' . (getenv('DB_HOST') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_USER: ' . (getenv('DB_USER') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_PASS: ' . (getenv('DB_PASS') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_NAME: ' . (getenv('DB_NAME') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_PORT: ' . (getenv('DB_PORT') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] ENVIRONMENT: ' . (getenv('ENVIRONMENT') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] SUPABASE_URL: ' . (getenv('SUPABASE_URL') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] SUPABASE_ANON_KEY: ' . (getenv('SUPABASE_ANON_KEY') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] RESEND_KEY: ' . (getenv('RESEND_KEY') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] TURNSTILE_SECRET_KEY: ' . (getenv('TURNSTILE_SECRET_KEY') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] SESSION_COOKIE_DOMAIN: ' . (getenv('SESSION_COOKIE_DOMAIN') ? 'SIM' : 'NÃO'));

// ============================================================
// 🌍 DETERMINAÇÃO DO AMBIENTE
// ============================================================
$is_production = (getenv('ENVIRONMENT') === 'production');

if ($is_production) {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// ============================================================
// 🔌 EXTRAÇÃO SEGURA DAS CONFIGURAÇÕES DO BANCO
// ============================================================
if ($is_production) {
    // Em produção na Vercel, o getenv busca direto do painel deles
    $host         = getenv('DB_HOST');
    $usuario      = getenv('DB_USER');
    $senha        = getenv('DB_PASS');
    $banco        = getenv('DB_NAME');
    $porta        = (int)(getenv('DB_PORT') ?: 4000);
    
    //  CORREÇÃO: usa __DIR__ para apontar para a pasta atual (raiz do projeto)
    // O arquivo isrgrootx1.pem está em /config dentro da raiz
    $certPath     = __DIR__ . '/config/isrgrootx1.pem';
    
    //  FORÇA SSL: TiDB Cloud exige conexão segura
    $ssl_flag     = MYSQLI_CLIENT_SSL;
    $cookieDomain = '.fendauniversity.com.br';
} else {
    // Em ambiente local, puxa o que foi definido no .env.php
    $host         = getenv('DB_HOST') ?: '127.0.0.1';
    $porta        = (int)(getenv('DB_PORT') ?: 3307);
    $usuario      = getenv('DB_USER') ?: 'root';
    $senha        = getenv('DB_PASS') ?: '';
    $banco        = getenv('DB_NAME') ?: 'fenda_local';
    $ssl_flag     = 0;
    $certPath     = null;
    $cookieDomain = null;
}

// Bloqueio de segurança: Se as variáveis essenciais sumirem, o script para
if (empty($host) || empty($usuario)) {
    fenda_log('🔴 ERRO CRÍTICO: Variáveis de ambiente do banco de dados não foram encontradas.');
    die("Erro interno de configuração do servidor.");
}

// ============================================================
// 🔌 INICIALIZAÇÃO DA CONEXÃO
// ============================================================
$conn = mysqli_init();
if (!$conn) {
    die("Erro interno do servidor.");
}

if ($is_production) {
    if (file_exists($certPath)) {
        mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        fenda_log('🟢 SSL: Certificado encontrado em ' . $certPath);
    } else {
        fenda_log('⚠️ AVISO: Arquivo de certificado não encontrado em: ' . $certPath);
        // Fallback: tenta SSL sem verificação de certificado (menos seguro, mas evita falha total)
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    }
}

try {
    $conectou = mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, $ssl_flag);
    if (!$conectou) {
        error_log("[CONEXAO] Falha ao conectar: " . mysqli_connect_error());
        die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
    }
    fenda_log('🟢 CONEXÃO COM BANCO ESTABELECIDA COM SUCESSO');
} catch (Exception $e) {
    error_log('[CONEXAO] EXCEÇÃO: ' . $e->getMessage());
    die("Erro interno do servidor.");
}

mysqli_set_charset($conn, "utf8mb4");

// ============================================================
// 🔒 MOTOR DE SEGURANÇA E CRIPTOGRAFIA SIMÉTRICA (AES-256-CBC)
// ============================================================
if (!defined('FENDA_CRYPT_KEY')) {
    define('FENDA_CRYPT_KEY', hash('sha256', (getenv('SUPABASE_ANON_KEY') ?: 'Fenda_Fallback_Sec_Key_2026_!!!')));
}

if (!function_exists('fenda_encrypt_state')) {
    function fenda_encrypt_state($plain_text) {
        $iv_len = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_len);
        $encrypted = openssl_encrypt($plain_text, 'aes-256-cbc', FENDA_CRYPT_KEY, 0, $iv);
        return base64_encode($encrypted . '::' . base64_encode($iv));
    }
}

if (!function_exists('fenda_decrypt_state')) {
    function fenda_decrypt_state($encrypted_bundle) {
        $data = base64_decode($encrypted_bundle, true);
        if (!$data || !str_contains($data, '::')) return false;
        list($encrypted_text, $iv_encoded) = explode('::', $data, 2);
        $iv = base64_decode($iv_encoded, true);
        if (!$iv) return false;
        return openssl_decrypt($encrypted_text, 'aes-256-cbc', FENDA_CRYPT_KEY, 0, $iv);
    }
}

// ============================================================
// 🍪 GERENCIAMENTO E HIDRATAÇÃO DE SESSÃO
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    if ($is_production) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
}

// 🔋 MÁGICA DO STATELESS: Recupera estado caso a instância Vercel tenha resetado
if (empty($_SESSION['usuario_id']) && !empty($_COOKIE['fenda_state_token'])) {
    $decrypted_payload = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    if ($decrypted_payload) {
        $user_data = json_decode($decrypted_payload, true);
        if (is_array($user_data) && !empty($user_data['id']) && !empty($user_data['exp'])) {
            if (time() < $user_data['exp']) {
                $_SESSION['usuario_id']       = $user_data['id'];
                $_SESSION['usuario_nome']     = $user_data['nome'];
                $_SESSION['usuario_username'] = $user_data['username'];
                if (!empty($user_data['email'])) {
                    $_SESSION['usuario_email'] = $user_data['email'];
                }
                fenda_log('🟢 [HYDRATION] Sessão recuperada via Token de Estado para ID: ' . $user_data['id']);
            } else {
                // Token expirado – força limpeza do cookie
                fenda_log('🔴 [HYDRATION] Token expirado para ID: ' . $user_data['id'] . '. Removendo cookie.');
                setcookie('fenda_state_token', '', [
                    'expires' => time() - 86400,
                    'path' => '/',
                    'domain' => $cookieDomain,
                    'secure' => $is_production,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                unset($_COOKIE['fenda_state_token']);
            }
        }
    }
}

// Atualiza última atividade do usuário (se logado)
if (!empty($_SESSION['usuario_id'])) {
    $id_logado = mysqli_real_escape_string($conn, $_SESSION['usuario_id']);
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

// ============================================================
// ⚙️ MOTOR DE MENÇÕES
// ============================================================
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto = $texto ?? '';
        if ($texto === '') return '';
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

// ============================================================
// 🔑 DEFINIÇÃO DE CONSTANTES (via getenv)
// ============================================================
if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', getenv('RESEND_KEY') ?: '');
}

if (!defined('TURNSTILE_SECRET_KEY')) {
    define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY') ?: '');
}
?>