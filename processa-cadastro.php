<?php
include_once __DIR__ . '/conexao.php';

// ============================================================
// 🔒 VALIDAÇÃO DA CHAVE TURNSTILE (Vercel ou .env.php já carregado)
// ============================================================
$turnstile_secret_key = getenv('TURNSTILE_SECRET_KEY');

// Se não tiver chave, interrompe com erro seguro (sem expor detalhes)
if (empty($turnstile_secret_key)) {
    error_log('[TURNSTILE] Chave secreta não configurada.');
    http_response_code(500);
    die('Erro interno de configuração. Contate o administrador.');
}

// ============================================================
// 🛡️ TRAVA 1: HONEYPOT (campo invisível)
// ============================================================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['field_verification_backup']) &&
    !empty($_POST['field_verification_backup'])
) {
    // Bot preencheu o campo oculto → aborta silenciosamente
    error_log('[HONEYPOT] Tentativa de bot bloqueada.');
    http_response_code(403);
    die('Acesso negado.');
}

// ============================================================
// 🛡️ TRAVA 2: VALIDAÇÃO DO TURNSTILE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turnstile_token = $_POST['cf-turnstile-response'] ?? '';

    // Se token não veio, já barra (pode ser requisição direta)
    if (empty($turnstile_token)) {
        error_log('[TURNSTILE] Token não enviado.');
        header('Location: cad-usuario.php?erro=turnstile');
        exit();
    }

    // Verifica o token com a API da Cloudflare
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $turnstile_secret_key,
        'response' => $turnstile_token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

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

    //  Se passou, continua com o cadastro...
}

// ============================================================
// 🧹 LIMPEZA E VALIDAÇÃO DOS DADOS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email            = mysqli_real_escape_string($conn, $_POST['email']);
    $nome             = mysqli_real_escape_string($conn, $_POST['nome']);
    $atletica_id      = mysqli_real_escape_string($conn, $_POST['atletica_id']);
    $pref_cor_padrao  = mysqli_real_escape_string($conn, $_POST['pref_cor_padrao']);
    $pref_vibe_padrao = mysqli_real_escape_string($conn, $_POST['pref_vibe_padrao']);
    $senha            = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $token            = bin2hex(random_bytes(32));

    $aura_inicial     = $_POST['aura_inicial'] ?? 'masculino';
    if ($aura_inicial === 'feminino') {
        $foto_perfil_final = 'default_feminino.jpg';
        $foto_capa_final   = 'default_capa_feminino.webp';
    } else {
        $foto_perfil_final = 'default_masculino.jpg';
        $foto_capa_final   = 'default_capa_masculino.webp';
    }

    // Verifica se e-mail já existe
    $check_sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_res = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_res) > 0) {
        header("Location: cad-usuario.php?erro=ja_existe");
        exit();
    }

    // Insere no banco
    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao, foto, capa) 
            VALUES ('$nome', '$email', '$senha', '$token', 0, '$atletica_id', '$pref_cor_padrao', '$pref_vibe_padrao', '$foto_perfil_final', '$foto_capa_final')";

    if (mysqli_query($conn, $sql)) {
        $apiKey = RESEND_KEY;

        //  Verifica se a chave do Resend está configurada
        if (empty($apiKey)) {
            error_log('[RESEND] Chave API do Resend não configurada.');
            echo "Erro ao enviar e-mail de confirmação. Contate o administrador.";
            exit();
        }

        // Dispara e-mail
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
        curl_close($ch);

        if ($resend_http_code !== 200) {
            error_log('[RESEND] Falha ao enviar e-mail: ' . $resend_response);
            // Mesmo se o e-mail falhar, o cadastro foi feito. Redireciona com aviso?
            // Por enquanto, redireciona normalmente.
        }

        header("Location: sucesso.php?email=" . urlencode($email));
        exit();
    } else {
        error_log("[CADASTRO] Erro no MySQL: " . mysqli_error($conn));
        echo "Erro ao cadastrar. Tente novamente mais tarde.";
        exit();
    }
} else {
    // Acesso direto sem POST
    header("Location: cad-usuario.php");
    exit();
}
?>