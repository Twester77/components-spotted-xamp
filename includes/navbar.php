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

<aside id="sidebar-fenda" class="sidebar-fenda" aria-hidden="true">
    <div class="sidebar-header">
        <button id="btn-fechar-menu" aria-label="Fechar menu de navegação">✕</button>
    </div>

    <nav aria-label="Menu principal">
        <ul class="menu">
            <li class="menu-item"><a href="index.php">Início</a></li>
            <li class="menu-item"><a href="feed.php">Feed Geral</a></li>
            <li class="menu-item"><a href="feed.pessoal.php">Feed Pessoal</a></li>
            
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