<?php
// Reportar erros (mantido como solicitado)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();

/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV (MODO PROFISSIONAL - TIDB CLOUD)
---------------------------------------------------------------------------------------------------------------*/

// Variáveis de ambiente (Render/Produção)
$db_host_env = getenv('DB_HOST');
$db_user_env = getenv('DB_USER');
$db_pass_env = getenv('DB_PASS');
$db_name_env = getenv('DB_NAME');
$db_port_env = getenv('DB_PORT') ?: 4000;
$db_ca_env   = getenv('DB_CA_CERT'); // Caminho opcional

// Caminho padrão do certificado (Pasta 'config' na raiz)
$certPath = __DIR__ . '/config/isrgrootx1.pem';

// Definição dos valores (Se não achar variável de ambiente, usa estes padrões locais)
$host    = $db_host_env ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
$usuario = $db_user_env ?: '4QGTrzXrgzivy34.root';
$senha   = $db_pass_env ?: '1HftPjHsoQb1pEmi';     
$banco   = $db_name_env ?: 'fenda_db';
$porta   = (int)($db_port_env ?: 4000);

// --- INICIALIZAÇÃO DA CONEXÃO ---
$conn = mysqli_init();
if (!$conn) {
    error_log("Falha ao inicializar o MySQLi");
    die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
}

// Configuração SSL (Obrigatória para TiDB Cloud)
if (file_exists($certPath)) {
    mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    $ssl_flag = MYSQLI_CLIENT_SSL;
} else {
    // Loga o erro, mas tenta conectar (se o TiDB permitir sem SSL, mas geralmente não permite)
    error_log("AVISO: Certificado SSL não encontrado em: $certPath");
    $ssl_flag = 0; 
}

// Conexão efetiva
if (!mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, $ssl_flag)) {
    error_log("ERRO FATAL DE CONEXÃO: " . mysqli_connect_error());
    die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
}

// Configurações Globais
mysqli_set_charset($conn, "utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Atualiza a última atividade
if (isset($_SESSION['usuario_id'])) {
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