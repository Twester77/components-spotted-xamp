/**
 * depoimentos-actions.js – Funções unificadas para aprovar/rejeitar depoimentos
 * 🔥 VERSÃO COM LOGS E REENTRÂNCIA (pode ser chamada múltiplas vezes)
 */
(function() {
    'use strict';

    // ============================================================
    // PROCESSAR AÇÃO (APROVAR/REJEITAR)
    // ============================================================
    window.processarDepoimento = function(id, acao, botao) {
        if (botao.disabled) return;
        botao.disabled = true;
        botao.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';

        const csrfToken = document.getElementById('csrf_token')?.value || '';
        console.log('[DEPOIMENTOS] Processando depoimento ID:', id, 'Ação:', acao, 'CSRF:', csrfToken);

        fetch('processa-aprovacao-depoimento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&acao=${acao}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('[DEPOIMENTOS] Resposta:', data);
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
            console.error('[DEPOIMENTOS] Erro na requisição:', err);
            if (typeof exibirBalao === 'function') {
                exibirBalao('❌ ' + err.message, 'erro', botao);
            } else {
                alert('Erro de conexão: ' + err.message);
            }
            botao.disabled = false;
            botao.innerHTML = acao === 'aprovar' 
                ? '<i class="fas fa-check"></i> Aprovar' 
                : '<i class="fas fa-times"></i> Rejeitar';
        });
    };

    // ============================================================
    // INICIALIZAR (DELEGAÇÃO DE EVENTOS) – COM REENTRÂNCIA
    // ============================================================
    window.initDepoimentosActions = function() {
        console.log('[DEPOIMENTOS] Inicializando eventos (ou reativando)');

        // Remove listeners antigos (se houver) para evitar duplicação
        // Como usamos delegação no documento, só precisamos adicionar uma vez.
        // Se já tiver sido adicionado, o removeEventListener não fará nada se não houver.
        document.removeEventListener('click', window._depoimentosClickHandler);
        
        // Define o handler e o armazena para poder remover depois
        window._depoimentosClickHandler = function(e) {
            // Botão Aprovar
            const btnAprovar = e.target.closest('.btn-aprovar-depoimento');
            if (btnAprovar) {
                e.preventDefault();
                const id = btnAprovar.dataset.id;
                if (!id) return;
                console.log('[DEPOIMENTOS] Clique em Aprovar para ID:', id);
                window.processarDepoimento(id, 'aprovar', btnAprovar);
                return;
            }

            // Botão Rejeitar
            const btnRejeitar = e.target.closest('.btn-rejeitar-depoimento');
            if (btnRejeitar) {
                e.preventDefault();
                const id = btnRejeitar.dataset.id;
                if (!id) return;
                console.log('[DEPOIMENTOS] Clique em Rejeitar para ID:', id);
                window.processarDepoimento(id, 'rejeitar', btnRejeitar);
                return;
            }
        };

        document.addEventListener('click', window._depoimentosClickHandler);
    };

    // ============================================================
    // EXPORTA PARA USO GLOBAL
    // ============================================================
    window.processarDepoimento = window.processarDepoimento || processarDepoimento;
    window.initDepoimentosActions = window.initDepoimentosActions || initDepoimentosActions;

    // ============================================================
    // INICIALIZA AUTOMATICAMENTE (se o DOM já estiver pronto)
    // ============================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDepoimentosActions);
    } else {
        initDepoimentosActions();
    }

})();