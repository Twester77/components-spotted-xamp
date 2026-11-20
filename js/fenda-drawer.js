/* ============================================================
   FENDA DRAWER COMENTÁRIOS – BRASA (FASE 5 - FINAL)
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
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#ffbc00;"></i>
                <span>Abrindo fofocas...</span>
            </div>
        </div>
    `;
    document.body.appendChild(panel);

        if (window.visualViewport) {
        let timerTeclado = null;
        _drawerTecladoHandler = () => {
            if (!document.body.contains(panel)) return;
            clearTimeout(timerTeclado);
            timerTeclado = setTimeout(() => {
                const alturaVisivel = window.visualViewport.height;
                const offsetTop = window.visualViewport.offsetTop;
                const tecladoAberto = alturaVisivel < window.innerHeight * 0.85;

                panel.style.transition = 'height 0.2s ease, transform 0.2s ease';

                if (tecladoAberto) {
                    const alturaAtual = panel.getBoundingClientRect().height;
                    if (alturaAtual > alturaVisivel * 0.95) {
                        panel.style.height = `${alturaVisivel * 0.95}px`;
                    }
                    panel.style.transform = `translate3d(0, ${offsetTop}px, 0)`;
                } else {
                    panel.style.transform = 'translate3d(0, 0, 0)';
                }
            }, 100);
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
                // Se o PHP retornar 403 (não logado) ou 500 (erro interno), pegamos o texto de erro
                return res.text().then(text => { throw new Error(text || 'Erro ao carregar.'); });
            }
            return res.text();
        })
        .then(html => {
            const contentArea = document.getElementById('drawer-cmt-content');
            if (!contentArea) return; // Usuário fechou antes do fetch terminar
            
            contentArea.innerHTML = html;
            
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            configurarGestoFechar(panel);

            const textarea = contentArea.querySelector('.textarea-chat');
            if (textarea) {
                textarea.addEventListener('focus', () => {
                    const currentHeight = parseFloat(panel.style.height) || 95;
                    if (currentHeight < 60) {
                        panel.style.transition = 'height 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)';
                        panel.style.height = '60dvh';
                    }
                });
            }
        })
        .catch(err => {
            console.error('[DRAWER] Erro no fetch:', err);
            const contentArea = document.getElementById('drawer-cmt-content');
            
            // 🔥 SOLUÇÃO SEGURA: Mostra o erro dentro do painel, sem deletar o backdrop.
            // O usuário pode ler a mensagem e clicar no X ou no backdrop para fechar.
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
        panel.style.transform = 'translateY(100%)';
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
    if (!handleArea) return;
    
    let startY = 0;
    let currentY = 0;
    let isDragging = false;
    let rafId = null;
    let alturaPainel = window.innerHeight * 0.95;

    handleArea.addEventListener('pointerdown', (e) => {
        if (!e.isPrimary) return;
        if (e.button !== 0 && e.pointerType === 'mouse') return;
        
        startY = e.clientY;
        isDragging = true;
        
        // Pega a altura atual exata (sem pulo ao retomar o arraste)
        alturaPainel = panel.getBoundingClientRect().height;
        
        panel.style.transition = 'none'; 
        handleArea.setPointerCapture(e.pointerId);
        e.preventDefault(); 
    });

    handleArea.addEventListener('pointermove', (e) => {
        if (!isDragging) return;
        
        const multiplicador = e.pointerType === 'mouse' ? 1.3 : 1; 
        currentY = (e.clientY - startY) * multiplicador;
        
        if (rafId) cancelAnimationFrame(rafId);
        
        rafId = requestAnimationFrame(() => {
            if (!isDragging) return;
            // Limita entre 10px (mínimo) e 95% da tela (máximo)
            const novaAlturaPx = Math.min(window.innerHeight * 0.95, Math.max(10, alturaPainel - currentY));
            const novaPct = (novaAlturaPx / window.innerHeight) * 100;
            panel.style.height = `${novaPct}dvh`;
        });
    });

    handleArea.addEventListener('pointerup', () => {
        if (!isDragging) return;
        isDragging = false;
        
        if (rafId) cancelAnimationFrame(rafId);
        
        const alturaFinalPx = Math.min(window.innerHeight * 0.95, Math.max(10, alturaPainel - currentY));
        const alturaFinalDvh = (alturaFinalPx / window.innerHeight) * 100;

        panel.style.transition = 'height 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1)';

        // Se soltou abaixo de 30dvh, fecha totalmente; senão, fica na posição livre escolhida
        if (alturaFinalDvh < 30) {
            window.fecharDrawerComentarios();
            setTimeout(() => { panel.style.height = ''; }, 300); 
        } else {
            panel.style.height = `${alturaFinalDvh}dvh`;
        }

        currentY = 0; 
    });

    handleArea.addEventListener('pointercancel', () => {
        if (!isDragging) return;
        isDragging = false;
        if (rafId) cancelAnimationFrame(rafId);
        panel.style.transition = 'height 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)';
        panel.style.height = '95dvh';
        currentY = 0;
    });
}


// Intercepta o clique no botão "Fofocar" e no "Expandir"
document.addEventListener('click', (e) => {
    const btnFofocar = e.target instanceof Element ? e.target.closest(
        '.btn-fofocar, #lightbox-body a[href*="comentarios-post.php"]'
    ) : null;
    if (btnFofocar) {
        // Se for swipe ou whatsapp, deixa o link normal agir.
        if (document.body.classList.contains('modo-swipe-ativo')) return;
        if (!document.body.classList.contains('estilo-cascata')) return;

        // No Cascata: intercepta e abre o Drawer
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