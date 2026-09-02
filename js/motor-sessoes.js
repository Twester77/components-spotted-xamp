// ============================================================
// MOTOR DE SESSÕES – DELEGAÇÃO DE EVENTOS (VERSÃO APRIMORADA)
// 🐚 BRISA – 2026-09-01 (v4 – com data-is-atual e tratamento granular de erros)
// ============================================================

console.log('[SESSOES] Script motor-sessoes.js carregado.');

// ============================================================
// FUNÇÕES AUXILIARES (ENCAPSULADAS)
// ============================================================

function encerrarSessaoUnica(btn, sessaoId) {
    console.log('[SESSOES] encerrarSessaoUnica chamado para ID:', sessaoId);

    // 🔥 Verifica se a sessão que estamos tentando encerrar é a atual (usando data-is-atual)
    const isAtual = btn.dataset.isAtual === '1';
    if (isAtual) {
        if (typeof exibirToast === 'function') {
            exibirToast('❌ Não é possível encerrar a sessão atual. Use "Encerrar todas" ou faça logout.', 'erro');
        } else {
            alert('Você não pode encerrar a sessão atual. Use "Encerrar todas" ou faça logout.');
        }
        console.warn('[SESSOES] Tentativa de encerrar a sessão atual (bloqueada via data-is-atual)');
        return;
    }

    if (!confirm('Tem certeza que deseja encerrar esta sessão? O dispositivo será desconectado.')) {
        console.log('[SESSOES] Usuário cancelou.');
        return;
    }

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-small"></span>';

    const csrfToken = document.getElementById('csrf_token')?.value || '';
    console.log('[SESSOES] CSRF Token lido:', csrfToken ? 'presente' : 'AUSENTE');

    if (!csrfToken) {
        alert('Erro de segurança: token CSRF não encontrado. Recarregue a página.');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        return;
    }

    const formData = new FormData();
    formData.append('sessao_id', sessaoId);
    formData.append('csrf_token', csrfToken);

    console.log('[SESSOES] Enviando requisição para encerrar-sessao-unica.php com ID:', sessaoId);

    fetch('encerrar-sessao-unica.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async response => {
        console.log('[SESSOES] Resposta recebida. Status:', response.status);
        const text = await response.text();
        console.log('[SESSOES] Texto da resposta:', text.substring(0, 200));
        try {
            const data = JSON.parse(text);
            console.log('[SESSOES] Dados parseados:', data);
            return { data, status: response.status };
        } catch (e) {
            console.error('[SESSOES] Erro ao parsear JSON:', e);
            throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
        }
    })
    .then(({ data, status }) => {
        console.log('[SESSOES] Dados recebidos no then:', data);

        // ============================================================
        // TRATAMENTO GRANULAR DE ERROS (contrato JSON)
        // ============================================================
        if (data.success) {
            console.log('[SESSOES] Sucesso! Encerrando sessão.');
            const item = btn.closest('.sessao-item');
            if (item) {
                item.style.transition = 'opacity 0.3s, transform 0.3s';
                item.style.opacity = '0';
                item.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    item.remove();
                    const totalSpan = document.querySelector('.sessoes-contador');
                    if (totalSpan) {
                        const total = document.querySelectorAll('.sessao-item').length;
                        totalSpan.textContent = '(' + total + ' dispositivo' + (total !== 1 ? 's' : '') + ')';
                    }
                    if (document.querySelectorAll('.sessao-item').length === 0) {
                        if (typeof window.recarregarAbaCentral === 'function') {
                            window.recarregarAbaCentral();
                        } else {
                            location.reload();
                        }
                    }
                }, 300);
            }
            if (typeof exibirToast === 'function') {
                exibirToast('✅ Sessão encerrada!', 'sucesso');
            } else {
                alert('✅ Sessão encerrada!');
            }
        } else {
            // Tratamento de erros específicos
            switch (data.error) {
                case 'current_session':
                    if (typeof exibirToast === 'function') {
                        exibirToast('❌ Não é possível encerrar a sessão atual.', 'erro');
                    } else {
                        alert(data.message);
                    }
                    break;
                case 'already_inactive':
                case 'not_found':
                    if (typeof exibirToast === 'function') {
                        exibirToast('ℹ️ ' + (data.message || 'Sessão já foi encerrada.'), 'info');
                    } else {
                        alert(data.message);
                    }
                    // Recarrega a lista para sincronizar o estado visual
                    if (typeof window.recarregarAbaCentral === 'function') {
                        window.recarregarAbaCentral();
                    } else {
                        location.reload();
                    }
                    break;
                case 'unauthorized':
                    if (typeof exibirToast === 'function') {
                        exibirToast('🔒 Sessão expirada. Faça login novamente.', 'erro');
                    } else {
                        alert(data.message);
                    }
                    window.location.href = 'logout.php';
                    break;
                case 'rate_limited':
                    if (typeof exibirToast === 'function') {
                        exibirToast('⏳ ' + data.message, 'info');
                    } else {
                        alert(data.message);
                    }
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    break;
                default:
                    console.warn('[SESSOES] Erro não mapeado:', data.error);
                    if (typeof exibirToast === 'function') {
                        exibirToast('❌ ' + (data.message || 'Erro ao encerrar sessão.'), 'erro');
                    } else {
                        alert(data.message || 'Erro ao encerrar sessão.');
                    }
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
            }
        }
    })
    .catch(err => {
        console.error('[SESSOES] Erro no fetch:', err);
        if (typeof exibirToast === 'function') {
            exibirToast('❌ Erro de conexão. Tente novamente.', 'erro');
        } else {
            alert('Erro: ' + err.message);
        }
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

function encerrarTodasSessoes() {
    console.log('[SESSOES] encerrarTodasSessoes chamado.');

    if (!confirm('Tem certeza que deseja encerrar todas as sessões ativas em outros dispositivos? A sessão atual será mantida.')) {
        console.log('[SESSOES] Usuário cancelou.');
        return;
    }

    const btn = document.querySelector('.btn-encerrar-todas') || document.querySelector('[data-acao="encerrar-todas"]');
    if (!btn) {
        console.error('[SESSOES] Botão "Encerrar todas" não encontrado.');
        alert('Erro: botão "Encerrar todas" não encontrado. Recarregue a página.');
        return;
    }

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Encerrando...';

    const csrfToken = document.getElementById('csrf_token')?.value || '';
    console.log('[SESSOES] CSRF Token lido:', csrfToken ? 'presente' : 'AUSENTE');

    if (!csrfToken) {
        alert('Erro de segurança: token CSRF não encontrado. Recarregue a página.');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    console.log('[SESSOES] Enviando requisição para encerrar-sessoes.php');

    fetch('encerrar-sessoes.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async response => {
        console.log('[SESSOES] Resposta recebida. Status:', response.status);
        const text = await response.text();
        console.log('[SESSOES] Texto da resposta:', text.substring(0, 200));
        try {
            const data = JSON.parse(text);
            console.log('[SESSOES] Dados parseados:', data);
            return data;
        } catch (e) {
            console.error('[SESSOES] Erro ao parsear JSON:', e);
            throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
        }
    })
    .then(data => {
        console.log('[SESSOES] Dados recebidos no then:', data);
        if (data.success) {
            if (data.forcar_logout) {
                alert('Todas as sessões foram encerradas, incluindo a atual. Você será redirecionado para o login.');
                window.location.href = 'logout.php';
                return;
            }
            alert(data.message);
            if (typeof window.recarregarAbaCentral === 'function') {
                window.recarregarAbaCentral();
            } else {
                location.reload();
            }
        } else {
            console.warn('[SESSOES] success = false. Mensagem:', data.message);
            if (typeof exibirToast === 'function') {
                exibirToast('❌ ' + (data.message || 'Erro ao encerrar sessões.'), 'erro');
            } else {
                alert(data.message || 'Erro ao encerrar sessões.');
            }
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error('[SESSOES] Erro no fetch:', err);
        if (typeof exibirToast === 'function') {
            exibirToast('❌ Erro de conexão. Tente novamente.', 'erro');
        } else {
            alert('Erro: ' + err.message);
        }
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

// ============================================================
// DELEGAÇÃO DE EVENTOS (EXPOSTA GLOBALMENTE)
// ============================================================
window.initSessoesActions = function() {
    console.log('[SESSOES] initSessoesActions chamado.');

    // Remove listener antigo para evitar duplicação
    document.removeEventListener('click', window._sessoesClickHandler);
    
    // Handler com prefixo único para evitar conflitos
    window._sessoesClickHandler = function(e) {
        // Botão "Encerrar" individual
        const btnEncerrar = e.target.closest('.btn-encerrar-sessao');
        if (btnEncerrar) {
            e.preventDefault();
            e.stopPropagation();
            const sessaoId = btnEncerrar.dataset.id;
            if (!sessaoId) return;
            console.log('[SESSOES] Clique em Encerrar para ID:', sessaoId);
            encerrarSessaoUnica(btnEncerrar, sessaoId);
            return;
        }

        // Botão "Encerrar todas"
        const btnTodas = e.target.closest('.btn-encerrar-todas');
        if (btnTodas) {
            e.preventDefault();
            e.stopPropagation();
            console.log('[SESSOES] Clique em Encerrar todas');
            encerrarTodasSessoes();
            return;
        }
    };

    document.addEventListener('click', window._sessoesClickHandler);
    console.log('[SESSOES] Delegador de eventos registrado.');
};

// ============================================================
// INICIALIZAÇÃO AUTOMÁTICA (FALLBACK)
// ============================================================
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(window.initSessoesActions, 100);
    });
} else {
    setTimeout(window.initSessoesActions, 100);
}

console.log('[SESSOES] Fim do script motor-sessoes.js');