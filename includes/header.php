<?php
include_once 'conexao.php';

$u_id = $_SESSION['usuario_id'] ?? 0;
$pref_swipe_real = 0; 
$pagina_atual = basename($_SERVER['PHP_SELF']); // Identifica a página (ex: feed.php)

if ($u_id > 0 && isset($conn)) {
    $stmt_pref = $conn->prepare("SELECT pref_swipe FROM usuarios WHERE id = ?");
    $stmt_pref->bind_param("i", $u_id);
    $stmt_pref->execute();
    $resultado_header_pessoal = $stmt_pref->get_result()->fetch_assoc();
    $pref_swipe_real = $resultado_header_pessoal['pref_swipe'] ?? 0;
}

// === REGRA DE OURO DA FENDA ===
// O Modo Swipe SÓ ativa se: 1. A preferência for 1 E 2. Estivermos no feed.php
$ativar_modo_app = ($pref_swipe_real == 1 && $pagina_atual == 'feed.php');

$classe_tema = $tema_classe ?? '';
$classe_pref = ($ativar_modo_app) ? 'modo-swipe-ativo feed-empilhado' : 'allow-hover';
// Se o modo swipe estiver ativo, o body fica limpo para o JS rodar a física livre!
$classes_finais = trim($ativar_modo_app ? "$classe_pref $classe_tema" : "$classe_pref $classe_tema is-touch-device");?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Fenda - Spotted Universitário (Votuporanga)</title>
    <link rel="stylesheet" href="css/root.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/feed.css">
    <link rel="stylesheet" href="css/formularios.css">
    <link rel="stylesheet" href="css/animacoes.css">
    <link rel="stylesheet" href="css/skin-hacker.css">
    <link rel="stylesheet" href="css/swipe.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="imagensfoto/favicon.png">
    <link rel="apple-touch-icon" href="imagensfoto/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body class="<?php echo $classes_finais; ?>">
    <header>
        <h1>A Fenda - Spotted Universitário</h1>

        <?php
        // MELHORIA SUPREMA: Os ícones agora aparecem na Index e no Feed, 
        // desde que o Habitante esteja devidamente logado no sistema!
        if ($u_id > 0): 
        ?>
            <div class="header-icons">
                <a href="buscar-usuario.php" class="btn-header" title="Pesquisar Habitantes">
                    <i class="fas fa-search"></i>
                </a>

                <div class="notificacao-wrapper" onclick="toggleJanelaNotificacoes()">
                    <i class="fa-solid fa-bell"></i>

                    <?php
                    $total_n = 0;
                    if (isset($conn)) {
                        $stmt_count_header = $conn->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0");
                        $stmt_count_header->bind_param("i", $u_id);
                        $stmt_count_header->execute();
                        $res_header_notif = $stmt_count_header->get_result()->fetch_assoc();
                        $total_n = $res_header_notif['total'] ?? 0;
                    }
                    ?>

                    <span id="badge-alertas" class="badge-alertas" style="<?php echo ($total_n > 0) ? '' : 'display:none;'; ?>">
                        <?php echo $total_n; ?>
                    </span>

                    <div id="dropdown-notificacoes" class="notificacao-dropdown-content">
                        <p style="padding:10px; color:#999;">Carregando fofocas...</p>
                    </div>
                </div>

                <a href="perfil.php" class="btn-config" title="Configurações do Perfil">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        <?php endif; ?>
    </header>