/* A FENDA - ENGINE CENTRAL (JavaScript Geral) */

// ============================================================
// 1. FUNÇÕES GLOBAIS DE CONTROLE
// ============================================================

window.setBolhas = function(ligar) {
    const container = document.querySelector('.bubbles-container');
    const status = ligar ? 'active' : 'inactive';
    localStorage.setItem('fenda_bolhas', status);
    if (container) container.style.display = (ligar) ? 'block' : 'none';
    atualizarInterfaceBolhas();
};

window.atualizarInterfaceBolhas = function() {
    const status = localStorage.getItem('fenda_bolhas') || 'active';
    const container = document.querySelector('.bubbles-container');
    if (container) container.style.display = (status === 'active') ? 'block' : 'none';
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');
    if (btnOn) btnOn.style.backgroundColor = (status === 'active') ? '#00a896' : 'transparent';
    if (btnOff) btnOff.style.backgroundColor = (status === 'inactive') ? '#ff4444' : 'transparent';
};

window.deslogar = function() {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'flex';
};

window.fecharModalSair = function() {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'none';
};

function confirmarExclusao(idPost) {
    if (confirm(" ATENÇÃO: Deseja mesmo apagar esse registro?")) {
        window.location.href = 'includes/excluir.php?id=' + idPost;
    }
}

function abrirDenuncia(idPost) {
    alert("Denúncia do post #" + idPost + " enviada aos ADMs.");
}

window.toggleHackerMode = function() {
    const body = document.body;
    const btn = document.getElementById('hacker-toggle');
    body.classList.toggle('hacker-mode');
    const isHacker = body.classList.contains('hacker-mode');
    localStorage.setItem('fenda_hacker', isHacker ? 'active' : 'inactive');
    if(btn) btn.innerHTML = isHacker ? '[ DESLIGAR_TERMINAL ]' : '[ ACESSAR_TERMINAL ]';
};

window.toggleMenu = function(menuId) {
    document.querySelectorAll('.options-menu-popup').forEach(menu => {
        if (menu.id !== menuId) menu.style.display = 'none';
    });
    const menu = document.getElementById(menuId);
    if(menu) menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
};

// ============================================================
// 2. INICIALIZAÇÃO E EVENTOS
// ============================================================

document.addEventListener("DOMContentLoaded", function() {
    atualizarInterfaceBolhas();

    let somAmbiente = localStorage.getItem('fenda_tipo_som') || 'off';
    let temaNotif = localStorage.getItem('fenda_tema_notif') || 'padrao';

    window.atualizarInterfaceAudio = function() {
        document.querySelectorAll('[id^="btn-som-"]').forEach(btn => btn.classList.remove('active'));
        const btnM = document.getElementById('btn-som-' + somAmbiente);
        if(btnM) btnM.classList.add('active');
        document.querySelectorAll('[id^="btn-notif-"]').forEach(btn => btn.classList.remove('active'));
        const btnN = document.getElementById('btn-notif-' + temaNotif);
        if(btnN) btnN.classList.add('active');
    };
    atualizarInterfaceAudio();

    window.mudarSomAmbiente = function(tipo) {
        somAmbiente = tipo;
        localStorage.setItem('fenda_tipo_som', tipo);
        let audio = document.getElementById('som-oceano');
        if (audio) {
            if (tipo === 'off') { audio.pause(); } 
            else {
                audio.src = (tipo === 'chuva') ? 'imagensfoto/chuva.mp3' : 'imagensfoto/ondas.mp3';
                audio.volume = 0.06;
                audio.play().catch(() => {});
            }
        }
        atualizarInterfaceAudio();
    };

    window.mudarTemaNotif = function(tema) {
        temaNotif = tema;
        localStorage.setItem('fenda_tema_notif', tema);
        atualizarInterfaceAudio();
    };

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

    // Navbar Dropdown
    const dropdownLinks = document.querySelectorAll('.menu-item.dropdown > a');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
            if (isTouch || window.innerWidth <= 1024) {
                const pai = this.parentElement;
                if (!pai.classList.contains('active')) {
                    e.preventDefault();
                    document.querySelectorAll('.menu-item.dropdown').forEach(item => {
                        if (item !== pai) item.classList.remove('active');
                    });
                    pai.classList.add('active');
                } 
            }
        });
    });

    // Radar de Alertas
    window.atualizarContadorAlertas = function() {
        fetch('includes/contar_alertas.php').then(res => res.json()).then(data => {
            const badge = document.getElementById('badge-alertas');
            if (badge) {
                badge.innerText = data.total;
                badge.style.display = data.total > 0 ? 'block' : 'none';
            }
        }).catch(() => {});
    };
    setInterval(atualizarContadorAlertas, 10000);
    atualizarContadorAlertas();

    // Rodar configuração inicial
    configurarPosts(); 
});

// ============================================================
// 3. SISTEMAS EXTERNOS E AJAX
// ============================================================

window.configurarPosts = function() {
    document.querySelectorAll('.post-content').forEach(post => {
        if (post.scrollHeight > post.offsetHeight && !post.dataset.ouvinte) {
            post.style.cursor = "pointer";
            post.dataset.ouvinte = "true"; 
            post.onclick = function() { this.classList.toggle('expandido'); };
        }
    });
    console.log("Fenda: Posts reconfigurados.");
};

function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';
    if (temaSalvo !== 'off') {
        let sons = { 'padrao': 'notificacao.mp3', 'resident': 'resident.mp3', 'cs': 'cs.mp3' };
        let bip = new Audio('imagensfoto/' + sons[temaSalvo]);
        bip.volume = 0.3;
        bip.play().catch(() => {});
    }
    const popup = document.createElement('div');
    popup.className = 'notificacao-popup';
    popup.innerHTML = `<div style="font-size: 20px;">🔔</div><div style="flex-grow: 1;"><strong style="display: block; font-size: 13px; color: #ddc80e;">Nova Interação!</strong><span>${mensagem}</span></div>`;
    document.body.appendChild(popup);
    setTimeout(() => { popup.style.opacity = '0'; setTimeout(() => popup.remove(), 500); }, 5000);
}

// --- BLOCO DE REAÇÕES ATUALIZADO ---
function enviarReacao(postId, tipo) {
    fetch(`includes/reagir.php?id=${postId}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => { 
            if(data.status === 'success') {
                // Aqui está o segredo: usar o nome exato que o PHP envia
                atualizarInterfaceReacao(postId, data.contagens, data.minhas_reacoes); 
            } 
        })
        .catch(err => console.error("Erro na requisição:", err));
}

window.atualizarInterfaceReacao = function(postId, contagens, minhas = []) {
    const container = document.getElementById(`reacoes-post-${postId}`);
    if (!container) return;

    const tradutor = {
        'amei': '💖', 'perplecto': '😲', 'haha': '😂', 
        'ranco': '🙄', 'forca': '🫂', 'triste': '😢', 'tendi-nada': '🤔'
    };

    container.innerHTML = ''; 

    Object.keys(contagens).forEach(tipo => {
        const emoji = tradutor[tipo] || '👍';
        const total = contagens[tipo];
        
        // Verifica se este emoji específico está na lista de reações do usuário
        const classeVoted = (minhas && minhas.includes(tipo)) ? 'voted' : '';
        
        const span = document.createElement('span');
        span.className = `reacao-item ${classeVoted}`;
        span.innerHTML = `${emoji} ${total}`;
        container.appendChild(span);
    });
};

window.onclick = function(event) {
    const modalSair = document.getElementById('modal-sair-fenda');
    if (event.target == modalSair) fecharModalSair();
    if (!event.target.closest('.dropdown') && !event.target.closest('.reacao-wrapper') && !event.target.closest('.btn-reagir')) {
        document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
        document.querySelectorAll('.reacoes-popup').forEach(p => {
            p.style.visibility = 'hidden';
            p.style.opacity = '0';
        });
    }
};

window.addEventListener('load', () => {
    if (localStorage.getItem('fenda_hacker') === 'active') {
        document.body.classList.add('hacker-mode');
        const hBtn = document.getElementById('hacker-toggle');
        if(hBtn) hBtn.innerHTML = '[ DESLIGAR_TERMINAL ]';
    }
    const bootScreen = document.getElementById('bios-boot');
    if (bootScreen && !sessionStorage.getItem('boot_concluido')) {
        setTimeout(() => {
            bootScreen.style.opacity = '0';
            setTimeout(() => { bootScreen.style.display = 'none'; sessionStorage.setItem('boot_concluido', 'true'); }, 500);
        }, 2500);
    } else if (bootScreen) { bootScreen.style.display = 'none'; }
});