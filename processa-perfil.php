<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'conexao.php'; 

function processarUpload($campo, $prefixo, $id, $pasta, $limite)
{
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {
        if ($_FILES[$campo]['size'] > $limite) return false;

        $nome_arq = $prefixo . "_" . $id . "_" . time() . ".jpg";
        $destino = $pasta . "/" . $nome_arq;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            return $nome_arq;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // 1. RECEBENDO E LIMPANDO OS DADOS (Mantendo sua lógica original)
    $novo_nome      = mysqli_real_escape_string($conn, $_POST['nome']);
    $nova_bio       = mysqli_real_escape_string($conn, $_POST['bio']);
    $nova_vibe      = mysqli_real_escape_string($conn, $_POST['pref_vibe_padrao']);
    $nova_cor       = mysqli_real_escape_string($conn, $_POST['pref_cor_padrao']);
    $nova_atletica  = mysqli_real_escape_string($conn, $_POST['atletica_id']);
    $novo_username  = mysqli_real_escape_string($conn, str_replace('@', '', $_POST['username']));

    $novo_swipe = isset($_POST['pref_swipe']) ? 1 : 0;
    
    // --- O QUE PRECISAVA CORRIGIR ---
    // 1. Criar a variável $nova_bolha que estava sendo usada no SQL mas não existia em cima
    $nova_bolha = isset($_POST['pref_bolhas']) ? (int)$_POST['pref_bolhas'] : 1;

    // 2. ATUALIZANDO O BANCO DE DADOS
    // Adicionei a VÍRGULA que faltava depois de pref_swipe e antes de pref_bolhas
    $sql = "UPDATE usuarios SET 
            nome = '$novo_nome', 
            bio = '$nova_bio', 
            username = '$novo_username',
            atletica_id = '$nova_atletica',
            pref_vibe_padrao = '$nova_vibe', 
            pref_cor_padrao = '$nova_cor',
            pref_swipe = '$novo_swipe', 
            pref_bolhas = '$nova_bolha'
            WHERE id = '$usuario_id'";

    mysqli_query($conn, $sql);

    // 3. ATUALIZA O NOME NA SESSÃO
    $_SESSION['usuario_nome'] = $novo_nome;

    $limite_bytes = 15 * 1024 * 1024; // 15MB

    $foto_nome = processarUpload('foto', 'user', $usuario_id, 'uploads', $limite_bytes);
    if ($foto_nome) {
        mysqli_query($conn, "UPDATE usuarios SET foto = '$foto_nome' WHERE id = '$usuario_id'");
    }

    $capa_nome = processarUpload('capa', 'capa', $usuario_id, 'uploads', $limite_bytes);
    if ($capa_nome) {
        mysqli_query($conn, "UPDATE usuarios SET capa = '$capa_nome' WHERE id = '$usuario_id'");
    }

    header("Location: perfil.php?sucesso=1");
    exit();
} else {
    header("Location: index.php");
    exit();
}