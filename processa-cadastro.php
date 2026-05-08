<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // ... (Validações de e-mail e checagem de existência continuam iguais)

    $nome             = mysqli_real_escape_string($conn, $_POST['nome']);
    $atletica_id      = mysqli_real_escape_string($conn, $_POST['atletica_id']);
    $pref_cor_padrao  = mysqli_real_escape_string($conn, $_POST['pref_cor_padrao']);
    $pref_vibe_padrao = mysqli_real_escape_string($conn, $_POST['pref_vibe_padrao']);
    $senha            = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $token            = bin2hex(random_bytes(32));

    // MUDANÇA: 'ativo' agora é 1 por padrão para a feira
    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao, foto, capa) 
            VALUES ('$nome', '$email', '$senha', '$token', 1, '$atletica_id', '$pref_cor_padrao', '$pref_vibe_padrao', 'default.jpg', 'default_capa.jpg')";

    if (mysqli_query($conn, $sql)) {
        $apiKey = 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8';

        // AJUSTE: URL oficial do Render aqui!

        $url_base = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
            ? "http://localhost/spotted-unifev"
            : "https://components-spotted-xamp.onrender.com";

        $email_payload = [
            'from' => 'Fenda <onboarding@resend.dev>',
            'to' => [$email],
            'subject' => 'Sua conta na Fenda está pronta!',
            'html' => "<h1>Bem-vindo, $nome!</h1><p>Sua conta já está ativa. Acesse agora: <a href='{$url_base}'>Entrar na Fenda</a></p>"
        ];

        // ... (Restante do envio via CURL continua igual)

        header("Location: sucesso.php?email=" . urlencode($email));
        exit();
    }
}
