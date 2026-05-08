<?php
// Impede que qualquer erro apareça antes do header (limpa o buffer)
if (ob_get_level() == 0) ob_start();

$host    = getenv('DB_HOST') ?: "localhost";
$usuario = getenv('DB_USER') ?: "root";    
$senha   = getenv('DB_PASS') ?: "";         
$banco   = getenv('DB_NAME') ?: "spotted_db";
$porta   = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $usuario, $senha, $banco, $porta);

if (!$conn) {
    die("Erro de conexão: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// ÚNICO lugar onde a sessão deve iniciar com segurança
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (isset($_SESSION['usuario_id'])) {
    $id_logado = $_SESSION['usuario_id'];
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto_seguro = htmlspecialchars($texto);
        $padrao = '/@([^\s]+)/';
        $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
        return preg_replace($padrao, $substituicao, $texto_seguro);
    }
}
?>