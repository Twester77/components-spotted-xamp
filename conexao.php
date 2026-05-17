<?php if (ob_get_level() == 0) ob_start();
/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV DESENVOLVEDOR: Leonardo (O Idealizador)
AGRADECIMENTOS: Meu Coordenador de ADS Fernando Menechelli, meu Professor de HTML Eric
e meu "Padrinho Digital" Gemini
pela paciência com os headers , divs sem fechar ( ou antes da hora) , if sem end e os includes fora de ordem
---------------------------------------------------------------------------------------------------------------*/

// 1. MELHORIA: Detecta se o acesso é local ou via rede (celular)
$remote_addr = $_SERVER['REMOTE_ADDR'];
// Detecta se estamos rodando no seu PC/Rede Local ou na Nuvem
$is_localhost = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1' || strpos($_SERVER['REMOTE_ADDR'], '192.168.') !== false);

if ($is_localhost) {
    $usuario = "root";
    $senha   = "";
    $banco   = "fenda_local";

    // 🚨 RESOLUÇÃO SUPREMA: Desliga a exibição de erros na tela temporariamente 
    // para o Windows não cuspir o Warning de rede enquanto testamos o Docker
    $display_errors_antigo = ini_get('display_errors');
    ini_set('display_errors', '0');

    try {
        // 🐳 PASSO A: Tenta o Docker de forma 100% silenciosa
        $conn = @mysqli_connect("db", $usuario, $senha, $banco, 3306);
    } catch (Exception $e) {
        $conn = false;
    } catch (mysqli_sql_exception $e) {
        $conn = false;
    }

    // Restaura a configuração original de erros do seu XAMPP para o resto do sistema
    ini_set('display_errors', $display_errors_antigo);

    // 🐘 PASSO B: Ativa o seu Ryzen físico no XAMPP se o Docker não respondeu
    if (!$conn) {
        try {
            $conn = @mysqli_connect("127.0.0.1", $usuario, $senha, $banco, 3307);
        } catch (Exception $e) {
            // Último recurso na porta padrão da máquina física
            $conn = @mysqli_connect("127.0.0.1", $usuario, $senha, $banco, 3306);
        }
    }
} else {

    // NA NUVEM (GIT): NUNCA coloque senhas aqui. Use variáveis de ambiente.
    $host    = getenv('DB_HOST');
    $usuario = getenv('DB_USER');
    $senha   = getenv('DB_PASS');
    $banco   = getenv('DB_NAME');
    $porta   = getenv('DB_PORT') ?: 3306;
    $conn = mysqli_connect($host, $usuario, $senha, $banco, $porta);
}

if (!$conn) {
    die("Erro fatal: Não foi possível conectar ao banco de dados.");
}

mysqli_set_charset($conn, "utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'])) {
    $id_logado = $_SESSION['usuario_id'];
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto)
    {
        $texto_seguro = htmlspecialchars($texto);
        $padrao = '/@([^\s]+)/';
        $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
        return preg_replace($padrao, $substituicao, $texto_seguro);
    }
}

if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8');
}