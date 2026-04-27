<footer>
    <p><strong>Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    <strong>Entre em contato: 0800 7070 6969 ou um email para floorspotted.fev@outlook.com</strong>
    <p>&copy; <?php echo date('Y'); ?> Desenvolvido por Leonardo - Todos os Direitos Reservados</p>

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
        <source src="imagensfoto/chuva.mp3" type="audio/mpeg">
    </audio>

<script src="js/fenda-main.js"></script>
</footer>
</body>
</html>