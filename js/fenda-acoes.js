// ============================================================
// fenda-acoes.js – Menu de Ações (Ellipsis) para Comunidades
// 🐚 BRISA – 2026-09-16 (v1.1)
//    - Adaptado para o novo endpoint excluir-post.php (raiz).
//    - Não envia mais comunidade_id (backend descobre pelo post_id).
// ============================================================

console.log('[ACOES] Script fenda-acoes.js carregado.');

// ============================================================
// ESTADO GLOBAL
// ============================================================
let ellipsisMenuAberto = null; // Armazena referência do menu ativo

// ============================================================
// FUNÇÃO PARA ABRIR O MENU ELLIPSIS
// ============================================================
function abrirMenuEllipsis(postId, btnElement) {
    console.log('[ACOES] abrirMenuEllipsis chamado para postId:', postId);

    // Se já houver um menu aberto, fecha antes de abrir outro
    if (ellipsisMenuAberto) {
        fecharMenuEllipsis();
    }

    // ============================================================
    // 1. CRIA O OVERLAY (fundo semi-transparente)
    // ============================================================
    const overlay = document.createElement('div');
    overlay.className = 'ellipsis-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 99999;
        background: rgba(0,0,0,0.25);
        cursor: default;
    `;

    // ============================================================
    // 2. CRIA O MENU POPUP
    // ============================================================
    const menu = document.createElement('div');
    menu.className = 'ellipsis-menu';
    menu.style.cssText = `
        position: fixed;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
        border: 1px solid rgba(255, 188, 0, 0.2);
        border-radius: 12px;
        padding: 6px 0;
        min-width: 150px;
        max-width: 240px;
        height:auto;
        max-height:fit-content;
        -webkit-user-select:none;
        user-select:none;
        box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        z-index: 100000;
        animation: ellipsisFadeIn 0.15s ease-out;
        font-family: 'Inter', system-ui, sans-serif;
    `;

    // ============================================================
    // 3. POSICIONAMENTO ADAPTATIVO (mobile vs desktop)
    // ============================================================
    const isMobile = window.innerWidth < 600;
    const rect = btnElement.getBoundingClientRect();

    if (isMobile) {
        // Mobile: centraliza e abre como bottom sheet
        menu.style.bottom = '30px';
        menu.style.left = '50%';
        menu.style.transform = 'translateX(-50%)';
        menu.style.width = 'calc(100% - 40px)';
        menu.style.maxWidth = '360px';
        menu.style.borderRadius = '16px 16px 16px 16px';
        menu.style.animation = 'ellipsisSlideUp 0.25s ease-out';
    } else {
        // Desktop: posiciona próximo ao botão
        const top = rect.bottom + 6;
        const left = Math.min(rect.left, window.innerWidth - 220 - 10);
        menu.style.top = Math.max(10, top) + 'px';
        menu.style.left = Math.max(10, left) + 'px';
        menu.style.animation = 'ellipsisFadeIn 0.15s ease-out';
    }

    // ============================================================
    // 4. ITENS DO MENU
    // ============================================================
    menu.innerHTML = `
        <button class="ellipsis-item" data-acao="excluir" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 16px; border:none; cursor:pointer; font-family:inherit; border-radius:0;">
            <i class="fas fa-trash-alt" style="width:20px; text-align:center;"></i> Excluir post
        </button>
        <button class="ellipsis-item" data-acao="cancelar" style="display:flex; align-items:center; gap:10px; width:100%; padding:10px 16px; border:none; cursor:pointer; font-family:inherit; border-radius:0;">
            <i class="fas fa-times" style="width:20px; text-align:center;"></i> Cancelar
        </button>
    `;

    // ============================================================
    // 5. EVENTOS DOS BOTÕES
    // ============================================================

    // Botão "Excluir"
    menu.querySelector('[data-acao="excluir"]').addEventListener('click', function (e) {
        e.stopPropagation();
        console.log('[ACOES] Ação "Excluir" para postId:', postId);
        fecharMenuEllipsis();

        // Usa o modal de confirmação global (se existir) ou fallback para confirm()
        if (typeof window.exibirConfirmacao === 'function') {
            window.exibirConfirmacao('Tem certeza que deseja excluir este post?', '⚠️ Excluir Post').then(confirmado => {
                if (confirmado) {
                    executarExclusao(postId);
                }
            });
        } else {
            if (confirm('⚠️ Tem certeza que deseja excluir este post? Esta ação é irreversível.')) {
                executarExclusao(postId);
            }
        }
    });

    // Botão "Cancelar"
    menu.querySelector('[data-acao="cancelar"]').addEventListener('click', function (e) {
        e.stopPropagation();
        console.log('[ACOES] Ação "Cancelar"');
        fecharMenuEllipsis();
    });

    // ============================================================
    // 6. FECHAR AO CLICAR FORA (overlay)
    // ============================================================
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            console.log('[ACOES] Fechando menu por clique fora.');
            fecharMenuEllipsis();
        }
    });

    // ============================================================
    // 7. FECHAR COM TECLA ESC
    // ============================================================
    const escHandler = function (e) {
        if (e.key === 'Escape') {
            console.log('[ACOES] Fechando menu por tecla ESC.');
            fecharMenuEllipsis();
        }
    };
    document.addEventListener('keydown', escHandler);

    // ============================================================
    // 8. FECHAR AO ROLAR A PÁGINA
    // ============================================================
    const scrollHandler = function () {
        if (ellipsisMenuAberto) {
            console.log('[ACOES] Fechando menu por scroll.');
            fecharMenuEllipsis();
        }
    };
    window.addEventListener('scroll', scrollHandler, { passive: true });

    // ============================================================
    // 9. ARMAZENA REFERÊNCIAS PARA LIMPEZA
    // ============================================================
    ellipsisMenuAberto = {
        overlay: overlay,
        menu: menu,
        escHandler: escHandler,
        scrollHandler: scrollHandler
    };

    // ============================================================
    // 10. MONTA O DOM
    // ============================================================
    overlay.appendChild(menu);
    document.body.appendChild(overlay);

    // Pequeno ajuste para garantir que o menu fique visível mesmo se a página tiver scroll
    if (!isMobile) {
        const menuRect = menu.getBoundingClientRect();
        if (menuRect.bottom > window.innerHeight) {
            menu.style.top = (rect.top - menuRect.height - 6) + 'px';
        }
        if (menuRect.left < 0) {
            menu.style.left = '10px';
        }
        if (menuRect.right > window.innerWidth) {
            menu.style.right = '10px';
            menu.style.left = 'auto';
        }
    }

    console.log('[ACOES] Menu ellipsis aberto com sucesso.');
}

// ============================================================
// FUNÇÃO PARA FECHAR O MENU
// ============================================================
function fecharMenuEllipsis() {
    if (ellipsisMenuAberto) {
        console.log('[ACOES] Fechando menu ellipsis...');

        // Remove overlay (e consequentemente o menu)
        if (ellipsisMenuAberto.overlay && ellipsisMenuAberto.overlay.parentNode) {
            ellipsisMenuAberto.overlay.remove();
        }

        // Remove listeners
        if (ellipsisMenuAberto.escHandler) {
            document.removeEventListener('keydown', ellipsisMenuAberto.escHandler);
        }
        if (ellipsisMenuAberto.scrollHandler) {
            window.removeEventListener('scroll', ellipsisMenuAberto.scrollHandler);
        }

        ellipsisMenuAberto = null;
        console.log('[ACOES] Menu ellipsis fechado.');
    }
}

// ============================================================
// FUNÇÃO PARA EXECUTAR A EXCLUSÃO (via excluir-post.php)
// ============================================================
function executarExclusao(postId) {
    console.log('[ACOES] executarExclusao para postId:', postId);

    // Obtém o token CSRF
    const csrfToken = document.getElementById('csrf_token')?.value || '';
    if (!csrfToken) {
        if (typeof exibirToast === 'function') {
            exibirToast('❌ Erro de segurança. Recarregue a página.', 'erro');
        } else {
            alert('Erro de segurança. Recarregue a página.');
        }
        return;
    }

    // Encontra o card para feedback visual
    const card = document.querySelector(`.spotted-card[data-id="${postId}"]`);
    if (card) {
        card.classList.add('is-loading');
        // Cria um spinner temporário (ou usa o existente)
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.style.position = 'absolute';
        spinner.style.top = '50%';
        spinner.style.left = '50%';
        spinner.style.transform = 'translate(-50%, -50%)';
        card.appendChild(spinner);
    }

    const formData = new FormData();
    formData.append('id', postId);
    formData.append('csrf_token', csrfToken);

    // 🔥 Novo endpoint unificado (raiz do projeto)
    fetch('excluir-post.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => response.json())
        .then(data => {
            console.log('[ACOES] Resposta da exclusão:', data);
            if (data.status === 'success') {
                if (typeof exibirToast === 'function') {
                    exibirToast('✅ Post excluído com sucesso!', 'sucesso');
                } else {
                    alert('Post excluído!');
                }
                // Remove o card com animação
                if (card) {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        if (card.parentNode) card.remove();
                    }, 350);
                }
            } else {
                const msg = data.message || 'Erro ao excluir.';
                if (typeof exibirToast === 'function') {
                    exibirToast('❌ ' + msg, 'erro');
                } else {
                    alert(msg);
                }
                if (card) {
                    card.classList.remove('is-loading');
                    const spinner = card.querySelector('.loading-spinner');
                    if (spinner) spinner.remove();
                }
            }
        })
        .catch(err => {
            console.error('[ACOES] Erro na exclusão:', err);
            if (typeof exibirToast === 'function') {
                exibirToast('❌ Erro de conexão. Tente novamente.', 'erro');
            } else {
                alert('Erro de conexão.');
            }
            if (card) {
                card.classList.remove('is-loading');
                const spinner = card.querySelector('.loading-spinner');
                if (spinner) spinner.remove();
            }
        });
}

// ============================================================
// INJEÇÃO DE ESTILOS (fallback caso o CSS não seja carregado)
// ============================================================
(function injectEllipsisStyles() {
    if (document.getElementById('ellipsis-styles')) return;
    const style = document.createElement('style');
    style.id = 'ellipsis-styles';
    style.textContent = `
        @keyframes ellipsisFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(-6px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes ellipsisSlideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        .ellipsis-item:hover {
            background: rgba(255,255,255,0.04);
        }
        .ellipsis-item[data-acao="excluir"]:hover {
            background: rgba(255,50,50,0.15);
        }
        .ellipsis-item[data-acao="cancelar"]:hover {
            background: rgba(0, 0, 0, 0.06);
        }
        .ellipsis-item:active {
            transform: scale(0.96);
        }
        /* Para mobile, ajusta o tamanho dos botões */
        @media (max-width: 480px) {
            .ellipsis-item {
                font-size: 1rem;
                padding: 14px 16px;
            }
        }
    `;
    document.head.appendChild(style);
})();

// ============================================================
// EXPOSIÇÃO GLOBAL
// ============================================================
window.abrirMenuEllipsis = abrirMenuEllipsis;
window.fecharMenuEllipsis = fecharMenuEllipsis;

console.log('[ACOES] fenda-acoes.js inicializado com sucesso.');