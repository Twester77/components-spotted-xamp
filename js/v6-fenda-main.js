/* A FENDA - ENGINE CENTRAL (V6 – Long Press Global + Swipe Blindado) */
/* Unifica o gesto de segurar (300ms) para ações administrativas em qualquer card */
/* Remove completamente o ellipsis do DOM – o Long Press é o único meio de gerenciar */

// ================================================================
// 1. FUNÇÕES GLOBAIS DE CONTROLE (áudio, bolhas, toolbar, etc.)
// ================================================================
window.audioLiberado = false;

window.setBolhasLocal = function (valor) {
    const inputHidden = document.getElementById('input_pref_bolhas');
    if (inputHidden) inputHidden.value = valor;
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');
    if (valor === 1) {
        btnOn.classList.add('active');
        btnOff.classList.remove('active');
    } else {
        btnOff.classList.add('active');
        btnOn.classList.remove('active');
    }
    const containerBolhas = document.querySelector('.bubbles-container');
    if (containerBolhas) containerBolhas.style.display = (valor === 1) ? 'block' : 'none';
};

window.atualizarInterfaceBolhas = function (ligar) {
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');
    if (btnOn && btnOff) {
        if (ligar) {
            btnOn.style.backgroundColor = '#00a896';
            btnOff.style.backgroundColor = 'transparent';
        } else {
            btnOn.style.backgroundColor = 'transparent';
            btnOff.style.backgroundColor = '#ff4444';
        }
    }
};

window.toggleToolbar = function () {
    const toolbar = document.getElementById('fenda-toolbar');
    const icon = document.getElementById('trigger-icon');
    if (!toolbar) return;
    toolbar.classList.toggle('toolbar-aberta');
    icon.innerText = toolbar.classList.contains('toolbar-aberta') ? '❌' : '🧭';
};

window.toggleHackerMode = function () {
    const body = document.body;
    const btnNav = document.getElementById('hacker-toggle');
    const btnToolbar = document.getElementById('hacker-toggle-lateral');
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

function abrirModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-aberto');
        document.body.style.overflow = 'hidden';
    }
}

function fecharModalPost() {
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

// ================================================================
// 2. FUNÇÃO GLOBAL DE LONG PRESS (unificada para qualquer card)
// ================================================================
// Cria e exibe o menu de ações (excluir/denunciar) no lugar do card
function exibirMenuAcoes(card) {
    // Remove qualquer menu existente
    const menuExistente = document.querySelector('.action-menu-longpress');
    if (menuExistente) menuExistente.remove();

    const postId = card.dataset.id;
    const isOwner = card.classList.contains('post-admin-gold'); // ou use dataset.userId vs sessão

    const menu = document.createElement('div');
    menu.className = 'action-menu-longpress';
    menu.style.position = 'fixed';
    menu.style.bottom = '20px';
    menu.style.left = '50%';
    menu.style.transform = 'translateX(-50%)';
    menu.style.backgroundColor = '#1e1e2f';
    menu.style.border = '1px solid #ff8c00';
    menu.style.borderRadius = '40px';
    menu.style.padding = '12px 24px';
    menu.style.display = 'flex';
    menu.style.gap = '20px';
    menu.style.zIndex = '20000';
    menu.style.backdropFilter = 'blur(10px)';
    menu.style.boxShadow = '0 0 20px rgba(0,0,0,0.5)';
    menu.style.fontFamily = 'inherit';

    if (isOwner) {
        const btnDelete = document.createElement('button');
        btnDelete.textContent = '🗑️ Excluir';
        btnDelete.style.background = 'none';
        btnDelete.style.border = 'none';
        btnDelete.style.color = '#ff4b2b';
        btnDelete.style.fontSize = '1rem';
        btnDelete.style.cursor = 'pointer';
        btnDelete.style.padding = '8px 16px';
        btnDelete.onclick = () => {
            if (confirm("⚠️ Tem certeza que quer excluir este post?")) {
                window.location.href = `includes/excluir.php?id=${postId}`;
            }
            menu.remove();
        };
        menu.appendChild(btnDelete);
    } else {
        const btnReport = document.createElement('button');
        btnReport.textContent = '🚨 Denunciar';
        btnReport.style.background = 'none';
        btnReport.style.border = 'none';
        btnReport.style.color = '#ffbc00';
        btnReport.style.fontSize = '1rem';
        btnReport.style.cursor = 'pointer';
        btnReport.style.padding = '8px 16px';
        btnReport.onclick = () => {
            alert(`Denúncia do post #${postId} enviada aos ADMs.`);
            menu.remove();
        };
        menu.appendChild(btnReport);
    }

    const btnClose = document.createElement('button');
    btnClose.textContent = '✖️ Fechar';
    btnClose.style.background = 'none';
    btnClose.style.border = 'none';
    btnClose.style.color = '#ccc';
    btnClose.style.cursor = 'pointer';
    btnClose.style.padding = '8px 16px';
    btnClose.onclick = () => menu.remove();
    menu.appendChild(btnClose);

    document.body.appendChild(menu);

    // Fecha o menu ao clicar/tocar fora
    const closeOnOutside = (e) => {
        if (!menu.contains(e.target)) {
            menu.remove();
            document.removeEventListener('click', closeOnOutside);
            document.removeEventListener('touchstart', closeOnOutside);
        }
    };
    setTimeout(() => {
        document.addEventListener('click', closeOnOutside);
        document.addEventListener('touchstart', closeOnOutside);
    }, 10);
}

// Aplica Long Press a um card específico (usado para cards estáticos e dinâmicos)
function aplicarLongPressAoCard(card) {
    if (!card || card.hasAttribute('data-longpress-active')) return;

    let timer = null;
    let startX = 0, startY = 0;
    let moved = false;

    const startHandler = (e) => {
        // Ignora se clicou em botões de reagir/fofocar (eles têm seus próprios handlers)
        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) {
            return;
        }
        // Apenas botão esquerdo do mouse
        if (e.button !== undefined && e.button !== 0) return;

        startX = e.touches ? e.touches[0].clientX : e.clientX;
        startY = e.touches ? e.touches[0].clientY : e.clientY;
        moved = false;

        timer = setTimeout(() => {
            if (!moved) {
                exibirMenuAcoes(card);
                // Remove os listeners temporários para não atrapalhar
                card.removeEventListener('mousemove', moveHandler);
                card.removeEventListener('touchmove', moveHandler);
                card.removeEventListener('mouseup', endHandler);
                card.removeEventListener('touchend', endHandler);
                card.removeEventListener('touchcancel', endHandler);
            }
        }, 300);

        card.addEventListener('mousemove', moveHandler);
        card.addEventListener('touchmove', moveHandler);
        card.addEventListener('mouseup', endHandler);
        card.addEventListener('touchend', endHandler);
        card.addEventListener('touchcancel', endHandler);
    };

    const moveHandler = (e) => {
        let clientX = e.touches ? e.touches[0].clientX : e.clientX;
        let clientY = e.touches ? e.touches[0].clientY : e.clientY;
        if (Math.abs(clientX - startX) > 5 || Math.abs(clientY - startY) > 5) {
            moved = true;
            if (timer) clearTimeout(timer);
            card.removeEventListener('mousemove', moveHandler);
            card.removeEventListener('touchmove', moveHandler);
            card.removeEventListener('mouseup', endHandler);
            card.removeEventListener('touchend', endHandler);
            card.removeEventListener('touchcancel', endHandler);
        }
    };

    const endHandler = () => {
        if (timer) clearTimeout(timer);
        card.removeEventListener('mousemove', moveHandler);
        card.removeEventListener('touchmove', moveHandler);
        card.removeEventListener('mouseup', endHandler);
        card.removeEventListener('touchend', endHandler);
        card.removeEventListener('touchcancel', endHandler);
    };

    card.addEventListener('mousedown', startHandler);
    card.addEventListener('touchstart', startHandler, { passive: false });

    card.setAttribute('data-longpress-active', 'true');
    // Guarda os handlers para poder remover depois se necessário (opcional)
    card._longPressCleanup = () => {
        card.removeEventListener('mousedown', startHandler);
        card.removeEventListener('touchstart', startHandler);
        card.removeEventListener('mousemove', moveHandler);
        card.removeEventListener('touchmove', moveHandler);
        card.removeEventListener('mouseup', endHandler);
        card.removeEventListener('touchend', endHandler);
        card.removeEventListener('touchcancel', endHandler);
        card.removeAttribute('data-longpress-active');
    };
}

// Função global para inicializar Long Press em todos os cards atuais e futuros
window.initCardGestures = function () {
    // Aplica em todos os cards já existentes
    document.querySelectorAll('.spotted-card:not([data-longpress-active])').forEach(card => {
        aplicarLongPressAoCard(card);
    });

    // Observa novos cards adicionados via fetch (mutação do DOM)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.matches && node.matches('.spotted-card')) {
                    if (!node.hasAttribute('data-longpress-active')) aplicarLongPressAoCard(node);
                } else if (node.nodeType === 1 && node.querySelectorAll) {
                    node.querySelectorAll('.spotted-card:not([data-longpress-active])').forEach(card => aplicarLongPressAoCard(card));
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
};

// ================================================================
// 3. SWIPE MODE (Física blindada, só ativa quando necessário)
// ================================================================
window.alternarInterfaceSwipe = function (ativar) {
    const body = document.body;
    const container = document.querySelector('.container-feed');
    const filtros = document.querySelector('.filtros-wrapper-retratil');
    const btnFiltros = document.getElementById('btn-abrir-filtros');
    if (!container) return;
    if (ativar) {
        body.classList.add('modo-swipe-ativo');
        container.classList.add('feed-empilhado');
        if (filtros) filtros.style.display = 'flex';
        if (btnFiltros) btnFiltros.style.display = 'inline-flex';
        window.iniciarFisicaSwipe();
        console.log("[SWIPE] Modo ativado");
    } else {
        body.classList.remove('modo-swipe-ativo', 'card-isolado');
        container.classList.remove('feed-empilhado');
        document.querySelectorAll('.focado-swipe').forEach(c => c.classList.remove('focado-swipe'));
        if (filtros) filtros.style.display = '';
        if (btnFiltros) btnFiltros.style.display = '';
        console.log("[SWIPE] Modo desativado");
    }
};

window.iniciarFisicaSwipe = function () {
    const container = document.querySelector('.container-feed');
    if (!container || !container.classList.contains('feed-empilhado')) return;

    let cards = container.querySelectorAll('.spotted-card');
    if (cards.length === 0) return;

    const cardTopo = cards[0];
    if (!cardTopo) return;

    if (cardTopo.getAttribute('data-swipe-pronto') === 'true') {
        console.log("[SWIPE] Card", cardTopo.dataset.id, "já ativo. Abortando.");
        return;
    }

    if (cardTopo._swipeCleanup) {
        cardTopo._swipeCleanup();
        delete cardTopo._swipeCleanup;
    }

    // Reset atômico
    cardTopo.style.setProperty('transition', 'none', 'important');
    cardTopo.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
    cardTopo.style.position = 'absolute';
    cardTopo.style.left = '50%';
    cardTopo.style.top = '45%';
    cardTopo.style.cursor = 'grab';
    cardTopo.style.touchAction = 'none';

    cardTopo.setAttribute('data-swipe-pronto', 'true');

    let isDragging = false;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let rafId = null;
    let longPressTimer = null;
    let isLongPressTriggered = false;

    const easeInOutOpacity = (dist, max = 150) => {
        let t = Math.min(Math.abs(dist) / max, 1);
        return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    };

    const onMove = (e) => {
        if (!isDragging) return;
        if (cardTopo.classList.contains('focado-swipe')) return;

        let clientX = e.touches ? e.touches[0].clientX : e.clientX;
        let clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const dx = clientX - startX;
        const dy = clientY - startY;

        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
            if (longPressTimer) clearTimeout(longPressTimer);
            isLongPressTriggered = false;
        }

        if (Math.abs(dx) < 5 && Math.abs(dy) < 5) return;

        if (e.cancelable) e.preventDefault();
        e.stopPropagation();

        currentX = dx;
        currentY = dy;

        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
            if (!isDragging) return;
            const rotate = currentX / 30;
            cardTopo.style.setProperty('transform',
                `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) rotate(${rotate}deg) scale(1.02)`,
                'important'
            );

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
    };

    const onEnd = (e) => {
        if (isLongPressTriggered) {
            isDragging = false;
            if (rafId) cancelAnimationFrame(rafId);
            if (longPressTimer) clearTimeout(longPressTimer);
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onEnd);
            window.removeEventListener('touchmove', onMove);
            window.removeEventListener('touchend', onEnd);
            cardTopo.style.cursor = 'grab';
            cardTopo.classList.remove('dragging');
            startX = 0; startY = 0;
            currentX = 0; currentY = 0;
            return;
        }

        if (!isDragging) return;
        isDragging = false;
        if (rafId) cancelAnimationFrame(rafId);
        if (longPressTimer) clearTimeout(longPressTimer);

        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onEnd);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);

        cardTopo.style.cursor = 'grab';
        cardTopo.classList.remove('dragging');
        document.querySelectorAll('.feedback-swipe').forEach(el => el.style.opacity = '0');

        const threshold = 120;
        const idPost = cardTopo.dataset.id;
        cardTopo.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.2s';

        if (currentX < -threshold) {
            console.log(`[SWIPE] Card ${idPost} → ESQUERDA (descartar)`);
            animarSaidaEReciclar(cardTopo, -800, currentY);
        } else if (currentX > threshold) {
            console.log(`[SWIPE] Card ${idPost} → DIREITA (descartar)`);
            animarSaidaEReciclar(cardTopo, 800, currentY);
        } else if (currentY < -threshold && Math.abs(currentX) < 80) {
            console.log(`[SWIPE] Card ${idPost} → CIMA (abrir post)`);
            window.location.href = `post.php?id=${idPost}#fofocar`;
        } else {
            cardTopo.style.setProperty('transform', 'translate(-50%, -50%) rotate(0deg) scale(1)', 'important');
            setTimeout(() => {
                if (cardTopo) cardTopo.style.transition = 'none';
            }, 300);
        }

        startX = 0; startY = 0;
        currentX = 0; currentY = 0;
    };

    const onStart = (e) => {
        // Ignora interações com botões de reagir/fofocar
        if (e.target.closest('.btn-reagir') || e.target.closest('.btn-fofocar') ||
            e.target.closest('.reacoes-popup') || e.target.closest('.reacao-wrapper')) {
            return;
        }
        if (e.button !== undefined && e.button !== 0) return;

        // Cancela qualquer timer anterior
        if (longPressTimer) clearTimeout(longPressTimer);
        isLongPressTriggered = false;

        startX = e.touches ? e.touches[0].clientX : e.clientX;
        startY = e.touches ? e.touches[0].clientY : e.clientY;

        const currentTransform = cardTopo.style.transform;
        if (currentTransform !== 'translate(-50%, -50%)' && currentTransform !== '') {
            console.warn("[SWIPE] Reset atômico no card", cardTopo.dataset.id);
            cardTopo.style.transition = 'none';
            cardTopo.style.transform = 'translate(-50%, -50%)';
        }

        longPressTimer = setTimeout(() => {
            if (!isDragging && !isLongPressTriggered) {
                isLongPressTriggered = true;
                exibirMenuAcoes(cardTopo);
                isDragging = false;
                if (rafId) cancelAnimationFrame(rafId);
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onEnd);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('touchend', onEnd);
                cardTopo.style.cursor = 'grab';
                cardTopo.classList.remove('dragging');
            }
        }, 300);

        isDragging = true;
        currentX = 0;
        currentY = 0;

        cardTopo.style.transition = 'none';
        cardTopo.style.cursor = 'grabbing';
        cardTopo.classList.add('dragging');

        window.addEventListener('mousemove', onMove, { passive: false });
        window.addEventListener('mouseup', onEnd);
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onEnd);
    };

    const cleanupListeners = () => {
        cardTopo.removeEventListener('mousedown', onStart);
        cardTopo.removeEventListener('touchstart', onStart);
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onEnd);
        window.removeEventListener('touchmove', onMove);
        window.removeEventListener('touchend', onEnd);
        if (longPressTimer) clearTimeout(longPressTimer);
        cardTopo.removeAttribute('data-swipe-pronto');
        cardTopo.style.cursor = '';
        cardTopo.style.touchAction = '';
    };

    cardTopo.addEventListener('mousedown', onStart);
    cardTopo.addEventListener('touchstart', onStart, { passive: false });

    cardTopo._swipeCleanup = cleanupListeners;

    console.log("[SWIPE] Card", cardTopo.dataset.id, "blindado com swipe e long press.");
};

const animarSaidaEReciclar = (card, x, y) => {
    if (!card) return;
    card.style.setProperty('transition', 'transform 0.3s ease-out, opacity 0.2s', 'important');
    card.style.setProperty('transform', `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${x / 30}deg) scale(0.95)`, 'important');
    card.style.opacity = '0';

    setTimeout(() => {
        if (card && card.parentNode) {
            if (card._swipeCleanup) card._swipeCleanup();
            card.remove();
            console.log("[SWIPE] Card removido. Chamando abastecerPilhaFenda.");
            if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda();
        }
    }, 300);
};

// ================================================================
// 4. CONFIGURAÇÃO DE POSTS (expansão de texto)
// ================================================================
window.configurarPosts = function () {
    const posts = document.querySelectorAll('.post-content');
    posts.forEach(post => {
        const precisaExpandir = post.scrollHeight > post.offsetHeight + 10;
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

// ================================================================
// 5. INICIALIZAÇÃO GERAL (áudio, dropdowns, alertas, etc.)
// ================================================================
const destravarAudio = () => {
    window.audioLiberado = true;
    console.log("[AUDIO] Áudio autorizado pelo usuário.");
    document.removeEventListener('click', destravarAudio);
    document.removeEventListener('touchstart', destravarAudio);
};
document.addEventListener('click', destravarAudio);
document.addEventListener('touchstart', destravarAudio);

document.addEventListener("DOMContentLoaded", function () {
    if (typeof atualizarInterfaceBolhas === 'function') atualizarInterfaceBolhas();

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

    let somAmbiente = localStorage.getItem('fenda_tipo_som') || 'off';
    let temaNotif = localStorage.getItem('fenda_tema_notif') || 'padrao';

    window.atualizarInterfaceAudio = function () {
        document.querySelectorAll('[id^="btn-som-"]').forEach(btn => btn.classList.remove('active'));
        const btnM = document.getElementById('btn-som-' + somAmbiente);
        if (btnM) btnM.classList.add('active');
        document.querySelectorAll('[id^="btn-notif-"]').forEach(btn => btn.classList.remove('active'));
        const btnN = document.getElementById('btn-notif-' + temaNotif);
        if (btnN) btnN.classList.add('active');
    };
    atualizarInterfaceAudio();

    window.mudarSomAmbiente = function (tipo) {
        somAmbiente = tipo;
        localStorage.setItem('fenda_tipo_som', tipo);
        let audio = document.getElementById('som-oceano');
        if (audio) {
            if (tipo === 'off') { audio.pause(); }
            else {
                audio.src = (tipo === 'chuva') ? 'sons/chuva.mp3' : 'sons/oceano.mp3';
                audio.volume = 0.04;
                audio.play().catch(() => {});
            }
        }
        atualizarInterfaceAudio();
    };

    window.mudarTemaNotif = function (tema) {
        temaNotif = tema;
        localStorage.setItem('fenda_tema_notif', tema);
        atualizarInterfaceAudio();
    };

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

    setTimeout(() => {
        window.atualizarContadorAlertas();
        setInterval(window.atualizarContadorAlertas, 8000);
    }, 1000);

    setTimeout(() => {
        if (typeof configurarPosts === 'function') configurarPosts();
    }, 500);
});

function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';
    let tempoExibicao = 5000;
    if (temaSalvo !== 'off') {
        let configuracaoSons = {
            'padrao': { arquivo: 'padrao.mp3', volume: 1.1 },
            'resident': { arquivo: 'resident.mp3', volume: 0.6 },
            'cs': { arquivo: 'cs.mp3', volume: 0.7 },
            'starwars': { arquivo: 'imperial-march.mp3', volume: 0.3 },
            'mario': { arquivo: 'mario-bros-1up.mp3', volume: 0.7 },
            'pokemon': { arquivo: 'pokemon_levelup.mp3', volume: 0.8 },
            'digimon': { arquivo: 'brave-heart_digimon.mp3', volume: 0.2 },
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
                        if (bip.duration > 3) {
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
    const modalSair = document.getElementById('modal-sair-fenda');
    if (event.target === modalSair) window.fecharModalSair();
    const clicouNoMenu = event.target.closest('.dropdown');
    const clicouNaReacao = event.target.closest('.reacao-wrapper') || event.target.closest('.btn-reagir');
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

// ================================================================
// 6. LOAD FINAL (Swipe + Infinito + Long Press global)
// ================================================================
window.addEventListener('load', () => {
    if (localStorage.getItem('fenda_hacker') === 'active') {
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

    // Inicializa Long Press para TODOS os cards (grid e futuros)
    window.initCardGestures();

    if (document.body.classList.contains('modo-swipe-ativo')) {
        console.log("[SWIPE] Inicialização no load. Garantindo limpeza e ativação.");
        if (window._fendaMove) window.removeEventListener('mousemove', window._fendaMove);
        if (window._fendaUp) window.removeEventListener('mouseup', window._fendaUp);
        window._fendaMove = null;
        window._fendaUp = null;

        const cardInicial = document.querySelector('.feed-empilhado .spotted-card:first-child');
        if (cardInicial) {
            if (cardInicial._fendaStart) {
                cardInicial.removeEventListener('mousedown', cardInicial._fendaStart);
                cardInicial.removeEventListener('touchstart', cardInicial._fendaStart);
            }
            cardInicial.removeAttribute('data-swipe-pronto');
        }

        if (typeof window.alternarInterfaceSwipe === 'function') window.alternarInterfaceSwipe(true);
    }

    // ============================================================
    // ABASTECIMENTO DE PILHA (infinito com lock)
    // ============================================================
    let offsetSwipe = 10;
    let carregandoSwipe = false;
    let fetchAgendado = null;

    window.abastecerPilhaFenda = function () {
        if (carregandoSwipe) {
            if (!fetchAgendado) {
                fetchAgendado = setTimeout(() => {
                    fetchAgendado = null;
                    window.abastecerPilhaFenda();
                }, 300);
            }
            console.log("[FETCH] Já carregando. Tentativa agendada.");
            return;
        }

        const container = document.querySelector('.container-feed');
        if (!container) return;

        const cardsRestantes = container.querySelectorAll('.spotted-card').length;
        if (cardsRestantes < 4) {
            carregandoSwipe = true;
            console.log("[FETCH] Estoque baixo (", cardsRestantes, "cards). Buscando novos...");

            const urlParams = new URLSearchParams(window.location.search);
            const categoria = urlParams.get('categoria') || '';

            fetch(`motor-feed.php?offset=${offsetSwipe}&categoria=${categoria}&tipo=geral`)
                .then(res => res.text())
                .then(html => {
                    if (html.trim() !== "FIM_DADOS") {
                        container.insertAdjacentHTML('beforeend', html);
                        offsetSwipe += 10;

                        setTimeout(() => {
                            const containerCheck = document.querySelector('.container-feed');
                            if (containerCheck) containerCheck.offsetHeight;
                            // Aplica Long Press nos novos cards
                            window.initCardGestures();
                            window.iniciarFisicaSwipe();
                            console.log("[FETCH] Novos cards inseridos, gestos e swipe reiniciados.");
                        }, 50);
                    } else {
                        console.log("[FETCH] Não há mais dados.");
                    }
                    carregandoSwipe = false;
                    if (fetchAgendado) {
                        clearTimeout(fetchAgendado);
                        fetchAgendado = null;
                    }
                })
                .catch(err => {
                    console.error("[FETCH] Erro ao buscar mais cards:", err);
                    carregandoSwipe = false;
                    if (fetchAgendado) {
                        clearTimeout(fetchAgendado);
                        fetchAgendado = null;
                    }
                });
        }
    };
});