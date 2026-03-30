
<?php if(isset($_SESSION['usuario_id'])): ?>
    <a href="perfil.php" title="Configurações">
        <img src="imagensfoto/engrenagem.png" style="width: 25px;"> 
        </a>
<?php endif; ?>
<nav>
    <ul class="menu">
        <li class="menu-item dropdown"> 
            <a href="index.php">Início</a>
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
</nav>