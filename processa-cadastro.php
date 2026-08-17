<?php
include_once __DIR__ . '/conexao.php';

// ============================================================
// 🔒 DETECÇÃO DE AMBIENTE LOCAL (DOMÍNIO + IP)
// ============================================================
$host = $_SERVER['HTTP_HOST'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$is_localhost = (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    strpos($host, '.test') !== false ||
    strpos($host, '.local') !== false ||
    strpos($ip, '192.168.') === 0 ||
    strpos($ip, '10.') === 0 ||
    strpos($ip, '172.16.') === 0 ||
    strpos($ip, '172.17.') === 0 ||
    strpos($ip, '172.18.') === 0 ||
    strpos($ip, '172.19.') === 0 ||
    strpos($ip, '172.20.') === 0 ||
    strpos($ip, '172.21.') === 0 ||
    strpos($ip, '172.22.') === 0 ||
    strpos($ip, '172.23.') === 0 ||
    strpos($ip, '172.24.') === 0 ||
    strpos($ip, '172.25.') === 0 ||
    strpos($ip, '172.26.') === 0 ||
    strpos($ip, '172.27.') === 0 ||
    strpos($ip, '172.28.') === 0 ||
    strpos($ip, '172.29.') === 0 ||
    strpos($ip, '172.30.') === 0 ||
    strpos($ip, '172.31.') === 0
);

// ============================================================
// 🔒 VALIDAÇÃO DA CHAVE TURNSTILE (APENAS SE NÃO FOR LOCAL)
// ============================================================
if (!$is_localhost) {
    $turnstile_secret_key = getenv('TURNSTILE_SECRET_KEY');
    if (empty($turnstile_secret_key)) {
        error_log('[TURNSTILE] Chave secreta não configurada em produção.');
        http_response_code(500);
        die('Erro interno de configuração. Contate o administrador.');
    }
} else {
    error_log('[TURNSTILE] 🔥 Modo local ativado – Turnstile ignorado.');
}

// ============================================================
// 🛡️ TRAVA 1: HONEYPOT (campo invisível)
// ============================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['field_verification_backup']) &&
    !empty($_POST['field_verification_backup'])
) {
    error_log('[HONEYPOT] Tentativa de bot bloqueada.');
    http_response_code(403);
    die('Acesso negado.');
}

// ============================================================
// 🛡️ TRAVA 2: VALIDAÇÃO DO TURNSTILE (SOMENTE SE NÃO FOR LOCAL)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
    error_log('[CADASTRO] Token recebido: ' . ($turnstile_token ? 'SIM (tamanho: ' . strlen($turnstile_token) . ')' : 'NÃO'));

    if (!$is_localhost) {
        if (empty($turnstile_token)) {
            error_log('[CADASTRO] Token vazio.');
            header('Location: cad-usuario.php?erro=turnstile');
            exit();
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $turnstile_secret_key,
            'response' => $turnstile_token,
            'remoteip' => $ip
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido – PHP 8.5+ gerencia automaticamente

        if ($httpCode !== 200) {
            error_log("[TURNSTILE] Falha na API (HTTP $httpCode)");
            header('Location: cad-usuario.php?erro=turnstile');
            exit();
        }

        $result = json_decode($response, true);
        if (!$result || $result['success'] !== true) {
            error_log('[TURNSTILE] Token inválido: ' . ($result['error-codes'][0] ?? 'unknown'));
            header('Location: cad-usuario.php?erro=turnstile');
            exit();
        }
    } else {
        error_log('[TURNSTILE] 🔥 MODO LOCAL – Turnstile ignorado.');
    }
}

// ============================================================
// 🧹 LIMPEZA E VALIDAÇÃO DOS DADOS (COM PREPARED STATEMENTS)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 🔥 Captura os dados sem escape (o bind_param fará a segurança)
    $nome             = $_POST['nome'] ?? '';
    $username         = $_POST['username'] ?? '';
    $email            = $_POST['email'] ?? '';
    $senha_raw        = $_POST['senha'] ?? '';
    $atletica_id      = $_POST['atletica_id'] ?? '';
    $pref_cor_padrao  = $_POST['pref_cor_padrao'] ?? '#70cde4';
    $pref_vibe_padrao = $_POST['pref_vibe_padrao'] ?? 'vibe-glass';

    // 🔥 Validação básica do username
    if (empty($username) || !preg_match('/^[a-z0-9_\.]{3,18}$/', $username)) {
        header('Location: cad-usuario.php?erro=username_invalido');
        exit();
    }

    $senha = password_hash($senha_raw, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    $ativo = 0; // Conta inativa até ativação via e-mail

    $aura_inicial = $_POST['aura_inicial'] ?? 'masculino';
    if ($aura_inicial === 'feminino') {
        $foto_perfil_final = 'default_feminino.jpg';
        $foto_capa_final   = 'default_capa_feminino.webp';
    } else {
        $foto_perfil_final = 'default_masculino.jpg';
        $foto_capa_final   = 'default_capa_masculino.webp';
    }

    // 🔥 Verifica se e-mail já existe
    $check_sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    if (!$stmt_check) {
        error_log('[CADASTRO] Erro ao preparar SELECT: ' . mysqli_error($conn));
        die('Erro interno do servidor.');
    }
    mysqli_stmt_bind_param($stmt_check, "s", $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        header("Location: cad-usuario.php?erro=ja_existe");
        exit();
    }
    mysqli_stmt_close($stmt_check);

    // 🔥 Verifica se username já existe
    $check_sql_username = "SELECT id FROM usuarios WHERE username = ?";
    $stmt_check_username = mysqli_prepare($conn, $check_sql_username);
    if (!$stmt_check_username) {
        error_log('[CADASTRO] Erro ao preparar SELECT username: ' . mysqli_error($conn));
        die('Erro interno do servidor.');
    }
    mysqli_stmt_bind_param($stmt_check_username, "s", $username);
    mysqli_stmt_execute($stmt_check_username);
    mysqli_stmt_store_result($stmt_check_username);
    if (mysqli_stmt_num_rows($stmt_check_username) > 0) {
        mysqli_stmt_close($stmt_check_username);
        header("Location: cad-usuario.php?erro=username_duplicado");
        exit();
    }
    mysqli_stmt_close($stmt_check_username);

    // 🔥 Insere no banco (PREPARED STATEMENT) – 11 colunas
    $sql = "INSERT INTO usuarios (nome, username, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao, foto, capa) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql);
    if (!$stmt_insert) {
        error_log('[CADASTRO] Erro ao preparar INSERT: ' . mysqli_error($conn));
        die('Erro interno do servidor.');
    }

    // 🔥 BIND: 11 variáveis na ordem correta
    mysqli_stmt_bind_param(
        $stmt_insert,
        "sssssssssss",
        $nome,
        $username,
        $email,
        $senha,
        $token,
        $ativo,
        $atletica_id,
        $pref_cor_padrao,
        $pref_vibe_padrao,
        $foto_perfil_final,
        $foto_capa_final
    );

    if (mysqli_stmt_execute($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
        $apiKey = RESEND_KEY;

        if (empty($apiKey)) {
            error_log('[RESEND] Chave API do Resend não configurada.');
            echo "Erro ao enviar e-mail de confirmação. Contate o administrador.";
            exit();
        }

        // Dispara e-mail (mantido igual)
        $email_payload = [
            'from' => 'Spotted - A Fenda <hello@fendauniversity.com.br>',
            'to' => [$email],
            'reply_to' => 'contato-spotted.fev@outlook.com.br',
            'subject' => 'Sua jornada na Fenda começou!',
            'html' => " 
<div style='font-family: sans-serif; background: #0a0a0a; color: #fff; padding: 0; border-radius: 15px; overflow: hidden; border: 1px solid #70cde4; max-width: 500px; margin: 20px auto;'>
    <div style='width: 100%; background: #000; text-align: center;'>
        <img src='https://fendauniversity.com.br/imagensfoto/banner-email.png' alt='Banner do A Fenda' style='width: 100%; max-width: 500px; display: block; justify-content: center; margin: auto; '>
    </div>
    <div style='padding: 30px;'>
        <h2 style='color: #70cde4; text-align: center; margin-top: 0;'>Seja bem-vindo, " . $nome . "!</h2>
        <p style='font-size: 16px; line-height: 1.6;'>Seu cadastro foi realizado com sucesso. Agora você tem acesso ao ecossistema mais exclusivo da UNIFEV.</p>
        <div style='background: rgba(112, 205, 228, 0.1); border-left: 4px solid #70cde4; padding: 15px; margin: 20px 0;'>
            <p style='margin: 0; font-style: italic;'>\"O que acontece na Fenda, fica na Fenda \"</p>
        </div>
        <div style='text-align: center; margin-top: 35px; margin-bottom: 20px;'>
            <a href='https://fendauniversity.com.br/verificar.php?token=" . $token . "' 
               style='background: #70cde4; color: #000; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 18px; box-shadow: 0 0 20px rgba(112, 205, 228, 0.4); display: inline-block;'>
               ACESSAR MINHA AURA
            </a>
        </div>
        <p style='font-size: 12px; color: #555; text-align: center; margin-top: 40px;'>
            Este é um e-mail automático. Para suporte, acesse o site.<br>
            &copy; 2026 Fenda University - Spotted UNIFEV
        </p>
    </div>
</div>
"
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resend_response = curl_exec($ch);
        $resend_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 🔥 curl_close removido – PHP 8.5+ gerencia automaticamente

        if ($resend_http_code !== 200) {
            error_log('[RESEND] Falha ao enviar e-mail: ' . $resend_response);
        }

        header("Location: sucesso.php?email=" . urlencode($email));
        exit();
    } else {
        error_log("[CADASTRO] Erro no INSERT: " . mysqli_stmt_error($stmt_insert));
        mysqli_stmt_close($stmt_insert);
        echo "Erro ao cadastrar. Tente novamente mais tarde.";
        exit();
    }
} else {
    header("Location: cad-usuario.php");
    exit();
}
?>