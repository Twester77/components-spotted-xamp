<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();

/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV (Conexão robusta com variável de ambiente e cookie dinâmico)
---------------------------------------------------------------------------------------------------------------*/

// Determina o ambiente de forma explícita (padrão: produção)
$is_production = (getenv('ENVIRONMENT') === 'production');

if ($is_production) {
    // === MODO PRODUÇÃO (RENDER + TiDB Cloud) ===
    $host    = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
    $usuario = getenv('DB_USER') ?: '4QGTrzXrgzivy34.root';
    $senha   = getenv('DB_PASS') ?: '1HftPjHsoQb1pEmi';
    $banco   = getenv('DB_NAME') ?: 'fenda_db';
    $porta   = (int)(getenv('DB_PORT') ?: 4000);
    $certPath = __DIR__ . '/config/isrgrootx1.pem';
    $ssl_flag = file_exists($certPath) ? MYSQLI_CLIENT_SSL : 0;
    
    // Cookie de sessão: dinâmico via variável de ambiente ou fallback
    $cookieDomain = getenv('SESSION_COOKIE_DOMAIN');
    if (empty($cookieDomain)) {
        $cookieDomain = '.fendauniversity.com.br'; // fallback seguro
    }
} else {
    // === MODO LOCAL (XAMPP) ===
    $host    = '127.0.0.1';
    $porta   = 3307;          // Ajuste para a porta do seu MySQL local
    $usuario = 'root';
    $senha   = '';
    $banco   = 'fenda_local'; // Nome do banco local
    $ssl_flag = 0;
    $certPath = null;
    $cookieDomain = null; // localhost não precisa de domínio explícito
}

// --- INICIALIZAÇÃO DA CONEXÃO ---
$conn = mysqli_init();
if (!$conn) {
    die("Falha ao inicializar o MySQLi");
}

// Configura SSL (apenas se for produção e o certificado existir)
if ($ssl_flag === MYSQLI_CLIENT_SSL) {
    mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
}

// Conexão efetiva
if (!mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, $ssl_flag)) {
    $ambiente = $is_production ? "PRODUÇÃO" : "LOCAL";
    error_log("ERRO FATAL DE CONEXÃO ($ambiente): " . mysqli_connect_error());
    die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
}

mysqli_set_charset($conn, "utf8mb4");

// Gerenciamento de sessão (centralizado)
if (session_status() === PHP_SESSION_NONE) {
    // Em produção, define o domínio do cookie para compartilhar entre www e sem www
    if ($is_production && $cookieDomain) {
        ini_set('session.cookie_domain', $cookieDomain);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
    }
    session_start();
}

// Atualiza última atividade do usuário (se logado)
if (!empty($_SESSION['usuario_id'])) {
    $id_logado = mysqli_real_escape_string($conn, $_SESSION['usuario_id']);
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

/* MOTOR DE MENÇÕES */
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto = $texto ?? '';
        if ($texto === '') return '';
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8');
}
?>