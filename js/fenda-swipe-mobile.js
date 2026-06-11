/* ============================================================
   FENDA SWIPE MOBILE – ADAPTER (usa SwipeEngine)
   ============================================================ */
(function() {
    'use strict';

    if (!window.SwipeEngine) {
        console.error("[SWIPE] Core não encontrado. Carregue swipe-engine-shared.js primeiro.");
        return;
    }

    const engine = window.SwipeEngine;
    let activeCardElement = null;
    let moveHandler = null, upHandler = null, cancelHandler = null;

    // Posicionamento via CSS variables (mobile)
    function updatePosition(dx, dy) {
        if (!activeCardElement) return;
        const rotate = dx / 30;
        activeCardElement.style.setProperty('--pos-x', dx + 'px');
        activeCardElement.style.setProperty('--pos-y', dy + 'px');
        activeCardElement.style.setProperty('--swipe-rot', rotate + 'deg');
    }
    function resetPosition() {
        if (!activeCardElement) return;
        activeCardElement.style.setProperty('--pos-x', '0px');
        activeCardElement.style.setProperty('--pos-y', '0px');
        activeCardElement.style.setProperty('--swipe-rot', '0deg');
    }
    function attachPositionMethods(card) {
        card._updatePosition = updatePosition.bind(null, card);
        card._resetPosition = resetPosition.bind(null, card);
        activeCardElement = card;
    }

    // Handlers locais
    function onPointerDown(e) {
        const card = e.target.closest('.spotted-card');
        if (!card) return;
        const container = document.querySelector('.container-feed');
        const topCard = container?.querySelector('.spotted-card:first-child');
        if (card !== topCard) return;

        attachPositionMethods(card);
        engine.onPointerStart(e, card);
        if (engine.isDragging()) {
            // registra listeners
            moveHandler = (ev) => {
                const dx = ev.clientX - engine.startX;
                const dy = ev.clientY - engine.startY;
                engine.onPointerMove(dx, dy, ev);
            };
            upHandler = () => {
                engine.onPointerEnd(engine.currentX, engine.currentY);
                cleanup();
            };
            cancelHandler = () => {
                engine.onPointerCancel();
                cleanup();
            };
            window.addEventListener('pointermove', moveHandler);
            window.addEventListener('pointerup', upHandler);
            window.addEventListener('pointercancel', cancelHandler);
        }
    }

    function cleanup() {
        if (moveHandler) window.removeEventListener('pointermove', moveHandler);
        if (upHandler) window.removeEventListener('pointerup', upHandler);
        if (cancelHandler) window.removeEventListener('pointercancel', cancelHandler);
        moveHandler = upHandler = cancelHandler = null;
        activeCardElement = null;
    }

    window._cleanupHandlers = cleanup;

    function initSwipe() {
        const container = document.querySelector('.container-feed');
        if (!container) return;
        container.removeEventListener('pointerdown', onPointerDown);
        container.addEventListener('pointerdown', onPointerDown);
    }

    // Conecta ações (funções globais do feed.php)
    engine.setCallbacks({
        onSwipeRight: (postId, card) => {
            if (typeof window.enviarReacao === 'function') {
                window.enviarReacao(postId, 'amei');
            } else {
                console.warn("[SWIPE] window.enviarReacao não definida");
            }
        },
        onSwipeLeft: (postId, card) => {
            // Descartar: apenas remove o card (a engine já faz isso)
            // Se quiser alguma ação extra, coloque aqui.
        },
        onSwipeUp: (postId, card) => {
            // Redireciona para comentários (a engine já faz)
        },
        onLongPress: (card) => {
            if (typeof window.showActionMenu === 'function') window.showActionMenu(card);
        }
    });

    // Feedback de tremor (mantém o estilo visual via classe)
    engine.setFeedbackHandlers({
    aplicar: (card) => {
        if (!card) return;
        card.classList.add('card-tremendo');
        card.style.animation = 'tremida-total 0.2s infinite';
        if (navigator.vibrate) navigator.vibrate(50);
    },
    remover: (card) => { 
        if (!card) return;
        card.classList.remove('card-tremendo');
        // MATA a animação e reseta o transform
        card.style.animation = 'none'; 
        card.style.transform = ''; 
    }
});

    window.iniciarFisicaSwipe = initSwipe;
    window.forcarParadaSwipe = engine.forceStop.bind(engine);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.body.classList.contains('modo-swipe-ativo')) initSwipe();
        });
    } else {
        if (document.body.classList.contains('modo-swipe-ativo')) initSwipe();
    }
})();
window.SwipeEngine.onReset = function() {
    console.log("[SWIPE] Limpeza visual completa (Hard Reset).");
    const cards = document.querySelectorAll('.spotted-card');
    cards.forEach(card => {
        // Remove classes
        card.classList.remove('card-tremendo', 'dragging');
        
        // MATA a animação e reseta o transform
        card.style.animation = 'none'; 
        card.style.transform = ''; 
        
        // Reseta variáveis CSS
        card.style.setProperty('--pos-x', '0px');
        card.style.setProperty('--pos-y', '0px');
        card.style.setProperty('--swipe-rot', '0deg');
    });
};