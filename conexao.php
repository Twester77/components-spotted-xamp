<?php
// APENAS mostre erros se NÃO for uma requisição feita via AJAX (fetch/XMLHttpRequest)
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

if (ob_get_level() == 0) ob_start();

/*-----------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV
DESENVOLVEDOR: Leonardo (O Idealizador)
------------------------------------------------------------------------------------*/

// ==================================================
// CONEXÃO COM BANCO DE DADOS (TiDB Cloud)
// ==================================================

// Tenta pegar variáveis de ambiente (produção no Render)
$db_host_env = getenv('DB_HOST');
$db_user_env = getenv('DB_USER');
$db_pass_env = getenv('DB_PASS');
$db_name_env = getenv('DB_NAME');
$db_port_env = getenv('DB_PORT') ?: 4000;
$ca_cert_env = getenv('DB_CA_CERT'); // caminho para o certificado no servidor

if ($db_host_env && $db_user_env && $db_pass_env) {
    // === AMBIENTE DE PRODUÇÃO (RENDER) ===
    $host    = $db_host_env;
    $usuario = $db_user_env;
    $senha   = $db_pass_env;
    $banco   = $db_name_env;
    $porta   = (int)$db_port_env;
    $certPath = $ca_cert_env ?: __DIR__ . '/config/isrgrootx1.pem';
} else {
    // === AMBIENTE LOCAL (XAMPP / WAMP) ===
    // Configure aqui com os dados do seu cluster TiDB Cloud
    $host    = 'gateway01.us-east-1.prod.aws.tidbcloud.com';
    $porta   = 4000;
    $usuario = '4QGTrzXrgzivy34.root';
    $senha   = '1HftPjHsoQb1pEmi';
    $banco   = 'sys'; // ATENÇÃO: o banco padrão é 'sys', mas você pode criar outro e mudar aqui
    $certPath = __DIR__ . '/config/isrgrootx1.pem'; // caminho local do certificado
}

// Conecta usando MySQLi com SSL
$conn = mysqli_init();
if (!$conn) {
    error_log("Falha ao inicializar a conexão MySQLi");
    die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
}

// Configura SSL (obrigatório para TiDB Cloud)
mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false); // evita problemas de hostname

// Tenta a conexão
if (!mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, MYSQLI_CLIENT_SSL)) {
    error_log("ERRO FATAL DE CONEXÃO: " . mysqli_connect_error());
    die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
}

// Configuração de charset
mysqli_set_charset($conn, "utf8mb4");

// Inicia sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Atualiza última atividade do usuário logado
if (isset($_SESSION['usuario_id'])) {
    $id_logado = mysqli_real_escape_string($conn, $_SESSION['usuario_id']);
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

/* ==================== FUNÇÃO DE FORMATAÇÃO DE MENÇÕES ==================== */
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto = $texto ?? '';
        if ($texto === '') return '';
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

// Chave da API Resend (se utilizada)
if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8');
}
?>