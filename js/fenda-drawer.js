/* ============================================================
   FENDA DRAWER COMENTÁRIOS – BRASA (FASE 5 - FINAL)
   ============================================================ */

window.abrirDrawerComentarios = function(postId) {
    if (!postId) return;
    
    // Trava o scroll do body
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-aberto');

    // Remove o drawer antigo se existir
    const backdropAntigo = document.getElementById('drawer-cmt-backdrop');
    const panelAntigo = document.getElementById('drawer-cmt-panel');
    if (backdropAntigo) backdropAntigo.remove();
    if (panelAntigo) panelAntigo.remove();

    // Cria o Backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'drawer-cmt-backdrop';
    backdrop.className = 'drawer-cmt-backdrop';
    backdrop.onclick = window.fecharDrawerComentarios;
    document.body.appendChild(backdrop);

    // Cria o Painel
    const panel = document.createElement('div');
    panel.id = 'drawer-cmt-panel';
    panel.className = 'drawer-cmt-panel';
    panel.innerHTML = `
        <div class="drawer-cmt-handle-area">
            <div class="drawer-cmt-handle"></div>
        </div>
        <button class="drawer-cmt-close" onclick="window.fecharDrawerComentarios()">
            <i class="fas fa-times"></i>
        </button>
        <div class="drawer-cmt-content" id="drawer-cmt-content">
            <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#888; flex-direction:column; gap:10px;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; font-size:clamp(2rem, 1.5vw, 4.5rem); color:#ffbc00;"></i>
                <span>Abrindo fofocas...</span>
            </div>
        </div>
    `;
    document.body.appendChild(panel);

    // Ativa animação
    requestAnimationFrame(() => {
        backdrop.classList.add('ativo');
        panel.classList.add('ativo');
    });

    // Fetch
    fetch(`comentarios-drawer.php?id=${postId}&_=${Date.now()}`)
        .then(res => {
            if (!res.ok) throw new Error('Erro ao carregar.');
            return res.text();
        })
        .then(html => {
            const contentArea = document.getElementById('drawer-cmt-content');
            contentArea.innerHTML = html;
            
            // Re-injeta os scripts que vieram no HTML via fetch
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Configura o gesto de fechar (deslizar a alça pra baixo)
            configurarGestoFechar(panel);
        })
        .catch(err => {
            const contentArea = document.getElementById('drawer-cmt-content');
            contentArea.innerHTML = `
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#ff6b6b;">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p>${err.message}</p>
                </div>
            `;
        });
};

window.fecharDrawerComentarios = function() {
    const backdrop = document.getElementById('drawer-cmt-backdrop');
    const panel = document.getElementById('drawer-cmt-panel');
    
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

    handleArea.addEventListener('touchstart', (e) => {
        startY = e.touches[0].clientY;
        isDragging = true;
        panel.style.transition = 'none';
    }, { passive: true });

    handleArea.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentY = e.touches[0].clientY - startY;
        if (currentY > 0) {
            panel.style.transform = `translateY(${currentY}px)`;
        }
    }, { passive: true });

    handleArea.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;
        panel.style.transition = 'transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)';
        
        if (currentY > 120) {
            window.fecharDrawerComentarios();
        } else {
            panel.style.transform = 'translateY(0)';
        }
        currentY = 0;
    });
}

// Intercepta o clique no botão "Fofocar" e no "Expandir"
document.addEventListener('click', (e) => {
    const btnFofocar = e.target.closest('.btn-fofocar');
    if (btnFofocar) {
        // Se for swipe ou whatsapp, deixa o link normal agir.
        if (document.body.classList.contains('modo-swipe-ativo')) return;
        if (!document.body.classList.contains('estilo-cascata')) return;

        // No Cascata: intercepta e abre o Drawer
        e.preventDefault();
        e.stopPropagation();
        const href = btnFofocar.getAttribute('href');
        if (href) {
            const urlParams = new URLSearchParams(href.split('?')[1]);
            const id = urlParams.get('id');
            if (id) window.abrirDrawerComentarios(id);
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