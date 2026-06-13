/* ============================================================
   FENDA SWIPE MOBILE – COM MODAL GLOBAL (FUNÇÃO ÚNICA)
   ============================================================ */
(function () {
    'use strict';

    let swipeLock = false;
    let animationFrameId = null;
    let isDragging = false;
    let activeCard = null;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let longPressTimer = null;
    let moveDetected = false;

    let domObserver = null;
    let observerActive = true;

    function silenceObserverLogs() {
        if (domObserver && observerActive) {
            domObserver.disconnect();
            observerActive = false;
        }
    }
    function resumeObserverLogs() {
        if (domObserver && !observerActive) {
            const container = document.querySelector('.container-feed');
            if (container) {
                domObserver.observe(container, { childList: true, subtree: false });
                observerActive = true;
            }
        }
    }

    const easeInOutOpacity = (dist, max = 150) => {
        let t = Math.min(Math.abs(dist) / max, 1);
        return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    };
    const resetFeedback = () => {
        document.querySelectorAll('.feedback-swipe').forEach(el => el.style.opacity = '0');
    };

    function aplicarEfeitoPrisma(card) {
        if (!card) return;
        card.classList.add('card-long-press-active');
        if (navigator.vibrate) navigator.vibrate(50);
    }
    function removerEfeitoPrisma(card) {
        if (!card) return;
        card.classList.remove('card-long-press-active');
    }

    function animateExitAndRemove(card, x, y) {
        if (!card) return;
        removerEfeitoPrisma(card);
        card.style.transition = 'transform 0.3s ease-out, opacity 0.2s';
        card.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`;
        card.style.opacity = '0';
        setTimeout(() => {
            if (card && card.parentNode) {
                card.remove();
            }
            if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda();
            else window.dispatchEvent(new CustomEvent('fenda:cardRemoved'));
            resumeObserverLogs();
        }, 300);
    }

    let onPointerMoveHandler, onPointerUpHandler, onPointerCancelHandler;

    function onPointerDown(e) {
        if (swipeLock) return;
        const card = e.target.closest('.spotted-card');
        if (!card) return;
        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) return;
        const container = document.querySelector('.container-feed');
        const topCard = container?.querySelector('.spotted-card:first-child');
        if (card !== topCard) return;

        e.preventDefault();
        e.stopPropagation();
        card.setPointerCapture(e.pointerId);
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
        startX = e.clientX; startY = e.clientY;
        currentX = 0; currentY = 0;
        window.addEventListener('pointermove', onPointerMoveHandler);
        window.addEventListener('pointerup', onPointerUpHandler);
        window.addEventListener('pointercancel', onPointerCancelHandler);

        longPressTimer = setTimeout(() => {
            if (!moveDetected && activeCard && isDragging) {
                aplicarEfeitoPrisma(activeCard);
                isDragging = false;
                if (animationFrameId) cancelAnimationFrame(animationFrameId);
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
                // 🔥 CHAMA A FUNÇÃO GLOBAL DO MODAL
                if (typeof window.mostrarMenuAcoes === 'function') {
                    window.mostrarMenuAcoes(activeCard.dataset.id, activeCard.classList.contains('post-admin-gold'), activeCard);
                } else {
                    console.warn("[SWIPE] window.mostrarMenuAcoes não definida");
                }
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
        if (Math.abs(dx) < 20 && Math.abs(dy) < 20) return;
        if (longPressTimer) { clearTimeout(longPressTimer); longPressTimer = null; }
        moveDetected = true;
        document.body.classList.add('fenda-arrastando');
        e.preventDefault(); e.stopPropagation();
        currentX = dx; currentY = dy;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(() => {
            if (!isDragging || !activeCard) return;
            const rotate = currentX / 30;
            activeCard.style.transform = `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) rotate(${rotate}deg) scale(1.02)`;
            const feedbackDir = document.querySelector('.feedback-direita');
            const feedbackEsq = document.querySelector('.feedback-esquerda');
            const feedbackCima = document.querySelector('.feedback-cima');
            if (currentX > 40 && feedbackDir) feedbackDir.style.opacity = easeInOutOpacity(currentX);
            else if (currentX < -40 && feedbackEsq) feedbackEsq.style.opacity = easeInOutOpacity(currentX);
            else if (currentY < -40 && Math.abs(currentX) < 40 && feedbackCima) feedbackCima.style.opacity = easeInOutOpacity(currentY);
            else { if (feedbackDir) feedbackDir.style.opacity = '0'; if (feedbackEsq) feedbackEsq.style.opacity = '0'; if (feedbackCima) feedbackCima.style.opacity = '0'; }
        });
    }

    function onPointerUp(e) {
        if (!isDragging || !activeCard) {
            if (longPressTimer) clearTimeout(longPressTimer);
            if (activeCard) removerEfeitoPrisma(activeCard);
            isDragging = false;
            activeCard = null;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            document.body.classList.remove('fenda-arrastando');
            resetFeedback();
            resumeObserverLogs();
            return;
        }

        document.body.classList.remove('fenda-arrastando');
        if (longPressTimer) { clearTimeout(longPressTimer); longPressTimer = null; }
        isDragging = false;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        const card = activeCard;
        const dx = currentX, dy = currentY, idPost = card.dataset.id;
        window.removeEventListener('pointermove', onPointerMoveHandler);
        window.removeEventListener('pointerup', onPointerUpHandler);
        window.removeEventListener('pointercancel', onPointerCancelHandler);
        card.style.cursor = 'grab';
        card.classList.remove('dragging');
        resetFeedback();
        const threshold = 120;
        card.classList.add('com-transicao');

        if (dx < -threshold) {
            animateExitAndRemove(card, -800, dy);
        } else if (dx > threshold) {
            if (!idPost) {
                console.error("[SWIPE] ID do post não encontrado!");
                animateExitAndRemove(card, 800, dy);
                return;
            }
            if (card._processing) return;
            card._processing = true;

            const reagirComTimeout = async (cardRef, postId) => {
                cardRef.classList.add('is-loading');
                const spinner = document.createElement('div');
                spinner.className = 'loading-spinner';
                cardRef.appendChild(spinner);
                const timeoutPromise = new Promise((_, reject) => setTimeout(() => reject(new Error("Timeout")), 5000));
                try {
                    await Promise.race([window.enviarReacao(postId, 'amei'), timeoutPromise]);
                    spinner.remove();
                    cardRef.classList.remove('is-loading');
                    animateExitAndRemove(cardRef, 800, 0);
                } catch (err) {
                    console.warn("[SWIPE] Falha na reação:", err);
                    spinner.remove();
                    cardRef.classList.remove('is-loading');
                    animateExitAndRemove(cardRef, 800, 0);
                } finally {
                    delete cardRef._processing;
                }
            };
            reagirComTimeout(card, idPost);
        } else if (dy < -threshold && Math.abs(dx) < 80) {
            window.location.href = `post.php?id=${idPost}#fofocar`;
        } else {
            card.style.transform = 'translate(-50%, -50%) rotate(0deg) scale(1)';
            setTimeout(() => { if (card) card.style.transition = 'none'; }, 300);
            resumeObserverLogs();
        }

        activeCard = null;
        startX = startY = currentX = currentY = 0;
    }

    function onPointerCancel(e) {
        if (activeCard) removerEfeitoPrisma(activeCard);
        if (activeCard && isDragging) {
            if (activeCard) {
                activeCard.style.transform = 'translate(-50%, -50%) rotate(0deg) scale(1)';
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
            }
            isDragging = false; activeCard = null;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            if (longPressTimer) clearTimeout(longPressTimer);
            resetFeedback();
            document.body.classList.remove('fenda-arrastando');
            window.removeEventListener('pointermove', onPointerMoveHandler);
            window.removeEventListener('pointerup', onPointerUpHandler);
            window.removeEventListener('pointercancel', onPointerCancelHandler);
        }
        resumeObserverLogs();
    }

    onPointerMoveHandler = onPointerMove;
    onPointerUpHandler = onPointerUp;
    onPointerCancelHandler = onPointerCancel;

    function initSwipeOnContainer() {
        const container = document.querySelector('.container-feed');
        if (!container) { console.warn("Container .container-feed não encontrado"); return false; }
        if (container._swipePointerDown) container.removeEventListener('pointerdown', container._swipePointerDown);
        container.addEventListener('pointerdown', onPointerDown);
        container._swipePointerDown = onPointerDown;
        return true;
    }
    function initObserver() {
        const container = document.querySelector('.container-feed');
        if (!container || domObserver) return;
        domObserver = new MutationObserver(() => {
            if (!observerActive || isDragging || swipeLock) return;
        });
        domObserver.observe(container, { childList: true, subtree: false });
        observerActive = true;
    }
    window.iniciarFisicaSwipe = function () { initSwipeOnContainer(); };
    window.forcarParadaSwipe = function () {
        if (activeCard) removerEfeitoPrisma(activeCard);
        if (isDragging && activeCard) onPointerUp({ type: 'force' });
        isDragging = false; activeCard = null;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        if (longPressTimer) clearTimeout(longPressTimer);
        swipeLock = false;
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