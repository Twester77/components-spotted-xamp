<?php
require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🟢 INÍCIO feed.php');

if (!isset($_SESSION['usuario_id'])) {
    fenda_log('🔴 REDIRECIONANDO para index.php (feed.php sem sessão)');
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<!-- Adicionado aria-label para indicar que este bloco é uma barra de ferramentas de controle -->
<div class="controles-feed-topo" role="toolbar" aria-label="Controles do Feed">
    <!-- Adicionado aria-expanded e aria-controls vinculando dinamicamente o botão à gaveta de filtros -->
    <button type="button" id="btn-abrir-filtros" class="btn-fenda-padrao" onclick="toggleFiltrosMobile()" aria-expanded="false" aria-controls="gaveta-filtros-swipe">
        <i class="fas fa-filter" aria-hidden="true"></i> CATEGORIAS
    </button>

    <!-- Adicionado aria-hidden e role para esconder a gaveta do leitor enquanto estiver fechada -->
    <div id="gaveta-filtros-swipe" class="filtros-wrapper-retratil" role="region" aria-label="Painel de Filtros por Categoria" aria-hidden="true">
        <?php include 'includes/filtros.php'; ?>
    </div>
</div>

<main class="main-fenda-total" id="conteudo-principal">
    <!-- Feedbacks visuais ocultados do leitor de tela para não causar poluição auditiva durante o arraste -->
    <div class="feedback-swipe feedback-direita" aria-hidden="true">🩶 AMEI</div>
    <div class="feedback-swipe feedback-esquerda" aria-hidden="true">🗑️ DESCARTAR</div>
    <div class="feedback-swipe feedback-cima" aria-hidden="true">💬 COMENTAR</div>
    <!-- O container começa vazio e o Motor preenche -->
    <!-- Adicionado aria-live para anunciar novos posts injetados dinamicamente via AJAX sem interromper a navegação -->
    <div class="container-feed" role="feed" aria-busy="false" aria-live="polite">
    </div>
</main>

<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais Resultados</button>
</div>

<script src="js/fenda-init.js"></script>

<script>
    // ==================== MOTOR DE LAYOUT UNIVERSAL (COM LOGS) ====================
    function recalcFeedLayout() {
        if (!document.body.classList.contains('modo-swipe-ativo')) return;

        var vw = window.innerWidth;
        var vh = window.innerHeight;
        var isLandscape = vw > vh;

        var cardWidth = isLandscape ?
            Math.max(320, Math.min(vw * 0.60, 600)) :
            Math.max(250, Math.min(vw * 0.70, 550));

        var maxCardHeight = Math.min(vh * 0.8);
        var cardPadding = Math.max(12, cardWidth * 0.05);
        var fontSize = Math.max(0.85, Math.min(cardWidth / 230, 1.5));
        var textMaxHeight = Math.max(60, cardWidth * 0.25);
        var avatarSize = Math.max(34, cardWidth * 0.12);

        var root = document.documentElement;
        root.style.setProperty('--card-width', cardWidth + 'px');
        root.style.setProperty('--card-padding', cardPadding + 'px');
        root.style.setProperty('--card-font-size', fontSize + 'rem');
        root.style.setProperty('--text-max-height', textMaxHeight + 'px');
        root.style.setProperty('--card-max-height', maxCardHeight + 'px');
        root.style.setProperty('--avatar-size', avatarSize + 'px');
        root.style.setProperty('--img-bg', isLandscape ? '#000' : 'transparent');
        root.style.setProperty('--img-fit', isLandscape ? 'contain' : 'cover');
        root.style.setProperty('--img-max-height', isLandscape ? (maxCardHeight * 0.5) + 'px' : 'none');
    }

    var layoutTimeout;

    function debounceLayout() {
        clearTimeout(layoutTimeout);
        layoutTimeout = setTimeout(recalcFeedLayout, 150);
    }
    window.addEventListener('load', recalcFeedLayout);
    window.addEventListener('resize', debounceLayout);
    window.addEventListener('orientationchange', debounceLayout);

    function reforcarLayoutNosCards() {
        console.log('[RECALC] 🔄 reforcarLayoutNosCards chamado.');
        recalcFeedLayout();
    }

    // ==================== TOGGLE FILTROS ====================
    window.toggleFiltrosMobile = function() {
        const gaveta = document.getElementById('gaveta-filtros-swipe');
        const btn = document.getElementById('btn-abrir-filtros');
        if (gaveta) {
            gaveta.classList.toggle('aberto');
            if (gaveta.classList.contains('aberto')) {
                btn.innerHTML = '<i class="fas fa-times"></i> FECHAR FILTROS';
                btn.setAttribute('aria-expanded', 'true');
            } else {
                btn.innerHTML = '<i class="fas fa-filter"></i> FILTRAR CATEGORIAS';
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    };

    // ==================== DECLARAÇÃO GLOBAL ====================
    window._modalAberto = false;

    // ================================================================
    //  🔥 NOVO POPUP DE REAÇÕES RÁPIDAS (LONG PRESS) – APROVADO V3
    // ================================================================

    // Injeção dos estilos essenciais para o popup (fallback)
    (function injectPopupStyles() {
        if (document.getElementById('fenda-reaction-styles')) return;
        const style = document.createElement('style');
        style.id = 'fenda-reaction-styles';
        style.textContent = `
            /* Overlay do popup */
            .reactions-popup-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 30000;
                display: none;
                align-items: flex-start;
                justify-content: center;
                padding-top: 12%;
                font-family: 'Inter', system-ui, sans-serif;
            }
            .reactions-popup-overlay.active {
                display: flex;
            }
            .reactions-popup-card {
                background: rgba(20, 20, 32, 0.95);
                backdrop-filter: blur(6px);
                -webkit-backdrop-filter: blur(6px);
                border: 1px solid rgba(255, 188, 0, 0.3);
                border-radius: 28px;
                padding: 20px 16px 18px 16px;
                max-width: 450px;
                width: 90%;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.7);
                transform: scale(0.95) translateY(10px);
                animation: popIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
                -webkit-user-select: none;
                -ms-user-select: none;
                -moz-user-select: none;
                user-select: none;
                position: relative;
            }
            .reactions-grid {
                display: grid;
                grid-template-columns: repeat(7, 1fr);
                gap: 6px;
                margin-bottom: 14px;
            }
            .reaction-emoji {
                font-size: clamp(1.1rem, 3.5vw, 1.8rem);
                padding: 4px 2px;
                text-align: center;
                border-radius: 16px;
                cursor: pointer;
                transition: all 0.15s ease;
                background: rgba(255, 255, 255, 0.03);
                border: 2px solid transparent;
                line-height: 1.4;
                -webkit-tap-highlight-color: transparent;
            }
            .reaction-emoji:hover {
                background: rgba(255, 255, 255, 0.08);
                transform: scale(1.12);
            }
            .reaction-emoji:active {
                transform: scale(0.88);
                background: rgba(255, 188, 0, 0.15);
                border-color: rgba(255, 188, 0, 0.3);
            }
            .reactions-divider {
                border: none;
                border-top: 1px solid rgba(255, 255, 255, 0.06);
                margin: 8px 0 12px 0;
            }
            .reactions-actions {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }
            .reactions-actions .action-btn {
                background: rgba(255, 255, 255, 0.03);
                border: none;
                border-radius: 60px;
                padding: 8px 14px;
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.9rem;
                font-weight: 500;
                cursor: pointer;
                transition: 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                font-family: inherit;
            }
            .reactions-actions .action-btn:hover {
                background: rgba(255, 255, 255, 0.06);
                color: #fff;
            }
            .reactions-actions .action-btn.danger {
                color: rgba(255, 100, 100, 0.6);
            }
            .reactions-actions .action-btn.danger:hover {
                background: rgba(255, 50, 50, 0.21);
                color: #ff6b6b;
            }
            .reactions-actions .action-btn.primary {
                color: #6af;
            }
            .reactions-actions .action-btn.primary:hover {
                background: rgba(0, 150, 255, 0.08);
                color: #8bf;
            }
            .reactions-actions .action-btn.bookmark {
                color: #f0c27f;
            }
            .reactions-actions .action-btn.bookmark:hover {
                background: rgba(255, 188, 0, 0.08);
                color: #ffbc00;
            }
            /* Animação "+1" */
            .plus-one-anim {
                position: fixed;
                pointer-events: none;
                z-index: 30001;
                font-size: 2.3rem;
                font-weight: 800;
                text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
                animation: floatUp 0.9s ease-out forwards;
                will-change: transform, opacity;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes popIn {
                to { transform: scale(1) translateY(0); }
            }
            @keyframes floatUp {
                0% { opacity: 1; transform: translateY(0) scale(1); }
                100% { opacity: 0; transform: translateY(-100px) scale(1.3); }
            }
            /* Responsividade híbrida (1x7 → 4+3) */
            @media (max-width: 360px) {
                .reactions-popup-card {
                    max-width: 280px;
                    padding: 14px 10px 12px 10px;
                    border-radius: 22px;
                }
                .reactions-grid {
                    grid-template-columns: repeat(4, 1fr);
                    grid-template-rows: auto auto;
                    gap: 2px;
                    max-width: 260px;
                    margin: 0 auto 12px auto;
                }
                .reaction-emoji {
                    font-size: clamp(1rem, 4vw, 1.4rem);
                    padding: 4px 1px;
                    border-radius: 12px;
                }
                .reaction-emoji:last-child {
                    grid-column: span 1;
                    align-content: center;
                }
                .reactions-actions .action-btn {
                    font-size: 0.75rem;
                    padding: 5px 8px;
                    margin-top:5px;
                }
            }
            @media (max-width: 480px) {
                .reactions-popup-card {
                    padding: 16px 12px 14px 12px;
                    border-radius: 22px;
                }
                .reaction-emoji {
                    font-size: clamp(1.1rem, 4vw, 1.6rem);
                    padding: 4px 1px;
                }
                .plus-one-anim {
                    font-size: 1.8rem;
                }
            }
            @media (pointer: coarse) {
                .reaction-emoji:hover {
                    transform: none;
                }
                .reaction-emoji:active {
                    transform: scale(0.88);
                }
            }
        `;
        document.head.appendChild(style);
    })();

    // ==================== MODAL DE AÇÕES (NOVA VERSÃO) ====================
    window.mostrarMenuAcoes = function(postId, isOwner, cardElement) {
        if (window._activeModal) return;
        window._modalAberto = true;
        const targetCard = cardElement || null;

        // Obtém a cor da aura
        let auraColor = '#ffbc00';
        if (targetCard) {
            const computed = getComputedStyle(targetCard);
            auraColor = computed.getPropertyValue('--aura-color') || computed.borderColor || '#ffbc00';
        }
        if (window.FendaConfig && window.FendaConfig.auraColor) {
            auraColor = window.FendaConfig.auraColor;
        }

        const REACTIONS = {
            'amei': '💖',
            'perplecto': '😲',
            'haha': '😂',
            'ranco': '🙄',
            'forca': '🫂',
            'triste': '😢',
            'tendi-nada': '🤔'
        };

        // Cria o overlay
        const overlay = document.createElement('div');
        overlay.className = 'reactions-popup-overlay active';

        // Card do popup
        const popup = document.createElement('div');
        popup.className = 'reactions-popup-card';

        // Grid de reações
        const grid = document.createElement('div');
        grid.className = 'reactions-grid';

        Object.entries(REACTIONS).forEach(([tipo, emoji]) => {
            const btn = document.createElement('div');
            btn.className = 'reaction-emoji';
            btn.textContent = emoji;
            btn.dataset.reaction = tipo;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                fecharPopup();
                if (typeof window.enviarReacao === 'function') {
                    window.enviarReacao(postId, tipo);
                }
                criarAnimacaoMaisUm(emoji, targetCard, auraColor);
                if (navigator.vibrate) navigator.vibrate(20);
            });
            grid.appendChild(btn);
        });

        const divider = document.createElement('hr');
        divider.className = 'reactions-divider';

        const actions = document.createElement('div');
        actions.className = 'reactions-actions';

        // Botão "Ver mais"
        const btnVerMais = document.createElement('button');
        btnVerMais.className = 'action-btn primary';
        btnVerMais.innerHTML = 'ⓘ Ver mais detalhes';
        btnVerMais.addEventListener('click', function(e) {
            e.stopPropagation();
            fecharPopup();
            if (typeof window.abrirLightbox === 'function') {
                window.abrirLightbox(postId);
            }
        });

        // Botão "Ler Depois" (futuro)
        const btnLerDepois = document.createElement('button');
        btnLerDepois.className = 'action-btn bookmark';
        btnLerDepois.innerHTML = '📌 Ler Depois';
        btnLerDepois.addEventListener('click', function(e) {
            e.stopPropagation();
            fecharPopup();
            if (typeof exibirToast === 'function') {
                exibirToast('📌 Post salvo para ler depois! (em breve)');
            } else {
                alert('Função em desenvolvimento.');
            }
        });

        // Botão "Expurgar" (dono) ou "Denunciar" (visitante)
        const btnDanger = document.createElement('button');
        btnDanger.className = 'action-btn danger';
        if (isOwner) {
            btnDanger.innerHTML = '🗑️ Expurgar da Fenda';
            btnDanger.addEventListener('click', function(e) {
                e.stopPropagation();
                fecharPopup();
                if (typeof window.confirmarExclusao === 'function') {
                    window.confirmarExclusao(postId);
                } else {
                    if (confirm('⚠️ Isso removerá o post permanentemente. Continuar?')) {
                        fetch('includes/excluir.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'id=' + postId
                        }).then(() => {
                            if (typeof exibirToast === 'function') exibirToast('Post expurgado!');
                            window.location.reload();
                        });
                    }
                }
            });
        } else {
            btnDanger.innerHTML = '🚨 Denunciar post';
            btnDanger.addEventListener('click', function(e) {
                e.stopPropagation();
                fecharPopup();
                alert('🚨 Denúncia do post #' + postId + ' enviada aos ADMs.');
            });
        }

        // Botão "Voltar"
        const btnClose = document.createElement('button');
        btnClose.className = 'action-btn';
        btnClose.innerHTML = '✖️ Voltar ao Feed';
        btnClose.addEventListener('click', fecharPopup);

        actions.appendChild(btnVerMais);
        actions.appendChild(btnLerDepois);
        actions.appendChild(btnDanger);
        actions.appendChild(btnClose);

        popup.appendChild(grid);
        popup.appendChild(divider);
        popup.appendChild(actions);
        overlay.appendChild(popup);
        document.body.appendChild(overlay);

        function fecharPopup() {
            if (overlay.parentNode) overlay.remove();
            window._modalAberto = false;
            window._activeModal = null;
            if (targetCard && targetCard.classList) {
                targetCard.classList.remove('card-long-press-active');
            }
            document.removeEventListener('keydown', escHandler);
        }

        // Fecha ao clicar no fundo
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) fecharPopup();
        });

        // Fecha com ESC
        function escHandler(e) {
            if (e.key === 'Escape') fecharPopup();
        }
        document.addEventListener('keydown', escHandler);

        window._activeModal = overlay;
        if (navigator.vibrate) navigator.vibrate(30);
    };

    // ==================== ANIMAÇÃO "+1" ====================
    function criarAnimacaoMaisUm(emoji, cardElement, auraColor) {
        if (!cardElement) return;
        const rect = cardElement.getBoundingClientRect();
        const x = rect.left + rect.width / 2;
        const y = rect.top + 20;

        const anterior = document.querySelector('.plus-one-anim');
        if (anterior) anterior.remove();

        const el = document.createElement('div');
        el.className = 'plus-one-anim';
        el.textContent = '+1 ' + emoji;
        el.style.left = (x - 30) + 'px';
        el.style.top = (y - 30) + 'px';
        el.style.color = auraColor;
        el.style.textShadow = `0 0 30px ${auraColor}55, 0 4px 20px rgba(0,0,0,0.5)`;
        document.body.appendChild(el);

        el.addEventListener('animationend', function() {
            el.remove();
        });
        setTimeout(() => {
            if (el.parentNode) el.remove();
        }, 1500);
    }

    // ==================== LONG PRESS NO MODO GRID ====================
    function initGridLongPress() {
        let timer = null;
        let pressedCard = null;
        let startX = 0,
            startY = 0;
        const MOVE_THRESHOLD = 10;

        function onPointerDown(e) {
            if (window._modalAberto) return;
            if (document.body.classList.contains('modo-swipe-ativo')) return;
            const card = e.target.closest('.spotted-card');
            if (!card) return;
            if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
                e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) return;
            e.preventDefault();
            pressedCard = card;
            startX = e.clientX;
            startY = e.clientY;
            timer = setTimeout(() => {
                if (pressedCard) {
                    const postId = pressedCard.dataset.id;
                    const isOwner = pressedCard.classList.contains('post-admin-gold');
                    if (postId) window.mostrarMenuAcoes(postId, isOwner, pressedCard);
                    cleanup();
                }
            }, 300);
        }

        function onPointerMove(e) {
            if (!pressedCard) return;
            const dx = Math.abs(e.clientX - startX);
            const dy = Math.abs(e.clientY - startY);
            if (dx > MOVE_THRESHOLD || dy > MOVE_THRESHOLD) cleanup();
        }

        function onPointerUp() {
            cleanup();
        }

        function cleanup() {
            if (timer) clearTimeout(timer);
            timer = null;
            pressedCard = null;
            startX = startY = 0;
        }
        document.removeEventListener('pointerdown', onPointerDown);
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerUp);
        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);
        document.addEventListener('pointercancel', onPointerUp);
    }

    // ==================== GERENCIAMENTO DO FEED (AJAX) ====================
    let offset = 0;
    let loadingMore = false;
    let currentCategoria = '';
    let feedAcabou = false;
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    function carregarFeedGeral(reset = false) {
        if (loadingMore) return;
        loadingMore = true;

        if (reset) {
            offset = 0;
            feedAcabou = false;
            if (feedContainer) feedContainer.innerHTML = '';
            if (btnLoad) {
                btnLoad.disabled = false;
                btnLoad.innerText = "Exibir Mais Resultados";
                btnLoad.style.display = 'inline-block';
            }
        }

        if (feedContainer) feedContainer.setAttribute('aria-busy', 'true');
        if (btnLoad) btnLoad.disabled = true;

        const urlParams = new URLSearchParams(window.location.search);
        const categoria = urlParams.get('categoria') || '';
        currentCategoria = categoria;

        fetch(`motor-feed.php?offset=${offset}&categoria=${categoria}&tipo=geral`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    feedAcabou = true;

                    if (offset === 0 && feedContainer) {
                        if (document.body.classList.contains('modo-swipe-ativo')) {
                            feedContainer.innerHTML = '';
                            exibirRadar();
                        } else {
                            feedContainer.innerHTML =
                                "<p style='text-align:center; color:#ccc; font-size:clamp(0.85rem, 2.5vw, 1.3rem)'>Nenhum post encontrado.</p>";
                        }
                    }
                    if (btnLoad) {
                        btnLoad.innerText = "FIM DO FEED";
                        btnLoad.disabled = true;
                    }
                } else {
                    feedAcabou = false;

                    if (feedContainer) {
                        const qtdAntes = feedContainer.children.length;
                        if (offset === 0) feedContainer.innerHTML = '';
                        feedContainer.insertAdjacentHTML('beforeend', data);

                        if (typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                            configurarPosts();
                        }

                        const novosCards = Array.from(feedContainer.children).slice(qtdAntes);
                        const isSwipe = document.body.classList.contains('modo-swipe-ativo');

                        novosCards.forEach((card, idx) => {
                            if (isSwipe) {
                                card.classList.add('swipe-distribuicao');
                                const delay = Math.min(idx * 0.12, 0.6);
                                card.style.animationDelay = `${delay}s`;
                                const onEnd = () => {
                                    card.classList.remove('swipe-distribuicao');
                                    card.style.animationDelay = '';
                                    card.removeEventListener('animationend', onEnd);
                                };
                                card.addEventListener('animationend', onEnd, {
                                    once: true
                                });
                            } else {
                                card.classList.add('grid-card-entrada');
                                const delay = Math.min(idx * 0.05, 0.4);
                                card.style.animationDelay = `${delay}s`;
                                const onEnd = () => {
                                    card.classList.remove('grid-card-entrada');
                                    card.style.animationDelay = '';
                                    card.removeEventListener('animationend', onEnd);
                                };
                                card.addEventListener('animationend', onEnd, {
                                    once: true
                                });
                            }
                        });

                        if (isSwipe && feedContainer && !feedContainer.classList.contains('feed-empilhado')) {
                            feedContainer.classList.add('feed-empilhado');
                        }

                        setTimeout(function() {
                            if (typeof reforcarLayoutNosCards === 'function') {
                                reforcarLayoutNosCards();
                            }
                        }, 50);
                    }
                    offset += 10;
                    if (btnLoad) btnLoad.disabled = false;
                }
                if (feedContainer) feedContainer.setAttribute('aria-busy', 'false');
            })
            .catch(err => {
                console.error("[AJAX] Erro no feed:", err);
                if (btnLoad) btnLoad.disabled = false;
            })
            .finally(() => {
                loadingMore = false;
                if (feedContainer) feedContainer.setAttribute('aria-busy', 'false');
                if (btnLoad && !document.body.classList.contains('modo-swipe-ativo') && btnLoad.innerText !== "FIM DO FEED") {
                    btnLoad.disabled = false;
                }
            });
    }

    function exibirRadar() {
        if (!document.body.classList.contains('modo-swipe-ativo')) return;
        if (feedContainer.querySelector('.fim-dos-cards-vibe')) return;
        const radarHtml = `
            <div class="fim-dos-cards-vibe" style="text-align:center; justify-content: center; padding:40px 20px; color:#ccc;">
                <i class="fas fa-ghost" style="font-size:clamp(3.5rem, 10vh, 7rem); color:#ff8c00; margin-bottom:15px; display:block;"></i>
                <strong style="font-size:clamp(0.95rem, 1.5vw, 1.6rem); display:block; margin-bottom:8px; color:#fff;">A Fenda foi Limpa!</strong>
                <p style="font-size:clamp(0.9rem, 1.3vw, 1.4rem); color:#888; margin-bottom:20px;">Você leu tudo por aqui ou o feed chegou ao fim.</p>
                <button onclick="window.reiniciarPilhaFenda()" class="btn-fenda-padrao" style="background:#ff8c00; color:#fff; border:none; padding:10px 20px; border-radius:20px; cursor:pointer; font-weight:bold;">
                    <i class="fas fa-sync-alt" style="margin-right:8px;"></i> RADAR DE MARACUTAIA
                </button>
            </div>
        `;
        feedContainer.insertAdjacentHTML('beforeend', radarHtml);
    }

    // ==================== EVENTO DE CLIQUE DO BOTÃO ====================
    if (btnLoad) {
        btnLoad.addEventListener('click', function(e) {
            e.preventDefault();
            if (document.body.classList.contains('modo-swipe-ativo')) return;
            if (loadingMore) {
                console.warn("[BOTÃO] Aguarde, já carregando...");
                return;
            }
            carregarFeedGeral(false);
        });
    } else {
        console.error("[BOTÃO] Elemento #btn-load-more não encontrado!");
    }

    // ==================== REAÇÕES ====================
    document.addEventListener('click', function(e) {
        const btnReagir = e.target.closest('.btn-reagir');
        if (btnReagir) {
            e.stopPropagation();
            const wrapper = btnReagir.closest('.reacao-wrapper');
            document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
                if (w !== wrapper) w.classList.remove('popup-ativo');
            });
            wrapper.classList.toggle('popup-ativo');
            return;
        }
        if (e.target.closest('.reacoes-popup')) return;
        document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
            w.classList.remove('popup-ativo');
        });
    });

    // ==================== REAÇÕES (VERSÃO SEGURA) ====================
    const _reacaoEmAndamento = {};

    window.enviarReacao = async function(postId, tipoReacao) {
        const tradutorEmojis = {
            'amei': '💖',
            'perplecto': '😲',
            'haha': '😂',
            'ranco': '🙄',
            'forca': '🫂',
            'triste': '😢',
            'tendi-nada': '🤔'
        };

        const chave = `${postId}_${tipoReacao}`;
        if (_reacaoEmAndamento[chave]) {
            console.warn('[REAÇÃO] Aguardando processamento da reação anterior para o post', postId);
            return;
        }
        _reacaoEmAndamento[chave] = true;

        try {
            const response = await fetch(`includes/reagir.php?id=${postId}&tipo=${tipoReacao}`);
            if (response.status === 429) {
                alert("Calma lá! O motor da Fenda precisa respirar.");
                delete _reacaoEmAndamento[chave];
                throw new Error("Rate limit exceeded");
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            if (data.status === 'success') {
                const containerReacoes = document.getElementById(`reacoes-post-${postId}`);
                if (containerReacoes) {
                    containerReacoes.innerHTML = '';
                    Object.keys(data.contagens).forEach(tipo => {
                        const total = data.contagens[tipo];
                        if (total > 0) {
                            const emoji = tradutorEmojis[tipo] || '👍';
                            const classeVoted = data.minhas_reacoes.includes(tipo) ? 'voted' : '';
                            containerReacoes.insertAdjacentHTML(
                                'beforeend',
                                `<span class="reacao-item ${classeVoted}">${emoji} ${total}</span>`
                            );
                        }
                    });
                }
            }
            return data;
        } catch (err) {
            if (err.message !== "Rate limit exceeded") console.error("[REAÇÃO] Erro:", err);
            throw err;
        } finally {
            delete _reacaoEmAndamento[chave];
        }
    };

    // ==================== RADAR DE MARACUTAIA (SWIPE) ====================
    let radarBloqueado = false;

    window.abastecerPilhaFenda = function() {
        if (!feedContainer) return;
        if (!document.body.classList.contains('modo-swipe-ativo')) return;
        if (!feedAcabou) {
            const cardsRestantes = feedContainer.querySelectorAll('.spotted-card').length;
            if (cardsRestantes <= 4) {
                if (btnLoad && !btnLoad.disabled && !loadingMore) {
                    carregarFeedGeral(false);
                }
            }
            return;
        }
        const cardsRestantes = feedContainer.querySelectorAll('.spotted-card').length;
        if (cardsRestantes === 0) {
            exibirRadar();
        }
    };

    window.reiniciarPilhaFenda = function() {
        if (radarBloqueado) {
            console.log("[RADAR] Calma lá, muito rolo estraga o feed!");
            return;
        }
        radarBloqueado = true;

        if (typeof window.fecharLightbox === 'function') {
            window.fecharLightbox();
        }

        offset = 0;
        feedAcabou = false;
        if (feedContainer) {
            const radarExistente = feedContainer.querySelector('.fim-dos-cards-vibe');
            if (radarExistente) radarExistente.remove();
            feedContainer.innerHTML = `
                <div style="text-align:center; padding:40px; color:#ff8c00;">
                    <i class="fas fa-sync-alt fa-spin" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p style="color:#fff;">Escaneando novas maracutaias...</p>
                </div>
            `;
        }
        if (btnLoad) {
            btnLoad.disabled = false;
            btnLoad.innerText = "Exibir Mais Resultados";
            btnLoad.style.display = 'inline-block';
        }
        carregarFeedGeral(true);
        setTimeout(() => {
            radarBloqueado = false;
        }, 1500);
    };

    // ==================== OBSERVADOR PARA ALTERNÂNCIA DE MODOS ====================
    const observerModoSwipe = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (!document.body.classList.contains('modo-swipe-ativo')) {
                    if (typeof configurarPosts === 'function') {
                        configurarPosts();
                    }
                }
            }
        });
    });
    observerModoSwipe.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // ==================== INICIALIZAÇÃO ====================
    initGridLongPress();
    carregarFeedGeral(false);
</script>

<?php include 'includes/footer.php'; ?>