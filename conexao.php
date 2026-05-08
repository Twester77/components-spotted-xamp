<?php
// 1. LIMPEZA DE BUFFER (Evita erros de "headers already sent" no Render)
if (ob_get_level() == 0) ob_start();

// 2. CONFIGURAÇÃO DE SESSÃO LONGA (Mantenha-me Conectado por 30 dias)
$tempo_sessao = 30 * 24 * 60 * 60; // 30 dias em segundos
ini_set('session.gc_maxlifetime', $tempo_sessao);
session_set_cookie_params([
    'lifetime' => $tempo_sessao,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Mudar para true se usar HTTPS (O Render usa!)
    'httponly' => true,
    'samesite' => 'Lax'
]);

// 3. INÍCIO SEGURO DA SESSÃO
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 4. PARÂMETROS DE CONEXÃO (Render/Railway vs Localhost)
$host    = getenv('DB_HOST') ?: "localhost";
$usuario = getenv('DB_USER') ?: "root";    
$senha   = getenv('DB_PASS') ?: "";         
$banco   = getenv('DB_NAME') ?: "spotted_db";
$porta   = getenv('DB_PORT') ?: 3306;

// 5. TENTATIVA DE CONEXÃO
$conn = mysqli_connect($host, $usuario, $senha, $banco, $porta);

if (!$conn) {
    // Se falhar no Render, mostra um erro mais limpo para você debugar
    die("A Fenda está temporariamente inacessível. Erro: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// 6. ATUALIZAÇÃO DE ATIVIDADE
if (isset($_SESSION['usuario_id'])) {
    $id_logado = $_SESSION['usuario_id'];
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

// 7. FUNÇÃO GLOBAL DE MENÇÕES (ÚNICA)
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto_seguro = htmlspecialchars($texto);
        $padrao = '/@([^\s]+)/';
        $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
        return preg_replace($padrao, $substituicao, $texto_seguro);
    }
}
?>