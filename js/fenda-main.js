/* A FENDA - ENGINE CENTRAL (JavaScript Geral) */

// 1. FUNÇÕES GLOBAIS DE CONTROLE
window.audioLiberado = false; // só pra não bugar

window.setBolhasLocal = function (valor) {
    const inputHidden = document.getElementById('input_pref_bolhas');
    if (inputHidden) {
        inputHidden.value = valor;
    }

    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');

    if (valor === 1) {
        btnOn.classList.add('active');
        btnOff.classList.remove('active');
    } else {
        btnOff.classList.add('active');
        btnOn.classList.remove('active');
    }

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
window.toggleToolbar = function () {
    const toolbar = document.getElementById('fenda-toolbar');
    const icon = document.getElementById('trigger-icon');
    if (!toolbar) return;

    toolbar.classList.toggle('toolbar-aberta');

    if (toolbar.classList.contains('toolbar-aberta')) {
        icon.innerText = '❌';
    } else {
        icon.innerText = '🧭';
    }
};

window.toggleHackerMode = function () {
    const body = document.body;
    const btnNav = document.getElementById('hacker-toggle');
    const btnToolbar = document.getElementById('hacker-toggle-lateral');

    body.classList.toggle('hacker-mode');
    const isHacker = body.classList.contains('hacker-mode');

    localStorage.setItem('fenda_hacker', isHacker ? 'active' : 'inactive');

    const texto = isHacker ? '[ DESLIGAR_TERMINAL ]' : '[ ACESSAR_TERMINAL ]';

    if (btnNav) btnNav.innerHTML = texto;
    if (btnToolbar) btnToolbar.innerHTML = isHacker ? 'MODO_NORMAL' : 'MODO_TERMINAL';

    window.removerBordasInlineHacker();
};

// --- FUNÇÃO PARA REMOVER BORDAS INLINE NO MODO HACKER ---
window.removerBordasInlineHacker = function () {
    if (!document.body.classList.contains('hacker-mode')) return;

    document.querySelectorAll('.spotted-card').forEach(card => {
        card.style.borderLeft = '';
        card.style.borderRight = '';
        card.style.borderLeftColor = '';
        card.style.borderRightColor = '';
        card.style.border = '';
    });

    document.querySelectorAll('.comentario-item').forEach(comentario => {
        comentario.style.borderLeft = '';
        comentario.style.borderRight = '';
        comentario.style.borderLeftColor = '';
        comentario.style.borderRightColor = '';
    });
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

// Abrir o Modal de Postagem
function abrirModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-aberto');
        document.body.style.overflow = 'hidden';
    }
}

// Fechar o Modal de Postagem
function fecharModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-aberto');
        document.body.style.overflow = 'auto';
    }
}

// --- SISTEMA DE RESPOSTA HÍBRIDO ---
window.prepararResposta = function (id, username) {
    const inputParent = document.getElementById('input_parent_id');
    const campo = document.querySelector('.textarea-fenda');

    if (inputParent && campo) {
        inputParent.value = id;
        const novaMencao = "@" + username.trim() + " ";
        const regexMencaoQualquer = /^@[a-zA-Z0-9._-]+\s/;

        if (regexMencaoQualquer.test(campo.value)) {
            campo.value = campo.value.replace(regexMencaoQualquer, novaMencao);
        } else {
            campo.value = novaMencao + campo.value;
        }

        campo.placeholder = "Respondendo a " + username.trim() + "...";
        campo.focus();

        const areaFofocar = document.getElementById('fofocar');
        if (areaFofocar) areaFofocar.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

// === GAVETA DE CONTROLE (MOBILE) ===
window.abrirGavetaControle = function () {
    const gaveta = document.getElementById('gaveta-pessoal');
    if (gaveta) {
        const displayAtual = window.getComputedStyle(gaveta).display;
        if (displayAtual === 'none') {
            gaveta.style.display = 'block';
            gaveta.scrollIntoView({ behavior: 'smooth' });
        } else {
            gaveta.style.display = 'none';
        }
    }
};

// ==================== SWIPE / TINDER MODE ====================

// 1. Função Central que controla a aparência e o comportamento 
window.alternarInterfaceSwipe = function (ativar) {
    const body = document.body;
    const container = document.querySelector('.container-feed');
    if (!container) return;

    if (ativar) {
        body.classList.add('modo-swipe-ativo');
        container.classList.add('feed-empilhado');
        window.iniciarFisicaSwipe(); // Liga o Hammer.js
        console.log("Fenda Engine: Modo Swipe Calibrado.");
    } else {
        body.classList.remove('modo-swipe-ativo', 'card-isolado');
        container.classList.remove('feed-empilhado');
        // Limpa qualquer card que tenha ficado focado (zoom)
        document.querySelectorAll('.focado-swipe').forEach(c => c.classList.remove('focado-swipe'));
    }
};

window.iniciarFisicaSwipe = function () {
    const container = document.querySelector('.container-feed');
    if (!container || !container.classList.contains('feed-empilhado')) return;

    const cards = container.querySelectorAll('.spotted-card');
    if (cards.length === 0) return;

    const cardTopo = cards[0];
    cardTopo.style.zIndex = "100";

    const mc = new Hammer(cardTopo);
    mc.add(new Hammer.Pan({ direction: Hammer.DIRECTION_ALL, threshold: 50 }));

    mc.on("pan", (ev) => {
        if (cardTopo.classList.contains('focado-swipe')) return;

        cardTopo.style.transition = 'none';
        cardTopo.classList.add('dragging');

        // Cálculo da opacidade baseado no movimento (200px é o limite do brilho total)
        let intensidade = Math.min(Math.abs(ev.deltaX) / 200, 1);

        if (ev.deltaX > 0) {
            // Brilha Verde na direita
            cardTopo.style.setProperty('--opacidade-verde', intensidade);
            cardTopo.style.setProperty('--opacidade-vermelha', 0);
        } else {
            // Brilha Vermelho na esquerda
            cardTopo.style.setProperty('--opacidade-vermelha', intensidade);
            cardTopo.style.setProperty('--opacidade-verde', 0);
        }

        let rotate = ev.deltaX / 15;
        cardTopo.style.transform = `translate(${ev.deltaX}px, ${ev.deltaY}px) rotate(${rotate}deg)`;
    });

    mc.on("panend", (ev) => {
        // 1. Mantemos a classe dragging por um milissegundo a mais
        // para o navegador não resetar o estilo antes da hora

        if (Math.abs(ev.deltaX) > 150 && !cardTopo.classList.contains('focado-swipe')) {
            // Dentro do if (Math.abs(ev.deltaX) > 150...)
            const flyX = ev.deltaX > 0 ? 1500 : -1500;

            // 1. Primeiro aplica a transição suave
            cardTopo.style.transition = 'transform 0.7s cubic-bezier(0.2, 0.5, 0.2, 1), opacity 0.4s ease-out';

            // 2. Depois dá o comando de movimento (O navegador agora vai animar do ponto atual até o flyX)
            requestAnimationFrame(() => {
                cardTopo.style.transform = `translateX(${flyX}px) rotate(${flyX / 12}deg)`;
                cardTopo.style.opacity = '0';
            });

            // 3. Limpa as cores IMEDIATAMENTE
            cardTopo.style.setProperty('--opacidade-verde', 0);
            cardTopo.style.setProperty('--opacidade-vermelha', 0);
            setTimeout(() => {
                cardTopo.classList.remove('dragging'); // Só remove agora
                cardTopo.remove();
                window.iniciarFisicaSwipe();
            }, 600);
        } else {
            // Volta para o centro se o arraste foi curto
            cardTopo.classList.remove('dragging');
            cardTopo.style.transition = 'transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            cardTopo.style.transform = cardTopo.classList.contains('focado-swipe') ? 'translate(-50%, -50%) scale(1.05)' : 'translate(0,0)';

            // Reseta as cores
            cardTopo.style.setProperty('--opacidade-verde', 0);
            cardTopo.style.setProperty('--opacidade-vermelha', 0);
        }
    });

};

window.focarCardSwipe = function (card) {
    if (!document.body.classList.contains('modo-swipe-ativo') || card.classList.contains('dragging')) return;

    const estaFocado = card.classList.contains('focado-swipe');
    const imagem = card.querySelector('.spotted-card-img');

    if (!estaFocado) {
        document.body.classList.add('card-isolado');
        card.classList.add('focado-swipe');
        
        // Se o card tiver foto, damos o zoom ADS
        if (imagem) {
            imagem.style.transition = 'all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            imagem.style.maxHeight = '350px'; // Expande a foto
            imagem.style.transform = 'scale(1.02)';
        }
    } else {
        document.body.classList.remove('card-isolado');
        card.classList.remove('focado-swipe');
        
        if (imagem) {
            imagem.style.maxHeight = '200px'; // Volta ao normal
            imagem.style.transform = 'scale(1)';
        }
    }
};

window.ativarModoSwipe = function () {
    const container = document.querySelector('.container-feed');
    const estaAtivo = container && container.classList.contains('feed-empilhado');
    window.alternarInterfaceSwipe(!estaAtivo);
    const btn = document.getElementById('toggle-swipe');
    if (btn) {
        btn.innerHTML = !estaAtivo ? '<i class="fas fa-th"></i> VOLTAR PARA LISTA' : '<i class="fas fa-mobile-alt"></i> ATIVAR MODO APP (SWIPE)';
    }
};

// --- FUNÇÃO CORRIGIDA: CONFIGURAR POSTS (SÓ UMA VEZ!) ---
window.configurarPosts = function () {
    const posts = document.querySelectorAll('.post-content');
    posts.forEach(post => {
        const precisaExpandir = post.scrollHeight > post.offsetHeight + 10;

        if (precisaExpandir && !post.dataset.ouvinte) {
            post.classList.add('tem-mais');
            post.dataset.ouvinte = "true";
            post.style.cursor = "pointer";

            post.addEventListener('click', function (e) {
                const card = this.closest('.spotted-card');
                // Se o modo swipe estiver ON, o clique isola o card (Modo Tinder)
                if (document.body.classList.contains('modo-swipe-ativo')) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.focarCardSwipe(card);
                } else {
                    // Modo grid: apenas expande o texto normal
                    this.classList.toggle('expandido');
                }
            });
        }
    });
};

// --- FECHA O MODO FOCO AO CLICAR NO FUNDO ESCURO ---
document.addEventListener('click', (e) => {
    if (document.body.classList.contains('card-isolado')) {
        const focado = document.querySelector('.focado-swipe');
        if (focado && (e.target.classList.contains('card-isolado') || !e.target.closest('.focado-swipe'))) {
            window.focarCardSwipe(focado);
        }
    }
});

window.toggleMenu = function (menuId) {
    document.querySelectorAll('.options-menu-popup').forEach(menu => {
        if (menu.id !== menuId) menu.style.display = 'none';
    });
    const menu = document.getElementById(menuId);
    if (menu) menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
};

// ==================== INICIALIZAÇÃO ====================
const destravarAudio = () => {
    window.audioLiberado = true;
    console.log("Áudio autorizado pelo usuário.");
    document.removeEventListener('click', destravarAudio);
    document.removeEventListener('touchstart', destravarAudio);
};
document.addEventListener('click', destravarAudio);
document.addEventListener('touchstart', destravarAudio);

document.addEventListener("DOMContentLoaded", function () {
    if (typeof atualizarInterfaceBolhas === 'function') atualizarInterfaceBolhas();

    const dropdownLinks = document.querySelectorAll('.menu-item.dropdown > a');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (window.innerWidth <= 1024) {
                const pai = this.parentElement;
                const estaAberto = pai.classList.contains('active');
                if (!estaAberto) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
                    pai.classList.add('active');
                }
            }
        });
    });

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
                audio.volume = 0.04;
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

    window.atualizarContadorAlertas = function () {
        fetch('includes/contar_alertas.php?cache=' + new Date().getTime())
            .then(res => res.text())
            .then(texto => {
                const match = texto.match(/\{.*\}/);
                if (!match) return;
                const data = JSON.parse(match[0]);
                const badge = document.getElementById('badge-alertas');
                if (badge && data.total !== undefined) {
                    let ultimoAviso = parseInt(sessionStorage.getItem('fenda_ultimo_aviso')) || 0;
                    if (data.total > ultimoAviso) {
                        if (typeof mostrarPopup === 'function') mostrarPopup("Nova interação na Fenda!");
                    }
                    sessionStorage.setItem('fenda_ultimo_aviso', data.total);
                    badge.innerText = data.total;
                    badge.style.display = data.total > 0 ? 'flex' : 'none';
                }
            })
            .catch(err => console.warn("Radar: Aguardando sinal limpo..."));
    };

    setTimeout(() => {
        window.atualizarContadorAlertas();
        setInterval(window.atualizarContadorAlertas, 8000);
    }, 1000);

    setTimeout(() => {
        if (typeof configurarPosts === 'function') configurarPosts();
    }, 500);
});

function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';
    let tempoExibicao = 5000;

    if (temaSalvo !== 'off') {
        let configuracaoSons = {
            'padrao': { arquivo: 'padrao.mp3', volume: 1.2 },
            'resident': { arquivo: 'resident.mp3', volume: 0.6 },
            'cs': { arquivo: 'cs.mp3', volume: 0.7 },
            'starwars': { arquivo: 'imperial-march.mp3', volume: 0.3 },
            'mario': { arquivo: 'mario-bros-1up.mp3', volume: 0.7 },
            'pokemon': { arquivo: 'pokemon_levelup.mp3', volume: 0.8 },
            'digimon': { arquivo: 'brave-heart_digimon.mp3', volume: 0.2 },
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

            const dispararSom = () => {
                let somUrl = 'sons/' + somEscolhido.arquivo + '?v=' + Date.now();
                let bip = new Audio(somUrl);
                bip.volume = somEscolhido.volume;

                bip.play().then(() => {
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
                }).catch(err => console.warn("Áudio aguardando clique."));
            };

            if (window.audioLiberado) {
                dispararSom();
            } else {
                document.addEventListener('click', () => {
                    window.audioLiberado = true;
                    dispararSom();
                }, { once: true });
            }
        }
    }

    const popup = document.createElement('div');
    popup.className = 'notificacao-popup';
    popup.style.cursor = 'pointer';
    popup.innerHTML = `
        <div style="font-size: 20px;">🔔</div>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 14px; color: #ddc80e;">Nova Interação!</strong>
            <span>${mensagem}</span>
        </div>
    `;
    popup.onclick = () => window.location.href = 'notificacoes.php';
    document.body.appendChild(popup);

    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 500);
    }, tempoExibicao);
}

window.toggleJanelaNotificacoes = function () {
    const box = document.getElementById('dropdown-notificacoes');
    if (box.style.display === 'none' || box.style.display === '') {
        fetch('notificacoes-rapidas.php')
            .then(res => res.text())
            .then(html => {
                box.innerHTML = html;
                box.style.display = 'block';
                const badge = document.getElementById('badge-alertas');
                if (badge) badge.style.display = 'none';
            });
    } else {
        box.style.display = 'none';
    }
};

window.addEventListener('click', function (e) {
    const box = document.getElementById('dropdown-notificacoes');
    if (box && !e.target.closest('.notificacao-wrapper')) {
        box.style.display = 'none';
    }
});

// --- REAÇÕES ---
window.enviarReacao = function (postId, tipo) {
    fetch(`includes/reagir.php?id=${postId}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
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
    if (event.target === modalSair) window.fecharModalSair();

    const clicouNoMenu = event.target.closest('.dropdown');
    const clicouNaReacao = event.target.closest('.reacao-wrapper') || event.target.closest('.btn-reagir');
    const clicouNaNotif = event.target.closest('.notificacao-wrapper') || event.target.closest('#btn-notificacoes');
    const clicouNoPost = event.target.closest('.post-content');

    if (!clicouNoMenu && !clicouNaReacao && !clicouNoPost) {
        document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
        document.querySelectorAll('.reacoes-popup').forEach(p => {
            p.style.visibility = 'hidden';
            p.style.opacity = '0';
        });
    }

    if (!clicouNaNotif) {
        const box = document.getElementById('dropdown-notificacoes');
        if (box) box.style.display = 'none';
    }
});

// --- LOAD FINAL (Modo Hacker e Swipe Inteligente) ---
window.addEventListener('load', () => {
    if (localStorage.getItem('fenda_hacker') === 'active') {
        document.body.classList.add('hacker-mode');
        const hBtn = document.getElementById('hacker-toggle-lateral') || document.getElementById('hacker-toggle');
        if (hBtn) hBtn.innerHTML = '[ DESLIGAR_TERMINAL ]';
        window.removerBordasInlineHacker();
    }
    const bootScreen = document.getElementById('bios-boot');
    if (bootScreen && !sessionStorage.getItem('boot_concluido')) {
        setTimeout(() => {
            bootScreen.style.opacity = '0';
            setTimeout(() => { bootScreen.style.display = 'none'; sessionStorage.setItem('boot_concluido', 'true'); }, 500);
        }, 2500);
    } else if (bootScreen) { bootScreen.style.display = 'none'; }

    // Ativação inteligente do modo swipe (somente se a preferência estiver ativada)
    if (window.prefSwipeAtivada === true) {
        const isFeedGeral = window.location.pathname.includes('feed.php');
        if (!isFeedGeral) {
            window.alternarInterfaceSwipe(true);
            const btn = document.getElementById('toggle-swipe');
            if (btn) btn.innerHTML = '<i class="fas fa-th"></i> VOLTAR PARA LISTA';
            const toolbar = document.getElementById('fenda-toolbar');
            if (toolbar) toolbar.classList.remove('toolbar-aberta');
        }
    }
});