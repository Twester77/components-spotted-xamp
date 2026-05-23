<?php
// Reportar erros apenas em desenvolvimento, mas manter ligado para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();

/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV (CORRIGIDO)
---------------------------------------------------------------------------------------------------------------*/

// Tenta pegar o host do banco via variável de ambiente (Railway)
$db_host_env = getenv('DB_HOST');

if ($db_host_env) {
    // === AMBIENTE DE PRODUÇÃO (RAILWAY) ===
    $host    = $db_host_env;
    $usuario = getenv('DB_USER');
    $senha   = getenv('DB_PASS');
    $banco   = getenv('DB_NAME');
    $porta   = getenv('DB_PORT') ?: 3306; 
    
    $conn = @mysqli_connect($host, $usuario, $senha, $banco, (int)$porta);

} else {
    // === AMBIENTE LOCAL (LOCALHOST/XAMPP) ===
    $conn = @mysqli_connect("127.0.0.1", "root", "", "fenda_local", 3307);
}

// Verificação de segurança (Sem morrer com mensagem técnica no front)
if (!$conn) {
    // Loga o erro no servidor, mas dá uma resposta amigável pro usuário
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

/* MOTOR DE MENÇÕES (O que estava faltando, espertinho!) */
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8');
}
?>