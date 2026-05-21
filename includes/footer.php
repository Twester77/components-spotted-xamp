<footer>
    <p><strong>Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    <strong>Entre em contato: (011) 1406 7070 7070 ou um email para contato.fev@fendauniversity.com.br</strong>
    <p>&copy; <?php echo date('Y'); ?> Desenvolvido por Leonardo - Todos os Direitos Reservados</p>

    <?php include_once 'toolbar.php'; ?>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <button class="fab-postar" onclick="abrirModalPost()" title="Sussurrar para a Fenda">
            <i class="fas fa-plus"></i>
        </button>

        <div id="modal-postar-fenda" class="fenda-modal-overlay" style="display:none;">
            <div class="fenda-modal-content">
                <span onclick="fecharModalPost()" style="position: absolute; top: 15px; right: 20px; color: #fff; font-size: 30px; cursor: pointer;">&times;</span>

                <div class="modal-body-ajuste">
                    <?php include 'includes/card-postar.php'; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="modal-sair-fenda" class="fenda-modal-overlay" style="display:none;">
        <div class="fenda-modal-content">
            <div class="fenda-modal-icon">⚓</div>
            <h2>Vai zarpar, marujo?</h2>
            <p>Tem certeza que deseja sair da Fenda e voltar para a terra firme?</p>
            <div class="fenda-modal-buttons">
                <button onclick="fecharModalSair()" class="btn-ficar-terra">⚓ Ficar em Terra Firme</button>
                <a href="logout.php" class="btn-zarpar">🌊 Zarpar (Sair)</a>
            </div>
        </div>
    </div>

    <audio id="som-oceano" loop preload="auto">
        <source src="sons/oceano.mp3" type="audio/mpeg">
    </audio>

<script src="js/fenda-main.js"></script>

<script src="js/fenda-swipe.js"></script>

</body>
</html>