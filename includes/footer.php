<footer>
    <p><strong>Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    <strong>Entre em contato com a gente: 0800 7070 6969 ou mande um email para floorspotted.fev@outlook.com</strong>
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

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- 1. MODAL DE SAIR ---
        window.deslogar = function() {
            const modal = document.getElementById('modal-sair-fenda');
            if (modal) modal.style.display = 'flex';
        };

        window.fecharModalSair = function() {
            const modal = document.getElementById('modal-sair-fenda');
            if (modal) modal.style.display = 'none';
        };

        // --- 2. SOM DO OCEANO (FADE IN) ---
        document.addEventListener('click', function() {
            var audio = document.getElementById('som-oceano');
            if (!audio || !audio.paused) return;
            audio.volume = 0; 
            audio.play();
            var fadeIn = setInterval(function() {
                if (audio.volume < 0.03) { audio.volume += 0.01; } 
                else { audio.volume = 0.03; clearInterval(fadeIn); }
            }, 120); 
        }, { once: true });

        // --- 3. NAVBAR MOBILE ---
        const dropdownLinks = document.querySelectorAll('.menu-item.dropdown > a');
        dropdownLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    const pai = this.parentElement;
                    pai.classList.toggle('active');
                }
            });
        });

        
        // --- 4. REAÇÕES MOBILE (CORRIGIDO) ---
        const botoesReagir = document.querySelectorAll('.btn-reagir');
        botoesReagir.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if ('ontouchstart' in window) { 
                    e.preventDefault();
                    const popup = this.parentElement.querySelector('.reacoes-popup');
                    if (popup) {
                        const taVisivel = popup.style.visibility === 'visible';
                        popup.style.visibility = taVisivel ? 'hidden' : 'visible';
                        popup.style.opacity = taVisivel ? '0' : '1';
                        popup.style.transform = taVisivel ? 'translateX(-50%) translateY(10px)' : 'translateX(-50%) translateY(0)';
                    }
                }
            });
        });

        // --- 5. CLICAR FORA FECHA TUDO ---
        window.onclick = function(event) {
            const modalSair = document.getElementById('modal-sair-fenda');
            if (event.target == modalSair) fecharModalSair();
            if (!event.target.closest('.dropdown') && !event.target.closest('.reacao-wrapper')) {
                document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
                document.querySelectorAll('.reacoes-popup').forEach(p => {
                    p.style.visibility = 'hidden';
                    p.style.opacity = '0';
                });
            }
        };

        // --- 6. ALERTAS/NOTIFICAÇÕES ---
        window.atualizarContadorAlertas = function() {
            fetch('includes/contar_alertas.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('badge-alertas');
                    if (badge) {
                        badge.innerText = data.total;
                        badge.style.display = data.total > 0 ? 'block' : 'none';
                    }
                }).catch(err => console.error("Erro nos alertas:", err));
        };

        setInterval(atualizarContadorAlertas, 10000); 
        atualizarContadorAlertas();
    });

    // Função de Popup Global
    function mostrarPopup(mensagem) {
        const popup = document.createElement('div');
        popup.className = 'notificacao-popup';
        popup.innerHTML = `
            <div style="font-size: 20px;">🔔</div>
            <div style="flex-grow: 1;">
                <strong style="display: block; font-size: 13px;">Nova Menção!</strong>
                <span style="font-size: 13px;">${mensagem}</span>
            </div>
            <span onclick="this.parentElement.remove()" style="cursor:pointer; font-weight:bold; margin-left:10px;">×</span>
        `;
        popup.onclick = function(e) { if(e.target.innerText !== '×') window.location.href = 'notificacoes.php'; };
        document.body.appendChild(popup);
        setTimeout(() => { if (document.body.contains(popup)) popup.remove(); }, 8000);
    }
    
    </script>
</footer>
</body>
</html>