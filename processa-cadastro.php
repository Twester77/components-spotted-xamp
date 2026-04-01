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

    // 3. Dados e Senha
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16)); 

    // 4. Inserção (Apenas UMA vez!)
    $sql = "INSERT INTO usuarios (nome, email, senha, token, ativo) 
            VALUES ('$nome', '$email', '$senha', '$token', 0)";

    if (mysqli_query($conn, $sql)) {
       header("Location: sucesso.php?email=" . $email);
        exit();

    } else {
        echo "Erro no banco: " . mysqli_error($conn);
    }
}
?>