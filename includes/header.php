<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'conexao.php'; // Segurança contra o erro de "Redeclare"

$u_id = $_SESSION['usuario_id'] ?? 0;
$pref_swipe_real = 0;

// Busca a preferência direto no banco para o Header saber se ativa o Swipe
if ($u_id > 0 && isset($conn)) {
    $stmt_pref = $conn->prepare("SELECT pref_swipe FROM usuarios WHERE id = ?");
    $stmt_pref->bind_param("i", $u_id);
    $stmt_pref->execute();
    $resultado = $stmt_pref->get_result()->fetch_assoc();
    $pref_swipe_real = $resultado['pref_swipe'] ?? 0;
}

// Define a classe baseada na resposta do banco
$classe_pref = ($pref_swipe_real == 1) ? 'allow-swipe' : 'allow-hover';

// Define o tema (Hacker ou Normal) vindo das páginas que o incluem
$classe_tema = $tema_classe ?? '';

// Junta tudo em uma string para o class do body
$classes_finais = trim("$classe_pref $classe_tema is-touch-device");
?>

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="<?php echo $classes_finais; ?>">
    <header>
        <h1>A Fenda - Spotted Universitário</h1>

        <?php
        $pagina_atual = basename($_SERVER['PHP_SELF']);
        if ($pagina_atual !== 'index.php'):
        ?>
            <div class="header-icons">
                <a href="buscar-usuario.php" class="btn-header">
                    <i class="fas fa-search"></i>
                </a>

                <div class="notificacao-wrapper" onclick="window.location.href='notificacoes.php'">
                    <i class="fa-solid fa-bell"></i>
                    <?php
                    if ($u_id > 0 && isset($conn)) {
                        $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0");
                        $stmt_count->bind_param("i", $u_id);
                        $stmt_count->execute();
                        $res_count = $stmt_count->get_result()->fetch_assoc();
                        $total_n = $res_count['total'];

                        if ($total_n > 0): ?>
                            <span id="badge-alertas" class="badge-alertas"><?php echo $total_n; ?></span>
                        <?php endif;
                    }
                    ?>
                </div>
                <a href="perfil.php" class="btn-config">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        <?php endif; ?>
    </header>