<footer>
    <p><strong>Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    
    <!-- Corrigido para parágrafo com as tags de link corretas para telefone e e-mail -->
    <p class="fenda-contato">
        <strong>Entre em contato: 
            <a href="tel:011140670707070">(011) 1406 7070 7070</a> ou um e-mail para 
            <a href="mailto:contato.fev@fendauniversity.com.br">contato.fev@fendauniversity.com.br</a>
        </strong>
    </p>
    
    <p>&copy; <?php echo date('Y'); ?> Desenvolvido por Leonardo - Todos os Direitos Reservados</p>

    <?php include_once 'toolbar.php'; ?>

    <?php if (isset($_SESSION['usuario_id'])): ?>
        <!-- FAB Button ganha tipo explícito e aria-label descrevendo a ação do ícone -->
        <button type="button" class="fab-postar" onclick="abrirModalPost()" title="Sussurrar para a Fenda" aria-label="Sussurrar para a Fenda (Criar nova publicação)">
            <i class="fas fa-plus" aria-hidden="true"></i>
        </button>

        <!-- Modal ganha papel de diálogo, rótulo descritivo e esconde do leitor de tela enquanto display:none via aria-hidden -->
        <div id="modal-postar-fenda" class="fenda-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="titulo-modal-postar">
            <div class="fenda-modal-content">
                <!-- Título oculto visualmente ou visível para identificar o diálogo -->
                <h2 id="titulo-modal-postar" class="sr-only">Criar Nova Publicação</h2>
                
                <!-- Botão de fechar transformado em tag <button> legítima com aria-label -->
                <button type="button" onclick="fecharModalPost()" class="btn-fechar-modal" aria-label="Fechar janela modal">&times;</button>

                <div class="modal-body-ajuste">
                    <?php include 'includes/card-postar.php'; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal de Sair configurado corretamente para acessibilidade -->
    <div id="modal-sair-fenda" class="fenda-modal-overlay" style="display:none;" role="alertdialog" aria-modal="true" aria-hidden="true" aria-labelledby="titulo-modal-sair" aria-describedby="desc-modal-sair">
        <div class="fenda-modal-content">
            <!-- Ícone decorativo ocultado -->
            <div class="fenda-modal-icon" aria-hidden="true">⚓</div>
            <h2 id="titulo-modal-sair">Vai zarpar, marujo?</h2>
            <p id="desc-modal-sair">Tem certeza que deseja sair da Fenda e voltar para a terra firme?</p>
            <div class="fenda-modal-buttons">
                <button type="button" onclick="fecharModalSair()" class="btn-ficar-terra">⚓ Ficar em Terra Firme</button>
                <a href="logout.php" class="btn-zarpar" role="button">🌊 Zarpar (Sair)</a>
            </div>
        </div>
    </div>

    <!-- Áudio de background ocultado para leitores de tela -->
    <audio id="som-oceano" loop preload="auto" aria-hidden="true">
        <source src="sons/oceano.mp3" type="audio/mpeg">
    </audio>

</footer>

<script src="js/fenda-main.js"></script>
</body>
</html>
