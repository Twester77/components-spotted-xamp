<aside id="sidebar-fenda" class="sidebar-fenda" aria-hidden="true" inert>
    <div class="sidebar-header">
        <button id="btn-fechar-menu" aria-label="Fechar menu de navegação">✕</button>
    </div>

    <nav aria-label="Menu principal">
        <ul class="menu">
            <!-- ============================================================
                 SEÇÃO: INÍCIO E GERAL
                 ============================================================ -->
            <li class="menu-item"><a href="index.php"><i class="fas fa-home" aria-hidden="true"></i> Início</a></li>
            <li class="menu-item"><a href="feed.php"><i class="fas fa-rss" aria-hidden="true"></i> Feed Geral</a></li>

            <!-- ============================================================
                 SEÇÃO: SOCIAL (Feed, Comunidades, Eventos)
                 ============================================================ -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- 🔥 NOVO MENU EVENTOS COM 3 SUBITENS (ECO-05: Botão em vez de Link) -->
                <li class="menu-item dropdown">
                    <button type="button" class="menu-trigger" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i> Eventos
                    </button>
                    <ul class="submenu">
                        <li><a href="balanga-teras.php?modo=grid"><i class="fas fa-list" aria-hidden="true"></i> Balanga Teras</a></li>
                        <li><a href="criar-evento.php"><i class="fas fa-plus-circle" aria-hidden="true"></i> Criar Evento</a></li>
                    </ul>
                </li>
                <li class="menu-item"><a href="lista-comunidades.php"><i class="fas fa-users" aria-hidden="true"></i> Comunidades</a></li>
            <?php endif; ?>

            <!-- ============================================================
                 SEÇÃO: PERFIL E CONFIGURAÇÕES
                 ============================================================ -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li class="menu-item"><a href="central.php"><i class="fas fa-user-circle" aria-hidden="true"></i> Central do Habitante</a></li>
                <li class="menu-item"><a href="ver-perfil.php?user=<?php echo $_SESSION['usuario_username'] ?? ''; ?>"><i class="fas fa-eye" aria-hidden="true"></i> Perfil Público</a></li>
                <li class="menu-item"><a href="perfil.php"><i class="fas fa-cog" aria-hidden="true"></i> Configurações</a></li>
            <?php endif; ?>

            <!-- ============================================================
                 SEÇÃO: UTILIDADES
                 ============================================================ -->
            <li class="menu-item dropdown">
                <button type="button" class="menu-trigger" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-toolbox" aria-hidden="true"></i> Utilidades
                </button>
                <ul class="submenu">
                    <li><a href="perdidos.php"><i class="fas fa-search" aria-hidden="true"></i> Achados & Perdidos</a></li>
                    <li><a href="classificados.php"><i class="fas fa-store" aria-hidden="true"></i> Classificados</a></li>
                </ul>
            </li>

            <!-- ============================================================
                 SEÇÃO: INSTITUCIONAL
                 ============================================================ -->
            <li class="menu-item dropdown">
                <button type="button" class="menu-trigger" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-info-circle" aria-hidden="true"></i> Institucional
                </button>
                <ul class="submenu">
                    <li><a href="quem-somos.php"><i class="fas fa-flag" aria-hidden="true"></i> Quem Somos</a></li>
                    <li><a href="diretrizes.php"><i class="fas fa-gavel" aria-hidden="true"></i> Regras da Casa</a></li>
                </ul>
            </li>

            <!-- ============================================================
                 SEÇÃO: SAIR / ENTRAR
                 ============================================================ -->
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <li class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                    <a href="#" onclick="event.preventDefault(); deslogarUsuario();" style="color: #ff6b6b;">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Sair
                    </a>
                </li>
            <?php else: ?>
                <li class="menu-item" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                    <a href="index.php"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Entrar</a>
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
            // 🔥 ECO-01: Devolve o foco ao botão hambúrguer antes de ocultar
            if (btnMenu) btnMenu.focus();

            // 🔥 ECO-02: Aplica inert ao fechar
            sidebar.inert = true;
            sidebar.classList.remove('ativa');
            overlay.classList.remove('ativa');
            btnMenu.setAttribute('aria-expanded', 'false');
            sidebar.setAttribute('aria-hidden', 'true');
        }

        if (btnMenu) btnMenu.addEventListener('click', () => {
            // 🔥 ECO-02: Remove inert ao abrir
            sidebar.inert = false;
            sidebar.classList.add('ativa');
            overlay.classList.add('ativa');
            btnMenu.setAttribute('aria-expanded', 'true');
            sidebar.setAttribute('aria-hidden', 'false');
            if (btnFechar) btnFechar.focus();
        });

        if (btnFechar) btnFechar.addEventListener('click', fecharTudo);
        if (overlay) overlay.addEventListener('click', fecharTudo);
    });
</script>