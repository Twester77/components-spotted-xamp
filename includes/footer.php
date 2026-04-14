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

    <audio id="som-oceano" loop preload="auto"></audio>
    <source src="imagensfoto/chuva.mp3" type="audio/mpeg">
    </audio>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            //  MODAL DE SAIR 
            window.deslogar = function() {
                const modal = document.getElementById('modal-sair-fenda');
                if (modal) modal.style.display = 'flex';
            };

            window.fecharModalSair = function() {
                const modal = document.getElementById('modal-sair-fenda');
                if (modal) modal.style.display = 'none';
            };

           // SISTEMA DE ÁUDIO (MÚSICA E RADAR) 
    let somAmbiente = localStorage.getItem('fenda_tipo_som') || 'off'; 
    let temaNotif = localStorage.getItem('fenda_tema_notif') || 'padrao';

    // Função para atualizar as cores dos botões na tela
    window.atualizarInterfaceAudio = function() {
        // Botões de Música (Chuva/Ondas)
        document.querySelectorAll('[id^="btn-som-"]').forEach(btn => btn.classList.remove('active'));
        const btnMusicaAtivo = document.getElementById('btn-som-' + somAmbiente);
        if(btnMusicaAtivo) btnMusicaAtivo.classList.add('active');

        // Botões de Notificação (Beep/Resident)
        document.querySelectorAll('[id^="btn-notif-"]').forEach(btn => btn.classList.remove('active'));
        const btnNotifAtivo = document.getElementById('btn-notif-' + temaNotif);
        if(btnNotifAtivo) btnNotifAtivo.classList.add('active');
    };

    // Função para mudar a Música de Fundo
    window.mudarSomAmbiente = function(tipo) {
        somAmbiente = tipo;
        localStorage.setItem('fenda_tipo_som', tipo);
        let audio = document.getElementById('som-oceano');
        if (audio) {
            if (tipo === 'off') { 
                audio.pause(); 
            } else {
                audio.src = (tipo === 'chuva') ? 'imagensfoto/chuva.mp3' : 'imagensfoto/ondas.mp3';
                audio.volume = 0.02;
                audio.play().catch(() => {});
            }
        }
        atualizarInterfaceAudio();
    };

    // Função para mudar o tema da Notificação
  window.mudarTemaNotif = function(tema) {
    temaNotif = tema;
    localStorage.setItem('fenda_tema_notif', tema);
    
    if (tema !== 'off') {
        // Mapeia o nome do tema para o nome do arquivo
        let sons = {
            'padrao': 'notificacao.mp3',
            'resident': 'resident.mp3',
            'cs': 'cs.mp3'
        };
        
    }
    atualizarInterfaceAudio();
};

    // Primeiro Clique: Ativa a Vibe escolhida com Fade In
    document.addEventListener('click', function() {
        var audio = document.getElementById('som-oceano');
        if (!audio || somAmbiente === 'off') return;
        
        if (audio.paused) {
            audio.src = (somAmbiente === 'chuva') ? 'imagensfoto/chuva.mp3' : 'imagensfoto/ondas.mp3';
            audio.volume = 0;
            audio.play().catch(() => {});
            
            var fadeIn = setInterval(function() {
                if (audio.volume < 0.02) { audio.volume += 0.005; } 
                else { clearInterval(fadeIn); }
            }, 200);
        }
    }, { once: true });

    //  NAVBAR MOBILE 
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

    // REAÇÕES MOBILE 
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

    // CLICAR FORA FECHA TUDO 
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

    //  ALERTAS/NOTIFICAÇÕES (CONTADOR) 
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
    atualizarInterfaceAudio();

    // Configurar expansão de posts
    function configurarPosts() {
        document.querySelectorAll('.post-content').forEach(post => {
            if (post.scrollHeight > post.offsetHeight) {
                post.style.cursor = "pointer";
                post.onclick = function() { this.classList.toggle('expandido'); };
            }
        });
    }
    setTimeout(configurarPosts, 500);

}); // FIM DO DOMCONTENTLOADED

// FUNÇÕES GLOBAIS (FORA DO DOMCONTENTLOADED) 

function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';

    if (temaSalvo !== 'off') {
        let sons = {
            'padrao': 'notificacao.mp3',
            'resident': 'resident.mp3',
            'cs': 'cs.mp3'
        };
        
        let bip = new Audio('imagensfoto/' + sons[temaSalvo]);
        // Ajuste de volumes específicos
        bip.volume = (temaSalvo === 'resident' || temaSalvo === 'cs') ? 0.6 : 0.3;
        bip.play().catch(() => {});
    }

    const popup = document.createElement('div');
    popup.className = 'notificacao-popup';
    popup.innerHTML = `
        <div style="font-size: 20px;">🔔</div>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 13px; color: #ddc80e;">Nova Interação!</strong>
            <span style="font-size: 13px;">${mensagem}</span>
        </div>
        <span onclick="event.stopPropagation(); this.parentElement.remove()" style="cursor:pointer; font-weight:bold; padding: 5px;">×</span>
    `;

    popup.onclick = function(e) {
        if (e.target.innerText !== '×') window.location.href = 'notificacoes.php';
    };

    document.body.appendChild(popup);
    setTimeout(() => {
        if (popup) {
            popup.style.opacity = '0';
            popup.style.transform = 'translateX(100px)';
            setTimeout(() => popup.remove(), 500);
        }
    }, 5000);
}

// RADAR
setInterval(function() {
    fetch('/spotted-unifev/includes/checar-notificacoes.php')
        .then(res => res.json())
        .then(data => {
            if (data.tem) {
                mostrarPopup(data.msg);
                if (typeof atualizarContadorAlertas === 'function') atualizarContadorAlertas();
            }
        })
        .catch(err => console.error("Radar offline:", err));
}, 5000);
    </script>
</footer>
</body>

</html>