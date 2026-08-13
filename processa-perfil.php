<?php
// 1. LIGAR O BUFFER E SILENCIAR ERROS ANTES DE QUALQUER OUTRA COISA
require_once __DIR__ . '/auth_check.php';
require_once 'includes/upload_engine.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO processa-perfil.php');

$url_origem = isset($_SERVER['HTTP_REFERER']) ? strtok($_SERVER['HTTP_REFERER'], '?') : 'perfil.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fenda_log('🔴 REDIRECIONANDO para ' . $url_origem . ' (método não POST)');
    header("Location: $url_origem");
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    fenda_log('🔴 REDIRECIONANDO para perfil.php (sem sessão)');
    header("Location: perfil.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
fenda_log('🔵 Usuário ID ' . $usuario_id . ' processando perfil');

// --- Captura dos dados textuais ---
$novo_nome     = strip_tags($_POST['nome'] ?? '');
$nova_bio      = strip_tags($_POST['bio'] ?? '');
$novo_username = strip_tags($_POST['username'] ?? '');

if (preg_match('/\s/', $novo_username)) {
    fenda_log('🔴 REDIRECIONANDO para ' . $url_origem . '?erro=username_espaco');
    header("Location: " . $url_origem . "?erro=username_espaco");
    exit();
}

$novo_username = strtolower(preg_replace('/[^a-zA-Z0-9\._]/', '', $novo_username));
if (empty($novo_username) || strlen($novo_username) < 5) {
    fenda_log('🔴 REDIRECIONANDO para ' . $url_origem . '?erro=username_curto');
    header("Location: " . $url_origem . "?erro=username_curto");
    exit();
}

$sql_check = "SELECT id FROM usuarios WHERE username = ? AND id != ?";
$stmt_check = mysqli_prepare($conn, $sql_check);
if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "si", $novo_username, $usuario_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);
        fenda_log('🔴 REDIRECIONANDO para ' . $url_origem . '?erro=username_duplicado');
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
$novo_pip      = isset($_POST['pref_pip']) ? (int)$_POST['pref_pip'] : 0;
$novo_badge    = isset($_POST['pref_badge']) ? (int)$_POST['pref_badge'] : 1;
$nova_notif_comunidade = isset($_POST['pref_notif_comunidade']) ? (int)$_POST['pref_notif_comunidade'] : 1;

// 🔥 NOVA PREFERÊNCIA
$novo_swipe_balanga = isset($_POST['pref_swipe_balanga']) ? (int)$_POST['pref_swipe_balanga'] : 0;
fenda_log("📝 preferência swipe balanga recebida: " . $novo_swipe_balanga);

// 🚀 UPLOAD
$caminhosEnviados = [];
$foto_nome = null;
$capa_nome = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $foto_nome = processarUploadSeguro($_FILES['foto'], './uploads', 'user', 2 * 1024 * 1024, $usuario_id);
    if ($foto_nome) {
        $caminhosEnviados[] = $foto_nome;
    }
}
if (isset($_FILES['capa']) && $_FILES['capa']['error'] == 0) {
    $capa_nome = processarUploadSeguro($_FILES['capa'], './uploads', 'capa', 2 * 1024 * 1024, $usuario_id);
    if ($capa_nome) {
        $caminhosEnviados[] = $capa_nome;
    }
}

// BUSCA ANTIGOS
$stmt_busca = mysqli_prepare($conn, "SELECT foto, capa FROM usuarios WHERE id = ?");
mysqli_stmt_bind_param($stmt_busca, "i", $usuario_id);
mysqli_stmt_execute($stmt_busca);
$res_busca = mysqli_stmt_get_result($stmt_busca);
$usuario_atual = mysqli_fetch_assoc($res_busca);
mysqli_stmt_close($stmt_busca);
$foto_antiga = $usuario_atual['foto'] ?? null;
$capa_antiga = $usuario_atual['capa'] ?? null;

// UPDATE
try {
    $fields = [
        'nome' => $novo_nome,
        'bio' => $nova_bio,
        'username' => $novo_username,
        'atletica_id' => $nova_atletica,
        'pref_vibe_padrao' => $nova_vibe,
        'pref_cor_padrao' => $nova_cor,
        'pref_swipe' => $novo_swipe,
        'pref_bolhas' => $nova_bolha,
        'pref_som_trilha' => $nova_trilha,
        'pref_som_notif' => $nova_notif,
        'pref_pip' => $novo_pip,
        'pref_badge' => $novo_badge,
        'pref_notif_comunidade' => $nova_notif_comunidade,
    ];

    if ($foto_nome !== null) {
        $fields['foto'] = $foto_nome;
    }
    if ($capa_nome !== null) {
        $fields['capa'] = $capa_nome;
    }

    // 🔥 ADICIONA NO FINAL
    $fields['pref_swipe_balanga'] = $novo_swipe_balanga;

    $set_parts = [];
    $values = [];
    $types = '';
    foreach ($fields as $campo => $valor) {
        $set_parts[] = "$campo = ?";
        $values[] = $valor;
        if (is_int($valor)) {
            $types .= 'i';
        } else {
            $types .= 's';
        }
    }
    $sql = "UPDATE usuarios SET " . implode(', ', $set_parts) . " WHERE id = ?";
    $values[] = $usuario_id;
    $types .= 'i';

    fenda_log("🟢 SQL: " . $sql);
    fenda_log("🟢 TYPES: " . $types);
    fenda_log("🟢 VALUES: " . print_r($values, true));

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar UPDATE: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$values);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Erro no UPDATE: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    if ($foto_antiga && $foto_antiga != $foto_nome) {
        if (file_exists("./uploads/" . $foto_antiga)) unlink("./uploads/" . $foto_antiga);
        deleteFromB2($foto_antiga, $usuario_id);
    }
    if ($capa_antiga && $capa_antiga != $capa_nome) {
        if (file_exists("./uploads/" . $capa_antiga)) unlink("./uploads/" . $capa_antiga);
        deleteFromB2($capa_antiga, $usuario_id);
    }

    $_SESSION['usuario_nome'] = $novo_nome;
    fenda_log('🟢 Perfil atualizado com sucesso para usuário ' . $usuario_id);
    header("Location: " . $url_origem . "?sucesso=1");
    exit();

} catch (Exception $e) {
    fenda_log('🔴 ERRO no UPDATE: ' . $e->getMessage());
    foreach ($caminhosEnviados as $caminho) {
        deleteFromB2($caminho, $usuario_id);
        fenda_log('🔴 [ROLLBACK] Arquivo removido do B2: ' . $caminho);
    }
    header("Location: " . $url_origem . "?erro=update_falhou");
    exit();
}
?>