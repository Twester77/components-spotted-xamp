<?php
include_once 'conexao.php';
$is_post_page = true;

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
$classes_finais = trim($ativar_modo_app ? "$classe_pref $classe_tema" : "$classe_pref $classe_tema is-touch-device"); ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Fenda - Spotted Universitário (Votuporanga)</title>
    <link rel="stylesheet" href="css/root.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/formularios.css">
    <link rel="stylesheet" href="css/animacoes.css">
    <link rel="stylesheet" href="css/feed.css">
    <link rel="stylesheet" href="css/skin-hacker.css">
    <?php if ($pagina_atual == 'post.php'): ?>
        <link rel="stylesheet" href="css/comentarios.css">
    <?php endif; ?>
    <?php if ($pagina_atual == 'feed.php'): ?>
        <link rel="stylesheet" href="css/swipe.css?v=<?php echo time(); ?>">
    <?php endif; ?>
    <link rel="icon" type="image/png" href="imagensfoto/favicon.png">
    <link rel="apple-touch-icon" href="imagensfoto/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body class="<?php echo $classes_finais; ?> <?php echo (isset($is_post_page) && $is_post_page) ? 'pagina-post' : ''; ?>">
    <header class="header-principal header-visivel">
        <div class="header-left">
            <div class="hamburger-floating-container">
                <button id="btn-menu-hamburguer"
                    aria-label="Abrir menu de navegação"
                    aria-expanded="false"
                    aria-controls="sidebar-fenda">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7l6 -3l6 3l6 -3v13l-6 3l-6 -3l-6 3v-13" />
                        <path d="M9 12v.01" />
                        <path d="M6 13v.01" />
                        <path d="M17 15l-4 -4" />
                        <path d="M13 15l4 -4" />
                    </svg>
                </button>
            </div>

            <h1>A Fenda - Spotted Universitário</h1>
        </div>

        <?php if ($u_id > 0): ?>
            <div class="header-right">
                <div class="header-icons">
                    <a href="buscar-usuario.php" class="btn-header" title="Pesquisar Habitantes" aria-label="Pesquisar habitantes">
                        <i class="fas fa-search"></i>
                    </a>

                    <div class="notificacao-wrapper" onclick="toggleJanelaNotificacoes()" aria-label="Suas Notificações">
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

                    <a href="#"
                        class="btn-config"
                        onclick="event.preventDefault(); abrirPerfilDrawer();"
                        title="Configurações do Perfil"
                        aria-label="Configurar seu perfil"
                        aria-expanded="false"
                        aria-controls="perfil-drawer">
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <!-- ========================================== -->
    <!-- 🚀 MOTOR GLOBAL DE TOASTS DA FENDA UNIVERSITY -->
    <!-- ========================================== -->
    <?php
    $exibir_toast = false;
    $toast_mensagem = "";
    $toast_classe = "";
    $toast_icone = "";

    // 🔴 1. CAPTURA DE ERROS GLOBAIS
    if (isset($_GET['erro'])) {
        $exibir_toast = true;
        $toast_classe = "toast-erro-global"; // Estilize no CSS com fundo vermelho/laranja
        $toast_icone = "fa-solid fa-triangle-exclamation";
        
        switch ($_GET['erro']) {
            case 'username_duplicado':
                $toast_mensagem = "Este nome de usuário já está em uso por outro habitante!";
                break;
            case 'username_espaco':
                $toast_mensagem = "O nome de usuário não pode conter espaços em branco!";
                break;
            case 'username_curto':
                $toast_mensagem = "Username muito curto! Escolha um com no mínimo 5 caracteres.";
                break;
            case 'perfil_invalido':
                $toast_mensagem = " Usuário não especificado ou inexistente.";
                break;
            default:
                $toast_mensagem = "Ops! Algo deu errado. Tente novamente.";
                break;
        }
    }

    // 🟢 2. CAPTURA DE SUCESSOS GLOBAIS
    if (isset($_GET['sucesso'])) {
        $exibir_toast = true;
        $toast_classe = "toast-sucesso-global"; 
        $toast_icone = "fa-solid fa-circle-check";

        switch ($_GET['sucesso']) {
            case 'perfil':
            case 'perfil_atualizado':
                $toast_mensagem = "Perfil atualizado com sucesso! ";
                break;
            case 'postado':
                $toast_mensagem = "Sussurro enviado com sucesso para a Fenda! ";
                break;
            default:
                $toast_mensagem = "Perfil atualizado com sucesso! ";
                break;
        }
    }

    // 🎬 3. RENDERIZAÇÃO DO TOAST
    if ($exibir_toast): 
    ?>
        <div id="toast-fenda-global" class="toast-fenda <?php echo $toast_classe; ?>" role="alert" aria-live="assertive" aria-atomic="true">
            <i class="<?php echo $toast_icone; ?>" aria-hidden="true"></i>
            <span style="margin-left: 10px;"><?php echo $toast_mensagem; ?></span>
        </div>

        <script>
            setTimeout(() => {
                const toastGlobal = document.getElementById('toast-fenda-global');
                if (toastGlobal) {
                    toastGlobal.style.opacity = '0';
                    setTimeout(() => toastGlobal.remove(), 500);
                }
                // Limpa os parâmetros da URL para o F5 não ficar repetindo o Toast
                window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[?&](erro|sucesso)=[^&*]/g, ''));
            }, 4000);
        </script>
    <?php endif; ?>

    