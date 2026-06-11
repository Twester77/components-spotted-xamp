/* A FENDA - ENGINE CENTRAL (UI + Áudio + Notificações) */
/* Sem swipe – toda a física de cards agora está em fenda-swipe-pc.js e fenda-swipe-mobile.js */

window.audioLiberado = false;

// ==================== BOLHAS (Imediato) ====================
window.setBolhasLocal = function (valor) {
    document.querySelectorAll('input[name="pref_bolhas"]').forEach(i => i.value = valor);
    
    // Marca active com cor específica para cada estado, e garante que só um estado esteja ativo
    document.querySelectorAll('.btn-bolhas-on').forEach(b => b.classList.toggle('active-blue', valor == 1));
    document.querySelectorAll('.btn-bolhas-off').forEach(b => b.classList.toggle('active-red', valor == 0));

    const container = document.querySelector('.bubbles-container');
    if (container) container.style.display = (valor == 1) ? 'block' : 'none';
};

// ==================== SOM (Imediato) ====================
window.mudarSomAmbiente = function (valor) {
    document.querySelectorAll('input[name="pref_som_trilha"]').forEach(i => i.value = valor);

    // MUDANÇA: Procura APENAS botões que possuem o atributo data-som
    document.querySelectorAll('.btn-audio-choice[data-som]').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-som') == valor);
    });

    // Lógica do Áudio
    const audio = document.getElementById('som-oceano');
    if (audio) {
        if (valor === 'off') {
            audio.pause();
        } else {
            audio.src = (valor === 'chuva') ? 'sons/chuva.mp3' : 'sons/oceano.mp3';
            if (window.audioLiberado) audio.play().catch(() => {});
        }
    }
};

// ==================== NOTIFICAÇÕES (Imediato) ====================
window.mudarTemaNotif = function (valor) {
    document.querySelectorAll('input[name="pref_som_notif"]').forEach(i => i.value = valor);

    // MUDANÇA: Procura APENAS botões que possuem o atributo data-notif
    document.querySelectorAll('.btn-audio-choice[data-notif]').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-notif') == valor);
    });
};

// ==================== TOOLBAR ====================
window.toggleToolbar = function () {
    const toolbar = document.getElementById('fenda-toolbar');
    const btn = toolbar.querySelector('.toolbar-trigger'); // Pega o botão
    const icon = document.getElementById('trigger-icon');
    
    if (!toolbar) return;
    
    const estaAberta = toolbar.classList.toggle('toolbar-aberta');
    
    // Atualiza o estado de acessibilidade do botão
    if (btn) btn.setAttribute('aria-expanded', estaAberta);
    
};

// ==================== HACKER MODE ====================
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

// ==================== LOGOUT / MODAIS ====================
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

// ==================== PERFIL DRAWER (GAVETA) ====================
window.abrirPerfilDrawer = function (e) {
    if (e) e.preventDefault();
    
    const drawer = document.getElementById('perfil-drawer');
    const containerPerfil = document.getElementById('conteudo-perfil');
    const backdrop = document.getElementById('drawer-backdrop');

    if (!drawer || !containerPerfil) return;

    fetch('perfil.php?modo=gaveta')
        .then(res => res.text())
        .then(html => {
            containerPerfil.innerHTML = html; 
            if (typeof window.configurarFormularioPerfil === 'function') {
                window.configurarFormularioPerfil();
            }
            // Reaplica a sincronização após carregar a gaveta
            inicializarPreferenciasAPartirDoDOM();
            
            drawer.classList.add('ativa');
            drawer.setAttribute('aria-hidden', 'false');
            backdrop.style.display = 'block';
            document.body.style.overflow = 'hidden';
        })
        .catch(err => console.error("Erro na Fenda:", err));
};

window.fecharPerfilDrawer = function () {
    const drawer = document.getElementById('perfil-drawer');
    const backdrop = document.getElementById('drawer-backdrop');
    
    drawer.classList.remove('ativa');
    drawer.setAttribute('aria-hidden', 'true');
    backdrop.style.display = 'none';
    document.body.style.overflow = 'auto';
};

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


// ==================== POSTS E MENUS ====================
window.configurarPosts = function () {
    const posts = document.querySelectorAll('.post-content');
    posts.forEach(post => {
        const precisaExpandir = post.scrollHeight > post.offsetHeight + 3;
        if (precisaExpandir && !post.dataset.ouvinte) {
            post.classList.add('tem-mais');
            post.dataset.ouvinte = "true";
            post.style.cursor = "pointer";
            post.addEventListener('click', function (e) {
                const card = this.closest('.spotted-card');
                if (document.body.classList.contains('modo-swipe-ativo')) {
                    e.preventDefault();
                    e.stopPropagation();
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

// ==================== ÁUDIO E PREFERÊNCIAS (FONTE ÚNICA: DOM) ====================

window.atualizarPreferenciasGlobais = function(idInput, novoValor) {
    const inputGlobal = document.getElementById(idInput);
    if (inputGlobal) {
        inputGlobal.value = novoValor;
        console.log(`[SYS] Preferência ${idInput} atualizada globalmente para: ${novoValor}`);
    }
};

const destravarAudio = () => {
    window.audioLiberado = true;
    console.log("[AUDIO] Áudio autorizado pelo usuário.");
    
    // NOVO: Assim que o usuário clicar, verifica se já tem algo configurado e manda tocar
    const audio = document.getElementById('som-oceano');
    if (audio && window.somAmbiente && window.somAmbiente !== 'off') {
        audio.play().catch(e => console.log("Erro ao iniciar áudio: ", e));
    }

    document.removeEventListener('click', destravarAudio);
    document.removeEventListener('touchstart', destravarAudio);
};

// Lê os valores dos inputs hidden e atualiza a interface (botões e áudio)
function inicializarPreferenciasAPartirDoDOM() {
    const inputTrilha = document.getElementById('input_pref_som_trilha');
    const inputNotif = document.getElementById('input_pref_som_notif');
    const inputBolhas = document.getElementById('input_pref_bolhas');

    // -- PREFERÊNCIA DE SOM --
    if (inputTrilha && inputTrilha.value) {
        const trilha = inputTrilha.value;
        window.somAmbiente = trilha;
        localStorage.setItem('fenda_tipo_som', trilha);
        
        // Remove active de todos, mas só se eles existirem
        document.querySelectorAll('[id^="btn-som-"]').forEach(btn => btn.classList.remove('active'));
        
        const btnSom = document.getElementById('btn-som-' + trilha);
        if (btnSom) btnSom.classList.add('active');
        
        const audio = document.getElementById('som-oceano');
        if (audio) {
            if (trilha === 'off') {
                audio.pause();
            } else {
                audio.src = (trilha === 'chuva') ? 'sons/chuva.mp3' : 'sons/oceano.mp3';
                audio.volume = 0.05;
                if (window.audioLiberado) {
                    audio.play().catch(e => console.log("Aguardando interação."));
                }
            }
        }
    }
    
    // -- PREFERÊNCIA DE NOTIFICAÇÃO --
    if (inputNotif && inputNotif.value) {
        const notif = inputNotif.value;
        window.temaNotif = notif;
        localStorage.setItem('fenda_tema_notif', notif);
        
        document.querySelectorAll('[id^="btn-notif-"]').forEach(btn => btn.classList.remove('active'));
        const btnNotif = document.getElementById('btn-notif-' + notif);
        if (btnNotif) btnNotif.classList.add('active');
    }
    
    // -- PREFERÊNCIA DE BOLHAS --
    if (inputBolhas) {
        const bolhas = inputBolhas.value;
        const btnOn = document.getElementById('btn-bolhas-on');
        const btnOff = document.getElementById('btn-bolhas-off');
        
        // AQUI: Proteção para não quebrar se os botões não estiverem na página
        if (btnOn && btnOff) {
            if (bolhas === '1') {
                btnOn.classList.add('active');
                btnOff.classList.remove('active');
            } else {
                btnOn.classList.remove('active');
                btnOff.classList.add('active');
            }
        }
    }
}



// ==================== NOTIFICAÇÕES E ALERTAS ====================
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
        .catch(err => console.warn("[RADAR] Aguardando sinal limpo..."));
};

// ==================== POPUPS E NOTIFICAÇÕES ====================
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
            const dispararSom = () => {
                let somUrl = 'sons/' + somEscolhido.arquivo + '?v=' + Date.now();
                let bip = new Audio(somUrl);
                bip.volume = somEscolhido.volume;
                bip.play().then(() => {
                    bip.onloadedmetadata = function () {
                        if (bip.duration > 4) {
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
                }).catch(err => console.warn("[AUDIO] Aguardando clique."));
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

// ==================== REAÇÕES ====================
window.enviarReacao = function (postId, tipo) {
    fetch(`includes/reagir.php?id=${postId}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                window.atualizarInterfaceReacao(postId, data.contagens, data.minhas_reacoes);
            }
        })
        .catch(err => console.error("[REACAO] Erro:", err));
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

// ==================== INICIALIZAÇÃO PRINCIPAL ====================
document.addEventListener("DOMContentLoaded", function () {
    if (typeof atualizarInterfaceBolhas === 'function') atualizarInterfaceBolhas();

    const btnConfig = document.querySelector('.btn-config');
    if (btnConfig) {
        btnConfig.addEventListener('click', window.abrirPerfilDrawer);
    }

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

    // INICIALIZA AS PREFERÊNCIAS A PARTIR DOS INPUTS HIDDEN (FONTE DA VERDADE)
    inicializarPreferenciasAPartirDoDOM();
    // Adiciona o ouvinte para destravar o áudio no primeiro clique
    document.addEventListener('click', destravarAudio, { once: true });

    // CONTADOR DE ALERTAS
    setTimeout(() => {
        window.atualizarContadorAlertas();
        setInterval(window.atualizarContadorAlertas, 8000);
    }, 1000);

    // CONFIGURA POSTS
    setTimeout(() => {
        if (typeof configurarPosts === 'function') configurarPosts();
    }, 500);
});

// ==================== LOAD FINAL (BOOT E HACKER MODE) ====================
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
        if (document.body.classList.contains('hacker-mode')) {
            sessionStorage.setItem('fenda_hacker_suspended_by_swipe', 'true');
            document.body.classList.remove('hacker-mode');
            console.log("[HACKER] Modo Hacker suspenso porque o modo swipe está ativo.");
        }
    } else {
        if (sessionStorage.getItem('fenda_hacker_suspended_by_swipe') === 'true') {
            if (localStorage.getItem('fenda_hacker') === 'active') {
                document.body.classList.add('hacker-mode');
                window.removerBordasInlineHacker();
                console.log("[HACKER] Modo Hacker restaurado ao sair do modo swipe.");
            }
            sessionStorage.removeItem('fenda_hacker_suspended_by_swipe');
        }
    }
});

// ========================================================
// LIGHTBOX DE IMAGENS (Ampliação de Fotos)
// ========================================================
document.addEventListener('click', function(e) {
    // Verifica se clicou em uma imagem de comentário
    if (e.target.classList.contains('comentario-img')) {
        const modal = document.getElementById('modal-imagem');
        const imgAmpliada = document.getElementById('img-ampliada');
        
        if (!modal || !imgAmpliada) return; // Segurança
        
        imgAmpliada.src = e.target.src; 
        modal.classList.remove('modal-hidden');
        modal.setAttribute('aria-hidden', 'false');
    }
});

// Fechar ao clicar no modal (no background com blur)
const modalFechamento = document.getElementById('modal-imagem');
if (modalFechamento) {
    modalFechamento.addEventListener('click', function(e) {
        // Fecha apenas se clicar no fundo ou no botão X
        if (e.target === this || e.target.classList.contains('fechar-modal')) {
            this.classList.add('modal-hidden');
            this.setAttribute('aria-hidden', 'true');
        }
    });
}

/* ==========================================================
   NEXUS ENGINE - CONTROLE ROBUSTO (À PROVA DE GLITCHES)
   ========================================================== */

let isAnimating = false; // Flag para impedir cliques rápidos (trava de segurança)

// 1. Função de Execução com Delay (O efeito "Procurando Função")
window.executarAcao = function(btn, graus, callback) {
    if (isAnimating) return; // Se já está girando, ignora novos cliques até terminar

    const ponteiro = document.getElementById('ponteiro-bussola');

    isAnimating = true; // Bloqueia o sistema durante a animação
    marcarBotaoAtivo(btn);
    aplicarRotacao(ponteiro, graus);

    // Delay de 500ms para a bússola atingir a posição antes de disparar a função
    setTimeout(() => {
        toggleNexus(); // Fecha o leque
        callback();    // Executa a função (abrir modal ou redirecionar)
        isAnimating = false; // Libera o sistema para novos cliques
    }, 500);
};

// 2. Função de Feedback Visual (Active)
window.marcarBotaoAtivo = function(btnClicado) {
    document.querySelectorAll('.nexus-item').forEach(item => item.classList.remove('active'));
    btnClicado.classList.add('active');
};

// 3. Rotação Auxiliar (Manipula apenas as classes)
function aplicarRotacao(elemento, graus) {
    if (!elemento) return;
    elemento.classList.remove('girar-0', 'girar-90', 'girar-180', 'girar-270');
    elemento.classList.add('girar-' + graus);
}

// 4. Toggle do Nexus (Entrada e Reset)
function toggleNexus() {
    const nexus = document.getElementById('fenda-nexus');
    if (!nexus) return;

    nexus.classList.toggle('aberto');

    // Se fechou, resetamos a bússola para o Norte e limpamos os destaques
    if (!nexus.classList.contains('aberto')) {
        const ponteiro = document.getElementById('ponteiro-bussola');
        aplicarRotacao(ponteiro, 0);
        document.querySelectorAll('.nexus-item').forEach(item => item.classList.remove('active'));
    }
}


// ==================================================
// VIEWPORT TRACKER (SOLUÇÃO PARA TECLADO EM LANDSCAPE)
// ==================================================
function initViewportTracker() {
    if (!window.visualViewport) {
        console.warn("[VIEWPORT] visualViewport não suportado. Usando fallback padrão.");
        document.documentElement.style.setProperty('--app-height', window.innerHeight + 'px');
        return;
    }

    function atualizarAltura() {
        const alturaVisivel = window.visualViewport.height;
        document.documentElement.style.setProperty('--app-height', alturaVisivel + 'px');
    }

    window.visualViewport.addEventListener('resize', atualizarAltura);
    window.addEventListener('resize', atualizarAltura);
    atualizarAltura();
}

// ==================================================
// MINIMIZAÇÃO INTELIGENTE DO CARD SUPERIOR
// ==================================================
function initCardMinimizer() {
    const cardTopo = document.querySelector('.card-focado-topo');
    const textarea = document.querySelector('.textarea-chat');
    const listaComentarios = document.querySelector('.lista-comentarios-social');

    if (!cardTopo) return;

    let timeoutBlur = null;
    let timeoutRestore = null;

    function minimizarCard() {
        if (timeoutRestore) clearTimeout(timeoutRestore);
        cardTopo.classList.add('card-minimizado');
    }

    function restaurarCard() {
        cardTopo.classList.remove('card-minimizado');
    }

    if (textarea) {
        textarea.addEventListener('focus', minimizarCard);
        textarea.addEventListener('blur', function() {
            if (timeoutBlur) clearTimeout(timeoutBlur);
            timeoutBlur = setTimeout(() => {
                restaurarCard();
                timeoutBlur = null;
            }, 200);
        });
    }

    if (listaComentarios) {
        let scrollTimeout;
        listaComentarios.addEventListener('scroll', function() {
            if (cardTopo.classList.contains('card-minimizado')) {
                if (scrollTimeout) clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    restaurarCard();
                }, 150);
            }
        });
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    initViewportTracker();
    initCardMinimizer();
});