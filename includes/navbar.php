<?php 
if(isset($_SESSION['usuario_id'])): 
    $meu_id_navbar = $_SESSION['usuario_id'];
?>
    <nav class="navbar-fenda">
        <div class="nav-container">
            
            <ul class="menu">
                <li class="menu-item dropdown"> 
                    <a href="feed.php" class="link-inicio">Início</a>
                    <ul class="submenu">
                        <li><a href="novo-post.php">Criar Spotted</a></li>
                        <li><a href="feed.php">Feed Geral</a></li>
                    </ul>
                </li>
                
                <li class="menu-item dropdown">
                    <a href="#">Utilidade Universitária</a>
                    <ul class="submenu">
                        <li><a href="perdidos.php">Perdidos (porém achados)</a></li>
                        <li><a href="#">Classificados & Eventos</a></li>
                    </ul>
                </li>

                <li class="menu-item"><a href="quem-somos.php">Quem Nós Somos</a></li>
                <li class="menu-item"><a href="diretrizes.php">Regras da Casa</a></li>
            </ul>

            <div class="nav-actions" style="display: flex; align-items: center; gap: 5px;">
                
                <a href="buscar-usuario.php" class="nav-btn-acao nav-icon-lupa" title="Buscar">
                    <i class="bi bi-search"></i>
                </a>

                <a href="perfil.php?id=<?php echo $meu_id_navbar; ?>" class="nav-btn-acao nav-icon-engrenagem" title="Configurações">
                    <i class="bi bi-gear-fill"></i>
                </a>

            </div> </div> </nav>
<?php endif; ?>