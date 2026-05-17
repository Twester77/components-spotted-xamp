<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Limpeza de dados para evitar SQL Injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $nome             = mysqli_real_escape_string($conn, $_POST['nome']);
    $atletica_id      = mysqli_real_escape_string($conn, $_POST['atletica_id']);
    $pref_cor_padrao  = mysqli_real_escape_string($conn, $_POST['pref_cor_padrao']);
    $pref_vibe_padrao = mysqli_real_escape_string($conn, $_POST['pref_vibe_padrao']);
    $senha            = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $token            = bin2hex(random_bytes(32));

    // 2. Verificação se o e-mail já existe
    $check_sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_res = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        header("Location: cad-usuario.php?erro=ja_existe");
        exit();
    }

    // 3. A  INTRODUÇÃO DOS DADOS NO BANCO E ATIVAÇÃO
    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao, foto, capa) 
        VALUES ('$nome', '$email', '$senha', '$token', 0, '$atletica_id', '$pref_cor_padrao', '$pref_vibe_padrao', 'default.jpg', 'default_capa.jpg')";

    if (mysqli_query($conn, $sql)) {
        // API KEY do Resend
        $apiKey = RESEND_KEY;

        // --- DISPARO DE E-MAIL COM BANNER DE BOAS VINDAS (CADADTRO) ---
$email_payload = [
    'from' => 'Spotted - A Fenda <hello@fendauniversity.com.br>',
    'to' => [$email],
    'reply_to' => 'contato-spotted.fev@outlook.com.br',
    'subject' => 'Sua jornada na Fenda começou!',
    'html' => "
<div style='font-family: sans-serif; background: #0a0a0a; color: #fff; padding: 0; border-radius: 15px; overflow: hidden; border: 1px solid #70cde4; max-width: 600px; margin: auto;'>
    
    <div style='width: 100%; background: #000; text-align: center;'>
        <img src='https://fendauniversity.com.br/imagensfoto/banner-email.png' alt='A Fenda' style='width: 100%; max-width: 600px; display: block;'>
    </div>

    <div style='padding: 30px;'>
        <h2 style='color: #70cde4; text-align: center; margin-top: 0;'>Seja bem-vindo, " . $nome . "!</h2>
        
        <p style='font-size: 16px; line-height: 1.6;'>Seu cadastro foi realizado com sucesso. Agora você tem acesso ao ecossistema mais exclusivo da UNIFEV.</p>
        
        <div style='background: rgba(112, 205, 228, 0.1); border-left: 4px solid #70cde4; padding: 15px; margin: 20px 0;'>
            <p style='margin: 0; font-style: italic;'>\"O que acontece na Fenda, fica na Fenda... ou não.\"</p>
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

        // Executa o envio sem travar a navegação do usuário
        curl_exec($ch);
        curl_close($ch);
        // --- FIM DO DISPARO ---

        // 7. Redirecionamento Final
        header("Location: sucesso.php?email=" . urlencode($email));
        exit();
    } else {
        echo "Erro ao cadastrar: " . mysqli_error($conn);
    }
}
