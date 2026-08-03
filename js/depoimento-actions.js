/**
 * depoimentos-actions.js – Funções unificadas para aprovar/rejeitar depoimentos
 * Usado tanto na Central (central.php) quanto na página avulsa (gerenciar-depoimentos.php)
 * 
 * Depende do CSRF token (elemento #csrf_token)
 */

(function() {
    'use strict';

    // ============================================================
    // PROCESSAR AÇÃO (APROVAR/REJEITAR)
    // ============================================================
    function processarDepoimento(id, acao, botao) {
        if (botao.disabled) return;
        botao.disabled = true;
        botao.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

        const csrfToken = document.getElementById('csrf_token')?.value || '';

        fetch('processa-aprovacao-depoimento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&acao=${acao}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove o item com animação
                const item = botao.closest('.depoimento-pendente-item') || 
                             botao.closest('.central-depoimento-pendente-item');
                if (item) {
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        item.remove();
                        // Verifica se ainda há itens
                        const container = document.querySelector('.depoimentos-pendentes-list') ||
                                          document.querySelector('.central-depoimentos-pendentes');
                        if (container && container.children.length === 0) {
                            const emptyState = document.createElement('div');
                            emptyState.className = 'empty-state';
                            emptyState.innerHTML = `
                                <i class="fas fa-check-circle" style="font-size: 3rem; color: #4caf50; margin-bottom: 15px;"></i>
                                <p>Todos os depoimentos foram processados! 🎉</p>
                            `;
                            container.parentNode.appendChild(emptyState);
                            container.remove();
                        }
                    }, 300);
                }
            } else {
                alert(data.message || 'Erro ao processar. Tente novamente.');
                botao.disabled = false;
                botao.innerHTML = acao === 'aprovar' 
                    ? '<i class="fas fa-check"></i> Aprovar' 
                    : '<i class="fas fa-times"></i> Rejeitar';
            }
        })
        .catch(err => {
            console.error('[DEPOIMENTOS] Erro:', err);
            alert('Erro de conexão. Tente novamente.');
            botao.disabled = false;
            botao.innerHTML = acao === 'aprovar' 
                ? '<i class="fas fa-check"></i> Aprovar' 
                : '<i class="fas fa-times"></i> Rejeitar';
        });
    }

    // ============================================================
    // INICIALIZAR (DELEGAÇÃO DE EVENTOS)
    // ============================================================
    function initDepoimentosActions() {
        // Usa delegação de eventos no documento para capturar cliques
        document.addEventListener('click', function(e) {
            // Botão Aprovar
            const btnAprovar = e.target.closest('.btn-aprovar-depoimento');
            if (btnAprovar) {
                e.preventDefault();
                const id = btnAprovar.dataset.id;
                if (!id) return;
                processarDepoimento(id, 'aprovar', btnAprovar);
                return;
            }

            // Botão Rejeitar
            const btnRejeitar = e.target.closest('.btn-rejeitar-depoimento');
            if (btnRejeitar) {
                e.preventDefault();
                const id = btnRejeitar.dataset.id;
                if (!id) return;
                processarDepoimento(id, 'rejeitar', btnRejeitar);
                return;
            }
        });
    }

    // ============================================================
    // EXPORTA PARA USO GLOBAL (se necessário)
    // ============================================================
    window.processarDepoimento = processarDepoimento;
    window.initDepoimentosActions = initDepoimentosActions;

    // ============================================================
    // INICIALIZA AUTOMATICAMENTE
    // ============================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDepoimentosActions);
    } else {
        initDepoimentosActions();
    }

})();