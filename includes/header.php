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
    <link rel="stylesheet" href="/spotted-unifev/estilos.css">    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body> 
   <header>
    <h1>A Fenda - Spotted Universitário</h1>
    
    <?php 
    $pagina_atual = basename($_SERVER['PHP_SELF']); 
    if ($pagina_atual !== 'index.php'): 
    ?>

    <div class="header-icons">
        <a href="buscar-usuario.php" class="btn-header" title="Buscar Usuários">
            <i class="fas fa-search icon-fenda"></i>
        </a>

        <div class="notificacao-wrapper" onclick="window.location.href='notificacoes.php'">
           <i class="fa-solid fa-bell"></i>
           <span id="badge-alertas" class="badge-alertas" style="display: none;">0</span>
        </div>

        <a href="perfil.php" class="btn-config" title="Configurações do Perfil">
           <i class="fas fa-cog icon-fenda"></i>
        </a>
    </div>

    <?php endif; ?>
</header>
    