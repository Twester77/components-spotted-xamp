<?php
/**
 * gerenciar-membros-modal.php – Modal para gerenciar membros da comunidade
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-12
 * Versão com busca, paginação e carregar mais.
 */
?>
<div id="modal-gerenciar-membros" class="modal-overlay" style="display:none;">
    <div class="modal-container">
        <!-- Cabeçalho -->
        <div class="modal-header">
            <h3><i class="fas fa-users-cog"></i> Gerenciar membros</h3>
            <button class="modal-fechar" id="fechar-modal-membros" aria-label="Fechar">&times;</button>
        </div>

        <!-- 🔥 CAMPO DE BUSCA -->
        <div class="modal-search-box">
            <input type="text" id="input-busca-membro" class="input-busca-membro" placeholder="Buscar por nome ou @username..." autocomplete="off">
        </div>

        <!-- Corpo (lista de membros) -->
        <div class="modal-body" id="modal-membros-body">
            <div class="loading-membros">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Carregando membros...</p>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="modal-footer">
            <span class="contador-membros-modal" id="contador-membros-modal">0 membros</span>
            <button class="btn-fechar-modal" id="btn-fechar-modal-membros">Fechar</button>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        // ============================================================
        // 1. ELEMENTOS DO DOM
        // ============================================================
        const modal = document.getElementById('modal-gerenciar-membros');
        const body = document.getElementById('modal-membros-body');
        const contador = document.getElementById('contador-membros-modal');
        const btnFechar = document.getElementById('fechar-modal-membros');
        const btnFecharRodape = document.getElementById('btn-fechar-modal-membros');
        const inputBusca = document.getElementById('input-busca-membro');
        let comunidadeId = 0;
        let offsetAtual = 0;
        let termoBusca = '';
        let statusFiltro = 'todos'; // futuramente pode virar um select
        let carregando = false;
        let totalMembros = 0;
        let carregados = 0;
        let debounceTimer = null;

        // ============================================================
        // 2. FUNÇÃO PARA ABRIR O MODAL
        // ============================================================
        window.abrirModalMembros = function(id) {
            comunidadeId = parseInt(id);
            if (!comunidadeId || comunidadeId <= 0) {
                alert('ID da comunidade inválido.');
                return;
            }

            // Reseta estado
            offsetAtual = 0;
            termoBusca = '';
            inputBusca.value = '';
            totalMembros = 0;
            carregados = 0;

            modal.style.display = 'flex';
            document.body.classList.add('modal-aberto');
            document.body.style.overflow = 'hidden';

            carregarListaMembros(true);
        };

        // ============================================================
        // 3. FUNÇÃO PARA FECHAR O MODAL
        // ============================================================
        function fecharModal() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-aberto');
            document.body.style.overflow = '';
        }

        // ============================================================
        // 4. CARREGAR LISTA DE MEMBROS (AJAX)
        // ============================================================
        function carregarListaMembros(reset = false) {
            if (carregando) return;
            if (reset) {
                offsetAtual = 0;
                body.innerHTML = `
                    <div class="loading-membros">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Carregando membros...</p>
                    </div>
                `;
            }

            carregando = true;

            const url = `listar-membros.php?comunidade_id=${comunidadeId}&offset=${offsetAtual}&status=${statusFiltro}&busca=${encodeURIComponent(termoBusca)}&_=${Date.now()}`;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    // Verifica se veio um estado vazio (sem membros)
                    if (html.trim() === '' || html.includes('Nenhum membro encontrado')) {
                        if (reset) {
                            body.innerHTML = `<p style="text-align:center; color:#aaa; padding:20px;">Nenhum membro encontrado.</p>`;
                            contador.textContent = '0 membros';
                        }
                        carregando = false;
                        return;
                    }

                    // Extrai dados do container (total, carregados, offset)
                    const container = document.createElement('div');
                    container.innerHTML = html;
                    const lista = container.querySelector('.membros-lista');
                    const botaoCarregar = container.querySelector('.carregar-mais-membros');

                    // Atualiza total e carregados
                    if (lista) {
                        totalMembros = parseInt(lista.dataset.total) || 0;
                        carregados = parseInt(lista.dataset.carregados) || 0;
                    }

                    if (reset) {
                        body.innerHTML = '';
                        // Adiciona a lista
                        if (lista) body.appendChild(lista);
                        // Adiciona o botão "Carregar mais" se existir
                        if (botaoCarregar) body.appendChild(botaoCarregar);
                        // Atualiza contador
                        contador.textContent = totalMembros + ' membro' + (totalMembros !== 1 ? 's' : '');
                    } else {
                        // Modo "carregar mais": anexa os novos itens
                        if (lista) {
                            const itens = lista.querySelectorAll('.membro-item');
                            const containerExistente = body.querySelector('.membros-lista');
                            if (containerExistente) {
                                itens.forEach(item => containerExistente.appendChild(item));
                                // Atualiza os atributos data do container
                                containerExistente.dataset.total = totalMembros;
                                containerExistente.dataset.carregados = carregados;
                                containerExistente.dataset.offset = parseInt(lista.dataset.offset) || 0;
                            } else {
                                body.appendChild(lista);
                            }
                            // Atualiza contador
                            contador.textContent = totalMembros + ' membro' + (totalMembros !== 1 ? 's' : '');
                        }

                        // Substitui o botão "Carregar mais" (se houver)
                        const btnExistente = body.querySelector('.carregar-mais-membros');
                        if (btnExistente) btnExistente.remove();
                        if (botaoCarregar) body.appendChild(botaoCarregar);
                    }

                    // Atualiza o offset para a próxima página
                    if (lista && lista.dataset.offset) {
                        offsetAtual = parseInt(lista.dataset.offset);
                    }

                    carregando = false;
                })
                .catch(err => {
                    console.error('[MODAL MEMBROS] Erro ao carregar:', err);
                    body.innerHTML = `
                        <div style="text-align:center; padding:30px; color:#ff6b6b;">
                            <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
                            <p>Erro ao carregar membros. Tente novamente.</p>
                            <button onclick="abrirModalMembros(${comunidadeId})" class="btn-tentar-novamente">Tentar novamente</button>
                        </div>
                    `;
                    carregando = false;
                });
        }

        // ============================================================
        // 5. DELEGAÇÃO DE EVENTOS PARA AÇÕES (banir, remover, promover)
        // ============================================================
        body.addEventListener('click', function(e) {
            const alvo = e.target.closest('button');
            if (!alvo) return;

            const csrfToken = document.getElementById('csrf_token')?.value || '';
            if (!csrfToken) {
                alert('Erro de segurança. Recarregue a página.');
                return;
            }

            // ---------- BANIR / DESBANIR ----------
            if (alvo.classList.contains('btn-banir')) {
                e.preventDefault();
                const usuarioId = alvo.dataset.usuario;
                const acao = alvo.dataset.acao;
                const item = alvo.closest('.membro-item');
                const nome = item?.querySelector('.nome-membro')?.textContent?.trim() || 'usuário';

                if (!confirm(`${acao === 'banir' ? 'Banir' : 'Desbanir'} ${nome}?`)) return;

                alvo.disabled = true;
                alvo.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('banir-membro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `comunidade_id=${comunidadeId}&usuario_id=${usuarioId}&acao=${acao}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Recarrega a lista (reseta offset)
                        carregarListaMembros(true);
                        if (typeof exibirToast === 'function') {
                            exibirToast(data.message, 'sucesso');
                        }
                    } else {
                        alert(data.message || 'Erro ao executar ação.');
                        alvo.disabled = false;
                        alvo.innerHTML = acao === 'banir' ? '🔒 Banir' : '🔓 Desbanir';
                    }
                })
                .catch(err => {
                    console.error('[BANIR] Erro:', err);
                    alert('Erro de conexão.');
                    alvo.disabled = false;
                    alvo.innerHTML = acao === 'banir' ? '🔒 Banir' : '🔓 Desbanir';
                });
            }

            // ---------- REMOVER ----------
            if (alvo.classList.contains('btn-remover')) {
                e.preventDefault();
                const usuarioId = alvo.dataset.usuario;
                const item = alvo.closest('.membro-item');
                const nome = item?.querySelector('.nome-membro')?.textContent?.trim() || 'usuário';

                if (!confirm(`Remover ${nome} da comunidade?`)) return;

                alvo.disabled = true;
                alvo.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('remover-membro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `comunidade_id=${comunidadeId}&usuario_id=${usuarioId}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (item) {
                            item.style.transition = 'opacity 0.3s, transform 0.3s';
                            item.style.opacity = '0';
                            item.style.transform = 'scale(0.95)';
                            setTimeout(() => {
                                item.remove();
                                // Atualiza contador e verifica se acabou
                                const lista = body.querySelector('.membros-lista');
                                if (lista) {
                                    const total = lista.querySelectorAll('.membro-item').length;
                                    contador.textContent = total + ' membro' + (total !== 1 ? 's' : '');
                                    if (total === 0) {
                                        body.innerHTML = `<p style="text-align:center; color:#aaa; padding:20px;">Nenhum membro encontrado.</p>`;
                                        contador.textContent = '0 membros';
                                    }
                                }
                            }, 300);
                        }
                        if (typeof exibirToast === 'function') {
                            exibirToast(data.message, 'sucesso');
                        }
                    } else {
                        alert(data.message || 'Erro ao remover.');
                        alvo.disabled = false;
                        alvo.innerHTML = '🗑️';
                    }
                })
                .catch(err => {
                    console.error('[REMOVER] Erro:', err);
                    alert('Erro de conexão.');
                    alvo.disabled = false;
                    alvo.innerHTML = '🗑️';
                });
            }

            // ---------- PROMOVER / REBAIXAR ----------
            if (alvo.classList.contains('btn-promover') || alvo.classList.contains('btn-rebaixar')) {
                e.preventDefault();
                const usuarioId = alvo.dataset.usuario;
                const acao = alvo.classList.contains('btn-promover') ? 'promover' : 'rebaixar';
                const item = alvo.closest('.membro-item');
                const nome = item?.querySelector('.nome-membro')?.textContent?.trim() || 'usuário';
                const acaoTexto = acao === 'promover' ? 'promover a admin' : 'rebaixar para membro';

                if (!confirm(`${acao === 'promover' ? 'Promover' : 'Rebaixar'} ${nome}?`)) return;

                alvo.disabled = true;
                alvo.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('promover-membro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `comunidade_id=${comunidadeId}&usuario_id=${usuarioId}&acao=${acao}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        carregarListaMembros(true);
                        if (typeof exibirToast === 'function') {
                            exibirToast(data.message, 'sucesso');
                        }
                    } else {
                        alert(data.message || 'Erro ao executar ação.');
                        alvo.disabled = false;
                        alvo.innerHTML = acao === 'promover' ? '⬆️' : '⬇️';
                    }
                })
                .catch(err => {
                    console.error('[PROMOVER] Erro:', err);
                    alert('Erro de conexão.');
                    alvo.disabled = false;
                    alvo.innerHTML = acao === 'promover' ? '⬆️' : '⬇️';
                });
            }
        });

        // ============================================================
        // 6. EVENTO DE BUSCA (com debounce de 300ms)
        // ============================================================
        inputBusca.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                termoBusca = this.value.trim();
                // Reseta a lista e recarrega com a busca
                carregarListaMembros(true);
            }, 300);
        });

        // ============================================================
        // 7. DELEGAÇÃO PARA O BOTÃO "CARREGAR MAIS"
        // ============================================================
        body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-carregar-mais-membros');
            if (!btn) return;
            e.preventDefault();

            const offset = parseInt(btn.dataset.offset);
            if (isNaN(offset) || carregando) return;

            // Atualiza o offset atual para o próximo lote
            offsetAtual = offset;
            carregarListaMembros(false);
        });

        // ============================================================
        // 8. FECHAR MODAL (eventos)
        // ============================================================
        btnFechar.addEventListener('click', fecharModal);
        btnFecharRodape.addEventListener('click', fecharModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) fecharModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') fecharModal();
        });

        // ============================================================
        // 9. EXPOR FUNÇÃO GLOBAL
        // ============================================================
        window.abrirModalMembros = window.abrirModalMembros || abrirModalMembros;

        console.log('[MODAL MEMBROS] Inicializado com sucesso.');
    })();
</script>