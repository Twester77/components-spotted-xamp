<aside id="sidebar-fenda" class="sidebar-fenda" aria-hidden="true">
    <div class="sidebar-header">
        <button id="btn-fechar-menu" aria-label="Fechar menu de navegação">✕</button>
    </div>

    <nav aria-label="Menu principal">
        <ul class="menu">
            <li class="menu-item"><a href="index.php">Início</a></li>
            <li class="menu-item"><a href="feed.php">Feed Geral</a></li>
            <li class="menu-item"><a href="">Feed Pessoal</a></li>
            
            <li class="menu-item dropdown">
                <a href="#" aria-haspopup="true" aria-expanded="false">Utilidade</a>
                <ul class="submenu">
                    <li><a href="perdidos.php">Perdidos</a></li>
                    <li><a href="classificados.php">Classificados</a></li>
                </ul>
            </li>
            
            <li class="menu-item dropdown">
                <a href="#" aria-haspopup="true" aria-expanded="false">Institucional</a>
                <ul class="submenu">
                    <li><a href="quem-somos.php">Quem Somos</a></li>
                    <li><a href="diretrizes.php">Regras da Casa</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</aside>

<div id="overlay-fenda" class="overlay" aria-hidden="true"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnMenu = document.getElementById('btn-menu-hamburguer');
        const sidebar = document.getElementById('sidebar-fenda');
        const overlay = document.getElementById('overlay-fenda');
        const btnFechar = document.getElementById('btn-fechar-menu');

        function fecharTudo() {
            sidebar.classList.remove('ativa');
            overlay.classList.remove('ativa');
            btnMenu.setAttribute('aria-expanded', 'false');
            sidebar.setAttribute('aria-hidden', 'true');
        }

        if (btnMenu) btnMenu.addEventListener('click', () => {
            sidebar.classList.add('ativa');
            overlay.classList.add('ativa');
            btnMenu.setAttribute('aria-expanded', 'true');
            sidebar.setAttribute('aria-hidden', 'false');
        });

        if (btnFechar) btnFechar.addEventListener('click', fecharTudo);
        if (overlay) overlay.addEventListener('click', fecharTudo);
    });
</script>