<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Validação universal de e-mail (Aceita qualquer provedor)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: cad-usuario.php?erro=email_invalido");
        exit();
    }

    $check_sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_res = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        header("Location: cad-usuario.php?erro=ja_existe");
        exit();
    }

    $nome             = mysqli_real_escape_string($conn, $_POST['nome']);
    $atletica_id      = mysqli_real_escape_string($conn, $_POST['atletica_id']);
    $pref_cor_padrao  = mysqli_real_escape_string($conn, $_POST['pref_cor_padrao']);
    $pref_vibe_padrao = mysqli_real_escape_string($conn, $_POST['pref_vibe_padrao']);
    $senha            = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $token            = bin2hex(random_bytes(32));

    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao, foto, capa) 
        VALUES ('$nome', '$email', '$senha', '$token', 0, '$atletica_id', '$pref_cor_padrao', '$pref_vibe_padrao', 'default.jpg', 'default_capa.jpg')";

    if (mysqli_query($conn, $sql)) {

        $apiKey = 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8';

        // --- CONFIGURAÇÃO DA URL (AJUSTE PARA O DOMÍNIO DO RENDER) ---
        $url_base = "http://localhost/spotted-unifev"; /*MUDAR URGENTEMENTE*/

        $email_payload = [
            'from' => 'Fenda <onboarding@resend.dev>',
            'to' => [$email],
            'subject' => 'Confirme seu acesso à Fenda!',
            'html' => "
                <div style='font-family: Arial, sans-serif; background-color: #050a0f; color: #ffffff; padding: 40px; text-align: center;'>
                    <div style='max-width: 500px; margin: 0 auto; background: #0b141d; padding: 30px; border-radius: 20px; border: 1px solid #70cde4;'>
                        <h1 style='color: #70cde4; margin-bottom: 10px;'>Bem-vindo à Fenda!</h1>
                        <p style='font-size: 16px; line-height: 1.6; color: #cccccc;'>
                            Olá, <strong>$nome</strong>! Ficamos felizes em ter você conosco. <br>
                            Para começar a interagir com a comunidade da UNIFEV, clique no botão abaixo para ativar sua conta:
                        </p>
                        
                        <div style='margin: 35px 0;'>
                            <a href='{$url_base}/verificar.php?token={$token}' 
                               style='background-color: #70cde4; color: #000000; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 18px; display: inline-block; box-shadow: 0 4px 15px rgba(112, 205, 228, 0.3);'>
                               ATIVAR MINHA CONTA
                            </a>
                        </div>
                        
                        <p style='font-size: 13px; color: #666666; margin-top: 30px;'>
                            Se você não realizou este cadastro durante o evento, pode ignorar este e-mail com segurança.
                        </p>
                    </div>
                    <p style='font-size: 12px; color: #444444; margin-top: 20px;'>
                        Projeto desenvolvido para a Feira de ADS - UNIFEV
                    </p>
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

        curl_exec($ch);
        curl_close($ch);

        header("Location: sucesso.php?email=" . urlencode($email));
        exit();
    } else {
        echo "Erro: " . mysqli_error($conn);
    }
}
