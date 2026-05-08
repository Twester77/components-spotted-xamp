<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    // 1. Validação do domínio
    if (!str_ends_with($email, '@unifev.edu.br')) {
        header("Location: cad-usuario.php?erro=dominio");
        exit();
    }

    // 2. Checar se já existe
    $check_sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_res = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        header("Location: cad-usuario.php?erro=ja_existe");
        exit();
    }

    /// 3. Dados e Senha + Novos campos de Aura e Atlética
    $nome             = mysqli_real_escape_string($conn, $_POST['nome']);
    $atletica_id      = mysqli_real_escape_string($conn, $_POST['atletica_id']);
    $pref_cor_padrao  = mysqli_real_escape_string($conn, $_POST['pref_cor_padrao']);
    $pref_vibe_padrao = mysqli_real_escape_string($conn, $_POST['pref_vibe_padrao']);
    $senha            = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $token            = bin2hex(random_bytes(16));

    // 4. Inserção Atualizada (Incluindo as novas colunas)
    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo, atletica_id, pref_cor_padrao, pref_vibe_padrao) 
        VALUES ('$nome', '$email', '$senha', '$token', 0, '$atletica_id', '$pref_cor_padrao', '$pref_vibe_padrao')";

    if (mysqli_query($conn, $sql)) {
        // --- DISPARO DE E-MAIL DE BOAS-VINDAS (CADASTRO) ---
        // Use a chave que você me passou: re_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8

        $apiKey = 're_gu3A9uZq_GeK1mRzZC6pkaq6rUHAaBLA8';
/*MUDAR URGENTEMENTE O LOCAL HOST HREF*/
        $email_payload = [ 
            'from' => 'Fenda <onboarding@resend.dev>', // No futuro, troque pelo seu domínio
            'to' => [$email], // Variável $email que vem do seu formulário
            'subject' => 'Sua jornada na Fenda começou!',
            'html' => "
        <div style='font-family: sans-serif; background: #0a0a0a; color: #fff; padding: 30px; border-radius: 15px; border: 1px solid #70cde4;'>
            <h2 style='color: #70cde4; text-align: center;'>Bem-vindo à Fenda!</h2>
            <p>Fala, <strong>$nome</strong>!</p>
            <p>Seu cadastro foi realizado com sucesso. Agora você faz parte do ecossistema mais secreto da UNIFEV.</p>
            <p>Prepare sua aura, ajuste sua vibe e comece a fofocar agora mesmo.</p>
            <div style='text-align: center; margin-top: 30px;'>
                <a href='http://localhost/spotted-unifev/index.php'  
                   style='background: #70cde4; color: #000; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; box-shadow: 0 0 15px #70cde4;'>
                   ENTRAR NA FENDA
                </a>
            </div>
            <p style='margin-top: 40px; font-size: 0.8rem; color: #555; text-align: center;'>
                Se você não solicitou este cadastro, ignore este e-mail.
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

        // Executa o envio "silencioso" (não trava o usuário se o e-mail demorar)
        curl_exec($ch);
        curl_close($ch);
        // --- FIM DO DISPARO ---

        header("Location: sucesso.php?email=" . $email);
        exit();
    } else {
        echo "Erro no banco: " . mysqli_error($conn);
    }
}
