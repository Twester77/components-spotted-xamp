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

    // 3. MUDANÇA ESTRATÉGICA: 'ativo' = 1 (Auto-ativação para a feira)
    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao, foto, capa) 
            VALUES ('$nome', '$email', '$senha', '$token', 1, '$atletica_id', '$pref_cor_padrao', '$pref_vibe_padrao', 'default.jpg', 'default_capa.jpg')";

    if (mysqli_query($conn, $sql)) {
        // API KEY do Resend
        $apiKey = 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8';

        // 4. Lógica de URL Universal (Funciona no XAMPP e no Render)
        $url_base = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
            ? "http://localhost/spotted-unifev"
            : "https://components-spotted-xamp.onrender.com";

        // 5. Payload do E-mail (Agora apenas como boas-vindas)
        $email_payload = [
            'from' => 'Fenda <onboarding@resend.dev>',
            'to' => [$email],
            'subject' => 'Sua conta na Fenda está pronta!',
            'html' => "
                <div style='font-family: sans-serif; background-color: #050a0f; color: #fff; padding: 20px;'>
                    <h1>Bem-vindo à Fenda, $nome!</h1>
                    <p>Sua conta já foi ativada automaticamente para a feira de ADS.</p>
                    <p><a href='{$url_base}' style='color: #70cde4; font-weight: bold;'>Clique aqui para acessar o Feed!</a></p>
                </div>"
        ];

        // 6. Envio via cURL (Configurado para não travar o site se a internet falhar)
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Limite de 5 segundos para não travar o site se a rede cair

        curl_exec($ch);
        curl_close($ch);

        // 7. Redirecionamento Final
        header("Location: sucesso.php?email=" . urlencode($email));
        exit();
    } else {
        echo "Erro ao cadastrar: " . mysqli_error($conn);
    }
}