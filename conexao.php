<?php
//  CONFIGURAÇÃO GLOBAL DE TEMPO (FUSO HORÁRIO BRASIL)
date_default_timezone_set('America/Sao_Paulo');

// ============================================================
//  CARREGAMENTO HÍBRIDO DO AMBIENTE – SÓ EM LOCAL!
// ============================================================
// Em produção (Vercel), as variáveis vêm do painel.
// Em local, o .env.php é carregado.
if (!getenv('ENVIRONMENT') || getenv('ENVIRONMENT') !== 'production') {
    if (file_exists(__DIR__ . '/.env.php')) {
        include_once __DIR__ . '/.env.php';
    }
}

// ============================================================
//  AJUSTADO PARA VERCEL – caminhos absolutos com ROOT_PATH
// ============================================================
define('ROOT_PATH', dirname(__DIR__));

if (ob_get_level() == 0) ob_start();
include_once __DIR__ . '/fenda_debug.php';
fenda_log('🔵 [CONEXAO] INÍCIO conexao.php (Vercel/Local)');

// 🔥 Polyfill para str_ends_with (caso PHP < 8.0)
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') return true;
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

// ============================================================
// 🌍 DETERMINAÇÃO DO AMBIENTE (MAIS ROBUSTA)
// ============================================================
$env_raw = getenv('ENVIRONMENT');
$env = trim($env_raw ?: '');
fenda_log('🔵 [CONEXAO] ENVIRONMENT (raw): "' . $env_raw . '" | (trim): "' . $env . '"');

// 🔥 CORREÇÃO DJÊ: domínio dinâmico para previews Vercel
$current_host = $_SERVER['HTTP_HOST'] ?? '';
$is_real_production = ($env === 'production') || str_ends_with($current_host, 'fendauniversity.com.br');
fenda_log('🔵 [CONEXAO] is_real_production = ' . ($is_real_production ? 'true' : 'false') . ' | host: ' . $current_host);

if ($is_real_production) {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// ============================================================
// 🔌 EXTRAÇÃO SEGURA DAS CONFIGURAÇÕES DO BANCO
// ============================================================
if ($is_real_production) {
    // Em produção na Vercel, o getenv busca direto do painel deles
    $host         = getenv('DB_HOST');
    $usuario      = getenv('DB_USER');
    $senha        = getenv('DB_PASS');
    $banco        = getenv('DB_NAME');
    $porta        = (int)(getenv('DB_PORT') ?: 4000);
    $certPath     = __DIR__ . '/config/isrgrootx1.pem';
    $ssl_flag     = MYSQLI_CLIENT_SSL;
    $cookieDomain = $is_real_production ? '.fendauniversity.com.br' : null;
    
    fenda_log('🔵 [CONEXAO] Modo PRODUÇÃO: host=' . $host . ', banco=' . $banco . ', porta=' . $porta);
} else {
    // Ambiente local
    $host         = getenv('DB_HOST') ?: '127.0.0.1';
    $porta        = (int)(getenv('DB_PORT') ?: 3307);
    $usuario      = getenv('DB_USER') ?: 'root';
    $senha        = getenv('DB_PASS') ?: '';
    $banco        = getenv('DB_NAME') ?: 'fenda_local';
    $ssl_flag     = 0;
    $certPath     = null;
    $cookieDomain = null;
    fenda_log('🔵 [CONEXAO] Modo LOCAL: host=' . $host . ', banco=' . $banco . ', porta=' . $porta);
}

// Bloqueio de segurança: Se as variáveis essenciais sumirem, o script para
if (empty($host) || empty($usuario)) {
    fenda_log('🔴 [CONEXAO] ERRO CRÍTICO: Variáveis de ambiente do banco de dados não foram encontradas.');
    die("Erro interno de configuração do servidor.");
}

// ============================================================
// 🔌 INICIALIZAÇÃO DA CONEXÃO
// ============================================================
fenda_log('🔵 [CONEXAO] Antes de mysqli_init()');
$conn = mysqli_init();
if (!$conn) {
    fenda_log('🔴 [CONEXAO] mysqli_init() falhou');
    die("Erro interno do servidor.");
}
fenda_log('🔵 [CONEXAO] mysqli_init() OK');

if ($is_real_production) {
    if (file_exists($certPath)) {
        mysqli_ssl_set($conn, NULL, NULL, $certPath, NULL, NULL);
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        fenda_log('🟢 [CONEXAO] Certificado encontrado em ' . $certPath);
    } else {
        fenda_log('⚠️ [CONEXAO] AVISO: Certificado não encontrado em ' . $certPath . ' – usando fallback SSL');
        mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    }
}

try {
    fenda_log('🔵 [CONEXAO] Conectando: host=' . $host . ', usuario=' . $usuario . ', banco=' . $banco . ', porta=' . $porta . ', ssl_flag=' . $ssl_flag);
    $conectou = mysqli_real_connect($conn, $host, $usuario, $senha, $banco, $porta, NULL, $ssl_flag);
    fenda_log('🔵 [CONEXAO] mysqli_real_connect = ' . ($conectou ? 'true' : 'false'));
    if (!$conectou) {
        $erro = mysqli_connect_error();
        fenda_log('🔴 [CONEXAO] Falha ao conectar: ' . $erro);
        error_log("[CONEXAO] Falha ao conectar: " . $erro);
        die("Estamos em manutenção técnica rápida. Volte em alguns instantes!");
    }
    fenda_log('🟢 [CONEXAO] CONEXÃO ESTABELECIDA COM SUCESSO');
} catch (Exception $e) {
    fenda_log('🔴 [CONEXAO] EXCEÇÃO: ' . $e->getMessage());
    error_log('[CONEXAO] EXCEÇÃO: ' . $e->getMessage());
    die("Erro interno do servidor.");
}

mysqli_set_charset($conn, "utf8mb4");

// ============================================================
// 🔒 MOTOR DE SEGURANÇA E CRIPTOGRAFIA SIMÉTRICA (AES-256-CBC)
// ============================================================
if (!defined('FENDA_CRYPT_KEY')) {
    define('FENDA_CRYPT_KEY', hash('sha256', (getenv('SUPABASE_ANON_KEY') ?: 'Fenda_Fallback_Sec_Key_2026_!!!')));
}

if (!function_exists('fenda_encrypt_state')) {
    function fenda_encrypt_state($plain_text) {
        $iv_len = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_len);
        $encrypted = openssl_encrypt($plain_text, 'aes-256-cbc', FENDA_CRYPT_KEY, 0, $iv);
        return base64_encode($encrypted . '::' . base64_encode($iv));
    }
}

if (!function_exists('fenda_decrypt_state')) {
    function fenda_decrypt_state($encrypted_bundle) {
        $data = base64_decode($encrypted_bundle, true);
        if (!$data || !str_contains($data, '::')) return false;
        list($encrypted_text, $iv_encoded) = explode('::', $data, 2);
        $iv = base64_decode($iv_encoded, true);
        if (!$iv) return false;
        return openssl_decrypt($encrypted_text, 'aes-256-cbc', FENDA_CRYPT_KEY, 0, $iv);
    }
}

// ============================================================
// ⏰ FUNÇÃO UNIVERSAL PARA EXIBIR DATAS NO FUSO BRASILEIRO
// ============================================================
if (!function_exists('exibirDataHoraBrasil')) {
    function exibirDataHoraBrasil($dataOriginal, $formato = 'd/m/Y H:i') {
        if (empty($dataOriginal)) {
            return '';
        }
        try {
            $dt = new DateTime($dataOriginal, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            return $dt->format($formato);
        } catch (Exception $e) {
            error_log("[EXIBIR_DATA] Erro ao converter data '$dataOriginal': " . $e->getMessage());
            return $dataOriginal;
        }
    }
}
fenda_log('🔵 [CONEXAO] exibirDataHoraBrasil() definida');

// ============================================================
// 🍪 GERENCIAMENTO E HIDRATAÇÃO DE SESSÃO
// ============================================================
fenda_log('🔵 [CONEXAO] Antes de session_status');

ini_set('session.gc_maxlifetime', 86400);

if ($is_real_production) {
    ini_set('session.cookie_domain', '.fendauniversity.com.br');
    fenda_log('🔵 [CONEXAO] session.cookie_domain definido como .fendauniversity.com.br');
}

if (session_status() === PHP_SESSION_NONE) {
    fenda_log('🔵 [CONEXAO] Iniciando sessão');
    if ($is_real_production) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
    fenda_log('🔵 [CONEXAO] Sessão iniciada');
} else {
    fenda_log('🔵 [CONEXAO] Sessão já estava ativa');
    if ($is_real_production) {
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => $params['lifetime'] ? time() + $params['lifetime'] : 0,
            'path' => $params['path'],
            'domain' => '.fendauniversity.com.br',
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
        fenda_log('🔵 [CONEXAO] Cookie de sessão redefinido com domínio .fendauniversity.com.br');
    }
}

// ============================================================
// 🔥 VALIDAÇÃO DO TOKEN EM TODAS AS REQUISIÇÕES
// ============================================================
// Esta verificação garante que, mesmo que a sessão PHP já esteja ativa,
// o token ainda seja validado no banco. Se o token estiver inativo,
// a sessão é destruída e o usuário é redirecionado para o login.
if (!empty($_SESSION['usuario_id']) && !empty($_COOKIE['fenda_state_token'])) {
    $decrypted_payload = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    if ($decrypted_payload) {
        $user_data = json_decode($decrypted_payload, true);
        if (is_array($user_data) && !empty($user_data['id'])) {
            $token_sessao = $user_data['token_sessao'] ?? $user_data['token'] ?? null;
            if ($token_sessao) {
                $stmt_check = $conn->prepare("SELECT ativo FROM sessoes_ativas WHERE usuario_id = ? AND token = ?");
                $stmt_check->bind_param("is", $user_data['id'], $token_sessao);
                $stmt_check->execute();
                $res = $stmt_check->get_result();
                $row = $res->fetch_assoc();
                $stmt_check->close();
                
                if (!$row || $row['ativo'] != 1) {
                    fenda_log('🔴 [VALIDACAO] Token INATIVO para usuário ' . $user_data['id'] . '. Destruindo sessão.');
                    $_SESSION = [];
                    session_destroy();
                    setcookie('fenda_state_token', '', [
                        'expires' => time() - 86400,
                        'path' => '/',
                        'domain' => $cookieDomain,
                        'secure' => $is_real_production,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                    // Redireciona para o login com uma mensagem
                    header("Location: index.php?erro=sessao_expirada");
                    exit;
                }
            }
        }
    }
}

// ============================================================
// 🔥 HIDRATAÇÃO COM VALIDAÇÃO DE SESSÃO ATIVA
// ============================================================
if (empty($_SESSION['usuario_id']) && !empty($_COOKIE['fenda_state_token'])) {
    fenda_log('🔵 [HYDRATION] Cookie fenda_state_token encontrado. Tentando decriptar...');
    $decrypted_payload = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    
    if ($decrypted_payload) {
        $user_data = json_decode($decrypted_payload, true);
        
        // 🔥 FALLBACK: aceita tanto 'token_sessao' (padrão) quanto 'token' (legado)
        if (is_array($user_data) && !empty($user_data['id'])) {
            $token_sessao = $user_data['token_sessao'] ?? $user_data['token'] ?? null;
            
            if ($token_sessao) {
                fenda_log('🔵 [HYDRATION] Payload decriptado: user_id=' . $user_data['id'] . ', token_sessao=' . substr($token_sessao, 0, 16) . '...');
                
                // 🔥 VERIFICA SE O TOKEN DA SESSÃO AINDA ESTÁ ATIVO NA TABELA
                $stmt_check_token = $conn->prepare("
                    SELECT id, ativo 
                    FROM sessoes_ativas 
                    WHERE usuario_id = ? AND token = ? AND ativo = 1
                ");
                $stmt_check_token->bind_param("is", $user_data['id'], $token_sessao);
                $stmt_check_token->execute();
                $res_token = $stmt_check_token->get_result();
                $sessao_ativa = $res_token->fetch_assoc();
                $stmt_check_token->close();

                if (!$sessao_ativa) {
                    fenda_log('🔴 [HYDRATION] Token de sessão NÃO encontrado ou INATIVO. Removendo cookie.');
                    setcookie('fenda_state_token', '', [
                        'expires'  => time() - 86400,
                        'path'     => '/',
                        'domain'   => $cookieDomain,
                        'secure'   => $is_real_production,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                    unset($_COOKIE['fenda_state_token']);
                    // Remove qualquer sessão PHP residual
                    $_SESSION = [];
                    session_destroy();
                    fenda_log('🔴 [HYDRATION] Sessão destruída por token inativo.');
                    // Não prossegue — o usuário será redirecionado para o login pela falta de sessão
                } else {
                    // Token ativo, prossegue com a hidratação
                    fenda_log('🟢 [HYDRATION] Token de sessão ATIVO na tabela. Prosseguindo...');
                    
                    // Busca dados do usuário no banco
                    $stmt = $conn->prepare("
                        SELECT id, nome, username, email 
                        FROM usuarios 
                        WHERE id = ? AND ativo = 1 AND ultima_atividade >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ");
                    $stmt->bind_param("i", $user_data['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $usuario = $result->fetch_assoc();
                    $stmt->close();

                    if ($usuario) {
                        fenda_log('🟢 [HYDRATION] Usuário validado, restaurando sessão para ID: ' . $usuario['id']);
                        $_SESSION['usuario_id']       = $usuario['id'];
                        $_SESSION['usuario_nome']     = $usuario['nome'];
                        $_SESSION['usuario_username'] = $usuario['username'];
                        if (!empty($usuario['email'])) {
                            $_SESSION['usuario_email'] = $usuario['email'];
                        }

                        // 🔥 DJÊ: SÓ RENOVA O COOKIE SE FOR PERSISTENTE
                        if (isset($user_data['persistente']) && $user_data['persistente'] === true) {
                            fenda_log('🟢 [HYDRATION] Cookie persistente ativo. Renovando token rolante...');
                            $new_expires_in = time() + (86400 * 30);
                            $new_cookie_payload = json_encode([
                                'id'           => $usuario['id'],
                                'nome'         => $usuario['nome'],
                                'username'     => $usuario['username'],
                                'email'        => $usuario['email'] ?? '',
                                'persistente'  => true,
                                'token_sessao' => $token_sessao,
                                'exp'          => $new_expires_in
                            ]);
                            $new_encrypted_payload = fenda_encrypt_state($new_cookie_payload);

                            setcookie('fenda_state_token', $new_encrypted_payload, [
                                'expires'  => $new_expires_in,
                                'path'     => '/',
                                'domain'   => $cookieDomain,
                                'secure'   => $is_real_production,
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);

                            fenda_log('🟢 [HYDRATION] Cookie rolante renovado para ID: ' . $usuario['id']);
                        } else {
                            fenda_log('🟡 [HYDRATION] Cookie NÃO persistente. Mantendo sem renovação.');
                        }
                        
                        // 🔥 ATUALIZA A ÚLTIMA ATIVIDADE DA SESSÃO NA TABELA
                        $stmt_update_sessao = $conn->prepare("
                            UPDATE sessoes_ativas 
                            SET ultima_atividade = NOW() 
                            WHERE token = ? AND usuario_id = ?
                        ");
                        $stmt_update_sessao->bind_param("si", $token_sessao, $usuario['id']);
                        $stmt_update_sessao->execute();
                        $stmt_update_sessao->close();
                        fenda_log('🟢 [HYDRATION] ultima_atividade atualizada para sessão ' . substr($token_sessao, 0, 16) . '...');
                        
                    } else {
                        fenda_log('🔴 [HYDRATION] Usuário não encontrado ou inativo. Removendo cookie.');
                        setcookie('fenda_state_token', '', [
                            'expires'  => time() - 86400,
                            'path'     => '/',
                            'domain'   => $cookieDomain,
                            'secure'   => $is_real_production,
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                        unset($_COOKIE['fenda_state_token']);
                    }
                }
            } else {
                fenda_log('🔴 [HYDRATION] Token não encontrado no payload (nem token_sessao nem token)');
            }
        } else {
            fenda_log('🔴 [HYDRATION] Payload inválido (faltando id)');
        }
    } else {
        fenda_log('🔴 [HYDRATION] Falha ao decriptar cookie');
    }
} else {
    if (!empty($_SESSION['usuario_id'])) {
        fenda_log('🔵 [CONEXAO] Sessão já contém usuario_id: ' . $_SESSION['usuario_id']);
    } else {
        fenda_log('🔵 [CONEXAO] Nenhum cookie fenda_state_token encontrado');
    }
}

// ============================================================
// 🔥 DJÊ: THROTTLE PARA ATUALIZAÇÃO DE ultima_atividade (5 minutos)
// ============================================================
if (!empty($_SESSION['usuario_id'])) {
    $id_logado = mysqli_real_escape_string($conn, $_SESSION['usuario_id']);
    
    $stmt_last = $conn->prepare("SELECT ultima_atividade FROM usuarios WHERE id = ?");
    $stmt_last->bind_param("i", $id_logado);
    $stmt_last->execute();
    $res_last = $stmt_last->get_result();
    $row_last = $res_last->fetch_assoc();
    $stmt_last->close();
    
    if ($row_last) {
        $ultima = strtotime($row_last['ultima_atividade']);
        $agora = time();
        $diferenca = $agora - $ultima;
        
        if ($diferenca > 300) {
            mysqli_query($conn, "UPDATE usuarios SET ultima_atividade = NOW() WHERE id = '$id_logado'");
            fenda_log('🔵 [CONEXAO] Atualizada ultima_atividade para usuário ' . $_SESSION['usuario_id'] . ' (throttle de 5min)');
        } else {
            fenda_log('🔵 [CONEXAO] ultima_atividade ainda recente ('. $diferenca . 's), pulando update.');
        }
    }
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
fenda_log('🔵 [CONEXAO] formatarMencoes definida');

// ============================================================
// 🔑 DEFINIÇÃO DE CONSTANTES (via getenv)
// ============================================================
if (!defined('RESEND_KEY')) {
    define('RESEND_KEY', getenv('RESEND_KEY') ?: '');
}

if (!defined('TURNSTILE_SECRET_KEY')) {
    define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY') ?: '');
}

fenda_log('🟢 [CONEXAO] FIM conexao.php executado com sucesso');