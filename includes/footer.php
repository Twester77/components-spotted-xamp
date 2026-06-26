<?php
// footer.php
$u_id = $_SESSION['usuario_id'] ?? 0;
?>
<footer class="footer-global">

    <!-- ============================================ -->
    <!-- CONTEÚDO INSTITUCIONAL (será escondido no comentarios-post) -->
    <!-- ============================================ -->
    <div class="footer-texto-institucional">
        <p><strong>Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>

        <p class="fenda-contato">
            <strong>Entre em contato:
                <a href="tel:011140670707070">(011) 1406 7070 7070</a> ou um e-mail para
                <a href="mailto:contato.fev@fendauniversity.com.br">contato.fev@fendauniversity.com.br</a>
            </strong>
        </p>

        <p>&copy; <?php echo date('Y'); ?> Desenvolvido por Leonardo - Todos os Direitos Reservados</p>
    </div>
    <!-- FIM DO CONTEÚDO INSTITUCIONAL -->
</footer>

<!-- MODAIS, TOOLBARS, SCRIPTS E ÁUDIO (PERMANECEM EM TODAS AS PÁGINAS) -->

<?php if ($u_id > 0): ?>
    <div id="drawer-backdrop" class="drawer-backdrop" onclick="fecharPerfilDrawer()"></div>

    <div id="perfil-drawer"
        class="drawer-perfil-container"
        role="region"
        aria-label="Configurações de Perfil"
        aria-hidden="true">

        <button type="button" class="btn-fechar-drawer" onclick="fecharPerfilDrawer()" aria-label="Fechar">
            <i class="fas fa-times"></i>
        </button>

        <div id="conteudo-perfil"></div>
    </div>
<?php endif; ?>


<?php include_once 'toolbar.php'; ?>
<?php include 'nexus.php'; ?>

<?php if (isset($_SESSION['usuario_id'])): ?>
    <button type="button" class="fab-postar" onclick="abrirModalPost()" title="Sussurrar para a Fenda" aria-label="Sussurrar para a Fenda (Criar nova publicação)">
        <i class="fas fa-plus" aria-hidden="true"></i>
    </button>

    <div id="modal-postar-fenda" class="fenda-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="titulo-modal-postar">
        <div class="fenda-modal-content">
            <h2 id="titulo-modal-postar" class="sr-only">Criar Nova Publicação</h2>
            <button type="button" onclick="fecharModalPost()" class="btn-fechar-modal" aria-label="Fechar janela modal">&times;</button>
            <div class="modal-body-ajuste">
                <?php include 'includes/card-postar.php'; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Sair -->
<div id="modal-sair-fenda" class="fenda-modal-overlay" style="display:none;" role="alertdialog" aria-modal="true" aria-hidden="true" aria-labelledby="titulo-modal-sair" aria-describedby="desc-modal-sair">
    <div class="fenda-modal-content">
        <div class="fenda-modal-icon" aria-hidden="true">⚓</div>
        <h2 id="titulo-modal-sair">Vai zarpar, marujo?</h2>
        <p id="desc-modal-sair">Tem certeza que deseja sair da Fenda e voltar para a terra firme?</p>
        <div class="fenda-modal-buttons">
            <button type="button" onclick="fecharModalSair()" class="btn-ficar-terra"> Ficar em Terra Firme</button>
            <a href="logout.php" class="btn-zarpar" role="button"> Zarpar (Sair)</a>
        </div>
    </div>
</div>

<!-- Áudio de background -->
<audio id="som-oceano" loop preload="auto" aria-hidden="true">
    <source src="sons/oceano.mp3" type="audio/mpeg">
</audio>

<?php if (isset($_SESSION['usuario_id'])):
   $stmt_footer = $conn->prepare("SELECT pref_som_trilha, pref_som_notif, pref_bolhas, pref_pip, pref_badge FROM usuarios WHERE id = ?");
    $stmt_footer->bind_param("i", $_SESSION['usuario_id']);
    $stmt_footer->execute();
    $res_pref = $stmt_footer->get_result()->fetch_assoc();
?>
    <input type="hidden" name="pref_som_trilha" id="input_pref_som_trilha" value="<?php echo $res_pref['pref_som_trilha'] ?? 'ondas'; ?>">
    <input type="hidden" name="pref_som_notif" id="input_pref_som_notif" value="<?php echo $res_pref['pref_som_notif'] ?? 'padrao'; ?>">
    <input type="hidden" name="pref_bolhas" id="input_pref_bolhas" value="<?php echo $res_pref['pref_bolhas'] ?? 1; ?>">
    <input type="hidden" name="pref_pip" id="input_pref_pip" value="<?php echo $res_pref['pref_pip'] ?? 0; ?>">
    <input type="hidden" name="pref_badge" id="input_pref_badge" value="<?php echo $res_pref['pref_badge'] ?? 1; ?>">
<?php endif; ?>


<!-- ==================== DIALOG DE CONFIRMAÇÃO (NATIVO - GLOBAL) ==================== -->
<dialog id="dialog-confirmacao" class="dialog-confirmacao">
    <div class="dialog-conteudo">
        <h3 id="dialog-titulo">⚠️ Confirmação</h3>
        <p id="dialog-mensagem">Deseja realmente excluir?</p>
        <div class="dialog-botoes">
            <button id="dialog-btn-sim" class="dialog-btn dialog-btn-sim">SIM, EXCLUIR</button>
            <button id="dialog-btn-nao" class="dialog-btn dialog-btn-nao">CANCELAR</button>
        </div>
    </div>
</dialog>

<script src="js/fenda-main.js"></script>

<!-- ========================================== -->
<!-- REGISTRO DO SERVICE WORKER (PWA)           -->
<!-- ========================================== -->
<script>
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('[PWA] Service Worker registrado com sucesso:', registration.scope);
                })
                .catch(function(error) {
                    console.log('[PWA] Falha no registro do Service Worker:', error);
                });
        });
    }
</script>

</body>
</html>