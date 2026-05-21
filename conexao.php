<?php 
// FORÇA O PHP A MOSTRAR QUALQUER ERRO QUE ACONTECER
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (ob_get_level() == 0) ob_start();

/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV
---------------------------------------------------------------------------------------------------------------*/

$ip_acesso = $_SERVER['REMOTE_ADDR'] ?? '';
$is_localhost = ($ip_acesso == '127.0.0.1' || $ip_acesso == '::1' || strpos($ip_acesso, '192.168.') !== false);

if ($is_localhost) {
    // === AMBIENTE LOCAL (XAMPP) ===
    $usuario = "root";
    $senha   = ""; 
    $banco   = "fenda_local";
    $porta   = 3306; // Conforme confirmado no seu phpMyAdmin

    $conn = @mysqli_connect("127.0.0.1", $usuario, $senha, $banco, $porta);

    if (!$conn) {
        die("ERRO DE CONEXÃO LOCAL: " . mysqli_connect_error());
    }
} else {
    // === AMBIENTE DE PRODUÇÃO (RENDER/RAILWAY) ===
    $host    = getenv('DB_HOST');
    $usuario = getenv('DB_USER');
    $senha   = getenv('DB_PASS');
    $banco   = getenv('DB_NAME');
    $porta   = getenv('DB_PORT'); 
    
    $porta_final = !empty($porta) ? (int)$porta : 34092;
    
    $conn = mysqli_connect($host, $usuario, $senha, $banco, $porta_final);
    
    if (!$conn) {
        die("ERRO DE CONEXÃO PRODUÇÃO: " . mysqli_connect_error());
    }
}

// Configura o charset
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
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8');
}
?>