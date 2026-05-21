/* ============================================================
   FENDA SWIPE ENGINE (V7 Final) – Isolado, Mutex, Zero-Lag Reset
   ============================================================ */
(function () {
    'use strict';
    console.log("DEBUG: Fenda Swipe Engine carregou!");

    // ========== MUTEX (Estado do Sistema) ==========
    let swipeLock = false;
    let menuAtivo = null;
    let animationFrameId = null;
    let isDragging = false;
    let activeCard = null;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let longPressTimer = null;
    let moveDetected = false;

    // ========== Utilitários ==========
    const easeInOutOpacity = (dist, max = 150) => {
        let t = Math.min(Math.abs(dist) / max, 1);
        return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    };

    const resetFeedback = () => {
        document.querySelectorAll('.feedback-swipe').forEach(el => el.style.opacity = '0');
    };

    // ========== Menu de Ações (Long Press) ==========
    function showActionMenu(card) {
        if (menuAtivo) {
            menuAtivo.remove();
            menuAtivo = null;
        }
        const postId = card.dataset.id;
        const isOwner = card.classList.contains('post-admin-gold');

        const menu = document.createElement('div');
        menu.className = 'action-menu-longpress';
        menu.style.cssText = `
            position: fixed; bottom: 20px; left: 50%;
            transform: translateX(-50%);
            background: #1e1e2f; border: 1px solid #ff8c00;
            border-radius: 40px; padding: 12px 24px;
            display: flex; gap: 20px;
            z-index: 20000; backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            font-family: inherit;
        `;

        if (isOwner) {
            const btnDelete = document.createElement('button');
            btnDelete.textContent = '🗑️ Excluir';
            btnDelete.style.cssText = 'background:none; border:none; color:#ff4b2b; font-size:1rem; cursor:pointer; padding:8px 16px;';
            btnDelete.onclick = () => {
                if (confirm("⚠️ Tem certeza que quer excluir este post?")) {
                    window.location.href = `includes/excluir.php?id=${postId}`;
                }
                destroyMenuAndUnlock();
            };
            menu.appendChild(btnDelete);
        } else {
            const btnReport = document.createElement('button');
            btnReport.textContent = '🚨 Denunciar';
            btnReport.style.cssText = 'background:none; border:none; color:#ffbc00; font-size:1rem; cursor:pointer; padding:8px 16px;';
            btnReport.onclick = () => {
                alert(`Denúncia do post #${postId} enviada aos ADMs.`);
                destroyMenuAndUnlock();
            };
            menu.appendChild(btnReport);
        }

        const btnClose = document.createElement('button');
        btnClose.textContent = '✖️ Fechar';
        btnClose.style.cssText = 'background:none; border:none; color:#ccc; font-size:1rem; cursor:pointer; padding:8px 16px;';
        btnClose.onclick = () => destroyMenuAndUnlock();
        menu.appendChild(btnClose);

        document.body.appendChild(menu);
        menuAtivo = menu;

        const closeOnOutside = (e) => {
            if (!menu.contains(e.target)) {
                destroyMenuAndUnlock();
                document.removeEventListener('click', closeOnOutside);
                document.removeEventListener('touchstart', closeOnOutside);
            }
        };
        setTimeout(() => {
            document.addEventListener('click', closeOnOutside);
            document.addEventListener('touchstart', closeOnOutside);
        }, 10);

        swipeLock = true;
    }

    function destroyMenuAndUnlock() {
        if (menuAtivo) {
            menuAtivo.remove();
            menuAtivo = null;
        }
        swipeLock = false;
        if (isDragging) {
            isDragging = false;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            if (activeCard) {
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
            }
        }
    }

    // ========== Animação de saída do card ==========
    function animateExitAndRemove(card, x, y) {
        if (!card) return;
        card.style.transition = 'transform 0.3s ease-out, opacity 0.2s';
        card.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`;
        card.style.opacity = '0';
        setTimeout(() => {
            if (card && card.parentNode) {
                if (card._swipeCleanup) card._swipeCleanup();
                card.remove();
            }
            if (typeof window.abastecerPilhaFenda === 'function') {
                window.abastecerPilhaFenda();
            } else {
                window.dispatchEvent(new CustomEvent('fenda:cardRemoved'));
            }
        }, 300);
    }

    // ========== Física do Movimento ==========
    function startDrag(card, e) {
        if (swipeLock) return;
        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) {
            return;
        }
        if (e.button !== undefined && e.button !== 0) return;

        if (longPressTimer) clearTimeout(longPressTimer);
        moveDetected = false;

        activeCard = card;
        isDragging = true;

        activeCard.style.transition = 'none';
        activeCard.style.transform = 'translate(-50%, -50%)';
        activeCard.style.cursor = 'grabbing';
        activeCard.classList.add('dragging');

        startX = e.touches ? e.touches[0].clientX : e.clientX;
        startY = e.touches ? e.touches[0].clientY : e.clientY;
        currentX = 0;
        currentY = 0;

        longPressTimer = setTimeout(() => {
            if (!moveDetected && activeCard && isDragging) {
                isDragging = false;
                if (animationFrameId) cancelAnimationFrame(animationFrameId);
                activeCard.style.cursor = 'grab';
                activeCard.classList.remove('dragging');
                showActionMenu(activeCard);
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onEnd);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('touchend', onEnd);
                activeCard = null;
            }
        }, 300);
    }

    function onMove(e) {
        if (!isDragging || !activeCard) return;
        if (swipeLock) return;

        let clientX = e.touches ? e.touches[0].clientX : e.clientX;
        let clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const dx = clientX - startX;
        const dy = clientY - startY;

        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
            }
            moveDetected = true;
        }

        if (Math.abs(dx) < 5 && Math.abs(dy) < 5) return;

        if (e.cancelable) e.preventDefault();
        e.stopPropagation();

        currentX = dx;
        currentY = dy;

        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        animationFrameId = requestAnimationFrame(() => {
            if (!isDragging || !activeCard) return;
            const rotate = currentX / 30;
            activeCard.style.transform = `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) rotate(${rotate}deg) scale(1.02)`;

            const feedbackDir = document.querySelector('.feedback-direita');
            const feedbackEsq = document.querySelector('.feedback-esquerda');
            const feedbackCima = document.querySelector('.feedback-cima');

            if (currentX > 40 && feedbackDir) {
                feedbackDir.style.opacity = easeInOutOpacity(currentX);
            } else if (currentX < -40 && feedbackEsq) {
                feedbackEsq.style.opacity = easeInOutOpacity(currentX);
            } else if (currentY < -40 && Math.abs(currentX) < 40 && feedbackCima) {
                feedbackCima.style.opacity = easeInOutOpacity(currentY);
            } else {
                if (feedbackDir) feedbackDir.style.opacity = '0';
                if (feedbackEsq) feedbackEsq.style.opacity = '0';
                if (feedbackCima) feedbackCima.style.opacity = '0';
            }
        });
    }

    function onEnd(e) {
        if (!isDragging || !activeCard) {
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            isDragging = false;
            activeCard = null;
            if (longPressTimer) clearTimeout(longPressTimer);
            return;
        }

        isDragging = false;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        if (longPressTimer) clearTimeout(longPressTimer);

        const card = activeCard;
        const dx = currentX;
        const dy = currentY;
        const idPost = card.dataset.id;

        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onEnd);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);

        card.style.cursor = 'grab';
        card.classList.remove('dragging');
        resetFeedback();

        const threshold = 120;
        card.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.2s';

        if (dx < -threshold) {
            console.log(`[SWIPE] Card ${idPost} → ESQUERDA`);
            animateExitAndRemove(card, -800, dy);
        } else if (dx > threshold) {
            console.log(`[SWIPE] Card ${idPost} → DIREITA`);
            animateExitAndRemove(card, 800, dy);
        } else if (dy < -threshold && Math.abs(dx) < 80) {
            console.log(`[SWIPE] Card ${idPost} → CIMA (abrir post)`);
            window.location.href = `post.php?id=${idPost}#fofocar`;
        } else {
            card.style.transform = 'translate(-50%, -50%) rotate(0deg) scale(1)';
            setTimeout(() => {
                if (card) card.style.transition = 'none';
            }, 300);
        }

        activeCard = null;
        startX = startY = currentX = currentY = 0;
    }

    // ========== Inicialização no card do topo (CORRIGIDA) ==========
    function setupSwipeOnTopCard() {
        console.log("DEBUG: Tentando configurar o swipe...");

        // Busca o container – USO ÚNICO, sem duplicação
        const container = document.querySelector('.container-feed'); // Busca apenas pela principal
        if (!container) {
            console.warn("DEBUG: Container .container-feed.feed-empilhado NÃO encontrado!");
            return;
        }
        console.log("DEBUG: Container encontrado!");

        const topCard = container.querySelector('.spotted-card:first-child');
        if (!topCard) {
            console.log("DEBUG: Nenhum card (.spotted-card) encontrado no topo.");
            return;
        }
        console.log("DEBUG: Card encontrado! Iniciando eventos.");

        // Impede setup duplicado
        if (topCard._swipeActive) {
            console.log("DEBUG: Swipe já ativo neste card. Abortando.");
            return;
        }

        // Limpeza anterior, se existir
        if (typeof topCard._swipeCleanup === 'function') {
            topCard._swipeCleanup();
        }

        // Aplica estilos essenciais
        try {
            topCard.style.touchAction = 'none';
            topCard.style.userSelect = 'none';
            topCard.style.transition = 'transform 0.3s ease';
        } catch (error) {
            console.error("Erro ao aplicar estilos no card:", error);
            return;
        }

        const onStartHandler = (e) => {
            startDrag(topCard, e);
            if (isDragging && activeCard === topCard) {
                window.addEventListener('mousemove', onMove, { passive: false });
                window.addEventListener('mouseup', onEnd);
                window.addEventListener('touchmove', onMove, { passive: false });
                window.addEventListener('touchend', onEnd);
            }
        };

        topCard.addEventListener('mousedown', onStartHandler);
        topCard.addEventListener('touchstart', onStartHandler, { passive: false });

        topCard._swipeActive = true;
        topCard._swipeCleanup = () => {
            topCard.removeEventListener('mousedown', onStartHandler);
            topCard.removeEventListener('touchstart', onStartHandler);
            delete topCard._swipeActive;
            delete topCard._swipeCleanup;
        };

        console.log("DEBUG: Swipe configurado com sucesso no card", topCard.dataset.id);
    }

    function observeNewCards() {
        const container = document.querySelector('.container-feed');
        if (!container) return;
        const observer = new MutationObserver(() => {
            console.log("DEBUG: Mutação detectada no container. Reiniciando swipe...");
            setupSwipeOnTopCard();
        });
        observer.observe(container, { childList: true, subtree: false });
        return observer;
    }

    window.iniciarFisicaSwipe = function () {
        console.log("DEBUG: window.iniciarFisicaSwipe chamado.");
        setupSwipeOnTopCard();
    };

    window.forcarParadaSwipe = function () {
        if (isDragging && activeCard) {
            onEnd({ type: 'force' });
        }
        isDragging = false;
        activeCard = null;
        if (animationFrameId) cancelAnimationFrame(animationFrameId);
        if (longPressTimer) clearTimeout(longPressTimer);
        swipeLock = false;
        if (menuAtivo) menuAtivo.remove();
        menuAtivo = null;
        resetFeedback();
    };

    // Auto‑inicialização (apenas se o modo swipe estiver ativo)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log("DEBUG: DOMContentLoaded – verificando modo swipe.");
            if (document.body.classList.contains('modo-swipe-ativo')) {
                window.iniciarFisicaSwipe();
                observeNewCards();
            } else {
                console.log("DEBUG: Modo swipe NÃO está ativo no body.");
            }
        });
    } else {
        console.log("DEBUG: Documento já carregado. Verificando modo swipe.");
        if (document.body.classList.contains('modo-swipe-ativo')) {
            window.iniciarFisicaSwipe();
            observeNewCards();
        } else {
            console.log("DEBUG: Modo swipe NÃO está ativo no body.");
        }
    }
})();