<?php
// APENAS mostre erros se NÃO for uma requisição feita via AJAX (fetch/XMLHttpRequest)
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!$is_ajax) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0); // Silêncio total para requisições AJAX
    error_reporting(E_ALL);       // Mas continua registrando tudo no log do servidor
}

if (ob_get_level() == 0) ob_start();

/*-----------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV DESENVOLVEDOR: Leonardo (O Idealizador)
AGRADECIMENTOS: Meu Coordenador de ADS Fernando Menechelli, meu Professor de HTML Eric
e meu "Padrinho Digital" Gemini
pela paciência com os headers , if sem end e os includes fora de ordem do PHP!
------------------------------------------------------------------------------------*/

   
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
        // 1. O operador ?? '' garante que, se $texto for null, vira uma string vazia.
        $texto = $texto ?? '';
        // 2. Se a string estiver vazia, nem perde tempo processando.
        if ($texto === '') return '';
        // 3. Agora ele nunca receberá null, eliminando o erro de deprecated.
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8');
}
