/* ============================================================
   FENDA SWIPE PC – ADAPTER (usa SwipeEngine)
   ============================================================ */
(function() {
    'use strict';

    if (!window.SwipeEngine) {
        console.error("[SWIPE] Core não encontrado.");
        return;
    }

    const engine = window.SwipeEngine;
    let activeCardElement = null;
    let moveHandler = null, upHandler = null, cancelHandler = null;

    function updatePosition(dx, dy) {
        if (!activeCardElement) return;
        const rotate = dx / 30;
        activeCardElement.style.transform = `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) rotate(${rotate}deg)`;
    }
    function resetPosition() {
        if (!activeCardElement) return;
        activeCardElement.style.transform = 'translate(-50%, -50%) rotate(0deg)';
    }
    function attachPositionMethods(card) {
        card._updatePosition = updatePosition.bind(null, card);
        card._resetPosition = resetPosition.bind(null, card);
        activeCardElement = card;
        // Previne seleção de texto durante arrasto
        card.style.userSelect = 'none';
        card.style.webkitUserSelect = 'none';
        card.style.userDrag = 'none';
    }

    function onPointerDown(e) {
        const card = e.target.closest('.spotted-card');
        if (!card) return;
        const container = document.querySelector('.container-feed');
        const topCard = container?.querySelector('.spotted-card:first-child');
        if (card !== topCard) return;

        attachPositionMethods(card);
        engine.onPointerStart(e, card);
        if (engine.isDragging()) {
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
        // Bloqueia menu de contexto nos cards
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('.spotted-card')) e.preventDefault();
        });
    }

    // Conexão com ações (mesmas do mobile)
    engine.setCallbacks({
        onSwipeRight: (postId) => { if (typeof window.enviarReacao === 'function') window.enviarReacao(postId, 'amei'); },
        onSwipeLeft: () => {},
        onSwipeUp: (postId) => { window.location.href = `post.php?id=${postId}#fofocar`; },
        onLongPress: (card) => { if (typeof window.showActionMenu === 'function') window.showActionMenu(card); }
    });

    engine.setFeedbackHandlers({
    aplicar: (card) => {
        if (!card) return;
        card.classList.add('card-tremendo');
        card.style.animation = 'tremida-total 0.2s infinite';
        if (navigator.vibrate) navigator.vibrate(50);
    },
    remover: (card) => { 
        if (!card) return;
        console.log("DEBUG: O sistema tentou remover a classe do card:", card);
        card.classList.remove('card-tremendo');
        card.classList.add('parar-tremor'); // <--- A força bruta
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

// Conecta a limpeza do Adaptador com a Engine
window.SwipeEngine.onReset = function() {
    console.warn("[SWIPE] Executando Hard Reset no DOM.");
    const cards = document.querySelectorAll('.spotted-card');
    cards.forEach(card => {
        // Remove classes de estado
        card.classList.remove('card-tremendo', 'dragging');
        
        // Zera transformações visuais
        card.style.transform = ''; 
        card.style.animation = 'none'; // Mata qualquer tremor residual
        
        // Zera variáveis CSS se estiverem em uso
        card.style.setProperty('--pos-x', '0px');
        card.style.setProperty('--pos-y', '0px');
        card.style.setProperty('--swipe-rot', '0deg');
    });
};