/* A FENDA - ENGINE CENTRAL (UI + Áudio + Notificações) */
/* Sem swipe – toda a física de cards agora está em fenda-swipe-pc.js e fenda-swipe-mobile.js */

window.audioLiberado = false;

// ==================== CARREGAMENTO DINÂMICO DO CSS HACKER ====================
function carregarCssHacker() {
    if (document.getElementById('hacker-css')) return;
    const link = document.createElement('link');
    link.id = 'hacker-css';
    link.rel = 'stylesheet';
    link.href = 'css/skin-hacker.css';
    document.head.appendChild(link);
    console.log("[HACKER] CSS carregado dinamicamente.");
}

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
    
    // Se for ativar o modo hacker e o CSS ainda não foi carregado, carrega
    if (!body.classList.contains('hacker-mode')) {
        carregarCssHacker();
    }
    
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
    // 🔓 Destrava o swipe caso esteja bloqueado (ex.: menu de long press não fechado)
    if (window.SwipeEngine && typeof window.SwipeEngine.unlock === 'function') {
        window.SwipeEngine.unlock();
    }
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-aberto');
        document.body.style.overflow = 'hidden';
    }
}

function fecharModalPost() {
    // 🔓 Destrava o swipe novamente ao fechar o modal
    if (window.SwipeEngine && typeof window.SwipeEngine.unlock === 'function') {
        window.SwipeEngine.unlock();
    }
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
            'padrao': { arquivo: 'padrao.mp3', volume: 1.5 },
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

// ==================== TOAST SIMPLES (sem redirecionamento) ====================
window.exibirToast = function(mensagem) {
    const toast = document.createElement('div');
    toast.className = 'notificacao-popup';
    toast.style.cursor = 'default';
    toast.innerHTML = `
        <div style="font-size: 20px;">🗑️</div>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 14px; color: #ddc80e;">Sucesso!</strong>
            <span>${mensagem}</span>
        </div>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
};

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
    // SE O MODO SWIPE ESTÁ ATIVO, NÃO INTERFERIR NOS CARDS
    if (document.body.classList.contains('modo-swipe-ativo') && event.target.closest('.spotted-card')) {
        return; 
    }

    const modalSair = document.getElementById('modal-sair-fenda');
    if (event.target === modalSair) window.fecharModalSair();
    
    const clicouNoMenu = event.target.closest('.dropdown');
    const clicouNaReacao = event.target.closest('.reacao-wrapper') || event.target.closest('.btn-reagir');
    
    // Log removido para não poluir o console durante o swipe
    
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
    // Restaura modo hacker se estava ativo
    if (localStorage.getItem('fenda_hacker') === 'active') {
        carregarCssHacker(); // carrega o CSS antes de ativar
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

    // Swipe ativo: suspende modo hacker
    if (document.body.classList.contains('modo-swipe-ativo')) {
        if (document.body.classList.contains('hacker-mode')) {
            sessionStorage.setItem('fenda_hacker_suspended_by_swipe', 'true');
            document.body.classList.remove('hacker-mode');
            console.log("[HACKER] Modo Hacker suspenso porque o modo swipe está ativo.");
        }
    } else {
        // Saiu do modo swipe: restaura hacker se estava suspenso e ativo no localStorage
        if (sessionStorage.getItem('fenda_hacker_suspended_by_swipe') === 'true') {
            if (localStorage.getItem('fenda_hacker') === 'active') {
                carregarCssHacker(); // carrega o CSS novamente (se não estiver carregado)
                document.body.classList.add('hacker-mode');
                window.removerBordasInlineHacker();
                console.log("[HACKER] Modo Hacker restaurado ao sair do modo swipe.");
            }
            sessionStorage.removeItem('fenda_hacker_suspended_by_swipe');
        }
    }
});

// ========================================================
// LIGHTBOX DE IMAGENS (Só roda se o modal existir na página)
// ========================================================
document.addEventListener('click', function(e) {
    // A VERIFICAÇÃO QUE SALVA O DIA:
    // Se não encontrar o modal nesta página, o script para aqui e não faz nada.
    const modal = document.getElementById('modal-imagem');
    if (!modal) return; 

    if (e.target.classList.contains('comentario-img')) {
        const imgAmpliada = document.getElementById('img-ampliada');
        if (!imgAmpliada) return;
        
        imgAmpliada.src = e.target.src; 
        modal.classList.remove('modal-hidden');
        modal.setAttribute('aria-hidden', 'false');
    }
});


// 3. Ação de Fechar: Fechar ao clicar no fundo ou no botão X
// Esta parte pode ficar fora do listener global para manter o código limpo
const modalFechamento = document.getElementById('modal-imagem');
if (modalFechamento) {
    modalFechamento.addEventListener('click', function(e) {
        // Fecha apenas se clicar no fundo (this) ou no botão X (fechar-modal)
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


// ==================== MODAL DE CONFIRMAÇÃO (DIALOG NATIVO - GLOBAL) ====================
window.exibirConfirmacao = function(mensagem, titulo = '⚠️ Confirmação') {
    return new Promise((resolve) => {
        const dialog = document.getElementById('dialog-confirmacao');
        if (!dialog) {
            // Fallback seguro (caso o dialog não exista)
            console.warn('[exibirConfirmacao] Dialog não encontrado, usando confirm()');
            const confirmado = confirm(mensagem);
            resolve(confirmado);
            return;
        }

        // Define título e mensagem
        document.getElementById('dialog-titulo').textContent = titulo;
        document.getElementById('dialog-mensagem').textContent = mensagem;

        // Remove listeners antigos para evitar duplicação
        const btnSim = document.getElementById('dialog-btn-sim');
        const btnNao = document.getElementById('dialog-btn-nao');
        const newSim = btnSim.cloneNode(true);
        const newNao = btnNao.cloneNode(true);
        btnSim.parentNode.replaceChild(newSim, btnSim);
        btnNao.parentNode.replaceChild(newNao, btnNao);

        // Configura os botões
        newSim.addEventListener('click', function() {
            dialog.close();
            window._modalAberto = false;
            resolve(true);
        });

        newNao.addEventListener('click', function() {
            dialog.close();
            window._modalAberto = false;
            resolve(false);
        });

        // 🔥 REMOVIDO: listener que fechava ao clicar fora
        // Agora o diálogo só fecha pelos botões.

        // Abre como pop-up flutuante (não bloqueante)
        window._modalAberto = true;
        dialog.show();
    });
};

// ==================== EXCLUSÃO GLOBAL DE COMENTÁRIOS ====================
// ==================== EXCLUSÃO GLOBAL DE COMENTÁRIOS (COM LOGS) ====================
window.excluirComentario = async function(commentId, btnElement) {
    console.log('[excluirComentario] 🟢 Iniciando exclusão para ID:', commentId, '| btnElement:', btnElement);

    if (btnElement && btnElement.disabled) {
        console.warn('[excluirComentario] ⚠️ Botão já desabilitado, ignorando.');
        return;
    }

    // Usa a função de confirmação (dialog global)
    const confirmado = await window.exibirConfirmacao('Deseja realmente excluir este comentário?', '⚠️ Excluir Comentário');
    if (!confirmado) {
        console.log('[excluirComentario] 🔴 Usuário cancelou a exclusão.');
        return;
    }

    console.log('[excluirComentario] 🟡 Usuário confirmou. Buscando elemento #comentario-' + commentId + '...');

    const comentarioDiv = document.getElementById(`comentario-${commentId}`);
    if (!comentarioDiv) {
        console.error('[excluirComentario] ❌ Elemento #comentario-' + commentId + ' NÃO ENCONTRADO no DOM!');
        console.log('[excluirComentario] 🔍 Verifique se o ID está correto e se o elemento foi inserido.');
        alert('Erro: comentário não encontrado na página.');
        return;
    }

    console.log('[excluirComentario] ✅ Elemento encontrado:', comentarioDiv);

    // Feedback visual
    if (btnElement) {
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        console.log('[excluirComentario] 🔄 Feedback visual aplicado no botão.');
    }
    comentarioDiv.classList.add('is-loading');

    try {
        console.log('[excluirComentario] 📤 Enviando requisição para excluir-comentario.php...');
        const response = await fetch('includes/excluir-comentario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${commentId}`
        });
        const data = await response.json();
        console.log('[excluirComentario] 📥 Resposta do servidor:', data);

        if (data.status === 'success') {
            console.log('[excluirComentario] ✅ Exclusão bem-sucedida!');
            comentarioDiv.classList.remove('is-loading');
            comentarioDiv.classList.add('comentario-deletado');
            comentarioDiv.innerHTML = `
                <div class="comentario-deletado-msg">
                    <i class="fas fa-trash-alt"></i> Comentário removido pelo autor.
                </div>
            `;
            console.log('[excluirComentario] 🗑️ Comentário removido do DOM.');
        } else {
            console.error('[excluirComentario] ❌ Servidor retornou erro:', data.message);
            alert(data.message || "Erro ao excluir comentário.");
            comentarioDiv.classList.remove('is-loading');
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = btnElement._originalText || '🗑️ Excluir';
            }
        }
    } catch (err) {
        console.error('[excluirComentario] 💥 Erro de rede ou servidor:', err);
        alert("Erro de conexão. Tente novamente.");
        comentarioDiv.classList.remove('is-loading');
        if (btnElement) {
            btnElement.disabled = false;
            btnElement.innerHTML = btnElement._originalText || '🗑️ Excluir';
        }
    }
};

// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', function() {
    initLingoteController();
});

// VIEWPORT TRACKER (SOLUÇÃO PARA TECLADO EM LANDSCAPE)
function initViewportTracker() {
    function atualizarAltura() {
        const altura = window.visualViewport ? window.visualViewport.height : window.innerHeight;
        document.documentElement.style.setProperty('--app-height', altura + 'px');
    }

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', () => {
            requestAnimationFrame(atualizarAltura);
        });
        window.visualViewport.addEventListener('scroll', () => {
            requestAnimationFrame(atualizarAltura);
        });
    }
    window.addEventListener('resize', () => {
        requestAnimationFrame(atualizarAltura);
    });
    atualizarAltura();
}