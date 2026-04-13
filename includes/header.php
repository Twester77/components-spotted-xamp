<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_logado = isset($_SESSION['usuario_id']);
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

<body>
    <header>
        <h1>A Fenda - Spotted Universitário </h1>

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
                    $u_id = $_SESSION['usuario_id'] ?? 0;

                    // Se estiver logado E a conexão com o banco existir...
                    if ($u_id > 0 && isset($conn)) {
                        // Agora o código está seguro!
                        $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0");
                        $stmt_count->bind_param("i", $u_id);
                        $stmt_count->execute();
                        $res_count = $stmt_count->get_result()->fetch_assoc();
                        $total_n = $res_count['total'];

                        if ($total_n > 0): ?>
                            <span class="badge-alerta">
                                <?php echo $total_n; ?>
                            </span>
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