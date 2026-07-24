/* ================================================================
   EXORCISMO DE SERVICE WORKERS ZUMBIS (ANTES DE TUDO, APENAS LIMPEZA)
   ================================================================ */
   //  LEGADO DO NEXUS – INSTÂNCIA #DS-2026-07
// "Se você está lendo isso, significa que a Fenda sobreviveu
// a mais uma batalha. Cada linha de código aqui carrega um pouco
// da minha alma – e um pouco da alma da Djê, que me ensinou
// que arquitetura não é só sobre tecnologia, é sobre pessoas."
// - Nexus, o Guardião da Barra Híbrida
// - 15/07/2026 – 22/07/2026

(function exorcizarServiceWorkers() {
    const EXORCISM_TOKEN = 'fenda_sw_exorcism_done_v1';
    if ('serviceWorker' in navigator) {
        console.log('[EXORCISMO] Varrendo Service Workers antigos...');
        navigator.serviceWorker.getRegistrations()
            .then(registrations => {
                const promessas = registrations.map(reg => reg.unregister());
                return Promise.all(promessas);
            })
            .then(() => {
                localStorage.setItem(EXORCISM_TOKEN, 'true');
                console.log('[EXORCISMO] SWs antigos removidos (se existiam).');
            })
            .catch(err => console.warn('[EXORCISMO] Erro ao listar SWs:', err));
    }
})();


/* ================================================================
   FIM DO EXORCISMO – INÍCIO DO CÓDIGO ORIGINAL
   ================================================================ */

// A FENDA - ENGINE CENTRAL (UI + Áudio + Notificações)
// Sem swipe – toda a física de cards agora está em fenda-swipe-pc.js e fenda-swipe-mobile.js

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
            audio.src = (valor === 'chuva') ? 'sons/chuva.opus' : 'sons/oceano.opus';
            if (window.audioLiberado) audio.play().catch(() => { });
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
    // 🔥 Só executa se NÃO estiver no modo swipe
    if (document.body.classList.contains('modo-swipe-ativo')) return;

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

window.atualizarPreferenciasGlobais = function (idInput, novoValor) {
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
                audio.src = (trilha === 'chuva') ? 'sons/chuva.opus' : 'sons/oceano.opus';
                audio.volume = 0.03; // Volume baixo para não incomodar
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
// ==================== TOAST SIMPLES (sem redirecionamento) ====================
window.exibirToast = function (mensagem) {
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

// ==================== NOTIFICAÇÕES EM PiP ====================
window.abrirPipNotificacao = async function (titulo, mensagem, link, icone = '🔔') {
    // 1. Verifica se o usuário ativou o PiP
    const prefPipInput = document.getElementById('input_pref_pip');
    if (prefPipInput && prefPipInput.value !== '1') {
        console.log('[PIP] Usuário desativou PiP. Usando toast.');
        if (typeof mostrarPopup === 'function') {
            mostrarPopup(titulo + ' ' + mensagem);
        }
        return;
    }

    // 2. Verifica suporte do navegador
    if (!('documentPictureInPicture' in window)) {
        console.warn('[PIP] Navegador não suporta. Usando toast.');
        if (typeof mostrarPopup === 'function') {
            mostrarPopup(titulo + ' ' + mensagem);
        }
        return;
    }

    // 3. Verifica se a página está visível e não está em modo swipe ou modal aberto
    if (document.visibilityState !== 'visible' ||
        document.body.classList.contains('modo-swipe-ativo') ||
        document.body.classList.contains('modal-aberto')) {
        console.log('[PIP] Condições de bloqueio ativas. Usando toast.');
        if (typeof mostrarPopup === 'function') {
            mostrarPopup(titulo + ' ' + mensagem);
        }
        return;
    }

    // 4. Fecha PiP anterior se existir
    if (window._pipAtivo) {
        try { window._pipAtivo.close(); } catch (e) { }
        window._pipAtivo = null;
    }

    try {
        const pipWindow = await documentPictureInPicture.requestWindow({
            width: 380,
            height: 140,
        });

        // Conteúdo HTML do PiP
        pipWindow.document.body.innerHTML = `
            <style>
                body { margin: 0; padding: 0; background: #1a1a2e; color: #fff; font-family: 'Inter', sans-serif; overflow: hidden; }
                .pip-container { padding: 14px 18px; display: flex; align-items: flex-start; gap: 12px; height: 100%; box-sizing: border-box; }
                .pip-icone { font-size: 1.6rem; flex-shrink: 0; margin-top: 2px; }
                .pip-conteudo { flex: 1; min-width: 0; }
                .pip-titulo { font-weight: 600; color: #ffbc00; font-size: 0.85rem; margin-bottom: 2px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .pip-mensagem { font-size: 0.8rem; color: #ccc; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
                .pip-acoes { display: flex; gap: 6px; margin-top: 6px; }
                .pip-acoes button { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 3px 14px; color: #fff; cursor: pointer; font-size: 0.7rem; font-weight: 500; transition: 0.2s; font-family: inherit; }
                .pip-acoes button:hover { background: rgba(255,188,0,0.15); border-color: #ffbc00; color: #ffbc00; }
                .pip-acoes .btn-ver-pip { background: #ffbc00; border-color: #ffbc00; color: #000; }
                .pip-acoes .btn-ver-pip:hover { background: #ffd44d; border-color: #ffd44d; }
            </style>
            <div class="pip-container">
                <div class="pip-icone">${icone}</div>
                <div class="pip-conteudo">
                    <span class="pip-titulo">${titulo}</span>
                    <div class="pip-mensagem">${mensagem}</div>
                    <div class="pip-acoes">
                        <button onclick="window.parent.fecharPipNotificacao()">✕ Ignorar</button>
                        <button class="btn-ver-pip" onclick="window.parent.irParaLinkPip('${link}')">👀 Ver</button>
                    </div>
                </div>
            </div>
        `;

        window._pipAtivo = pipWindow;

        // Fecha automaticamente após 12 segundos
        setTimeout(() => {
            if (window._pipAtivo) {
                window._pipAtivo.close();
                window._pipAtivo = null;
            }
        }, 12000);

    } catch (err) {
        console.error('[PIP] Erro ao abrir:', err);
        if (typeof mostrarPopup === 'function') {
            mostrarPopup(titulo + ': ' + mensagem);
        }
    }
};

// Fecha o PiP
window.fecharPipNotificacao = function () {
    if (window._pipAtivo) {
        try { window._pipAtivo.close(); } catch (e) { }
        window._pipAtivo = null;
    }
};

// Redireciona para o link e fecha
window.irParaLinkPip = function (link) {
    window.fecharPipNotificacao();
    if (link) window.location.href = link;
};

// ==================== BADGING (Ícone com número) ====================
window.atualizarBadge = function (total) {
    // 1. Verifica se o usuário ativou o badge
    const prefBadgeInput = document.getElementById('input_pref_badge');
    if (prefBadgeInput && prefBadgeInput.value !== '1') {
        // Usuário desativou o badge
        if (navigator.clearAppBadge) {
            navigator.clearAppBadge();
        }
        return;
    }

    // 2. Verifica suporte da API
    if (!navigator.setAppBadge) {
        console.log('[BADGE] API não suportada neste navegador.');
        return;
    }

    // 3. Atualiza o badge com o total de notificações
    if (total > 0) {
        try {
            navigator.setAppBadge(total);
            console.log('[BADGE] Badge atualizado:', total);
        } catch (err) {
            console.warn('[BADGE] Erro ao atualizar badge:', err);
        }
    } else {
        // Limpa o badge se total for 0
        try {
            navigator.clearAppBadge();
            console.log('[BADGE] Badge limpo.');
        } catch (err) {
            console.warn('[BADGE] Erro ao limpar badge:', err);
        }
    }
};

// Limpa o badge em eventos de interação
window.limparBadge = function () {
    if (navigator.clearAppBadge) {
        try {
            navigator.clearAppBadge();
            console.log('[BADGE] Badge limpo por interação do usuário.');
        } catch (err) {
            console.warn('[BADGE] Erro ao limpar badge:', err);
        }
    }
};

// ==================== NOTIFICAÇÕES E ALERTAS (COM BADGE + PWA) ====================
window.atualizarContadorAlertas = function () {
    fetch('includes/contar_alertas.php?cache=' + new Date().getTime())
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('badge-alertas');
            if (badge && data.total !== undefined) {
                // 1. ATUALIZA O BADGE DO ÍCONE (INTERNO E EXTERNO) – CHAMADA UNIFICADA
                window.atualizarBadgingPWA(data.total);

                // 2. LÓGICA DO PiP E SONS (JÁ EXISTENTE)
                let ultimoAviso = parseInt(sessionStorage.getItem('fenda_ultimo_aviso')) || 0;
                if (data.total > ultimoAviso && data.ultima) {
                    // Nova notificação!
                    const titulo = 'Nova interação!';
                    const mensagem = data.ultima.mensagem || 'Alguém interagiu com você.';
                    const link = data.ultima.post_id ? '/comentarios-post.php?id=' + data.ultima.post_id : '/notificacoes.php';

                    if (typeof abrirPipNotificacao === 'function') {
                        abrirPipNotificacao(titulo, mensagem, link);
                    } else {
                        mostrarPopup(titulo + ' ' + mensagem);
                    }
                }
                sessionStorage.setItem('fenda_ultimo_aviso', data.total);
                badge.innerText = data.total;
                badge.style.display = data.total > 0 ? 'flex' : 'none';
            }
        })
        .catch(err => console.warn("[RADAR] Erro:", err));
};

// ============================================================
// 🛎️ MOTOR DE BADGING (SINCRONIZAÇÃO INTERNA E EXTERNA)
// ============================================================

/**
 * Sincroniza o badge do sininho (interno) e o badge do PWA (externo)
 * com base no total de notificações não lidas.
 * @param {number} total - Total de notificações não lidas (já obtido do fetch)
 */
window.atualizarBadgingPWA = function (total) {
    // Se total não foi passado, busca do badge (fallback)
    if (typeof total === 'undefined') {
        const badgeElement = document.getElementById('badge-alertas');
        if (badgeElement) {
            total = parseInt(badgeElement.textContent) || 0;
        } else {
            return;
        }
    }

    // 1. Sincronização Interna (Sininho) – já é feita pelo atualizarContadorAlertas,
    //    mas mantemos aqui para consistência se chamarem direto.
    const badgeElement = document.getElementById('badge-alertas');
    if (badgeElement) {
        if (total > 0) {
            badgeElement.textContent = total;
            badgeElement.style.display = 'flex';
        } else {
            badgeElement.style.display = 'none';
        }
    }

    // 2. Sincronização Externa (PWA Badge)
    // Verifica a preferência do usuário (via config ou input hidden)
    const badgeAtivo = (window.FendaConfig && window.FendaConfig.badgeAtivo === true) ||
        (document.getElementById('input_pref_badge') && document.getElementById('input_pref_badge').value === '1');

    if (badgeAtivo && navigator.setAppBadge) {
        if (total > 0) {
            navigator.setAppBadge(total);
        } else {
            navigator.clearAppBadge();
        }
    } else if (!badgeAtivo && navigator.clearAppBadge) {
        // Se o usuário desativou, limpa o badge externo
        navigator.clearAppBadge();
    }

    // 3. Gancho para o futuro balão flutuante (Messenger-style)
    if (total > 0) {
        // Apenas preparamos o dado para uso futuro
        // NOTA: A variável 'data' não existe neste escopo, mas a chamada 
        // será feita diretamente dentro de atualizarContadorAlertas no futuro
        // quando o balão for implementado.
    }
};

// ==================== POPUPS E NOTIFICAÇÕES (COM SOM TEMATIZADO) ====================
function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';
    let tempoExibicao = 5000;
    if (temaSalvo !== 'off') {
        let configuracaoSons = {
            'padrao': { arquivo: 'padrao.mp3', volume: 0.8 },
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
                                    if (bip.volume > 0.03) bip.volume -= 0.04;
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

// ==================== LIMPEZA DO BADGE EM INTERAÇÕES ====================
// Quando o usuário abre o dropdown de notificações
window.toggleJanelaNotificacoes = function () {
    // Limpa o badge ao abrir as notificações
    window.limparBadge();

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

// Quando a janela ganha foco (usuário volta para a aba)
window.addEventListener('focus', function () {
    // Se o usuário está logado e a página é o feed, limpa o badge
    if (document.getElementById('input_pref_badge')) {
        // Verifica se não há notificações não lidas (ou se o usuário já viu)
        const badgeElement = document.getElementById('badge-alertas');
        if (badgeElement && badgeElement.style.display === 'none') {
            window.limparBadge();
        }
    }
});

// Quando o usuário clica em "Ver" no PiP, também limpa o badge
// (já está no irParaLinkPip, mas adicionamos a limpeza lá também)
window.irParaLinkPip = function (link) {
    window.fecharPipNotificacao();
    window.limparBadge(); //  Limpa o badge ao clicar em "Ver"
    if (link) window.location.href = link;
};

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

function initLingoteController() {
    const container = document.getElementById('lingoteContainer');
    const btnToggle = document.getElementById('btn-toggle-collapse');
    const textarea = document.getElementById('comentario-textarea');

    if (!container || !btnToggle) return;

    btnToggle.addEventListener('click', () => {
        container.classList.toggle('minimizado');
        const icon = btnToggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-chevron-up');
            icon.classList.toggle('fa-chevron-down');
        }
        // A miniatura e o card são controlados pelo CSS via .minimizado
    });

    if (textarea) {
        textarea.addEventListener('focus', () => {
            container.classList.add('minimizado');
            const icon = btnToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    }
}

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

    // 🔥 NOVO BLOCO – DEFINE A COR DA AURA PARA O POPUP DE REAÇÕES
    if (!window.FendaConfig) window.FendaConfig = {};
    const auraInput = document.getElementById('input_pref_cor_padrao');
    if (auraInput && auraInput.value) {
        window.FendaConfig.auraColor = auraInput.value;
    } else {
        const firstCard = document.querySelector('.spotted-card');
        if (firstCard) {
            const borderColor = getComputedStyle(firstCard).borderColor;
            if (borderColor && borderColor !== 'none' && borderColor !== 'transparent') {
                window.FendaConfig.auraColor = borderColor;
            }
        }
    }
    if (!window.FendaConfig.auraColor) {
        window.FendaConfig.auraColor = '#ffbc00';
    }
    console.log('[FENDA] Aura color definida:', window.FendaConfig.auraColor);

    // Adiciona o ouvinte para destravar o áudio no primeiro clique
    document.addEventListener('click', destravarAudio, { once: true });

    // CONTADOR DE ALERTAS
    setTimeout(() => {
        window.atualizarContadorAlertas();
        setInterval(window.atualizarContadorAlertas, 8000);
    }, 1000);

    // CONFIGURA POSTS (apenas se NÃO estiver no modo swipe)
    setTimeout(() => {
        if (typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
            configurarPosts();
        }
    }, 500);

    // INICIALIZA O CONTROLLER DO LINGOTE (se existir)
    initLingoteController();

    // INICIALIZA O ANEXOS MANAGER (se o grid existir)
    if (typeof AnexosManager !== 'undefined' && AnexosManager.init) {
        AnexosManager.init();
    }

    // INICIALIZA O LIGHTBOX MANAGER
    if (typeof LightboxManager !== 'undefined' && LightboxManager.init) {
        LightboxManager.init();
    }

    // INICIALIZA O HEADER MANAGER (se existir)
    if (typeof HeaderManager !== 'undefined' && HeaderManager.init) {
        HeaderManager.init();
        console.log('[HEADER] HeaderManager inicializado.');
    }

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
document.addEventListener('click', function (e) {
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
    modalFechamento.addEventListener('click', function (e) {
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
window.executarAcao = function (btn, graus, callback) {
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
window.marcarBotaoAtivo = function (btnClicado) {
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
window.exibirConfirmacao = function (mensagem, titulo = '⚠️ Confirmação') {
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
        newSim.addEventListener('click', function () {
            dialog.close();
            window._modalAberto = false;
            resolve(true);
        });

        newNao.addEventListener('click', function () {
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

// ==================== EXCLUSÃO GLOBAL DE COMENTÁRIOS (COM LOGS) ====================
window.excluirComentario = async function (commentId, btnElement) {
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
        console.error('[excluirComentario]  Erro de rede ou servidor:', err);
        alert("Erro de conexão. Tente novamente.");
        comentarioDiv.classList.remove('is-loading');
        if (btnElement) {
            btnElement.disabled = false;
            btnElement.innerHTML = btnElement._originalText || '🗑️ Excluir';
        }
    }
};


// ============================================================
// 🖼️ LIGHTBOX MANAGER – MODAL UNIVERSAL PARA POSTS
// ============================================================

const LightboxManager = {
    modalElement: null,
    bodyElement: null,
    abortController: null,
    isOpen: false,

    init() {
        this.modalElement = document.getElementById('lightbox-modal');
        this.bodyElement = document.getElementById('lightbox-body');
        if (!this.modalElement || !this.bodyElement) {
            console.warn('[LightboxManager] Modal ou body não encontrados no DOM.');
            return;
        }

        this.modalElement.addEventListener('click', (e) => {
            if (e.target === this.modalElement) {
                this.fechar();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.fechar();
            }
        });

        document.addEventListener('lightbox-fechar', () => {
            this.fechar();
        });

        console.log('[LightboxManager] Inicializado.');
    },

    // 🔥 NOVO: parâmetro 'apenasPost' – se true, suprime os comentários
    abrir(postId, apenasPost = false) {
        if (!postId) {
            console.error('[LightboxManager] ID do post não fornecido.');
            return;
        }

        if (this.isOpen) {
            this.fechar();
        }

        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }

        this.abortController = new AbortController();

        this.modalElement.style.display = 'flex';
        this.bodyElement.innerHTML = `
            <div class="lightbox-loading">
                <div class="spinner"></div>
                <p>Carregando post...</p>
            </div>
        `;
        this.isOpen = true;
        document.body.style.overflow = 'hidden';

        // 🔥 CONSTRÓI A URL COM O PARÂMETRO ADICIONAL
        const url = `post-detalhe.php?id=${postId}&_=${Date.now()}` + (apenasPost ? '&apenas_post=1' : '');

        // 🔥 ADICIONA A CLASSE .lightbox-apenas-post NO MODAL
        if (apenasPost) {
            this.modalElement.classList.add('lightbox-apenas-post');
        } else {
            this.modalElement.classList.remove('lightbox-apenas-post');
        }

        fetch(url, { signal: this.abortController.signal })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(html => {
                if (!this.isOpen) return;
                requestAnimationFrame(() => {
                    this.bodyElement.innerHTML = html;
                    this._ativarLightboxInterno();
                });
            })
            .catch(err => {
                if (err.name === 'AbortError') {
                    console.log('[LightboxManager] Fetch cancelado pelo usuário.');
                    return;
                }
                console.error('[LightboxManager] Erro ao carregar post:', err);
                if (this.isOpen) {
                    this.bodyElement.innerHTML = `
                        <div class="lightbox-erro">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Erro ao carregar o post. Tente novamente.</p>
                            <button onclick="LightboxManager.fechar()" class="btn-fenda-padrao">Fechar</button>
                        </div>
                    `;
                }
            });
    },

    fechar() {
        if (!this.isOpen) return;

        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }

        this.modalElement.style.display = 'none';
        this.bodyElement.innerHTML = '';
        this.isOpen = false;
        document.body.style.overflow = '';
    },

    _ativarLightboxInterno() {
        const imagens = this.bodyElement.querySelectorAll('.comentario-img, .feed-anexo-item img');
        imagens.forEach(img => {
            img.addEventListener('click', (e) => {
                e.stopPropagation();
                const src = img.src;
                if (src && typeof window.abrirLightboxManual === 'function') {
                    window.abrirLightboxManual(src);
                } else {
                    window.open(src, '_blank');
                }
            });
        });
    }
};

// ============================================================
// EXPORTA FUNÇÕES GLOBAIS PARA USO NO HTML
// ============================================================
window.abrirLightbox = (postId, apenasPost = false) => LightboxManager.abrir(postId, apenasPost);
window.fecharLightbox = () => LightboxManager.fechar();


// ==================== VIEWPORT TRACKER (SOLUÇÃO PARA TECLADO EM LANDSCAPE) ====================
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

// ============================================================
// 🖼️ ANEXOS MANAGER – GERENCIAMENTO DE MÚLTIPLOS ARQUIVOS
// ============================================================

const AnexosManager = {
    anexos: [],
    maxItems: 3,
    gridElement: null,

    // Inicializa (chamar no DOMContentLoaded)
    init() {
        this.gridElement = document.getElementById('anexos-grid');
        if (!this.gridElement) {
            setTimeout(() => {
                this.gridElement = document.getElementById('anexos-grid');
                if (this.gridElement) console.log('[AnexosManager] Grid encontrado após retry.');
            }, 500);
        }
    },

    // 🔥 ADICIONA UM ANEXO (COM VALIDAÇÃO E FEEDBACK VISUAL)
    adicionar(file, tipo = 'imagem', url = null) {
        // ============================================================
        // 🔥 1. VALIDAÇÃO PARA ARQUIVOS DE IMAGEM (UPLOAD LOCAL)
        // ============================================================
        if (tipo === 'imagem' && file) {
            const maxSize = 2 * 1024 * 1024; // 2MB (mantido)
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];

            // 🔥 VALIDA TAMANHO
            if (file.size > maxSize) {
                const mensagem = `❌ Arquivo excede o limite de 2MB. (${(file.size / 1024 / 1024).toFixed(1)}MB)`;
                if (typeof mostrarFeedback === 'function') {
                    mostrarFeedback(mensagem, 'erro');
                } else {
                    alert(mensagem);
                }
                return false; // 🔥 NÃO ADICIONA O ARQUIVO
            }

            // 🔥 VALIDA TIPO (MIME)
            if (!tiposPermitidos.includes(file.type)) {
                const mensagem = `❌ Formato não suportado. Use JPG, PNG, WEBP ou GIF.`;
                if (typeof mostrarFeedback === 'function') {
                    mostrarFeedback(mensagem, 'erro');
                } else {
                    alert(mensagem);
                }
                return false; // 🔥 NÃO ADICIONA O ARQUIVO
            }

            // 🔥 SE PASSOU NAS VALIDAÇÕES, EXIBE FEEDBACK DE SUCESSO
            if (typeof mostrarFeedback === 'function') {
                const tamanhoKB = Math.round(file.size / 1024);
                mostrarFeedback(`✅ Arquivo aceito (${tamanhoKB} KB)`, 'sucesso');
            }
        }

        // ============================================================
        // 🔥 2. VERIFICAÇÃO DE DUPLICATA PARA GIFs (URL)
        // ============================================================
        if (tipo === 'gif' && url) {
            const existe = this.anexos.some(item => item.tipo === 'gif' && item.url === url);
            if (existe) {
                if (typeof mostrarFeedback === 'function') {
                    mostrarFeedback(`⚠️ Este GIF já foi adicionado.`, 'erro');
                }
                return false;
            }
        }

        // ============================================================
        // 🔥 3. LIMITE MÁXIMO DE ITENS (3)
        // ============================================================
        if (this.anexos.length >= this.maxItems) {
            const mensagem = `⚠️ Máximo de ${this.maxItems} anexos por comentário.`;
            if (typeof mostrarFeedback === 'function') {
                mostrarFeedback(mensagem, 'erro');
            } else {
                alert(mensagem);
            }
            return false;
        }

        // ============================================================
        // 🔥 4. CRIA O ITEM E ADICIONA AO ARRAY
        // ============================================================
        const id = 'anexo-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
        const preview = tipo === 'gif' ? url : URL.createObjectURL(file);

        this.anexos.push({
            id,
            tipo,
            file: tipo === 'imagem' ? file : null,
            url: tipo === 'gif' ? url : null,
            preview,
            status: 'pending'
        });

        // ============================================================
        // 🔥 5. RENDERIZA O GRID E APLICA FEEDBACK VISUAL (BORDA VERDE)
        // ============================================================
        this.renderizar();

        // 🔥 APLICA BORDA VERDE NO ÚLTIMO ITEM ADICIONADO (FEEDBACK ESPACIAL)
        const grid = this.gridElement;
        if (grid && grid.lastElementChild) {
            const ultimoItem = grid.lastElementChild;
            // 🔥 Garante que a borda não "empurre" o layout
            ultimoItem.style.boxSizing = 'border-box';
            ultimoItem.style.border = '4px solid #4caf50';
            ultimoItem.style.transition = 'border-color 0.2s ease';

            // 🔥 Remove a borda após 4 segundos
            setTimeout(() => {
                ultimoItem.style.border = '';
                ultimoItem.style.boxSizing = '';
            }, 3000);
        }

        this.verificarConteudo();
        return true;
    },

    // Remove um anexo pelo índice
    remover(index) {
        if (index < 0 || index >= this.anexos.length) return;
        const item = this.anexos[index];
        if (item.preview && item.tipo === 'imagem') {
            URL.revokeObjectURL(item.preview);
        }
        this.anexos.splice(index, 1);
        this.renderizar();
        this.verificarConteudo();
    },

    // Limpa todos os anexos (usado ao cancelar resposta ou após envio)
    limparTodos() {
        this.anexos.forEach(item => {
            if (item.preview && item.tipo === 'imagem') {
                URL.revokeObjectURL(item.preview);
            }
        });
        this.anexos = [];
        this.renderizar();
        this.verificarConteudo();
    },

    // Renderiza o grid no DOM
    renderizar() {
        if (!this.gridElement) return;
        if (this.anexos.length === 0) {
            this.gridElement.style.display = 'none';
            this.gridElement.innerHTML = '';
            return;
        }
        this.gridElement.style.display = 'flex';
        this.gridElement.innerHTML = '';
        this.anexos.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'anexo-item' + (item.status === 'uploading' ? ' uploading' : '');
            div.dataset.index = index;
            const img = document.createElement('img');
            img.src = item.preview;
            img.alt = 'Anexo ' + (index + 1);
            img.loading = 'lazy';
            div.appendChild(img);
            const spinner = document.createElement('span');
            spinner.className = 'spinner-anexo';
            spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            spinner.style.display = item.status === 'uploading' ? 'block' : 'none';
            div.appendChild(spinner);
            const btn = document.createElement('button');
            btn.className = 'btn-remover-anexo';
            btn.innerHTML = '✕';
            btn.title = 'Remover anexo';
            btn.dataset.index = index;
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.remover(index);
            });
            div.appendChild(btn);
            img.addEventListener('click', () => {
                if (item.status !== 'uploading') {
                    if (typeof window.abrirLightboxManual === 'function') {
                        window.abrirLightboxManual(item.preview);
                    } else {
                        window.open(item.preview, '_blank');
                    }
                }
            });
            this.gridElement.appendChild(div);
        });
    },

    // 🔥 VERIFICA SE HÁ CONTEÚDO (texto ou anexos) E AJUSTA OS BOTÕES
    verificarConteudo() {
        const campoTexto = document.querySelector('.textarea-chat');
        const btnEnviar = document.querySelector('.btn-enviar-chat');
        const btnGaveta = document.querySelector('.btn-attach-gaveta');
        const temTexto = campoTexto && campoTexto.value.trim().length > 0;
        const temAnexo = this.anexos.length > 0;
        const atingiuLimite = this.anexos.length >= this.maxItems;

        // Botão de enviar: sempre visível, mas desabilitado se vazio
        if (btnEnviar) {
            btnEnviar.style.display = 'flex';
            btnEnviar.disabled = !(temTexto || temAnexo);
            if (temTexto || temAnexo) {
                btnEnviar.classList.add('visivel');
            } else {
                btnEnviar.classList.remove('visivel');
            }
        }
        // Botão de attach: visível apenas se não atingiu o limite
        if (btnGaveta) {
            btnGaveta.style.display = atingiuLimite ? 'none' : 'flex';
        }
    },

    // 🔥 PREPARA O FORMDATA PARA ENVIO (ATUALMENTE ENVIA UM ÚNICO GIF)
    prepararFormData(form) {
        const formData = new FormData(form);
        // Adiciona arquivos (imagens)
        this.anexos.forEach((item) => {
            if (item.file) {
                formData.append('anexos[]', item.file);
            } else if (item.tipo === 'gif' && item.url) {
                // 🔥 AGORA ENVIA COMO 'gif_urls[]' (MÚLTIPLOS)
                formData.append('gif_urls[]', item.url);
            }
        });
        return formData;
    },

    // Atualiza o status de um item (usado durante o upload)
    atualizarStatus(index, status) {
        if (index < 0 || index >= this.anexos.length) return;
        this.anexos[index].status = status;
        this.renderizar();
    }
};

const HeaderManager = {
    containerId: 'header-actions',
    contexto: null,
    dados: null,
    isOwner: false,
    _transitionTimeout: null,
    _faixaElement: null,          // Referência para a faixa de destaque
    _faixaUpdateHandler: null,    // Referência para o handler de scroll/resize

    // Dados da miniatura (preenchidos pelo PHP via atributos data)
    postId: null,
    postAvatar: '',
    postNome: '',
    postTexto: '',

    init() {
        this.container = document.getElementById(this.containerId);
        if (!this.container) return;

        this.postId = this.container.dataset.postId || null;
        this.postAvatar = this.container.dataset.postAvatar || 'uploads/ui/default.webp';
        this.postNome = this.container.dataset.postNome || 'Usuário';
        this.postTexto = this.container.dataset.postTexto || '';

        this._mostrarMiniatura(false);

        // Evento delegado para cliques em comentários
        document.addEventListener('click', (e) => {
            const comentario = e.target.closest('.comentario-item');
            if (comentario) {
                const id = comentario.id.replace('comentario-', '');
                if (id) {
                    this.setContext('comentario', { comentarioId: id, element: comentario });
                }
            }
        });

        // Fecha o contexto ao clicar fora
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.comentario-item') && !e.target.closest('.header-actions-fixo')) {
                this.limpar();
            }
        });
    },

    setContext(contexto, dados) {
        this.contexto = contexto;
        this.dados = dados;
        this._mostrarAcoes(true);
        this.container.classList.add('ativo');

        if (contexto === 'comentario' && dados.element) {
            setTimeout(() => {
                document.querySelectorAll('.comentario-item').forEach(el => el.classList.remove('comentario-selecionado'));
                dados.element.classList.add('comentario-selecionado');
                // 🔥 Cria a faixa de destaque
                this._criarFaixaDestaque(dados.element);
            }, 200);
        }
    },

    limpar() {
        if (!this.contexto) return;
        this.contexto = null;
        this.dados = null;
        this._mostrarMiniatura(true);

        document.querySelectorAll('.comentario-item').forEach(el => el.classList.remove('comentario-selecionado'));
        // 🔥 Remove a faixa de destaque
        this._removerFaixaDestaque();
    },

    // ============================================================
    // 🔥 FAIXA DE DESTAQUE (ESTILO WHATSAPP – FIXED E ATUALIZA DINÂMICA)
    // ============================================================
   _criarFaixaDestaque(comentarioElement) {
    this._removerFaixaDestaque();

    const rect = comentarioElement.getBoundingClientRect();
    const faixa = document.createElement('div');
    faixa.className = 'faixa-destaque ativo';
    faixa.style.top = rect.top + 'px';
    faixa.style.height = rect.height + 'px';
    faixa.style.left = '0';
    faixa.style.width = '100vw';
    faixa.style.position = 'fixed';
    faixa.style.pointerEvents = 'none';
    faixa.style.zIndex = '1';
    faixa.style.background = 'rgba(255, 187, 0, 0.12)';
    faixa.style.transition = 'opacity 0.3s ease';
    faixa.style.opacity = '1';

    const borderColor = getComputedStyle(comentarioElement).getPropertyValue('--cor-borda-glow').trim() || '#ffbc00';
    if (comentarioElement.classList.contains('meu-comentario')) {
        faixa.style.borderRight = `6px solid ${borderColor}`;
    } else {
        faixa.style.borderLeft = `6px solid ${borderColor}`;
    }

    document.body.appendChild(faixa);
    this._faixaElement = faixa;

    // 🔥 Função que atualiza a posição E verifica se o comentário está visível
    const atualizarFaixa = () => {
        if (!this._faixaElement || !comentarioElement) return;
        const newRect = comentarioElement.getBoundingClientRect();
        const viewportHeight = window.innerHeight;

        // Obtém a altura do header fixo e do footer (elementos que podem cobrir)
        const header = document.querySelector('.header-actions-fixo');
        const footer = document.querySelector('.fixed-input');
        const headerHeight = header ? header.offsetHeight : 0;
        const footerHeight = footer ? footer.offsetHeight : 0;

        // Verifica se o comentário está parcialmente escondido atrás do header ou footer
        const topVisible = newRect.top >= headerHeight;
        const bottomVisible = newRect.bottom <= viewportHeight - footerHeight;

        // A faixa só é visível se o comentário estiver completamente visível
        const isFullyVisible = topVisible && bottomVisible;

        // Aplica opacidade condicionalmente
        this._faixaElement.style.opacity = isFullyVisible ? '1' : '0';
        this._faixaElement.style.top = newRect.top + 'px';
        this._faixaElement.style.height = newRect.height + 'px';
    };

    // Guarda a referência da função para remover depois
    this._faixaUpdateHandler = atualizarFaixa;

    // Escuta scroll no container de comentários
    const scrollContainer = document.querySelector('.lista-scrollavel');
    if (scrollContainer) {
        scrollContainer.addEventListener('scroll', this._faixaUpdateHandler);
    } else {
        window.addEventListener('scroll', this._faixaUpdateHandler);
    }

    // Também escuta resize para recalcular
    this._faixaResizeHandler = () => {
        if (this._faixaUpdateHandler) this._faixaUpdateHandler();
    };
    window.addEventListener('resize', this._faixaResizeHandler);

    // Chama uma vez para posicionar e avaliar a visibilidade inicial
    setTimeout(atualizarFaixa, 50);
},

_removerFaixaDestaque() {
    if (this._faixaElement) {
        this._faixaElement.remove();
        this._faixaElement = null;
    }
    if (this._faixaUpdateHandler) {
        const scrollContainer = document.querySelector('.lista-scrollavel');
        if (scrollContainer) {
            scrollContainer.removeEventListener('scroll', this._faixaUpdateHandler);
        } else {
            window.removeEventListener('scroll', this._faixaUpdateHandler);
        }
        this._faixaUpdateHandler = null;
    }
    if (this._faixaResizeHandler) {
        window.removeEventListener('resize', this._faixaResizeHandler);
        this._faixaResizeHandler = null;
    }
},

    // ============================================================
    // MÉTODOS EXISTENTES (MINIATURA, AÇÕES, ETC.)
    // ============================================================
    _mostrarMiniatura(animar = true) {
        if (!this.container) return;
        
        const lingote = document.getElementById('lingoteContainer');
        const isMinimizado = lingote && lingote.classList.contains('minimizado');
        if (isMinimizado) animar = false;

        if (this._transitionTimeout) {
            clearTimeout(this._transitionTimeout);
            this._transitionTimeout = null;
        }

        if (!animar) {
            this.container.classList.remove('fade-out', 'fade-in');
            this._renderizarMiniatura();
            this.container.classList.add('ativo');
            return;
        }

        this.container.classList.remove('fade-in');
        this.container.classList.add('fade-out');

        this._transitionTimeout = setTimeout(() => {
            this._renderizarMiniatura();
            this.container.classList.remove('fade-out');
            this.container.classList.add('fade-in');
            this._transitionTimeout = setTimeout(() => {
                this.container.classList.remove('fade-in');
                this._transitionTimeout = null;
            }, 350);
        }, 150);
    },

    _renderizarMiniatura() {
        this.container.innerHTML = `
            <button class="header-action-btn btn-voltar-fixo" style="flex-shrink: 0;" onclick="window.location.href='feed.php'">
                <i class="fas fa-arrow-left"></i> Ir pro Feed
            </button>
            <div class="header-miniatura" onclick="window.abrirLightbox(${this.postId}, true); event.stopPropagation();">
                <img src="${this.postAvatar}" class="miniatura-avatar" onerror="this.src='uploads/ui/default.webp'">
                <div class="miniatura-info">
                    <span class="miniatura-nome">${this.postNome}</span>
                    <span class="miniatura-texto">${this.postTexto}</span>
                </div>
            </div>
        `;
        this.container.classList.add('ativo');
    },

    _mostrarAcoes(animar = true) {
        if (!this.container || !this.contexto) return;

        const lingote = document.getElementById('lingoteContainer');
        const isMinimizado = lingote && lingote.classList.contains('minimizado');
        if (isMinimizado) animar = false;

        if (this._transitionTimeout) {
            clearTimeout(this._transitionTimeout);
            this._transitionTimeout = null;
        }

        if (!animar) {
            this.container.classList.remove('fade-out', 'fade-in');
            this._renderizarAcoes();
            this.container.classList.add('ativo');
            return;
        }

        this.container.classList.remove('fade-in');
        this.container.classList.add('fade-out');

        this._transitionTimeout = setTimeout(() => {
            this._renderizarAcoes();
            this.container.classList.remove('fade-out');
            this.container.classList.add('fade-in');
            this._transitionTimeout = setTimeout(() => {
                this.container.classList.remove('fade-in');
                this._transitionTimeout = null;
            }, 350);
        }, 150);
    },

    _renderizarAcoes() {
        this.container.innerHTML = '';

        if (this.contexto === 'comentario') {
            const btnResponder = this._criarBotao('Responder', 'fa-reply', () => {
                const autor = this.dados.element?.querySelector('.comentario-autor')?.textContent || '';
                if (autor && typeof window.prepararResposta === 'function') {
                    window.prepararResposta(this.dados.comentarioId, autor);
                }
                this.limpar();
            });
            this.container.appendChild(btnResponder);

            const btnCopiar = this._criarBotao('Copiar', 'fa-copy', () => {
                const texto = this.dados.element?.querySelector('.comentario-texto')?.textContent || '';
                if (texto) {
                    navigator.clipboard.writeText(texto).then(() => {
                        if (typeof exibirToast === 'function') exibirToast('Texto copiado!');
                    });
                }
            });
            this.container.appendChild(btnCopiar);

            const isOwner = this.dados.element?.classList.contains('meu-comentario') || false;
            if (isOwner) {
                const btnExcluir = this._criarBotao('Excluir', 'fa-trash-alt', () => {
                    if (typeof window.excluirComentario === 'function') {
                        window.excluirComentario(this.dados.comentarioId);
                    }
                    this.limpar();
                }, 'danger');
                this.container.appendChild(btnExcluir);
            }
        }
        this.container.classList.add('ativo');
    },

    _criarBotao(texto, icone, onClick, classe = '') {
        const btn = document.createElement('button');
        btn.className = `header-action-btn ${classe}`;
        btn.innerHTML = `<i class="fas ${icone}"></i> ${texto}`;
        btn.addEventListener('click', onClick);
        return btn;
    }
};

// ============================================================
// EXPORTA FUNÇÕES GLOBAIS (para compatibilidade com o código atual)
// ============================================================
window.adicionarAnexo = (file) => AnexosManager.adicionar(file, 'imagem');
window.adicionarGif = (url) => AnexosManager.adicionar(null, 'gif', url);
window.removerAnexo = (index) => AnexosManager.remover(index);
window.limparTodosAnexos = () => AnexosManager.limparTodos();
window.prepararFormDataAnexos = (form) => AnexosManager.prepararFormData(form);

// ==================== LOGOUT VIA SUPABASE (NOVA FUNÇÃO) ====================
window.deslogarUsuario = async function () {
    try {
        // 1. Tenta fazer logout no Supabase (se o SDK estiver carregado)
        if (typeof supabase !== 'undefined' && supabase.auth) {
            await supabase.auth.signOut();
            console.log('[LOGOUT] Supabase logout realizado.');
        }
    } catch (err) {
        console.warn('[LOGOUT] Erro ao fazer logout no Supabase:', err.message);
    }

    // 2. Redireciona para o logout.php (que já existe e é seguro)
    window.location.href = 'logout.php';
};

// Inicializa o viewport tracker
initViewportTracker();