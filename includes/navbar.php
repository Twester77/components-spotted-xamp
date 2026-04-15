<?php if (isset($_SESSION['usuario_id'])): ?>

<?php endif; ?>
<nav>
    <meta charset="UTF-8">
    <ul class="menu">
        <li class="menu-item"><a href="index.php">Início</a></li>
        <li class="menu-item dropdown">
            <a href="">Social</a>
            <ul class="submenu">
                <li><a href="novo-post.php">Criar Spotted</a></li>
                <li><a href="feed.php">Feed Geral</a></li>
            </ul>
        </li>

        <li class="menu-item dropdown">
            <a href="">Utilidade Universitária</a>
            <ul class="submenu">
                <li><a href="perdidos.php">Perdidos(porém achados)</a></li>
                <li><a href="classificados.php">Classificados & Eventos</a></li>
            </ul>
        </li>

        <li class="menu-item"><a href="quem-somos.php">Quem Nós Somos</a></li>
        <li class="menu-item"><a href="diretrizes.php">Regras da Casa</a></li>
    </ul>
</nav>