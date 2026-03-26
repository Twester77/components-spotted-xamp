<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    //  Validação do domínio 
    if (!str_ends_with($email, '@unifev.edu.br')) {
        header("Location: cad-usuario.php?erro=dominio");
        exit();
    }

    // Checar se o e-mail já está cadastrado
    $check_sql = "SELECT id FROM usuarios WHERE email = '$email'";
    $check_res = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_res) > 0) {
        // Se achou alguém, manda de volta com erro de "já existe"
        header("Location: cad-usuario.php?erro=ja_existe");
        exit();
    }

    //  Se passou pelas travas, aí sim cadastra
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES ('$nome', '$email', '$senha')";

    if (mysqli_query($conn, $sql)) {
        header("Location: index.php?msg=sucesso");
        exit();
    } else {
        echo "Erro no banco: " . mysqli_error($conn);
    }
}
?>