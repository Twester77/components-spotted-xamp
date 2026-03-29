<?php
session_start();
include 'conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];

    // 1. RECEBENDO E LIMPANDO OS DADOS (Segurança contra invasão)
    $novo_nome     = mysqli_real_escape_string($conn, $_POST['nome']);
    $nova_bio      = mysqli_real_escape_string($conn, $_POST['bio']);
    
    // O str_replace tira o "@" caso o cara digite (ex: @apresenca vira apresenca)
    $novo_username = mysqli_real_escape_string($conn, str_replace('@', '', $_POST['username']));

    // 2. ATUALIZANDO O BANCO DE DADOS (Nome, Bio e Username de uma vez só)
    $sql = "UPDATE usuarios SET 
            nome = '$novo_nome', 
            bio = '$nova_bio', 
            username = '$novo_username' 
            WHERE id = '$usuario_id'";
            
    mysqli_query($conn, $sql);

    // 3. ATUALIZA O NOME NA SESSÃO (Pra mudar na Navbar na hora)
    $_SESSION['usuario_nome'] = $novo_nome;

    //  PARTE DE UPLOAD DE IMAGENS 
    $limite_bytes = 15 * 1024 * 1024; // Limite de 15MB

    // Função "Mecânica" para mover a foto para a pasta uploads
    function processarUpload($campo, $prefixo, $id, $pasta, $limite) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {
            if ($_FILES[$campo]['size'] > $limite) return false;
            
            $nome_arq = $prefixo . "_" . $id . ".jpg";
            $destino = $pasta . "/" . $nome_arq;
            
            if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
                return $nome_arq;
            }
        }
        return false;
    }

    // Processa a Foto de Perfil (Avatar)
    $foto_nome = processarUpload('foto', 'user', $usuario_id, 'uploads', $limite_bytes);
    if ($foto_nome) {
        mysqli_query($conn, "UPDATE usuarios SET foto = '$foto_nome' WHERE id = '$usuario_id'");
    }

    // Processa a Foto de Capa (Banner)
    $capa_nome = processarUpload('capa', 'capa', $usuario_id, 'uploads', $limite_bytes);
    if ($capa_nome) {
        mysqli_query($conn, "UPDATE usuarios SET capa = '$capa_nome' WHERE id = '$usuario_id'");
    }
    
    // 4. DEVOLVE O USUÁRIO PARA O PERFIL COM SUCESSO
    header("Location: perfil.php?sucesso=1");
    exit();
}
?>