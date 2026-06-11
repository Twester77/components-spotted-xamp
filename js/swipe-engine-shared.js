/* ============================================================
   SWIPE ENGINE SHARED – CORE DE FÍSICA (independente)
   ============================================================ */

   /* === PONTE DE SEGURANÇA - NÃO APAGAR === */
window.silenceObserverLogs = function() { console.log("Engine: Logs silenciados."); };
window.resumeObserverLogs = function() { console.log("Engine: Logs retomados."); };
window._cleanupHandlers = function() { console.log("Engine: Handlers limpos."); };
window.showActionMenu = function(card) { console.log("Ação: Mostrando menu para", card); };
/* ======================================= */
(function(global) {
    'use strict';

    // ========== ESTADO INTERNO ==========
    let swipeLock = false;
    let menuAtivo = null;          // menu de long press (se existir)
    let animationFrameId = null;
    let isDragging = false;
    let activeCard = null;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let longPressTimer = null;
    let moveDetected = false;

    // ========== CALLBACKS (definidos externamente) ==========
    let onSwipeRight = null;   // será window.executarAmei
    let onSwipeLeft = null;    // será window.executarDescartar
    let onSwipeUp = null;      // será window.executarComentar
    let onLongPress = null;    // opcional (menu)

    // ========== CONFIGURAÇÕES ==========
    const CONFIG = {
        LONG_PRESS_MS: 300,
        MOVE_THRESHOLD: 10,     // px para movimento significativo
        SWIPE_THRESHOLD: 120,   // px para disparar swipe
        SWIPE_UP_MAX_DX: 80,    // tolerância lateral no swipe up
    };

    // ========== FEEDBACK VISUAL (long press) – podem ser sobrescritos ==========
    let aplicarFeedbackLongPress = (card) => {
        if (!card) return;
        card.classList.add('card-tremendo');
        if (navigator.vibrate) navigator.vibrate(50);
    };
    let removerFeedbackLongPress = (card) => {
        if (!card) return;
        card.classList.remove('card-tremendo');
    };

    // ========== FUNÇÕES INTERNAS ==========
    function resetFeedback() {
        // remove opacidade dos indicadores (direita, esquerda, cima)
        document.querySelectorAll('.feedback-swipe').forEach(el => el.style.opacity = '0');
    }

    function animateExitAndRemove(card, x, y) {
        if (!card) return;
        card.style.transition = 'transform 0.3s ease-out, opacity 0.2s';
        // Usa transform inline (compatível com PC e mobile)
        card.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`;
        card.style.opacity = '0';
        setTimeout(() => {
            if (card && card.parentNode) {
                card.remove();
            }
            // Dispara recarga do feed se existir
            if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda();
            else window.dispatchEvent(new CustomEvent('fenda:cardRemoved'));
            // Libera observer (se houver)
            if (global.resumeObserverLogs) global.resumeObserverLogs();
        }, 300);
    }

    function destroyMenuAndUnlock() {
        if (menuAtivo) { menuAtivo.remove(); menuAtivo = null; }
        swipeLock = false;
        if (isDragging) {
            isDragging = false;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            if (activeCard) {
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
                removerFeedbackLongPress(activeCard);
            }
        }
        if (global.resumeObserverLogs) global.resumeObserverLogs();
    }

    // ========== HANDLERS PÚBLICOS (chamados pelos adapters) ==========
    function onPointerStart(e, card) {
        if (swipeLock) return;
        if (!card) return;

        // Impede ação se clicou em botões internos
        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();
        card.setPointerCapture(e.pointerId);

        if (global.silenceObserverLogs) global.silenceObserverLogs();
        if (longPressTimer) clearTimeout(longPressTimer);
        moveDetected = false;

        activeCard = card;
        isDragging = true;
        activeCard.classList.remove('com-transicao');
        activeCard.style.transition = 'none';
        // Reseta posição (cada adapter usa seu próprio método de posicionamento)
        if (activeCard._resetPosition) activeCard._resetPosition();

        startX = e.clientX;
        startY = e.clientY;
        currentX = 0;
        currentY = 0;

        // Timer para long press
        longPressTimer = setTimeout(() => {
            if (!moveDetected && activeCard && isDragging) {
                aplicarFeedbackLongPress(activeCard);
                isDragging = false;
                if (animationFrameId) cancelAnimationFrame(animationFrameId);
                if (activeCard) {
                    activeCard.style.cursor = 'grab';
                    activeCard.classList.remove('dragging');
                    if (onLongPress) onLongPress(activeCard);
                    else if (global.showActionMenu) global.showActionMenu(activeCard);
                }
                // Limpa handlers (quem chamou deve remover os listeners)
                if (global._cleanupHandlers) global._cleanupHandlers();
                activeCard = null;
                swipeLock = true; // trava para não interagir enquanto menu aberto
            }
        }, CONFIG.LONG_PRESS_MS);
    }

    function onPointerMove(dx, dy, e) {
        if (!isDragging || !activeCard || swipeLock) return;

        const isSignificantMove = Math.abs(dx) > CONFIG.MOVE_THRESHOLD || Math.abs(dy) > CONFIG.MOVE_THRESHOLD;
        if (!isSignificantMove && !moveDetected) return;

        if (!moveDetected) {
            // Cancela long press
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
                removerFeedbackLongPress(activeCard);
            }
            moveDetected = true;
            document.body.classList.add('fenda-arrastando');
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
        }

        currentX = dx;
        currentY = dy;

        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(() => {
            if (!isDragging || !activeCard) return;
            // Atualiza posição via método específico do adapter
            if (activeCard._updatePosition) activeCard._updatePosition(currentX, currentY);

            // Feedbacks visuais (setas)
            const feedbackDir = document.querySelector('.feedback-direita');
            const feedbackEsq = document.querySelector('.feedback-esquerda');
            const feedbackCima = document.querySelector('.feedback-cima');
            const easeInOutOpacity = (dist, max = 150) => {
                let t = Math.min(Math.abs(dist) / max, 1);
                return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
            };
            if (currentX > 40 && feedbackDir) feedbackDir.style.opacity = easeInOutOpacity(currentX);
            else if (currentX < -40 && feedbackEsq) feedbackEsq.style.opacity = easeInOutOpacity(currentX);
            else if (currentY < -40 && Math.abs(currentX) < CONFIG.SWIPE_UP_MAX_DX && feedbackCima) feedbackCima.style.opacity = easeInOutOpacity(currentY);
            else {
                if (feedbackDir) feedbackDir.style.opacity = '0';
                if (feedbackEsq) feedbackEsq.style.opacity = '0';
                if (feedbackCima) feedbackCima.style.opacity = '0';
            }
        });
    }

    function onPointerEnd(dx, dy) {
        if (!isDragging || !activeCard) {
            // Limpeza de emergência
            if (longPressTimer) clearTimeout(longPressTimer);
            if (activeCard) removerFeedbackLongPress(activeCard);
            isDragging = false;
            activeCard = null;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            document.body.classList.remove('fenda-arrastando');
            resetFeedback();
            if (global.resumeObserverLogs) global.resumeObserverLogs();
            return;
        }

        document.body.classList.remove('fenda-arrastando');
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
            removerFeedbackLongPress(activeCard);
        }
        isDragging = false;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);

        const card = activeCard;
        const idPost = card.dataset.id;

        // Chamar ações com base nos gestos
        if (dx < -CONFIG.SWIPE_THRESHOLD) {
            // Swipe para esquerda → descartar
            if (onSwipeLeft) onSwipeLeft(idPost, card);
            animateExitAndRemove(card, -800, dy);
        } else if (dx > CONFIG.SWIPE_THRESHOLD) {
            // Swipe para direita → reagir (amei)
            if (onSwipeRight) onSwipeRight(idPost, card);
            animateExitAndRemove(card, 800, dy);
        } else if (dy < -CONFIG.SWIPE_THRESHOLD && Math.abs(dx) < CONFIG.SWIPE_UP_MAX_DX) {
            // Swipe para cima → comentar
            if (onSwipeUp) onSwipeUp(idPost, card);
            // Não remove o card, apenas redireciona
            window.location.href = `post.php?id=${idPost}#fofocar`;
        } else {
            // Snap back
            if (card._resetPosition) card._resetPosition();
            if (card._updatePosition) card._updatePosition(0, 0);
            setTimeout(() => {
                if (card) card.style.transition = 'none';
            }, 300);
            if (global.resumeObserverLogs) global.resumeObserverLogs();
        }

        activeCard = null;
        startX = startY = currentX = currentY = 0;
    }

    function onPointerCancel() {
        if (activeCard && isDragging) {
            if (activeCard._resetPosition) activeCard._resetPosition();
            activeCard.style.cursor = 'grab';
            activeCard.classList.remove('dragging');
            removerFeedbackLongPress(activeCard);
            isDragging = false;
            activeCard = null;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            if (longPressTimer) clearTimeout(longPressTimer);
            resetFeedback();
            document.body.classList.remove('fenda-arrastando');
            if (global.resumeObserverLogs) global.resumeObserverLogs();
        }
        if (menuAtivo) destroyMenuAndUnlock();
    }

   // ========== API PÚBLICA ==========
    const SwipeEngine = {
        // 1. Adicionamos o campo para o callback
        onReset: null,

        // Configura callbacks de ação
        setCallbacks: function(actions) {
            if (actions.onSwipeRight) onSwipeRight = actions.onSwipeRight;
            if (actions.onSwipeLeft) onSwipeLeft = actions.onSwipeLeft;
            if (actions.onSwipeUp) onSwipeUp = actions.onSwipeUp;
            if (actions.onLongPress) onLongPress = actions.onLongPress;
        },
        // Permite sobrescrever feedbacks visuais (ex: tremor)
        setFeedbackHandlers: function(feedback) {
            if (feedback.aplicar) aplicarFeedbackLongPress = feedback.aplicar;
            if (feedback.remover) removerFeedbackLongPress = feedback.remover;
        },
        // Métodos que os adaptadores vão usar
        onPointerStart,
        onPointerMove,
        onPointerEnd,
        onPointerCancel,
        destroyMenuAndUnlock,
        // Utilitários
        isDragging: () => isDragging,
        getActiveCard: () => activeCard,
        
        // 2. Forçar parada (CORRIGIDO E SEGURO)
        // Substitua o seu forceStop atual por este bloco exato:
forceStop: function() {
    console.warn("[SWIPE] Executando Hard Reset...");
    
    // 1. Limpeza de Estado Interno (Prioridade Zero)
    isDragging = false; 
    activeCard = null;
    swipeLock = false;
    
    // 2. Limpeza de Timers e Animações
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (longPressTimer) clearTimeout(longPressTimer);
    longPressTimer = null; // Garante que o timer não dispare depois
    
    // 3. Limpeza de Menu e DOM
    if (menuAtivo) {
        menuAtivo.remove();
        menuAtivo = null;
    }
    resetFeedback();
    document.body.classList.remove('fenda-arrastando');
    
    // 4. Se o adaptador configurou um callback de Reset visual, chama agora
    if (typeof this.onReset === 'function') {
        this.onReset();
    }
},
    };

    // Exporta para o escopo global (será usado pelos adapters)
    global.SwipeEngine = SwipeEngine;
})(window);