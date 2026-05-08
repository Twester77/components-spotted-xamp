<?php
/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV DESENVOLVEDOR: Leonardo (O Idealizador)
AGRADECIMENTOS: Meu Coordenador de ADS Fernando Menechelli, meu Professor de HTML Eric
e meu "Padrinhos Digitais" Gemini  e Deep Seek
---------------------------------------------------------------------------------------------------------------*/

if (ob_get_level() == 0) {
    ob_start();
}

// --- CONFIGURAÇÃO INTELIGENTE (LOCAL vs RENDER) ---
// Tenta pegar as variáveis de ambiente (Render), se não existirem, usa o padrão local
$host    = getenv('DB_HOST') ?: "localhost";
$usuario = getenv('DB_USER') ?: "root";    
$senha   = getenv('DB_PASS') ?: "";         
$banco   = getenv('DB_NAME') ?: "spotted_db";
$porta   = getenv('DB_PORT') ?: (strpos($host, 'localhost') !== false ? 3307 : 3306);

// Conecta ao banco de dados
$conn = mysqli_connect($host, $usuario, $senha, $banco, $porta);

// Backup para porta 3306 se estiver no localhost e a 3307 falhar
if (!$conn && strpos($host, 'localhost') !== false) {
    $conn = mysqli_connect($host, $usuario, $senha, $banco, 3306);
}

if (!$conn) {
    die("Erro de conexão: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// --- LOGICA DE PRESENÇA (ONLINE/OFFLINE) ---
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

if (isset($_SESSION['usuario_id'])) {
    $id_logado = $_SESSION['usuario_id'];
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

// FUNÇÃO GLOBAL PARA MENÇÕES (@)
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto_seguro = htmlspecialchars($texto);
        $padrao = '/@([^\s]+)/';
        $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
        return preg_replace($padrao, $substituicao, $texto_seguro);
    }
}
?>