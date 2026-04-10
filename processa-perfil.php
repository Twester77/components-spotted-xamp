<?php
session_start();
include 'conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];

    // 1. RECEBENDO E LIMPANDO OS DADOS
    $novo_nome      = mysqli_real_escape_string($conn, $_POST['nome']);
    $nova_bio       = mysqli_real_escape_string($conn, $_POST['bio']);
    
    // Capturando a Atlética (o valor vem do <select name="atletica_id">)
    $nova_atletica  = mysqli_real_escape_string($conn, $_POST['atletica_id']);

    // O str_replace tira o "@" caso o cara digite
    $novo_username  = mysqli_real_escape_string($conn, str_replace('@', '', $_POST['username']));

    // 2. ATUALIZANDO O BANCO DE DADOS (Agora com atletica_id incluída!)
    $sql = "UPDATE usuarios SET 
            nome = '$novo_nome', 
            bio = '$nova_bio', 
            username = '$novo_username',
            atletica_id = '$nova_atletica' 
            WHERE id = '$usuario_id'";
            
    mysqli_query($conn, $sql);

    // 3. ATUALIZA O NOME NA SESSÃO
    $_SESSION['usuario_nome'] = $novo_nome;

    // PARTE DE UPLOAD DE IMAGENS 
    $limite_bytes = 15 * 1024 * 1024; // 15MB

    function processarUpload($campo, $prefixo, $id, $pasta, $limite) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {
            if ($_FILES[$campo]['size'] > $limite) return false;
            
            $nome_arq = $prefixo . "_" . $id . ".jpg";
            $destino = $pasta . "/" . $nome_arq;
            
            // Se já existir uma imagem antiga com o mesmo nome, o move_uploaded_file sobrescreve
            if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
                return $nome_arq;
            }
        }
        return false;
    }

    // Processa Avatar
    $foto_nome = processarUpload('foto', 'user', $usuario_id, 'uploads', $limite_bytes);
    if ($foto_nome) {
        mysqli_query($conn, "UPDATE usuarios SET foto = '$foto_nome' WHERE id = '$usuario_id'");
    }

    // Processa Capa
    $capa_nome = processarUpload('capa', 'capa', $usuario_id, 'uploads', $limite_bytes);
    if ($capa_nome) {
        mysqli_query($conn, "UPDATE usuarios SET capa = '$capa_nome' WHERE id = '$usuario_id'");
    }
    
    // 4. RETORNO
    header("Location: perfil.php?sucesso=1");
    exit();
}

?>