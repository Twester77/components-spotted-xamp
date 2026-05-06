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
        // AQUI vai entrar a configuração do RESEND que o Eric recomendou
        // Vou te mandar o código do Resend assim que você confirmar que as colunas
        // atletica_id e pref_cor_padrao existem na sua tabela 'usuarios'.

        header("Location: sucesso.php?email=" . $email);
        exit();
    } else {
        echo "Erro no banco: " . mysqli_error($conn);
    }
}
