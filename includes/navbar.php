<aside id="sidebar-fenda" class="sidebar-fenda" aria-hidden="true">
    <div class="sidebar-header">
        <button id="btn-fechar-menu" aria-label="Fechar menu de navegação">✕</button>
    </div>

    <nav aria-label="Menu principal">
        <ul class="menu">
            <!-- ============================================================
                 SEÇÃO: INÍCIO E GERAL
                 ============================================================ -->
            <li class="menu-item"><a href="index.php"><i class="fas fa-home"></i> Início</a></li>
            <li class="menu-item"><a href="feed.php"><i class="fas fa-rss"></i> Feed Geral</a></li>

            <!-- ============================================================
                 SEÇÃO: SOCIAL (Feed, Comunidades, Eventos)
                 ============================================================ -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- 🔥 NOVO MENU EVENTOS COM 3 SUBITENS -->
                <li class="menu-item dropdown">
                    <a href="#" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-calendar-alt"></i> Eventos
                    </a>
                    <ul class="submenu">
                        <li><a href="balanga-teras.php?modo=grid"><i class="fas fa-list"></i> Balanga Teras</a></li>
                        <li><a href="criar-evento.php"><i class="fas fa-plus-circle"></i> Criar Evento</a></li>
                    </ul>
                </li>
                <li class="menu-item"><a href="lista-comunidades.php"><i class="fas fa-users"></i> Comunidades</a></li>
            <?php endif; ?>

            <!-- ============================================================
                 SEÇÃO: PERFIL E CONFIGURAÇÕES
                 ============================================================ -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li class="menu-item"><a href="central.php"><i class="fas fa-user-circle"></i> Central do Habitante</a></li>
                <li class="menu-item"><a href="ver-perfil.php?user=<?php echo $_SESSION['usuario_username'] ?? ''; ?>"><i class="fas fa-eye"></i> Perfil Público</a></li>
                <li class="menu-item"><a href="perfil.php"><i class="fas fa-cog"></i> Configurações</a></li>
            <?php endif; ?>

            <!-- ============================================================
                 SEÇÃO: UTILIDADES
                 ============================================================ -->
            <li class="menu-item dropdown">
                <a href="#" aria-haspopup="true" aria-expanded="false"><i class="fas fa-toolbox"></i> Utilidades</a>
                <ul class="submenu">
                    <li><a href="perdidos.php"><i class="fas fa-search"></i> Achados & Perdidos</a></li>
                    <li><a href="classificados.php"><i class="fas fa-store"></i> Classificados</a></li>
                </ul>
            </li>

            <!-- ============================================================
                 SEÇÃO: INSTITUCIONAL
                 ============================================================ -->
            <li class="menu-item dropdown">
                <a href="#" aria-haspopup="true" aria-expanded="false"><i class="fas fa-info-circle"></i> Institucional</a>
                <ul class="submenu">
                    <li><a href="quem-somos.php"><i class="fas fa-flag"></i> Quem Somos</a></li>
                    <li><a href="diretrizes.php"><i class="fas fa-gavel"></i> Regras da Casa</a></li>
                </ul>
            </li>

            <!-- ============================================================
                 SEÇÃO: SAIR / ENTRAR
                 ============================================================ -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                    <a href="#" onclick="event.preventDefault(); deslogarUsuario();" style="color: #ff6b6b;">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </li>
            <?php else: ?>
                <li class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                    <a href="index.php"><i class="fas fa-sign-in-alt"></i> Entrar</a>
                </li>
            <?php endif; ?>
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