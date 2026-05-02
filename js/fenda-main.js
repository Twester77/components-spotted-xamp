/* A FENDA - ENGINE CENTRAL (JavaScript Geral) */

// ============================================================
// 1. FUNÇÕES GLOBAIS DE CONTROLE
// ============================================================

window.setBolhasLocal = function (valor) {
    // 1. Atualiza o input hidden para que o PHP saiba o que salvar no banco
    const inputHidden = document.getElementById('input_pref_bolhas');
    if (inputHidden) {
        inputHidden.value = valor;
    }

    // 2. Gerencia as classes 'active' para o visual mudar na hora
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');

    if (valor === 1) {
        btnOn.classList.add('active');
        btnOff.classList.remove('active');
    } else {
        btnOff.classList.add('active');
        btnOn.classList.remove('active');
    }

    // 3. (Opcional) Se você quiser que as bolhas sumam ou apareçam sem dar F5:
    const containerBolhas = document.querySelector('.bubbles-container');
    if (containerBolhas) {
        containerBolhas.style.display = (valor === 1) ? 'block' : 'none';
    }
};

window.atualizarInterfaceBolhas = function (ligar) {
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');

    if (btnOn && btnOff) {
        if (ligar) {
            btnOn.style.backgroundColor = '#00a896';
            btnOff.style.backgroundColor = 'transparent';
        } else {
            btnOn.style.backgroundColor = 'transparent';
            btnOff.style.backgroundColor = '#ff4444';
        }
    }
};

// --- CONTROLE DA TOOLBAR (BÚSSOLA) ---
window.toggleToolbar = function() {
    const toolbar = document.getElementById('fenda-toolbar');
    const icon = document.getElementById('trigger-icon');
    if (!toolbar) return;

    toolbar.classList.toggle('toolbar-aberta');
    
    if(toolbar.classList.contains('toolbar-aberta')) {
        icon.innerText = '❌'; 
    } else {
        icon.innerText = '🧭'; 
    }
};

window.toggleHackerMode = function () {
    const body = document.body;
    // Pega todos os botões possíveis
    const btnNav = document.getElementById('hacker-toggle');
    const btnToolbar = document.getElementById('hacker-toggle-lateral');
    
    body.classList.toggle('hacker-mode');
    const isHacker = body.classList.contains('hacker-mode');
    
    localStorage.setItem('fenda_hacker', isHacker ? 'active' : 'inactive');
    
    const texto = isHacker ? '[ DESLIGAR_TERMINAL ]' : '[ ACESSAR_TERMINAL ]';
    
    if (btnNav) btnNav.innerHTML = texto;
    if (btnToolbar) btnToolbar.innerHTML = isHacker ? 'MODO_NORMAL' : 'MODO_TERMINAL';
};


/*------MODAL DE LOGOUT------*/
window.deslogar = function () {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'flex';
};

window.fecharModalSair = function () {
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


window.toggleMenu = function (menuId) {
    document.querySelectorAll('.options-menu-popup').forEach(menu => {
        if (menu.id !== menuId) menu.style.display = 'none';
    });
    const menu = document.getElementById(menuId);
    if (menu) menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
};

// ============================================================
// 2. INICIALIZAÇÃO E EVENTOS
// ============================================================

document.addEventListener("DOMContentLoaded", function () {
    atualizarInterfaceBolhas();

    let somAmbiente = localStorage.getItem('fenda_tipo_som') || 'off';
    let temaNotif = localStorage.getItem('fenda_tema_notif') || 'padrao';

    window.atualizarInterfaceAudio = function () {
        document.querySelectorAll('[id^="btn-som-"]').forEach(btn => btn.classList.remove('active'));
        const btnM = document.getElementById('btn-som-' + somAmbiente);
        if (btnM) btnM.classList.add('active');
        document.querySelectorAll('[id^="btn-notif-"]').forEach(btn => btn.classList.remove('active'));
        const btnN = document.getElementById('btn-notif-' + temaNotif);
        if (btnN) btnN.classList.add('active');
    };
    atualizarInterfaceAudio();

    window.mudarSomAmbiente = function (tipo) {
        somAmbiente = tipo;
        localStorage.setItem('fenda_tipo_som', tipo);
        let audio = document.getElementById('som-oceano');
        if (audio) {
            if (tipo === 'off') { audio.pause(); }
            else {
                audio.src = (tipo === 'chuva') ? 'sons/chuva.mp3' : 'sons/oceano.mp3';
                audio.volume = 0.02;
                audio.play().catch(() => { });
            }
        }
        atualizarInterfaceAudio();
    };

    window.mudarTemaNotif = function (tema) {
        temaNotif = tema;
        localStorage.setItem('fenda_tema_notif', tema);
        atualizarInterfaceAudio();
    };

    document.addEventListener('click', function () {
        var audio = document.getElementById('som-oceano');
        if (!audio || somAmbiente === 'off') return;
        if (audio.paused) {
            audio.src = (somAmbiente === 'chuva') ? 'sons/chuva.mp3' : 'sons/oceano.mp3';
            audio.volume = 0;
            audio.play().catch(() => { });
            var fadeIn = setInterval(function () {
                if (audio.volume < 0.03) { audio.volume += 0.005; }
                else { clearInterval(fadeIn); }
            }, 200);
        }
    }, { once: true });

    // Navbar Dropdown
    const dropdownLinks = document.querySelectorAll('.menu-item.dropdown > a');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function (e) {
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

    


    // ============================================================
    // RADAR DE ALERTAS (Versão Blindada com Memória de Sessão)
    // ============================================================

    window.atualizarContadorAlertas = function () {
        fetch('includes/contar_alertas.php')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('badge-alertas');

                if (badge) {
                    // Buscamos o último número que o usuário "ouviu" nesta aba
                    // Se não existir (primeira vez), o padrão é o que está no badge agora
                    let ultimoAviso = parseInt(sessionStorage.getItem('fenda_ultimo_aviso'));

                    // Se for a primeira execução da página, inicializamos com o valor atual do banco
                    // Isso evita que o som toque só porque você deu F5
                    if (isNaN(ultimoAviso)) {
                        sessionStorage.setItem('fenda_ultimo_aviso', data.total);
                        ultimoAviso = data.total;
                    }

                    // SÓ dispara o som e o banner se o total do banco for MAIOR que o último avisado
                    if (data.total > ultimoAviso) {
                        mostrarPopup("Nova interação na Fenda!");
                        // Atualiza a memória de sessão imediatamente para evitar disparos duplicados
                        sessionStorage.setItem('fenda_ultimo_aviso', data.total);
                    }

                    // Se o usuário leu as notificações, o número diminui. 
                    // Atualizamos a memória para que o próximo "aumento" dispare o som corretamente.
                    if (data.total < ultimoAviso) {
                        sessionStorage.setItem('fenda_ultimo_aviso', data.total);
                    }

                    // Atualiza o visual do badge (círculo vermelho)
                    badge.innerText = data.total;
                    badge.style.display = data.total > 0 ? 'flex' : 'none';
                }
            })
            .catch(err => console.log("Radar: Aguardando estabilidade da conexão..."));
    };

    // 1. Inicia o radar (executa a função a cada 8 segundos)
    setInterval(atualizarContadorAlertas, 8000);

    // 2. Chama uma vez logo que a página carrega (para não ter que esperar os primeiros 10s)
    atualizarContadorAlertas();

    // 3. Rodar configuração inicial dos posts 
    configurarPosts();

    // Fecha o DOMContentLoaded 
});

// ============================================================
// 3. SISTEMAS EXTERNOS E AJAX
// ============================================================

window.configurarPosts = function () {
    document.querySelectorAll('.post-content').forEach(post => {
        // Verifica se o texto é maior que o limite de 5rem (aprox 80px)
        if (post.scrollHeight > post.offsetHeight && !post.dataset.ouvinte) {
            post.classList.add('tem-mais'); // Adiciona a classe para mostrar o "... ler mais"
            post.style.cursor = "pointer";
            post.dataset.ouvinte = "true";
            post.onclick = function () { 
                this.classList.toggle('expandido'); 
            };
        }
    });
    console.log("Fenda: Posts reconfigurados.");
};


function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';
    let tempoExibicao = 5000;

    if (temaSalvo !== 'off') {
        let configuracaoSons = {
            'padrao': { arquivo: 'padrao.mp3', volume: 1.1 },
            'resident': { arquivo: 'resident.mp3', volume: 0.7 },
            'cs': { arquivo: 'cs.mp3', volume: 0.7 },
            'starwars': { arquivo: 'imperial-march.mp3', volume: 0.4 },
            'mario': { arquivo: 'mario-bros-1up.mp3', volume: 0.7 },
            'pokemon': { arquivo: 'pokemon_levelup.mp3', volume: 0.8 },
            'digimon': { arquivo: 'brave-heart_digimon.mp3', volume: 0.3 },
            'dbz': { arquivo: 'teletransporte_goku.mp3', volume: 0.6 },
            'naruto': { arquivo: 'naruto_shadow_clones.mp3', volume: 0.5 },
            'streetfighter': { arquivo: 'shoryuken.mp3', volume: 0.8 },
            'desgraca1': { arquivo: 'filosofo-piton-tudo-na-vida-e-pra-comer-alguem.mp3', volume: 0.4 },
            'desgraca2': { arquivo: 'eu-quero-dormir.mp3', volume: 0.3 }
        };

        if (temaSalvo === 'digimon') tempoExibicao = 11000;
        else if (temaSalvo === 'starwars') tempoExibicao = 9000;

        if (configuracaoSons[temaSalvo]) {
            let somEscolhido = configuracaoSons[temaSalvo];
            let bip = new Audio('sons/' + somEscolhido.arquivo);
            bip.volume = somEscolhido.volume;
            bip.play().catch(e => console.log("Áudio bloqueado:", e));

            bip.onloadedmetadata = function () {
                if (bip.duration > 3) {
                    setTimeout(() => {
                        let intervaloFade = setInterval(() => {
                            if (bip.volume > 0.03) bip.volume -= 0.03;
                            else {
                                bip.volume = 0;
                                bip.pause();
                                clearInterval(intervaloFade);
                            }
                        }, 50);
                    }, tempoExibicao - 1500);
                }
            };
        }
    }

    // --- PARTE VISUAL COM O "CLIQUE DA FENDA" ---
    const popup = document.createElement('div');
    popup.className = 'notificacao-popup';
    
    // Deixa o cursor com a mãozinha pra mostrar que é link
    popup.style.cursor = 'pointer'; 

    popup.innerHTML = `
        <div style="font-size: 20px;">🔔</div>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 13px; color: #ddc80e;">Nova Interação!</strong>
            <span>${mensagem}</span>
        </div>
    `;

    // A MÁGICA: Ao clicar em qualquer lugar do banner, vai para notificações
    popup.onclick = function() {
        window.location.href = 'notificacoes.php';
    };

    document.body.appendChild(popup);

    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 500);
    }, tempoExibicao);
}

// 1. Função para carregar as notificações na janelinha (sem sair da página)
window.toggleJanelaNotificacoes = function() {
    const box = document.getElementById('dropdown-notificacoes');
    
    // Se a janela estiver fechada, vamos carregar os dados antes de abrir
    if (box.style.display === 'none' || box.style.display === '') {
        fetch('notificacoes-rapidas.php') // Um arquivo novo, versão leve do notificacoes.php
            .then(res => res.text())
            .then(html => {
                box.innerHTML = html;
                box.style.display = 'block';
                
                // Limpa o badge visual já que o usuário abriu a lista
                const badge = document.getElementById('badge-alertas');
                if (badge) badge.style.display = 'none';
            });
    } else {
        box.style.display = 'none';
    }
};

// 2. Fechar a janelinha se clicar fora (igual você fez com os outros menus)[cite: 26]
window.addEventListener('click', function(e) {
    const box = document.getElementById('dropdown-notificacoes');
    if (box && !e.target.closest('.notificacao-wrapper')) {
        box.style.display = 'none';
    }
});

// --- BLOCO DE REAÇÕES ATUALIZADO ---
window.enviarReacao = function (postId, tipo) {
    fetch(`includes/reagir.php?id=${postId}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Usa a função global que já está declarada no arquivo
                window.atualizarInterfaceReacao(postId, data.contagens, data.minhas_reacoes);
            }
        })
        .catch(err => console.error("Erro na requisição:", err));
};

window.atualizarInterfaceReacao = function (postId, contagens, minhas = []) {
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
        const classeVoted = (minhas && minhas.includes(tipo)) ? 'voted' : '';

        const span = document.createElement('span');
        span.className = `reacao-item ${classeVoted}`;
        span.innerHTML = `${emoji} ${total}`;
        container.appendChild(span);
    });
};

// --- CONTROLE UNIFICADO DE CLIQUES GLOBAIS ---
window.addEventListener('click', function (event) {
    const modalSair = document.getElementById('modal-sair-fenda');
    if (event.target === modalSair) {
        window.fecharModalSair();
    }

    // Fecha o dropdown e o popup de reações se clicar fora
    if (!event.target.closest('.dropdown') && !event.target.closest('.reacao-wrapper') && !event.target.closest('.btn-reagir')) {
        document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
        document.querySelectorAll('.reacoes-popup').forEach(p => {
            p.style.visibility = 'hidden';
            p.style.opacity = '0';
        });
    }

    const box = document.getElementById('dropdown-notificacoes');
    if (box && !event.target.closest('.notificacao-wrapper')) {
        box.style.display = 'none';
    }
});

// Verificação de Boot e Modo Hacker salvo
window.addEventListener('load', () => {
    if (localStorage.getItem('fenda_hacker') === 'active') {
        document.body.classList.add('hacker-mode');
        const hBtn = document.getElementById('hacker-toggle-lateral') || document.getElementById('hacker-toggle');
        if (hBtn) hBtn.innerHTML = '[ DESLIGAR_TERMINAL ]';
    }
    const bootScreen = document.getElementById('bios-boot');
    if (bootScreen && !sessionStorage.getItem('boot_concluido')) {
        setTimeout(() => {
            bootScreen.style.opacity = '0';
            setTimeout(() => { bootScreen.style.display = 'none'; sessionStorage.setItem('boot_concluido', 'true'); }, 500);
        }, 2500);
    } else if (bootScreen) { bootScreen.style.display = 'none'; }
});