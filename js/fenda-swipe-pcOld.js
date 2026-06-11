/* ============================================================
   FENDA SWIPE PC – ENGINE AUTORITÁRIA (CORRIGIDA)
   ============================================================
   - Arraste pointerdown iniciado
   - Captura de ponteiro + GPU acceleration
   - Bloqueia hover e transições durante o swipe
   - Feedback long-press com tremor e vibração (corrigido)
   ============================================================ */
(function () {
    'use strict';
    console.log("DEBUG: Fenda Swipe PC carregou (Modo Dr. Doom - Corrigido)");

    // ========== ESTADO GLOBAL ==========
    let swipeLock = false;
    let menuAtivo = null;
    let animationFrameId = null;
    let isDragging = false;
    let activeCard = null;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let longPressTimer = null;
    let moveDetected = false;

    // ========== FEEDBACK LONG PRESS (TREMOR + VIBRAÇÃO) – CORRIGIDO ==========
    function aplicarFeedbackLongPress(card) {
        if (!card) return;
        // Salva o transform original e remove inline
        const originalTransform = card.style.transform;
        card.dataset.originalTransform = originalTransform || '';
        card.style.transform = '';
        card.classList.add('card-tremendo');
        if (navigator.vibrate) navigator.vibrate(50);
    }

    function removerFeedbackLongPress(card) {
        if (!card) return;
        card.classList.remove('card-tremendo');
        const original = card.dataset.originalTransform;
        if (original !== undefined) {
            card.style.transform = original;
            delete card.dataset.originalTransform;
        } else {
            card.style.transform = '';
        }
    }

    // ========== FUNÇÃO GLOBAL PARA EXPANDIR POST (fallback / menu) ==========
    window.expandirPostCompleto = function(card) {
        if (!card) return;
        const btnExpandir = card.querySelector('.btn-expandir');
        if (btnExpandir && typeof btnExpandir.onclick === 'function') {
            btnExpandir.onclick();
        } else {
            const postId = card.dataset.id;
            if (postId) window.location.href = `post.php?id=${postId}#fofocar`;
        }
    };

    // Observer apenas para logs (não interfere)
    let domObserver = null;
    let observerActive = true;
    function silenceObserverLogs() { if (domObserver && observerActive) { domObserver.disconnect(); observerActive = false; } }
    function resumeObserverLogs() { if (domObserver && !observerActive) { const container = document.querySelector('.container-feed'); if (container) { domObserver.observe(container, { childList: true, subtree: false }); observerActive = true; } } }

    // ========== UTILITÁRIOS ==========
    const easeInOutOpacity = (dist, max = 150) => {
        let t = Math.min(Math.abs(dist) / max, 1);
        return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    };
    const resetFeedback = () => {
        document.querySelectorAll('.feedback-swipe').forEach(el => el.style.opacity = '0');
    };

    // ========== MENU LONG PRESS ==========
    function showActionMenu(card) {
        if (swipeLock) return;
        silenceObserverLogs();
        removerFeedbackLongPress(card);
        if (menuAtivo) { menuAtivo.remove(); menuAtivo = null; }
        const postId = card.dataset.id;
        const isOwner = card.classList.contains('post-admin-gold');
        const menu = document.createElement('div');
        menu.className = 'action-menu-longpress';
        menu.style.cssText = `position: fixed; bottom: 50px; left: 50%; transform: translateX(-50%); background: #1e1e2f; border: 1px solid #ff8c00; border-radius: 40px; padding: 12px 24px; display: flex; gap: 20px; z-index: 20000; backdrop-filter: blur(10px); box-shadow: 0 0 20px rgba(0,0,0,0.5);`;
        if (isOwner) {
            const btnDelete = document.createElement('button');
            btnDelete.textContent = '🗑️ Excluir';
            btnDelete.style.cssText = 'background:none; border:none; color:#ff4b2b; font-size:1rem; cursor:pointer; padding:8px 16px;';
            btnDelete.onclick = () => { if (confirm("⚠️ Tem certeza que quer excluir este post?")) window.location.href = `includes/excluir.php?id=${postId}`; destroyMenuAndUnlock(); };
            menu.appendChild(btnDelete);
        } else {
            const btnReport = document.createElement('button');
            btnReport.textContent = '🚨 Denunciar';
            btnReport.style.cssText = 'background:none; border:none; color:#ffbc00; font-size:1rem; cursor:pointer; padding:8px 16px;';
            btnReport.onclick = () => { alert(`Denúncia do post #${postId} enviada aos ADMs.`); destroyMenuAndUnlock(); };
            menu.appendChild(btnReport);
        }
        const btnClose = document.createElement('button');
        btnClose.textContent = '✖️ Fechar';
        btnClose.style.cssText = 'background:none; border:none; color:#ccc; font-size:1rem; cursor:pointer; padding:8px 16px;';
        btnClose.onclick = () => destroyMenuAndUnlock();
        menu.appendChild(btnClose);
        document.body.appendChild(menu);
        menuAtivo = menu;
        const closeOnOutside = (e) => { if (!menu.contains(e.target)) { destroyMenuAndUnlock(); document.removeEventListener('click', closeOnOutside); document.removeEventListener('mousedown', closeOnOutside); } };
        setTimeout(() => { document.addEventListener('click', closeOnOutside); document.addEventListener('mousedown', closeOnOutside); }, 10);
        swipeLock = true;
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
                activeCard.style.pointerEvents = 'auto';
                removerFeedbackLongPress(activeCard);
            }
        }
        resumeObserverLogs();
    }

    function animateExitAndRemove(card, x, y) {
        if (!card) return;
        card.style.transition = 'transform 0.3s ease-out, opacity 0.2s';
        card.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`;
        card.style.opacity = '0';
        setTimeout(() => {
            if (card && card.parentNode) {
                if (card._swipeCleanup) card._swipeCleanup?.();
                card.remove();
            }
            if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda();
            else window.dispatchEvent(new CustomEvent('fenda:cardRemoved'));
            resumeObserverLogs();
        }, 300);
    }

    // ========== HANDLERS CORRIGIDOS ==========
    let onPointerMoveHandler, onPointerUpHandler, onPointerCancelHandler;

    function onPointerDown(e) {
        if (swipeLock) return;

        const card = e.target.closest('.spotted-card');
        if (!card) return;

        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) {
            return;
        }

        const container = document.querySelector('.container-feed');
        const topCard = container?.querySelector('.spotted-card:first-child');
        if (card !== topCard) return;

        e.preventDefault();
        e.stopPropagation();
        card.setPointerCapture(e.pointerId);

        card.style.userSelect = 'none';
        card.style.webkitUserSelect = 'none';
        card.style.webkitUserDrag = 'none';
        card.style.userDrag = 'none';
        card.style.willChange = 'transform';
        card.style.pointerEvents = 'none';

        silenceObserverLogs();
        if (longPressTimer) clearTimeout(longPressTimer);
        moveDetected = false;

        activeCard = card;
        isDragging = true;
        activeCard.classList.remove('com-transicao');
        activeCard.style.transition = 'none';
        activeCard.style.transform = 'translate(-50%, -50%)';
        activeCard.style.cursor = 'grabbing';
        activeCard.classList.add('dragging');

        startX = e.clientX;
        startY = e.clientY;
        currentX = 0;
        currentY = 0;

        window.addEventListener('pointermove', onPointerMoveHandler);
        window.addEventListener('pointerup', onPointerUpHandler);
        window.addEventListener('pointercancel', onPointerCancelHandler);

        longPressTimer = setTimeout(() => {
            if (!moveDetected && activeCard && isDragging) {
                aplicarFeedbackLongPress(activeCard);
                
                isDragging = false;
                if (animationFrameId) cancelAnimationFrame(animationFrameId);
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
                activeCard.style.pointerEvents = 'auto';
                showActionMenu(activeCard);
                window.removeEventListener('pointermove', onPointerMoveHandler);
                window.removeEventListener('pointerup', onPointerUpHandler);
                window.removeEventListener('pointercancel', onPointerCancelHandler);
                activeCard = null;
            }
        }, 300);
    }

    function onPointerMove(e) {
    if (!isDragging || !activeCard || swipeLock) return;

    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    // THRESHOLD: só considera movimento significativo se ultrapassar 10px
    const isSignificantMove = Math.abs(dx) > 10 || Math.abs(dy) > 10;

    // Se o movimento ainda é pequeno (<=10px), não faz nada – mantém o timer intacto
    if (!isSignificantMove && !moveDetected) return;

    // A partir daqui, o movimento já ultrapassou o limiar ou já foi detectado antes
    if (!moveDetected) {
        // Primeira vez que ultrapassa o limiar: cancela o timer de long press
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
            removerFeedbackLongPress(activeCard);
        }
        moveDetected = true;
        document.body.classList.add('fenda-arrastando');
        e.preventDefault();
        e.stopPropagation();
    }

    // Atualiza as coordenadas atuais (já sabemos que moveDetected é true)
    currentX = dx;
    currentY = dy;

    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    animationFrameId = requestAnimationFrame(() => {
        if (!isDragging || !activeCard) return;
        const rotate = currentX / 30;
        activeCard.style.setProperty('--pos-x', currentX + 'px');
        activeCard.style.setProperty('--pos-y', currentY + 'px');
        activeCard.style.setProperty('--swipe-rot', rotate + 'deg');

        const feedbackDir = document.querySelector('.feedback-direita');
        const feedbackEsq = document.querySelector('.feedback-esquerda');
        const feedbackCima = document.querySelector('.feedback-cima');
        if (currentX > 40 && feedbackDir) feedbackDir.style.opacity = easeInOutOpacity(currentX);
        else if (currentX < -40 && feedbackEsq) feedbackEsq.style.opacity = easeInOutOpacity(currentX);
        else if (currentY < -40 && Math.abs(currentX) < 40 && feedbackCima) feedbackCima.style.opacity = easeInOutOpacity(currentY);
        else {
            if (feedbackDir) feedbackDir.style.opacity = '0';
            if (feedbackEsq) feedbackEsq.style.opacity = '0';
            if (feedbackCima) feedbackCima.style.opacity = '0';
        }
    });
}

    function onPointerUp(e) {
        if (!isDragging || !activeCard) {
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
                removerFeedbackLongPress(activeCard);
            }
            isDragging = false;
            activeCard = null;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            document.body.classList.remove('fenda-arrastando');
            resetFeedback();
            resumeObserverLogs();
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
        const dx = currentX, dy = currentY, idPost = card.dataset.id;

        window.removeEventListener('pointermove', onPointerMoveHandler);
        window.removeEventListener('pointerup', onPointerUpHandler);
        window.removeEventListener('pointercancel', onPointerCancelHandler);

        card.style.cursor = 'grab';
        card.classList.remove('dragging');
        card.style.pointerEvents = 'auto';
        card.style.willChange = '';
        resetFeedback();

        const threshold = 120;
        card.classList.add('com-transicao');

        if (dx < -threshold) {
            animateExitAndRemove(card, -800, dy);
        } else if (dx > threshold) {
            animateExitAndRemove(card, 800, dy);
        } else if (dy < -threshold && Math.abs(dx) < 80) {
            window.location.href = `post.php?id=${idPost}#fofocar`;
        } else {
            card.style.transform = 'translate(-50%, -50%) rotate(0deg) scale(1)';
            setTimeout(() => {
                if (card) card.style.transition = 'none';
            }, 300);
            resumeObserverLogs();
        }

        activeCard = null;
        startX = startY = currentX = currentY = 0;
    }

    function onPointerCancel(e) {
        if (activeCard && isDragging) {
            if (activeCard) {
                activeCard.style.transform = 'translate(-50%, -50%) rotate(0deg) scale(1)';
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
                activeCard.style.pointerEvents = 'auto';
                removerFeedbackLongPress(activeCard);
            }
            isDragging = false;
            activeCard = null;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
                removerFeedbackLongPress(activeCard);
            }
            resetFeedback();
            document.body.classList.remove('fenda-arrastando');
            window.removeEventListener('pointermove', onPointerMoveHandler);
            window.removeEventListener('pointerup', onPointerUpHandler);
            window.removeEventListener('pointercancel', onPointerCancelHandler);
        }
        if (menuAtivo) destroyMenuAndUnlock();
        resumeObserverLogs();
    }

    onPointerMoveHandler = onPointerMove;
    onPointerUpHandler = onPointerUp;
    onPointerCancelHandler = onPointerCancel;

    function blockContextMenu(e) {
        if (e.target.closest('.spotted-card')) {
            e.preventDefault();
            e.stopPropagation();
        }
    }
    document.addEventListener('contextmenu', blockContextMenu);

    function initSwipeOnContainer() {
        const container = document.querySelector('.container-feed');
        if (!container) { console.warn("Container .container-feed não encontrado no PC"); return false; }
        if (container._swipePointerDown) container.removeEventListener('pointerdown', container._swipePointerDown);
        container.addEventListener('pointerdown', onPointerDown);
        container._swipePointerDown = onPointerDown;

        const applyNoDragStyles = () => {
            document.querySelectorAll('.spotted-card').forEach(card => {
                card.style.userSelect = 'none';
                card.style.webkitUserSelect = 'none';
                card.style.webkitUserDrag = 'none';
                card.style.userDrag = 'none';
                if (document.body.classList.contains('modo-swipe-ativo')) {
                    card.style.transition = 'none';
                }
            });
        };
        applyNoDragStyles();
        const styleObserver = new MutationObserver(() => applyNoDragStyles());
        styleObserver.observe(container, { childList: true, subtree: true });
        return true;
    }

    function initObserver() {
        const container = document.querySelector('.container-feed');
        if (!container || domObserver) return;
        domObserver = new MutationObserver(() => {
            if (!observerActive || isDragging || menuAtivo || swipeLock) return;
        });
        domObserver.observe(container, { childList: true, subtree: false });
        observerActive = true;
    }

    window.iniciarFisicaSwipe = function () { initSwipeOnContainer(); };
    window.forcarParadaSwipe = function () {
        if (isDragging && activeCard) onPointerUp({ type: 'force' });
        isDragging = false; activeCard = null;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        if (longPressTimer) {
            clearTimeout(longPressTimer);
            longPressTimer = null;
            removerFeedbackLongPress(activeCard);
        }
        swipeLock = false;
        if (menuAtivo) menuAtivo.remove();
        menuAtivo = null;
        resetFeedback();
        document.body.classList.remove('fenda-arrastando');
        window.removeEventListener('pointermove', onPointerMoveHandler);
        window.removeEventListener('pointerup', onPointerUpHandler);
        window.removeEventListener('pointercancel', onPointerCancelHandler);
        resumeObserverLogs();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { if (document.body.classList.contains('modo-swipe-ativo')) { initObserver(); window.iniciarFisicaSwipe(); } });
    } else { if (document.body.classList.contains('modo-swipe-ativo')) { initObserver(); window.iniciarFisicaSwipe(); } }
})();