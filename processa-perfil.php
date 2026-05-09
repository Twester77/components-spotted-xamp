<?php
include 'conexao.php';

function processarUploadSeguro($campo, $prefixo, $id, $pasta, $limite)
{

// O "DEDO-DURO" ENTRA AQUI DENTRO:
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] != 0) {
        // Se der erro 1, é o tamanho do servidor. Se der 4, ele não achou o arquivo.
        die("Erro no Upload do campo [$campo]: Código " . $_FILES[$campo]['error']); 
    }  
    
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {
        if ($_FILES[$campo]['size'] > $limite) return false;

        $info = getimagesize($_FILES[$campo]['tmp_name']);
        if ($info === false) return false;

        // Definindo direto no in_array para o VS Code parar de reclamar
        if (!in_array($info['mime'], ['image/jpeg', 'image/jpg', 'image/png'])) {
            return false;
        }

        $ext = ($info['mime'] == 'image/png') ? '.png' : '.jpg';
        $nome_arq = $prefixo . "_" . $id . "_" . time() . $ext;
        $destino = $pasta . "/" . $nome_arq;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            return $nome_arq;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // Capturando os dados do formulário
    $novo_nome     = $_POST['nome'] ?? '';
    $nova_bio      = $_POST['bio'] ?? '';
    $novo_username = $_POST['username'] ?? '';

    // 1. Remove qualquer espaço que sobrou ou foi burlado no HTML
    if (preg_match('/\s/', $novo_username)) {
        header("Location: perfil.php?erro=espaco_proibido");
        exit();
    }

    // 2. Limpa símbolos proibidos, deixando apenas letras, números e underline
    $novo_username = preg_replace('/[^a-zA-Z0-9\_]/', '', $novo_username);

    // 3. Verifica se o username não ficou vazio após a limpeza
    if (empty($novo_username)) {
        header("Location: perfil.php?erro=username_invalido");
        exit();
    }

    $nova_atletica = $_POST['atletica_id'] ?? 'agronomia';
    $nova_vibe = $_POST['pref_vibe_padrao'] ?? 'vibe-glass';
    $nova_cor = isset($_POST['pref_cor_padrao']) ? $_POST['pref_cor_padrao'] : '#70cde4';
    $novo_swipe    = isset($_POST['pref_swipe']) ? (int)$_POST['pref_swipe'] : 0;
    $nova_bolha    = isset($_POST['pref_bolhas']) ? (int)$_POST['pref_bolhas'] : 0;

    // --- PREPARED STATEMENT ---
    // A ordem dos tipos deve bater exatamente com a ordem das variáveis
    // nome(s), bio(s), username(s), atletica(i), vibe(s), cor(s), swipe(i), bolhas(i), id(i)
    // TIPO: s s s i s s i i i (9 total) - s para string, i para inteiros

    $sql = "UPDATE usuarios SET 
            nome = ?, 
            bio = ?, 
            username = ?,
            atletica_id = ?,
            pref_vibe_padrao = ?, 
            pref_cor_padrao = ?,
            pref_swipe = ?, 
            pref_bolhas = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssiii",
            $novo_nome,
            $nova_bio,
            $novo_username,
            $nova_atletica,
            $nova_vibe,
            $nova_cor,
            $novo_swipe,
            $nova_bolha,
            $usuario_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $_SESSION['usuario_nome'] = $novo_nome;
    $limite_bytes = 15 * 1024 * 1024;

    // Foto de Perfil
    $foto_nome = processarUploadSeguro('foto', 'user', $usuario_id, './uploads', $limite_bytes);
    if ($foto_nome) {
        $stmt_f = mysqli_prepare($conn, "UPDATE usuarios SET foto = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_f, "si", $foto_nome, $usuario_id);
        mysqli_stmt_execute($stmt_f);
        mysqli_stmt_close($stmt_f);
    }

    // Foto de Capa
    $capa_nome = processarUploadSeguro('capa', 'capa', $usuario_id, './uploads', $limite_bytes);
    if ($capa_nome) {
        $stmt_c = mysqli_prepare($conn, "UPDATE usuarios SET capa = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_c, "si", $capa_nome, $usuario_id);
        mysqli_stmt_execute($stmt_c);
        mysqli_stmt_close($stmt_c);
    }

    header("Location: perfil.php?sucesso=1");
    exit();
}
