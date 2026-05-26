<?php
include 'conexao.php';

function processarUploadSeguro($campo, $prefixo, $id, $pasta, $limite)
{
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] == 0) {
        if ($_FILES[$campo]['size'] > $limite) return false;

        $info = getimagesize($_FILES[$campo]['tmp_name']);
        if ($info === false) return false;

        $mime = $info['mime'];
        if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])) {
            return false;
        }

        // Criar recurso de imagem na memória
        if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
            $img_recurso = imagecreatefromjpeg($_FILES[$campo]['tmp_name']);
        } elseif ($mime == 'image/png') {
            $img_recurso = imagecreatefrompng($_FILES[$campo]['tmp_name']);
        } elseif ($mime == 'image/webp') {
            $img_recurso = imagecreatefromwebp($_FILES[$campo]['tmp_name']);
        }

        if ($img_recurso) {
            // Nome do arquivo padronizado salvando sempre como .webp
            $nome_arq = $prefixo . "_" . $id . "_" . time() . ".webp";
            $destino = $pasta . "/" . $nome_arq;

            // Comprime a foto de perfil/capa para 75% de qualidade
            $sucesso = imagewebp($img_recurso, $destino, 75);
            imagedestroy($img_recurso);

            if ($sucesso) {
                return $nome_arq;
            }
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // Capturando os dados do formulário e arrancando tags HTML para evitar injeção
    $novo_nome     = strip_tags($_POST['nome'] ?? '');
    $nova_bio      = strip_tags($_POST['bio'] ?? '');
    $novo_username = strip_tags($_POST['username'] ?? '');

    // 1. Remove qualquer espaço que sobrou ou foi burlado no HTML
    if (preg_match('/\s/', $novo_username)) {
        header("Location: perfil.php?erro=espaco_proibido");
        exit();
    }

    // 2. Limpa símbolos proibidos e força para minúsculo para casar com o padrão
    $novo_username = strtolower(preg_replace('/[^a-zA-Z0-9\._]/', '', $novo_username));

    // 3. Verifica se o username não ficou vazio ou curto demais após a limpeza
    if (empty($novo_username) || strlen($novo_username) < 5) {
        header("Location: perfil.php?erro=username_invalido");
        exit();
    }

    // 🕵️‍♂️ CRUCIAL: CHECAGEM DE DUPLICIDADE (O username já existe?)
    $sql_check = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_query($conn, "SET NAMES 'utf8'"); // Garante integridade de caracteres

    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "si", $novo_username, $usuario_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        // Se achou alguma linha, significa que OUTRO habitante já usa esse @
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            mysqli_stmt_close($stmt_check);
            header("Location: perfil.php?erro=username_duplicado");
            exit();
        }
        mysqli_stmt_close($stmt_check);
    }

    $nova_atletica = $_POST['atletica_id'] ?? 'ads'; // Valor padrão para evitar null
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
    $limite_bytes = 2 * 1024 * 1024;

    // ==========================================
    // 📸 PROCESSO DA FOTO DE PERFIL (ATUALIZADO)
    // ==========================================
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        
        // 1. Busca qual é a foto antiga antes de gravar a nova
        $stmt_busca = mysqli_prepare($conn, "SELECT foto FROM usuarios WHERE id = ?");
        mysqli_stmt_bind_param($stmt_busca, "i", $usuario_id);
        mysqli_stmt_execute($stmt_busca);
        $res_busca = mysqli_stmt_get_result($stmt_busca);
        $usuario_atual = mysqli_fetch_assoc($res_busca);
        mysqli_stmt_close($stmt_busca);

        // 2. Processa e gera a nova imagem .webp
        $foto_nome = processarUploadSeguro('foto', 'user', $usuario_id, './uploads', $limite_bytes);
        
        if ($foto_nome) {
            $stmt_f = mysqli_prepare($conn, "UPDATE usuarios SET foto = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_f, "si", $foto_nome, $usuario_id);
            
            if (mysqli_stmt_execute($stmt_f)) {
                // 3. Se atualizou com sucesso, remove o arquivo físico antigo da pasta
                if (!empty($usuario_atual['foto'])) {
                    $caminho_foto_antiga = "./uploads/" . $usuario_atual['foto'];
                    if (file_exists($caminho_foto_antiga)) {
                        unlink($caminho_foto_antiga);
                    }
                }
            }
            mysqli_stmt_close($stmt_f);
        }
    }

    // ==========================================
    // 🖼️ PROCESSO DA FOTO DE CAPA (ATUALIZADO)
    // ==========================================
    if (isset($_FILES['capa']) && $_FILES['capa']['error'] == 0) {
        
        // 1. Busca qual é a capa antiga
        $stmt_busca_c = mysqli_prepare($conn, "SELECT capa FROM usuarios WHERE id = ?");
        mysqli_stmt_bind_param($stmt_busca_c, "i", $usuario_id);
        mysqli_stmt_execute($stmt_busca_c);
        $res_busca_c = mysqli_stmt_get_result($stmt_busca_c);
        $usuario_atual_c = mysqli_fetch_assoc($res_busca_c);
        mysqli_stmt_close($stmt_busca_c);

        // 2. Processa e gera a nova capa .webp
        $capa_nome = processarUploadSeguro('capa', 'capa', $usuario_id, './uploads', $limite_bytes);
        
        if ($capa_nome) {
            $stmt_c = mysqli_prepare($conn, "UPDATE usuarios SET capa = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_c, "si", $capa_nome, $usuario_id);
            
            if (mysqli_stmt_execute($stmt_c)) {
                // 3. Se atualizou com sucesso, remove a capa antiga da pasta
                if (!empty($usuario_atual_c['capa'])) {
                    $caminho_capa_antiga = "./uploads/" . $usuario_atual_c['capa'];
                    if (file_exists($caminho_capa_antiga)) {
                        unlink($caminho_capa_antiga);
                    }
                }
            }
            mysqli_stmt_close($stmt_c);
        }
    }

    // Redireciona de volta para o perfil
    header("Location: perfil.php?sucesso=1");
    exit();
}
