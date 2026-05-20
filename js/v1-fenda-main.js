/* A FENDA - ENGINE CENTRAL (JavaScript Geral) */
// 1. FUNÇÕES GLOBAIS DE CONTROLE
window.audioLiberado = false;

window.setBolhasLocal = function (valor) {
    const inputHidden = document.getElementById('input_pref_bolhas');
    if (inputHidden) inputHidden.value = valor;
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
    if (containerBolhas) containerBolhas.style.display = (valor === 1) ? 'block' : 'none';
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

window.toggleToolbar = function () {
    const toolbar = document.getElementById('fenda-toolbar');
    const icon = document.getElementById('trigger-icon');
    if (!toolbar) return;
    toolbar.classList.toggle('toolbar-aberta');
    icon.innerText = toolbar.classList.contains('toolbar-aberta') ? '❌' : '🧭';
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

window.deslogar = function () {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'flex';
};

window.fecharModalSair = function () {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'none';
};

function confirmarExclusao(idPost) {
    if (confirm(" ATENÇÃO: Deseja mesmo apagar esse post?")) {
        window.location.href = 'includes/excluir.php?id=' + idPost;
    }
}

function abrirDenuncia(idPost) {
    alert("Denúncia do post #" + idPost + " enviada aos ADMs.");
}

function abrirModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-aberto');
        document.body.style.overflow = 'hidden';
    }
}

function fecharModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-aberto');
        document.body.style.overflow = 'auto';
    }
}

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
        const barraChat = document.querySelector('.sessao-fofoca-focada');
        if (barraChat) {
            barraChat.style.boxShadow = "0 -5px 30px var(--cor-accent)";
            setTimeout(() => {
                barraChat.style.boxShadow = "0 -5px 20px rgba(0, 0, 0, 0.5)";
            }, 1000);
        }
    }
};

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

window.alternarInterfaceSwipe = function (ativar) {
    const body = document.body;
    const container = document.querySelector('.container-feed');
    const filtros = document.querySelector('.filtros-wrapper-retratil');
    const btnFiltros = document.getElementById('btn-abrir-filtros');
    if (!container) return;
    if (ativar) {
        body.classList.add('modo-swipe-ativo');
        container.classList.add('feed-empilhado');
        if (filtros) filtros.style.display = 'flex';
        if (btnFiltros) btnFiltros.style.display = 'inline-flex';
        window.iniciarFisicaSwipe();
        console.log("Modo Swipe ativado com filtros visíveis");
    } else {
        body.classList.remove('modo-swipe-ativo', 'card-isolado');
        container.classList.remove('feed-empilhado');
        document.querySelectorAll('.focado-swipe').forEach(c => c.classList.remove('focado-swipe'));
        if (filtros) filtros.style.display = '';
        if (btnFiltros) btnFiltros.style.display = '';
        console.log("Modo Swipe desativado");
    }
};

// ===============================================================
// FÍSICA NATIVA SUPREMA (MANOPLA DO INFINITO - VERSÃO ESTÁVEL)
// ===============================================================
window.iniciarFisicaSwipe = function () {
    const container = document.querySelector('.container-feed');
    if (!container || !container.classList.contains('feed-empilhado')) return;

    let cards = container.querySelectorAll('.spotted-card');
    if (cards.length === 0) return;

    // O card do topo é o nosso Alvo Alpha
    const cardTopo = cards[0];
    if (!cardTopo) return;

    // 🛡️ BARREIRA CÓSMICA DO TRIBUNAL VIVO: Se o card já tem física ativa, aborta para não duplicar!
    if (cardTopo.getAttribute('data-swipe-pronto') === 'true') {
        console.log("⚠️ Fenda Engine: Tentativa de duplicar física abortada para o card:", cardTopo.dataset.id);
        return;
    }
    
    // Marca o card imediatamente para nenhum outro script clonar o evento
    cardTopo.setAttribute('data-swipe-pronto', 'true');

    // Limpa listeners residuais históricos
    if (cardTopo._swipeCleanup) {
        cardTopo._swipeCleanup();
    }

    // Alinhamento geométrico impecável para evitar o "efeito metralhadora"
    cardTopo.style.setProperty('position', 'absolute', 'important');
    cardTopo.style.setProperty('left', '50%', 'important');
    cardTopo.style.setProperty('top', '45%', 'important');
    cardTopo.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
    cardTopo.style.cursor = 'grab';
    cardTopo.style.touchAction = 'none'; // Previne scroll da página no celular

    let isDragging = false;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let rafId = null;

    // 🏃‍♂️ MOTOR DE MOVIMENTO (Aceleração e Rotação via RAF)
    const onMove = (e) => {
        if (!isDragging) return;
        if (cardTopo.classList.contains('focado-swipe')) return;

        // Captura universal: Dedos (Mobile) ou Mouse (PC)
        let clientX = e.touches ? e.touches[0].clientX : e.clientX;
        let clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const dx = clientX - startX;
        const dy = clientY - startY;

        // Zona morta de 5px para ignorar micro-tremores involuntários
        if (Math.abs(dx) < 5 && Math.abs(dy) < 5) return;

        if (e.cancelable) e.preventDefault();
        e.stopPropagation();

        currentX = dx;
        currentY = dy;

        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            if (!isDragging) return;

            const rotate = currentX / 30; // Suavização do ângulo de rotação

            // 🟢 Injeção matemática estável vencendo qualquer barreira de CSS
            cardTopo.style.setProperty('transform', 
                `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) rotate(${rotate}deg) scale(1.02)`, 
                'important'
            );

            // FEEDBACK VISUAL CYBERPUNK (Acendendo as laterais do site)
            const feedbackDir = document.querySelector('.feedback-direita');
            const feedbackEsq = document.querySelector('.feedback-esquerda');
            const feedbackCima = document.querySelector('.feedback-cima');

            if (currentX > 40 && feedbackDir) {
                feedbackDir.style.opacity = Math.min(currentX / 150, 1);
            } else if (currentX < -40 && feedbackEsq) {
                feedbackEsq.style.opacity = Math.min(Math.abs(currentX) / 150, 1);
            } else if (currentY < -40 && Math.abs(currentX) < 40 && feedbackCima) {
                feedbackCima.style.opacity = Math.min(Math.abs(currentY) / 150, 1);
            }
        });
    };

    // 🛑 CICLO DE FINALIZAÇÃO E RECICLAGEM DO DOM
    const onEnd = (e) => {
        if (!isDragging) return;
        isDragging = false;
        if (rafId) cancelAnimationFrame(rafId);

        // Destruição instantânea dos listeners globais para evitar vazamento de memória
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onEnd);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);

        cardTopo.style.cursor = 'grab';
        cardTopo.classList.remove('dragging');
        
        // Apaga opacidade de todas as setas de feedback visual
        document.querySelectorAll('.feedback-swipe').forEach(el => el.style.opacity = '0');

        const threshold = 120; // Ponto de não-retorno (pixels)
        const idPost = cardTopo.dataset.id;

        // Transição suave para o veredito final do card
        cardTopo.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.2s';

        if (currentX < -threshold) {
            // ◀️ ESQUERDA: Reação de "Amei" e Descarte
            console.log(`[FENDA ENGINE] Card ${idPost} ejetado para a ESQUERDA.`);
            if (typeof window.enviarReacao === 'function') window.enviarReacao(idPost, 'amei');
            animarSaidaEReciclar(cardTopo, -800, currentY);
        } else if (currentX > threshold) {
            // ▶️ DIREITA: Apenas Descarte/Pular
            console.log(`[FENDA ENGINE] Card ${idPost} ejetado para a DIREITA.`);
            animarSaidaEReciclar(cardTopo, 800, currentY);
        } else if (currentY < -threshold && Math.abs(currentX) < 80) {
            // 🔼 CIMA: Entrar na fofoca (Redirecionamento)
            console.log(`[FENDA ENGINE] Entrar no post ${idPost}.`);
            window.location.href = `post.php?id=${idPost}#fofocar`;
        } else {
            // 🔄 RETORNO: Puxou pouco, devolve pro centro magnético
            cardTopo.style.setProperty('transform', 'translate(-50%, -50%) rotate(0deg) scale(1)', 'important');
            setTimeout(() => {
                if (cardTopo) cardTopo.style.transition = 'none';
            }, 300);
        }

        startX = 0; startY = 0; currentX = 0; currentY = 0;
    };

    // 🚀 INICIALIZAÇÃO DO TOQUE/CLIQUE
    const onStart = (e) => {
        // Bloqueia se clicar em botões, links ou menus internos do card
        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.options-container') || e.target.closest('.options-menu-popup')) {
            return;
        }
        if (e.button !== undefined && e.button !== 0) return; // Só aceita clique esquerdo

        isDragging = true;

        startX = e.touches ? e.touches[0].clientX : e.clientX;
        startY = e.touches ? e.touches[0].clientY : e.clientY;
        currentX = 0;
        currentY = 0;

        cardTopo.style.transition = 'none';
        cardTopo.style.cursor = 'grabbing';
        cardTopo.classList.add('dragging');

        // Escuta global amarrada no objeto window para não perder o foco
        window.addEventListener('mousemove', onMove, { passive: false });
        window.addEventListener('mouseup', onEnd);
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onEnd);
    };

    const cleanupListeners = () => {
        cardTopo.removeEventListener('mousedown', onStart);
        cardTopo.removeEventListener('touchstart', onStart);
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onEnd);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);
        cardTopo.removeAttribute('data-swipe-pronto');
    };

    // Vincula os gatilhos de ignição
    cardTopo.addEventListener('mousedown', onStart);
    cardTopo.addEventListener('touchstart', onStart, { passive: false });

    // Armazena a limpeza para reuso seguro
    cardTopo._swipeCleanup = cleanupListeners;
};

// 🎬 FUNÇÃO COADJUVANTE DE RECICLAGEM DO DOM (Mantida idêntica para segurança)
const animarSaidaEReciclar = (card, x, y) => {
    // Força a transição de saída ignorando travas antigas
    card.style.setProperty('transition', 'transform 0.3s ease-out, opacity 0.2s', 'important');
    card.style.setProperty('transform', `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`, 'important');
    card.style.opacity = '0';

    setTimeout(() => {
        const container = document.querySelector('.container-feed');
        if (!container) return;
        
        const cardsRestantes = container.querySelectorAll('.spotted-card');
        
        // Remove os atributos da manopla velha antes de deletar ou reciclar
        card.removeAttribute('data-swipe-pronto');
        if (card._swipeCleanup) card._swipeCleanup();

        if (cardsRestantes.length <= 1) {
            // Se era o último, limpa os estilos para o esqueleto do motor rodar limpo
            card.style.transition = '';
            card.style.transform = '';
            card.style.opacity = '';
            if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda();
        } else {
            card.remove();
        }
        
        // Dá um micro-intervalo pro navegador tirar o lixo do DOM e inicia o topo novo
        setTimeout(() => {
            window.iniciarFisicaSwipe();
            if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda();
        }, 30);

    }, 300);
};

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
                if (document.body.classList.contains('modo-swipe-ativo')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
            });
        }
    });
};


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
            'padrao': { arquivo: 'padrao.mp3', volume: 1.1 },
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

window.addEventListener('load', () => {
    if (localStorage.getItem('fenda_hacker') === 'active') {
        document.body.classList.add('hacker-mode');
        window.removerBordasInlineHacker();
    }
    const bootScreen = document.getElementById('bios-boot');
    if (bootScreen && !sessionStorage.getItem('boot_concluido')) {
        setTimeout(() => {
            bootScreen.style.opacity = '0';
            setTimeout(() => {
                bootScreen.style.display = 'none';
                sessionStorage.setItem('boot_concluido', 'true');
            }, 500);
        }, 2500);
    } else if (bootScreen) bootScreen.style.display = 'none';

    if (document.body.classList.contains('modo-swipe-ativo')) {
        console.log("Fenda Engine: Detectado modo Swipe via PHP. Forçando Pilha...");

        // Limpeza nuclear antes de iniciar — mata qualquer listener zumbi
        if (window._fendaMove) window.removeEventListener('mousemove', window._fendaMove);
        if (window._fendaUp)   window.removeEventListener('mouseup',   window._fendaUp);
        window._fendaMove = null;
        window._fendaUp   = null;

        const cardInicial = document.querySelector('.feed-empilhado .spotted-card:first-child');
        if (cardInicial) {
            if (cardInicial._fendaStart) {
                cardInicial.removeEventListener('mousedown',  cardInicial._fendaStart);
                cardInicial.removeEventListener('touchstart', cardInicial._fendaStart);
            }
            cardInicial.removeAttribute('data-swipe-pronto');
        }

        if (typeof window.alternarInterfaceSwipe === 'function') window.alternarInterfaceSwipe(true);
    }

   let offsetSwipe = 10;
    let carregandoSwipe = false;
    window.abastecerPilhaFenda = function () {
        if (carregandoSwipe) return;
        const container = document.querySelector('.container-feed');
        if (!container) return; // Proteção para não quebrar caso mude de página

        const cardsRestantes = container.querySelectorAll('.spotted-card').length;
        if (cardsRestantes < 4) {
            carregandoSwipe = true;
            console.log("Fenda Engine: Estoque baixo! Buscando fofocas novas...");
            const urlParams = new URLSearchParams(window.location.search);
            const categoria = urlParams.get('categoria') || '';
            
            fetch(`motor-feed.php?offset=${offsetSwipe}&categoria=${categoria}&tipo=geral`)
                .then(res => res.text())
                .then(html => {
                    if (html.trim() !== "FIM_DADOS") {
                        container.insertAdjacentHTML('beforeend', html);
                        offsetSwipe += 10;
                        
                        // 🚀 O ESTALO DE DEDOS: Dá 50ms para o navegador respirar e estruturar o card novo
                        setTimeout(() => {
                            window.iniciarFisicaSwipe();
                            console.log("🛡️ Fenda Engine: Próximo card blindado com sucesso após renderização.");
                        }, 50);
                    }
                    carregandoSwipe = false;
                })
                .catch(() => { carregandoSwipe = false; });
        }
    };
});