<?php
// 1. LIGAR O BUFFER E SILENCIAR ERROS ANTES DE QUALQUER OUTRA COISA
require_once __DIR__ . '/auth_check.php';
require_once 'includes/upload_engine.php'; // 🚀 Motor oficial de upload

// Se veio do feed.php, vira feed.php. Se veio do perfil.php, vira perfil.php...
$url_origem = isset($_SERVER['HTTP_REFERER']) ? strtok($_SERVER['HTTP_REFERER'], '?') : 'perfil.php';

// 1. Segurança: Só processa se for POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $url_origem");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    $novo_nome     = strip_tags($_POST['nome'] ?? '');
    $nova_bio      = strip_tags($_POST['bio'] ?? '');
    $novo_username = strip_tags($_POST['username'] ?? '');

    // Validação de espaços
    if (preg_match('/\s/', $novo_username)) {
        header("Location: " . $url_origem . "?erro=username_espaco");
        exit();
    }

    $novo_username = strtolower(preg_replace('/[^a-zA-Z0-9\._]/', '', $novo_username));

    if (empty($novo_username) || strlen($novo_username) < 5) {
        header("Location: " . $url_origem . "?erro=username_curto");
        exit();
    }

    // Checagem de duplicidade
    $sql_check = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    if ($stmt_check) {
        mysqli_stmt_bind_param($stmt_check, "si", $novo_username, $usuario_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            mysqli_stmt_close($stmt_check);
            header("Location: " . $url_origem . "?erro=username_duplicado");
            exit();
        }
        mysqli_stmt_close($stmt_check);
    }

    $nova_atletica = $_POST['atletica_id'] ?? 'ads';
    $nova_vibe     = $_POST['pref_vibe_padrao'] ?? 'vibe-glass';
    $nova_cor      = $_POST['pref_cor_padrao'] ?? '#70cde4';
    $novo_swipe    = isset($_POST['pref_swipe']) ? (int)$_POST['pref_swipe'] : 0;
    $nova_bolha    = isset($_POST['pref_bolhas']) ? (int)$_POST['pref_bolhas'] : 0;
    $nova_trilha   = $_POST['pref_som_trilha'] ?? 'off';
    $nova_notif    = $_POST['pref_som_notif'] ?? 'padrao';

    // Atualização dos dados textuais
    $sql = "UPDATE usuarios SET 
            nome = ?, 
            bio = ?, 
            username = ?, 
            atletica_id = ?, 
            pref_vibe_padrao = ?, 
            pref_cor_padrao = ?, 
            pref_swipe = ?, 
            pref_bolhas = ?, 
            pref_som_trilha = ?, 
            pref_som_notif = ? 
        WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssiissi",
            $novo_nome,
            $nova_bio,
            $novo_username,
            $nova_atletica,
            $nova_vibe,
            $nova_cor,
            $novo_swipe,
            $nova_bolha,
            $nova_trilha,
            $nova_notif,
            $usuario_id
        );
        if (!mysqli_stmt_execute($stmt)) {
            die("Erro no banco de dados: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }

    $_SESSION['usuario_nome'] = $novo_nome;
    $limite_bytes = 2 * 1024 * 1024; // 2MB

    // ============================================================
    // 🚀 UPLOAD DE FOTO (AVATAR) – usando motor oficial
    // ============================================================
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        // Busca a foto antiga para deletar depois
        $stmt_busca = mysqli_prepare($conn, "SELECT foto FROM usuarios WHERE id = ?");
        mysqli_stmt_bind_param($stmt_busca, "i", $usuario_id);
        mysqli_stmt_execute($stmt_busca);
        $res_busca = mysqli_stmt_get_result($stmt_busca);
        $usuario_atual = mysqli_fetch_assoc($res_busca);
        mysqli_stmt_close($stmt_busca);

        $foto_nome = processarUploadSeguro($_FILES['foto'], './uploads', 'user', $limite_bytes);
        if ($foto_nome) {
            $stmt_f = mysqli_prepare($conn, "UPDATE usuarios SET foto = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_f, "si", $foto_nome, $usuario_id);
            if (mysqli_stmt_execute($stmt_f)) {
                // Deleta a foto antiga se existir e for diferente da nova
                if (!empty($usuario_atual['foto']) && file_exists("./uploads/" . $usuario_atual['foto'])) {
                    unlink("./uploads/" . $usuario_atual['foto']);
                }
            }
            mysqli_stmt_close($stmt_f);
        }
    }

    // ============================================================
    // 🚀 UPLOAD DE CAPA – usando motor oficial
    // ============================================================
    if (isset($_FILES['capa']) && $_FILES['capa']['error'] == 0) {
        $stmt_busca_c = mysqli_prepare($conn, "SELECT capa FROM usuarios WHERE id = ?");
        mysqli_stmt_bind_param($stmt_busca_c, "i", $usuario_id);
        mysqli_stmt_execute($stmt_busca_c);
        $res_busca_c = mysqli_stmt_get_result($stmt_busca_c);
        $usuario_atual_c = mysqli_fetch_assoc($res_busca_c);
        mysqli_stmt_close($stmt_busca_c);

        $capa_nome = processarUploadSeguro($_FILES['capa'], './uploads', 'capa', $limite_bytes);
        if ($capa_nome) {
            $stmt_c = mysqli_prepare($conn, "UPDATE usuarios SET capa = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_c, "si", $capa_nome, $usuario_id);
            if (mysqli_stmt_execute($stmt_c)) {
                if (!empty($usuario_atual_c['capa']) && file_exists("./uploads/" . $usuario_atual_c['capa'])) {
                    unlink("./uploads/" . $usuario_atual_c['capa']);
                }
            }
            mysqli_stmt_close($stmt_c);
        }
    }

    // Redirecionamento final
    if (isset($_SERVER['HTTP_REFERER'])) {
        // Limpa qualquer parâmetro de URL antigo que possa ter vindo
        $url_origem = strtok($_SERVER['HTTP_REFERER'], '?');
        header("Location: " . $url_origem . "?sucesso=1");
    } else {
        header("Location: perfil.php?sucesso=1");
    }
    exit();
} else {
    header("Location: perfil.php");
    exit();
}
?>