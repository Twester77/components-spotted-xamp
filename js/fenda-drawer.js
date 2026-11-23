/* ============================================================
   FENDA DRAWER COMENTÁRIOS – BRASA (FASE 5 - GPU TRANSLATE)
   ============================================================ */

let _drawerTecladoHandler = null;

window.abrirDrawerComentarios = function(postId) {
    if (!postId) return;

    if (typeof window.HeaderManager?.limpar === 'function') {
        window.HeaderManager.limpar();
    }
    
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-aberto');

    const backdropAntigo = document.getElementById('drawer-cmt-backdrop');
    const panelAntigo = document.getElementById('drawer-cmt-panel');
    if (backdropAntigo) backdropAntigo.remove();
    if (panelAntigo) panelAntigo.remove();

    if (_drawerTecladoHandler && window.visualViewport) {
        window.visualViewport.removeEventListener('resize', _drawerTecladoHandler);
        window.visualViewport.removeEventListener('scroll', _drawerTecladoHandler);
        _drawerTecladoHandler = null;
    }

    const backdrop = document.createElement('div');
    backdrop.id = 'drawer-cmt-backdrop';
    backdrop.className = 'drawer-cmt-backdrop';
    backdrop.onclick = window.fecharDrawerComentarios;
    document.body.appendChild(backdrop);

    const panel = document.createElement('div');
    panel.id = 'drawer-cmt-panel';
    panel.className = 'drawer-cmt-panel';
    panel.innerHTML = `
        <div class="drawer-cmt-handle-area"><div class="drawer-cmt-handle"></div></div>
        <button class="drawer-cmt-close" onclick="window.fecharDrawerComentarios()"><i class="fas fa-times"></i></button>
        <div class="drawer-cmt-content" id="drawer-cmt-content">
            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#888; flex-direction:column; gap:10px;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; font-size: clamp(2rem, 2.5vw, 5rem); color:#ffbc00;"></i>
                <span>Abrindo fofocas...</span>
            </div>
        </div>
    `;
    document.body.appendChild(panel);

    const state = {
        offsetY: 0,
        parkedRatio: 0,
        keyboardBottomOffset: 0,
        vvOffsetTop: 0,
        aplicarPosicao(comTransicao, atualizarScrollPadding = false) {
            const footer = panel.querySelector('.fixed-input');
            const transicaoCSS = comTransicao ? '' : 'none';
            panel.style.transition = transicaoCSS;
            if (footer) footer.style.transition = transicaoCSS;

            const panelY = Math.max(0, state.offsetY + state.vvOffsetTop);
            // Compensa tanto o offset do drawer quanto o recuo total do teclado + scroll do visualViewport
            const footerY = -(state.offsetY + state.keyboardBottomOffset);

            panel.style.setProperty('--drawer-panel-y', `${panelY}px`);
            panel.style.setProperty('--drawer-footer-y', `${footerY}px`);

            if (atualizarScrollPadding) {
                const lista = panel.querySelector('.lista-scrollavel');
                if (lista) {
                    const extraBottom = Math.max(0, state.offsetY + state.keyboardBottomOffset);
                    lista.style.paddingBottom = extraBottom > 0
                        ? `calc(20px + ${extraBottom}px + env(safe-area-inset-bottom, 0px))`
                        : '';
                }
            }
        }
    };
    panel._drawerState = state;

    const sincronizarViewport = (comTransicao = true) => {
        if (!document.body.contains(panel) || !window.visualViewport) return;

        const layoutHeight = window.innerHeight;
        const vvHeight = window.visualViewport.height;
        const vvOffsetTop = window.visualViewport.offsetTop;
        const keyboardBottomOffset = Math.max(0, layoutHeight - vvHeight);
        const keyboardInset = Math.max(0, keyboardBottomOffset - vvOffsetTop);
        const isLandscape = window.innerWidth > window.innerHeight;
        const limiarTeclado = isLandscape ? 40 : 60;
        const tecladoAberto = keyboardInset > limiarTeclado || vvHeight < layoutHeight * 0.85;

        state.keyboardBottomOffset = tecladoAberto ? keyboardBottomOffset : 0;
        state.vvOffsetTop = tecladoAberto ? vvOffsetTop : 0;

        if (tecladoAberto) {
            state.offsetY = 0;
            state.parkedRatio = 0;
        } else {
            const panelHeight = panel.getBoundingClientRect().height || (layoutHeight * 0.95);
            const maxOffset = Math.max(0, panelHeight - 160);
            state.offsetY = Math.min(maxOffset, state.parkedRatio * layoutHeight);
        }

        state.aplicarPosicao(comTransicao, true);
    };

    if (window.visualViewport) {
        let rafViewport = null;
        let timerSettle = null;
        _drawerTecladoHandler = () => {
            if (!document.body.contains(panel)) return;
            if (rafViewport) cancelAnimationFrame(rafViewport);
            rafViewport = requestAnimationFrame(() => sincronizarViewport(false));

            clearTimeout(timerSettle);
            timerSettle = setTimeout(() => sincronizarViewport(true), 120);
        };
        window.visualViewport.addEventListener('resize', _drawerTecladoHandler);
        window.visualViewport.addEventListener('scroll', _drawerTecladoHandler);
    }

    requestAnimationFrame(() => {
        backdrop.classList.add('ativo');
        panel.classList.add('ativo');
    });

    fetch(`comentarios-drawer.php?id=${postId}&_=${Date.now()}`)
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => { throw new Error(text || 'Erro ao carregar.'); });
            }
            return res.text();
        })
        .then(html => {
            const contentArea = document.getElementById('drawer-cmt-content');
            if (!contentArea) return;
            
            contentArea.innerHTML = html;
            
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            configurarGestoFechar(panel);
            sincronizarViewport(false);

            const textarea = contentArea.querySelector('.textarea-chat');
            if (textarea) {
                textarea.addEventListener('focus', () => {
                    if (state.parkedRatio > 0.35) {
                        state.offsetY = 0;
                        state.parkedRatio = 0;
                        state.aplicarPosicao(true, true);
                    }
                    // Recheca após a animação da barra de sugestões do teclado Android (Gboard/Samsung)
                    setTimeout(() => sincronizarViewport(true), 90);
                    setTimeout(() => sincronizarViewport(true), 260);
                });
            }
        })
        .catch(err => {
            console.error('[DRAWER] Erro no fetch:', err);
            const contentArea = document.getElementById('drawer-cmt-content');
            if (contentArea) {
                contentArea.innerHTML = `
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#ff6b6b; padding: 20px; text-align: center;">
                        <i class="fas fa-exclamation-triangle" style="font-size:2rem; margin-bottom:10px;"></i>
                        <p>${err.message}</p>
                        <button onclick="window.fecharDrawerComentarios()" style="margin-top: 15px; padding: 8px 16px; border-radius: 8px; background: #ff6b6b; border: none; color: white; font-weight: bold; cursor: pointer;">Fechar</button>
                    </div>
                `;
            }
        });
};

window.fecharDrawerComentarios = function() {
    const backdrop = document.getElementById('drawer-cmt-backdrop');
    const panel = document.getElementById('drawer-cmt-panel');
    
    if (_drawerTecladoHandler && window.visualViewport) {
        window.visualViewport.removeEventListener('resize', _drawerTecladoHandler);
        window.visualViewport.removeEventListener('scroll', _drawerTecladoHandler);
        _drawerTecladoHandler = null;
    }

    if (backdrop && panel) {
        const footer = panel.querySelector('.fixed-input');
        panel.style.transition = '';
        if (footer) footer.style.transition = '';
        panel.style.removeProperty('--drawer-panel-y');
        panel.style.removeProperty('--drawer-footer-y');
        panel.classList.remove('ativo');
        backdrop.classList.remove('ativo');
        
        setTimeout(() => {
            backdrop.remove();
            panel.remove();
            document.body.style.overflow = '';
            document.body.classList.remove('modal-aberto');
        }, 350);
    }
};

function configurarGestoFechar(panel) {
    const handleArea = panel.querySelector('.drawer-cmt-handle-area');
    const state = panel._drawerState;
    if (!handleArea || !state) return;
    
    let startY = 0;
    let startOffsetY = 0;
    let isDragging = false;
    let rafId = null;
    let panelHeight = window.innerHeight * 0.95;

    handleArea.addEventListener('pointerdown', (e) => {
        if (!e.isPrimary) return;
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        
        startY = e.clientY;
        startOffsetY = state.offsetY;
        isDragging = true;
        panelHeight = panel.getBoundingClientRect().height || (window.innerHeight * 0.95);
        
        state.aplicarPosicao(false, false);
        handleArea.setPointerCapture(e.pointerId);
        e.preventDefault(); 
    });

    handleArea.addEventListener('pointermove', (e) => {
        if (!isDragging) return;
        
        const multiplicador = e.pointerType === 'mouse' ? 1.3 : 1; 
        const deltaY = (e.clientY - startY) * multiplicador;
        const maxDrag = Math.max(0, panelHeight - 130);
        const nextOffsetY = Math.min(maxDrag, Math.max(0, startOffsetY + deltaY));
        
        if (rafId) cancelAnimationFrame(rafId);
        
        rafId = requestAnimationFrame(() => {
            if (!isDragging) return;
            state.offsetY = nextOffsetY;
            state.aplicarPosicao(false, false);
        });
    });

    handleArea.addEventListener('pointerup', () => {
        if (!isDragging) return;
        isDragging = false;
        if (rafId) cancelAnimationFrame(rafId);

        const visibleHeight = panelHeight - state.offsetY;
        const visibleDvh = (visibleHeight / window.innerHeight) * 100;

        // Se deixou menos de 30dvh visível, fecha; senão, estaciona com o textbox livre
        if (visibleDvh < 30) {
            window.fecharDrawerComentarios();
        } else {
            const maxParkedOffset = Math.max(0, panelHeight - 165);
            state.offsetY = Math.min(maxParkedOffset, Math.max(0, state.offsetY));
            state.parkedRatio = state.offsetY / window.innerHeight;
            state.aplicarPosicao(true, true);
        }
    });

    handleArea.addEventListener('pointercancel', () => {
        if (!isDragging) return;
        isDragging = false;
        if (rafId) cancelAnimationFrame(rafId);
        state.offsetY = startOffsetY;
        state.aplicarPosicao(true, false);
    });
}

// Intercepta o clique no botão "Fofocar" e no "Expandir"
document.addEventListener('click', (e) => {
    const btnFofocar = e.target instanceof Element ? e.target.closest(
        '.btn-fofocar, #lightbox-body a[href*="comentarios-post.php"]'
    ) : null;
    if (btnFofocar) {
        if (document.body.classList.contains('modo-swipe-ativo')) return;
        if (!document.body.classList.contains('estilo-cascata')) return;

        e.preventDefault();
        e.stopPropagation();
        const href = btnFofocar.getAttribute('href');
        if (href) {
            const id = new URL(href, window.location.href).searchParams.get('id');
            if (id) {
                if (btnFofocar.closest('#lightbox-body')) {
                    if (typeof window.fecharLightbox === 'function') {
                        window.fecharLightbox();
                    }
                }
                window.abrirDrawerComentarios(id);
            }
        }
        return;
    }
});

// Esconde o botão Expandir se estiver no modo cascata (Fase 5)
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('estilo-cascata')) {
        document.querySelectorAll('.btn-ver-mais').forEach(btn => {
            btn.style.display = 'none';
        });
    }
});