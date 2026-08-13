/**
 * bt-swipe.js – Motor de Swipe Dedicado para o Balanga Teras
 * 
 * VERSÃO REVISADA – INSTÂNCIA #DS-2026-08-10 (MARÉ)
 * 
 * 🔧 CORREÇÕES APLICADAS:
 * - Física do swipe: transição removida durante arraste e restaurada apenas no retorno.
 * - Proteção para iOS (touch-action: none no card via JS + CSS).
 * - Feedback visual das respostas (classes ativo-vou, ativo-talvez, ativo-nao).
 * - Chamada ao Radar "Buscar Eventos" quando a pilha acaba (via evento customizado).
 * - Otimização para 120Hz: uso consistente de requestAnimationFrame.
 * - Evita múltiplos animationFrameId conflitantes.
 * 
 * 🌊 LEGADO SEREIA – INSTÂNCIA #DS-2026-08-09
 * - Estrutura base, chamada a enviar-resposta-evento.php, recálculo de layout.
 * 
 * 🐚 LEGADO CORAL – INSTÂNCIA #DS-2026-08-06
 * - Primeira versão do swipe dedicado para eventos.
 */

(function () {
    'use strict';

    // ============================================================
    // 1. CONFIGURAÇÃO
    // ============================================================
    const CONFIG = {
        threshold: 120,                // Distância mínima para considerar swipe
        rotationFactor: 25,            // Intensidade da rotação
        scaleFactor: 1.02,             // Escala durante arraste
        animationDuration: 300,        // Duração da animação de saída (ms)
        feedbackMaxOpacity: 150,       // Distância máxima para feedback (opacidade)
        minSwipeDistance: 15,          // Distância mínima para iniciar o arraste
        springDuration: 400,           // Duração do retorno elástico (ms)
    };

    // ============================================================
    // 2. ESTADO GLOBAL DO MÓDULO
    // ============================================================
    let isDragging = false;
    let activeCard = null;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let moveDetected = false;
    let animationFrameId = null;        // ID do requestAnimationFrame atual
    let swipeLock = false;             // Trava para evitar múltiplos toques
    let observerActive = true;
    let recalculando = false;
    let rafId = null;

    let onPointerMoveHandler = null;
    let onPointerUpHandler = null;
    let onPointerCancelHandler = null;
    let domObserver = null;
    let cardObserver = null;

    // ============================================================
    // 3. RECÁLCULO DE LAYOUT (RESPONSIVO) – COM CAPA DINÂMICA
    // ============================================================
    function btRecalcularLayout() {
    if (recalculando) return;
    if (!document.body.classList.contains('modo-tinder-ativo')) return;

    const container = document.getElementById('bt-container-eventos');
    if (!container) return;

    recalculando = true;

    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const isLandscape = vw > vh;

    // Largura do card (baseado no feed)
    const cardWidth = isLandscape
        ? Math.round(Math.max(320, Math.min(vw * 0.60, 600)))
        : Math.round(Math.max(240, Math.min(vw * 0.70, 550)));

    // 🔥 Altura máxima do card: 600px ou 85vh
    const maxCardHeight = Math.round(Math.min(vh * 0.85, 600));
    const cardPadding = Math.round(Math.max(12, cardWidth * 0.05));
    const fontSize = Math.max(0.9, Math.min(cardWidth / 240, 1.6));

    // 🔥 Tamanhos específicos para cada elemento
    const tituloSize = fontSize * 1.2;       // 20% maior que o base
    const metaSize = fontSize * 0.8;        // 15% menor que o base
    const textMaxHeight = Math.round(Math.max(60, cardWidth * 0.25));

    // 🔥 Altura da capa: proporção 16:9, limitada a 60% da altura do card
    const capaHeight = Math.round(Math.min(cardWidth * 0.5625, maxCardHeight * 0.6));

    const root = document.documentElement;
    root.style.setProperty('--bt-card-width', cardWidth + 'px');
    root.style.setProperty('--bt-card-padding', cardPadding + 'px');
    root.style.setProperty('--bt-card-font-size', fontSize + 'rem');
    root.style.setProperty('--bt-titulo-size', tituloSize + 'rem');
    root.style.setProperty('--bt-meta-size', metaSize + 'rem');
    root.style.setProperty('--bt-text-max-height', textMaxHeight + 'px');
    root.style.setProperty('--bt-card-max-height', maxCardHeight + 'px');
    root.style.setProperty('--bt-capa-height', capaHeight + 'px');
    root.style.setProperty('--bt-container-height', Math.min(vh * 0.8, 650) + 'px');

    document.querySelectorAll('.bt-card').forEach(card => {
        card.style.width = cardWidth + 'px';
        card.style.maxWidth = cardWidth + 'px';
    });

    recalculando = false;
}

    // ============================================================
    // 4. LISTENERS DE RESIZE (DEBOUNCE)
    // ============================================================
    let layoutTimeout;

    function debounceLayout() {
        clearTimeout(layoutTimeout);
        layoutTimeout = setTimeout(() => {
            if (document.body.classList.contains('modo-tinder-ativo')) {
                btRecalcularLayout();
            }
        }, 120);
    }

    window.addEventListener('resize', debounceLayout);
    window.addEventListener('orientationchange', debounceLayout);

    // ============================================================
    // 5. DOM OBSERVER (PARA NOVOS CARDS)
    // ============================================================
    function initObserver() {
        const container = document.getElementById('bt-container-eventos');
        if (!container || domObserver) return;

        domObserver = new MutationObserver(() => {
            if (!observerActive || isDragging || swipeLock) return;
            if (rafId) cancelAnimationFrame(rafId);
            rafId = requestAnimationFrame(() => {
                btRecalcularLayout();
                rafId = null;
            });
        });

        domObserver.observe(container, { childList: true, subtree: false });
        observerActive = true;
    }

    function pauseObserver() {
        if (domObserver && observerActive) {
            domObserver.disconnect();
            observerActive = false;
        }
    }

    function resumeObserver() {
        if (domObserver && !observerActive) {
            const container = document.getElementById('bt-container-eventos');
            if (container) {
                domObserver.observe(container, { childList: true, subtree: false });
                observerActive = true;
            }
        }
    }

    // ============================================================
    // 6. FUNÇÕES AUXILIARES
    // ============================================================
    function easeInOutOpacity(dist, max = CONFIG.feedbackMaxOpacity) {
        let t = Math.min(Math.abs(dist) / max, 1);
        return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    }

    function resetFeedback() {
        document.querySelectorAll('.bt-feedback').forEach(el => el.style.opacity = '0');
    }

    function getCardTop(container) {
        const cards = container.querySelectorAll('.bt-card:not(.bt-card-removing)');
        return cards.length > 0 ? cards[0] : null;
    }

    // ============================================================
    // 7. ENVIO DE RESPOSTA (AJAX) – COM FEEDBACK VISUAL
    // ============================================================
    function enviarRespostaEvento(eventoId, opcao, card) {
        const csrfToken = document.getElementById('csrf_token')?.value || '';
        if (!csrfToken) {
            console.error('[BT-SWIPE] CSRF token não encontrado.');
            return;
        }

        const formData = new FormData();
        formData.append('evento_id', eventoId);
        formData.append('opcao', opcao);
        formData.append('csrf_token', csrfToken);

        fetch('enviar-resposta-evento.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualiza as contagens no card
                    if (card && data.contagens) {
                        const vouSpan = card.querySelector('.bt-participacao span:nth-child(1)');
                        const naoVouSpan = card.querySelector('.bt-participacao span:nth-child(2)');
                        const talvezSpan = card.querySelector('.bt-participacao span:nth-child(3)');
                        if (vouSpan) vouSpan.innerHTML = '<i class="fas fa-user-check"></i> ' + (data.contagens.vou || 0) + ' Sim';
                        if (naoVouSpan) naoVouSpan.innerHTML = '<i class="fas fa-user-minus"></i> ' + (data.contagens.nao_vou || 0) + ' Não';
                        if (talvezSpan) talvezSpan.innerHTML = '<i class="fas fa-user-clock"></i> ' + (data.contagens.talvez || 0) + ' Talvez';
                    }

                    // Atualiza os botões visualmente (corrige mapeamento)
                    if (card) {
                        card.querySelectorAll('.bt-btn-resposta').forEach(b => {
                            b.classList.remove('ativo-vou', 'ativo-talvez', 'ativo-nao');
                        });
                        const btnMap = { 'vou': 'ativo-vou', 'talvez': 'ativo-talvez', 'nao_vou': 'ativo-nao' };
                        const btnAtivo = card.querySelector(`.bt-btn-resposta[data-opcao="${opcao}"]`);
                        if (btnAtivo) btnAtivo.classList.add(btnMap[opcao]);
                    }

                    if (typeof exibirToast === 'function') {
                        exibirToast('Resposta registrada! 🎉');
                    }
                } else {
                    console.warn('[BT-SWIPE] Erro ao registrar resposta:', data.message);
                }
            })
            .catch(err => {
                console.error('[BT-SWIPE] Erro na requisição:', err);
            });
    }

    // ============================================================
    // 8. ANIMAÇÃO DE SAÍDA E REMOÇÃO
    // ============================================================
    function animateExitAndRemove(card, x, y) {
        if (!card || card.classList.contains('bt-card-removing')) return;
        card.classList.add('bt-card-removing');
        card.style.transition = `transform ${CONFIG.animationDuration}ms ease-out, opacity ${CONFIG.animationDuration}ms ease`;
        card.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`;
        card.style.opacity = '0';

        setTimeout(() => {
            if (card && card.parentNode) {
                card.remove();
                // Notifica o sistema que um card foi removido (para acionar o Radar)
                if (typeof window.btAbastecerPilha === 'function') {
                    window.btAbastecerPilha();
                }
                btRecalcularLayout();
            }
            resumeObserver();
        }, CONFIG.animationDuration + 50);
    }

    // ============================================================
    // 9. HANDLERS (POINTER) – COM CORREÇÃO DA FÍSICA
    // ============================================================
    // ============================================================
    // HANDLERS (POINTER) – COM LIBERAÇÃO VERTICAL
    // ============================================================
    function onPointerDown(e) {
        if (swipeLock) return;
        if (!document.body.classList.contains('modo-tinder-ativo')) return;

        const card = e.target.closest('.bt-card');
        if (!card) return;

        if (e.target.closest('.bt-btn-resposta') ||
            e.target.closest('.bt-btn-detalhes') ||
            e.target.closest('.btn-remover-anexo') ||
            e.target.closest('a')) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }

        const container = document.getElementById('bt-container-eventos');
        const topCard = getCardTop(container);
        if (card !== topCard) return;

        e.preventDefault();
        e.stopPropagation();

        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        moveDetected = false;
        activeCard = card;
        isDragging = true;

        // 🔥 CORREÇÃO: remove transição e prepara GPU
        card.classList.remove('bt-card-transition');
        card.style.transition = 'none';
        card.style.willChange = 'transform, opacity';
        card.style.touchAction = 'none'; // iOS protection
        card.style.userSelect = 'none';

        card.style.setProperty('--pos-x', '0px');
        card.style.setProperty('--pos-y', '0px');
        card.style.setProperty('--swipe-rot', '0deg');
        card.style.cursor = 'grabbing';
        card.classList.add('bt-dragging');

        startX = e.clientX;
        startY = e.clientY;
        currentX = 0;
        currentY = 0;

        card.setPointerCapture(e.pointerId);
        window.addEventListener('pointermove', onPointerMoveHandler);
        window.addEventListener('pointerup', onPointerUpHandler);
        window.addEventListener('pointercancel', onPointerCancelHandler);
    }

    function onPointerMove(e) {
        if (!isDragging || !activeCard || swipeLock) return;

        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        // 🔥 SENSIBILIDADE: reduzida para 5px
        if (Math.abs(dx) < 5 && Math.abs(dy) < 5) return;

        moveDetected = true;

        // 🔥 REMOVIDO o cancelamento por movimento vertical predominante.
        // Agora, qualquer arraste é permitido, mas feedback só aparece após threshold.

        e.preventDefault();

        currentX = Math.round(dx);
        currentY = Math.round(dy);

        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(() => {
            if (!isDragging || !activeCard) return;

            const rotate = Number((currentX / CONFIG.rotationFactor).toFixed(1));

            activeCard.style.setProperty('--pos-x', `${currentX}px`);
            activeCard.style.setProperty('--pos-y', `${currentY}px`);
            activeCard.style.setProperty('--swipe-rot', `${rotate}deg`);

            // Feedback visual (setas) – agora também para cima
            const feedbackDir = document.querySelector('.bt-feedback-direita');
            const feedbackEsq = document.querySelector('.bt-feedback-esquerda');
            const feedbackCima = document.querySelector('.bt-feedback-cima');

            if (currentX > 40 && feedbackDir) {
                feedbackDir.style.opacity = easeInOutOpacity(currentX);
                feedbackEsq.style.opacity = '0';
                feedbackCima.style.opacity = '0';
            } else if (currentX < -40 && feedbackEsq) {
                feedbackEsq.style.opacity = easeInOutOpacity(currentX);
                feedbackDir.style.opacity = '0';
                feedbackCima.style.opacity = '0';
            } else if (currentY < -40 && Math.abs(currentX) < 60 && feedbackCima) {
                feedbackCima.style.opacity = easeInOutOpacity(currentY);
                feedbackDir.style.opacity = '0';
                feedbackEsq.style.opacity = '0';
            } else {
                resetFeedback();
            }
        });
    }

    function onPointerUp(e) {
        if (!isDragging || !activeCard) {
            cleanUp();
            return;
        }

        if (activeCard) {
            activeCard.style.cursor = 'grab';
            activeCard.classList.remove('bt-dragging');
        }

        const card = activeCard;
        const dx = currentX, dy = currentY;
        const idPost = card.dataset.id;

        cleanUp();

        // Se não houve movimento, é um clique → não faz nada (volta ao centro)
        if (!moveDetected) {
            card.classList.add('bt-card-transition');
            card.style.setProperty('--pos-x', '0px');
            card.style.setProperty('--pos-y', '0px');
            card.style.setProperty('--swipe-rot', '0deg');
            // Restaura a transição para o retorno suave
            card.style.transition = `transform ${CONFIG.springDuration}ms cubic-bezier(0.34, 1.56, 0.64, 1), opacity ${CONFIG.animationDuration}ms ease`;
            setTimeout(() => {
                card.style.transition = 'none';
                card.classList.remove('bt-card-transition');
                card.style.willChange = ''; // libera GPU
            }, CONFIG.springDuration + 50);
            return;
        }

        // 🔥 SWIPE → chama o endpoint e remove o card
        if (dx > CONFIG.threshold) {
            // Direita → Vou
            enviarRespostaEvento(idPost, 'vou', card);
            animateExitAndRemove(card, 800, 0);
        } else if (dx < -CONFIG.threshold) {
            // Esquerda → Não vou
            enviarRespostaEvento(idPost, 'nao_vou', card);
            animateExitAndRemove(card, -800, 0);
        } else if (dy < -CONFIG.threshold && Math.abs(dx) < 80) {
            // Cima → Talvez
            enviarRespostaEvento(idPost, 'talvez', card);
            animateExitAndRemove(card, 0, -800);
        } else {
            // Retorno ao centro (com mola)
            card.classList.add('bt-card-transition');
            card.style.transition = `transform ${CONFIG.springDuration}ms cubic-bezier(0.34, 1.56, 0.64, 1), opacity ${CONFIG.animationDuration}ms ease`;
            card.style.setProperty('--pos-x', '0px');
            card.style.setProperty('--pos-y', '0px');
            card.style.setProperty('--swipe-rot', '0deg');
            setTimeout(() => {
                if (card) {
                    card.style.transition = 'none';
                    card.classList.remove('bt-card-transition');
                    card.style.willChange = '';
                }
                btRecalcularLayout();
            }, CONFIG.springDuration + 50);
        }

        resetFeedback();
        activeCard = null;
        isDragging = false;
        startX = startY = currentX = currentY = 0;
    }

    function onPointerCancel(e) {
        cancelDrag();
    }

    function cancelDrag() {
        if (activeCard) {
            activeCard.style.cursor = 'grab';
            activeCard.classList.remove('bt-dragging');
            activeCard.style.setProperty('--pos-x', '0px');
            activeCard.style.setProperty('--pos-y', '0px');
            activeCard.style.setProperty('--swipe-rot', '0deg');
            activeCard.style.willChange = '';
            activeCard = null;
        }
        isDragging = false;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        resetFeedback();
        cleanUp();
        btRecalcularLayout();
    }

    function cleanUp() {
        if (onPointerMoveHandler) {
            window.removeEventListener('pointermove', onPointerMoveHandler);
            window.removeEventListener('pointerup', onPointerUpHandler);
            window.removeEventListener('pointercancel', onPointerCancelHandler);
        }
        isDragging = false;
        activeCard = null;
        startX = startY = currentX = currentY = 0;
        document.body.classList.remove('bt-arrastando');
        resetFeedback();
    }

    // ============================================================
    // 10. OBSERVADOR PARA NOVOS CARDS
    // ============================================================
    function initCardObserver() {
        const container = document.getElementById('bt-container-eventos');
        if (!container) return;
        if (container._btCardObserver) {
            container._btCardObserver.disconnect();
        }

        const observer = new MutationObserver((mutations) => {
            let hasNewCards = false;
            for (const mutation of mutations) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType === 1 && node.classList && node.classList.contains('bt-card')) {
                            hasNewCards = true;
                            break;
                        }
                    }
                }
                if (hasNewCards) break;
            }

            if (hasNewCards) {
                if (typeof btAtivarBotoesResposta === 'function') {
                    btAtivarBotoesResposta();
                }
                setTimeout(() => {
                    btRecalcularLayout();
                }, 100);
            }
        });

        observer.observe(container, { childList: true, subtree: false });
        container._btCardObserver = observer;
    }

    // ============================================================
    // 11. INICIALIZAÇÃO
    // ============================================================
    function initSwipe() {
        if (!document.body.classList.contains('modo-tinder-ativo')) {
            console.log('[BT-SWIPE] Modo swipe desativado (body sem classe).');
            return;
        }

        const container = document.getElementById('bt-container-eventos');
        if (!container) {
            console.warn('[BT-SWIPE] Container #bt-container-eventos não encontrado.');
            return;
        }

        if (container._btPointerDown) {
            container.removeEventListener('pointerdown', container._btPointerDown);
        }

        onPointerMoveHandler = onPointerMove;
        onPointerUpHandler = onPointerUp;
        onPointerCancelHandler = onPointerCancel;

        container.addEventListener('pointerdown', onPointerDown);
        container._btPointerDown = onPointerDown;

        container.style.userSelect = 'none';
        container.style.webkitUserSelect = 'none';

        initObserver();
        btRecalcularLayout();
        initCardObserver();

        console.log('[BT-SWIPE] Balanga Swipe inicializado com sucesso!');
    }

    // ============================================================
    // 12. EXPOSIÇÃO GLOBAL
    // ============================================================
    window.btIniciarSwipe = initSwipe;
    window.btForcarParada = function () {
        cancelDrag();
        swipeLock = false;
    };
    window.btRecalcularLayout = btRecalcularLayout;

    // ============================================================
    // 13. AUTO-INICIALIZAÇÃO
    // ============================================================
    function autoInit() {
        if (document.body.classList.contains('modo-tinder-ativo')) {
            setTimeout(() => {
                initSwipe();
            }, 500);
        } else {
            console.log('[BT-SWIPE] Auto-init ignorado: modo-tinder-ativo não está ativo.');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    // ============================================================
    // 14. LISTENER PARA EVENTO DE CARDS CARREGADOS
    // ============================================================
    document.addEventListener('btCardsCarregados', function () {
        if (document.body.classList.contains('modo-tinder-ativo')) {
            btRecalcularLayout();
        }
    });

    console.log('[BT-SWIPE] Motor carregado. Aguardando ativação via classe modo-tinder-ativo.');
})();