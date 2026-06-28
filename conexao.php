<?php
// ============================================================
// 🔥 AJUSTADO PARA VERCEL – caminhos absolutos com ROOT_PATH
// ============================================================

// Define o caminho raiz do projeto (fora da pasta /api)
define('ROOT_PATH', dirname(__DIR__)); // Isso aponta para a raiz do projeto

if (ob_get_level() == 0) ob_start();
include_once __DIR__ . '/fenda_debug.php';
fenda_log('🔵 INÍCIO conexao.php (Vercel)');

// ============================================================
// 🔍 SONDA DIAGNÓSTICA COMPLETA (AUDITORIA DE VARIÁVEIS)
// ============================================================
fenda_log('DEBUG: [AUDITORIA] DB_HOST: ' . (getenv('DB_HOST') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_USER: ' . (getenv('DB_USER') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_PASS: ' . (getenv('DB_PASS') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_NAME: ' . (getenv('DB_NAME') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] DB_PORT: ' . (getenv('DB_PORT') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] ENVIRONMENT: ' . (getenv('ENVIRONMENT') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] SUPABASE_URL: ' . (getenv('SUPABASE_URL') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] SUPABASE_ANON_KEY: ' . (getenv('SUPABASE_ANON_KEY') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] RESEND_KEY: ' . (getenv('RESEND_KEY') ? 'SIM' : 'NÃO'));
fenda_log('DEBUG: [AUDITORIA] SESSION_COOKIE_DOMAIN: ' . (getenv('SESSION_COOKIE_DOMAIN') ? 'SIM' : 'NÃO'));
// ============================================================

/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV (Conexão robusta com variável de ambiente)
---------------------------------------------------------------------------------------------------------------*/

// Determina o ambiente de forma explícita (padrão: produção)
$is_production = (getenv('ENVIRONMENT') === 'production');

// 🔧 CORREÇÃO: configuração de erro baseada no ambiente
if ($is_production) {
    // Em produção: não exibe erros na tela, mas ainda registra no log
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Em desenvolvimento: exibe todos os erros
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

if ($is_production) {
    // === MODO PRODUÇÃO (VERCEL + TiDB Cloud) ===
    $host    = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
    $usuario = getenv('DB_USER') ?: '4QGTrzXrgzivy34.root';
    $senha   = getenv('DB_PASS') ?: '1HftPjHsoQb1pEmi';
    $banco   = getenv('DB_NAME') ?: 'fenda_db';
    $porta   = (int)(getenv('DB_PORT') ?: 4000);
    $certPath = ROOT_PATH . '/config/isrgrootx1.pem';
    $ssl_flag = file_exists($certPath) ? MYSQLI_CLIENT_SSL : 0;

    // 🔥 REMOVIDO: Cookie de sessão com domínio fixo
    $cookieDomain = null;
} else {
    // === MODO LOCAL (XAMPP) ===
    $host    = '127.0.0.1';
    $porta   = 3307;
    $usuario = 'root';
    $senha   = '';
    $banco   = 'fenda_local';
    $ssl_flag = 0;
    $certPath = null;
    $cookieDomain = null;
}

// ============================================================
// 🔌 INICIALIZAÇÃO DA CONEXÃO COM SSL FORÇADO
// ============================================================
$conn = mysqli_init();
if (!$conn) {
    $erro = 'Falha ao inicializar o MySQLi';
    error_log('[CONEXAO] ERRO: ' . $erro);
    fenda_log('🔴 ERRO: ' . $erro);
    die("Erro interno do servidor.");
}

// ============================================================
// 🔐 CONFIGURAÇÃO SSL (apenas produção)
// ============================================================
if ($is_production) {
    // Se o certificado existe, usa ele
    if (file_exists($certPath)) {
        mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        fenda_log('🟢 SSL: Certificado encontrado em ' . $certPath);
    } else {
        // Fallback: tenta conexão SSL sem certificado (apenas criptografia)
        fenda_log('🟡 SSL: Certificado não encontrado, tentando conexão SSL com verificação reduzida');
        // Força SSL mesmo sem certificado (alguns drivers aceitam)
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        // Configura SSL com parâmetros vazios (usa o padrão do sistema)
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    }
}

// ============================================================
// 🔥 TENTATIVA DE CONEXÃO COM TRATAMENTO DE ERRO
// ============================================================
try {
    // 🔥 Força o uso de SSL na conexão (para TiDB)
    $flags = ($is_production) ? MYSQLI_CLIENT_SSL : 0;
    
    $conectou = mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, $flags);
    
    if (!$conectou) {
        $erro = mysqli_connect_error();
        $erro_num = mysqli_connect_errno();
        error_log("[CONEXAO] Falha ao conectar (código $erro_num): $erro");
        fenda_log("🔴 ERRO DE CONEXÃO (código $erro_num): $erro");
        die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
    }
    
    fenda_log('🟢 CONEXÃO COM BANCO ESTABELECIDA COM SUCESSO');
    
} catch (Exception $e) {
    error_log('[CONEXAO] EXCEÇÃO: ' . $e->getMessage());
    fenda_log('🔴 EXCEÇÃO: ' . $e->getMessage());
    die("Erro interno do servidor. Tente novamente mais tarde.");
}

mysqli_set_charset($conn, "utf8mb4");

// ============================================================
// 🍪 GERENCIAMENTO DE SESSÃO
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    if ($is_production) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
}

// Atualiza última atividade do usuário (se logado)
if (!empty($_SESSION['usuario_id'])) {
    $id_logado = mysqli_real_escape_string($conn, $_SESSION['usuario_id']);
    mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
}

// ============================================================
// ⚙️ MOTOR DE MENÇÕES
// ============================================================
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto = $texto ?? '';
        if ($texto === '') return '';
        $texto_seguro = htmlspecialchars($texto);
        return preg_replace('/@([^\s]+)/', '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>', $texto_seguro);
    }
}

// ============================================================
// 🔑 CHAVE RESEND (via variável de ambiente)
// ============================================================
if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', getenv('RESEND_KEY') ?: '');
}
?>