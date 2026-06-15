<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();

/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV (Otimizado para Local/Produção)
---------------------------------------------------------------------------------------------------------------*/

// 1. Detectar se estamos em Localhost
$is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

if ($is_local) {
    // === CONFIGURAÇÃO LOCAL (XAMPP) ===
    $host    = '127.0.0.1';
    $usuario = 'root';
    $senha   = '';
    $banco   = 'fenda_local'; // Verifique se este é o nome do seu banco local
    $porta   = 3307;
    $ssl_flag = 0; // Local não usa SSL
} else {
    // === CONFIGURAÇÃO PRODUÇÃO (RENDER + TIDB CLOUD) ===
    $db_host_env = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
    $db_user_env = getenv('DB_USER') ?: '4QGTrzXrgzivy34.root';
    $db_pass_env = getenv('DB_PASS') ?: '1HftPjHsoQb1pEmi';
    $db_name_env = getenv('DB_NAME') ?: 'fenda_db';
    $db_port_env = (int)(getenv('DB_PORT') ?: 4000);
    $certPath    = __DIR__ . '/config/isrgrootx1.pem';

    $host    = $db_host_env;
    $usuario = $db_user_env;
    $senha   = $db_pass_env;
    $banco   = $db_name_env;
    $porta   = $db_port_env;
    $ssl_flag = 0;

    // Configuração SSL (Apenas para produção)
    if (file_exists($certPath)) {
        $ssl_flag = MYSQLI_CLIENT_SSL;
    }
}

// --- INICIALIZAÇÃO DA CONEXÃO ---
$conn = mysqli_init();
if (!$conn) {
    die("Falha ao inicializar o MySQLi");
}

if ($ssl_flag === MYSQLI_CLIENT_SSL) {
    mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
}

if (!mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, $ssl_flag)) {
    error_log("ERRO FATAL DE CONEXÃO: " . mysqli_connect_error());
    die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
}

mysqli_set_charset($conn, "utf8mb4");

// 2. Correção da Sessão para parar os warnings
if (session_status() === PHP_SESSION_NONE) {
    // Define o cookie para ser válido no domínio (resolve problemas de login/warnings)
    if (!$is_local) {
        ini_set('session.cookie_domain', $_SERVER['HTTP_HOST']);
    }
    session_start();
}

// Atualiza a última atividade (com verificação de existência para evitar warning)
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