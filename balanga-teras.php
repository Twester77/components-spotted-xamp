<?php

/**
 * balanga-teras.php – Página principal do Balanga Teras (Swipe de Eventos)
 * 
 * 🔧 ÚLTIMA REVISÃO – MARÉ (2026-08-10)
 * - Transição suave ao resetar pilha (fade-out/in).
 * - Long press com menu de ações (Ver detalhes, Salvar favoritos, Cancelar evento).
 * - Cancelar evento integrado via AJAX com CSRF e confirmação.
 * - Radar "Buscar Eventos" com animação.
 * - Separação grid/swipe com observador de classe.
 */

require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🟢 INÍCIO balanga-teras.php');

if (!isset($_SESSION['usuario_id'])) {
    fenda_log('🔴 REDIRECIONANDO para index.php (balanga-teras sem sessão)');
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$status_filtro = isset($_GET['status']) ? $_GET['status'] : 'todos';
$comunidade_filtro = isset($_GET['comunidade']) ? (int)$_GET['comunidade'] : 0;
?>

<div class="bt-controles" role="toolbar" aria-label="Controles do Balanga Teras">
    <button type="button" id="bt-btn-filtros" class="bt-btn-filtro" onclick="btToggleFiltros()" aria-expanded="false">
        <i class="fas fa-filter" aria-hidden="true"></i> FILTRAR EVENTOS
    </button>
    <div id="bt-gaveta-filtros" class="bt-gaveta-filtros" role="region" aria-label="Painel de Filtros" aria-hidden="true">
        <div class="bt-filtros">
            <a href="balanga-teras.php?status=todos" class="bt-filtro <?= ($status_filtro == 'todos') ? 'ativo' : '' ?>">#TODOS</a>
            <a href="balanga-teras.php?status=agendado" class="bt-filtro <?= ($status_filtro == 'agendado') ? 'ativo' : '' ?>">#AGENDADO</a>
            <a href="balanga-teras.php?status=em-andamento" class="bt-filtro <?= ($status_filtro == 'em-andamento') ? 'ativo' : '' ?>">#ACONTECENDO</a>
            <a href="balanga-teras.php?status=expirado" class="bt-filtro <?= ($status_filtro == 'expirado') ? 'ativo' : '' ?>">#ENCERRADO</a>
        </div>
    </div>
</div>

<main class="bt-main" id="bt-conteudo-principal">
    <div class="bt-feedback bt-feedback-direita" aria-hidden="true">✅ VOU</div>
    <div class="bt-feedback bt-feedback-esquerda" aria-hidden="true">❌ NÃO VOU</div>
    <div class="bt-feedback bt-feedback-cima" aria-hidden="true">🤔 VOU VER E TE AVISO</div>
    <div class="bt-container" id="bt-container-eventos" role="feed" aria-busy="false" aria-live="polite"></div>
</main>

<div class="bt-load-more-wrapper" id="bt-load-more-wrapper">
    <button id="bt-btn-load-more" class="bt-btn-load-more">Exibir Mais Eventos</button>
</div>

<script>
    // ============================================================
    // 1. TOGGLE FILTROS
    // ============================================================
    function btToggleFiltros() {
        const gaveta = document.getElementById('bt-gaveta-filtros');
        const btn = document.getElementById('bt-btn-filtros');
        if (gaveta) {
            gaveta.classList.toggle('aberto');
            btn.innerHTML = gaveta.classList.contains('aberto') ?
                '<i class="fas fa-times"></i> FECHAR FILTROS' :
                '<i class="fas fa-filter"></i> FILTRAR EVENTOS';
            btn.setAttribute('aria-expanded', gaveta.classList.contains('aberto'));
        }
    }

    // ============================================================
    // 2. CARREGAR EVENTOS (AJAX)
    // ============================================================
    let btOffset = 0;
    let btCarregando = false;
    let btAcabou = false;
    const btnLoad = document.getElementById('bt-btn-load-more');
    const loadWrapper = document.getElementById('bt-load-more-wrapper');
    const container = document.getElementById('bt-container-eventos');
    const usuarioId = <?= (int)$_SESSION['usuario_id'] ?>; // para verificar dono

    function btCarregarEventos(reset = false) {
        if (btCarregando) return;
        btCarregando = true;

        if (reset) {
            btOffset = 0;
            btAcabou = false;
            container.innerHTML = '';
            if (btnLoad) {
                btnLoad.disabled = false;
                btnLoad.innerText = "Exibir Mais Eventos";
                btnLoad.style.display = 'inline-block';
            }
            if (loadWrapper) loadWrapper.style.display = 'inline-block';
        }

        if (container) container.setAttribute('aria-busy', 'true');
        if (btnLoad) btnLoad.disabled = true;

        const status = new URLSearchParams(window.location.search).get('status') || 'todos';
        const comunidade = new URLSearchParams(window.location.search).get('comunidade') || 0;

        fetch(`swipe-eventos.php?offset=${btOffset}&status=${status}&comunidade_id=${comunidade}`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    btAcabou = true;
                    if (btOffset === 0) {
                        container.innerHTML = `
                            <div class="bt-empty">
                                <i class="fas fa-calendar-plus"></i>
                                <strong>Nenhum evento encontrado!</strong>
                                <p>Que tal criar um evento para movimentar a Fenda?</p>
                                <button onclick="window.location.href='criar-evento.php'" class="bt-btn-empty">Criar Evento</button>
                            </div>
                        `;
                    }
                    if (document.body.classList.contains('modo-tinder-ativo')) {
                        if (loadWrapper) loadWrapper.style.display = 'none';
                    } else {
                        if (btnLoad) {
                            btnLoad.innerText = "FIM DOS EVENTOS";
                            btnLoad.disabled = true;
                        }
                    }
                } else {
                    btAcabou = false;
                    if (btOffset === 0) container.innerHTML = '';
                    container.insertAdjacentHTML('beforeend', data);
                    document.dispatchEvent(new CustomEvent('btCardsCarregados'));
                    btAtivarBotoesResposta();
                    btOffset += 10;

                    if (document.body.classList.contains('modo-tinder-ativo')) {
                        if (loadWrapper) loadWrapper.style.display = 'none';
                    } else {
                        if (loadWrapper) loadWrapper.style.display = 'block';
                        if (btnLoad) {
                            btnLoad.disabled = false;
                            btnLoad.innerText = "EXIBIR MAIS";
                        }
                    }
                }
                if (container) container.setAttribute('aria-busy', 'false');
            })
            .catch(err => {
                console.error('[BALANGA] Erro:', err);
                if (btnLoad) btnLoad.disabled = false;
            })
            .finally(() => {
                btCarregando = false;
                if (container) container.setAttribute('aria-busy', 'false');
            });
    }

    // ============================================================
    // 3. BOTÃO "EXIBIR MAIS" (grid)
    // ============================================================
    if (btnLoad) {
        btnLoad.addEventListener('click', function(e) {
            e.preventDefault();
            if (btCarregando || document.body.classList.contains('modo-tinder-ativo')) return;
            btCarregarEventos(false);
        });
    }

    // ============================================================
    // 4. RESPOSTAS RÁPIDAS (Vou/Não vou/Talvez)
    // ============================================================
    function btAtivarBotoesResposta() {
        document.querySelectorAll('.bt-btn-resposta').forEach(btn => {
            btn.removeEventListener('click', btHandlerResposta);
            btn.addEventListener('click', btHandlerResposta);
        });
    }

    function btHandlerResposta(e) {
        const btn = e.currentTarget;
        const eventoId = btn.dataset.evento;
        const opcao = btn.dataset.opcao;
        if (!eventoId || !opcao || btn.disabled) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const csrfToken = document.getElementById('csrf_token')?.value || '';
        const formData = new FormData();
        formData.append('evento_id', eventoId);
        formData.append('opcao', opcao);
        formData.append('csrf_token', csrfToken);

        fetch('enviar-resposta-evento.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const card = btn.closest('.bt-card');
                    if (card) {
                        card.querySelectorAll('.bt-btn-resposta').forEach(b => {
                            b.classList.remove('ativo-vou', 'ativo-talvez', 'ativo-nao');
                            b.innerHTML = b.dataset.opcao === 'vou' ? '👍 Vou' :
                                b.dataset.opcao === 'talvez' ? '🤔 Talvez' : '👎 Não vou';
                            b.disabled = false;
                        });
                        const classeCss = opcao === 'nao_vou' ? 'nao' : opcao;
                        btn.classList.add('ativo-' + classeCss);
                        btn.innerHTML = '✅ ' + (opcao === 'vou' ? 'Vou' : opcao === 'talvez' ? 'Talvez' : 'Não vou');
                    }
                    if (data.contagens) {
                        const card = btn.closest('.bt-card');
                        if (card) {
                            const spans = card.querySelectorAll('.bt-participacao span');
                            if (spans[0]) spans[0].innerHTML = '<i class="fas fa-user-check"></i> ' + (data.contagens.vou || 0);
                            if (spans[1]) spans[1].innerHTML = '<i class="fas fa-user-minus"></i> ' + (data.contagens.nao_vou || 0);
                            if (spans[2]) spans[2].innerHTML = '<i class="fas fa-user-clock"></i> ' + (data.contagens.talvez || 0);
                        }
                    }
                    if (typeof exibirToast === 'function') exibirToast('Resposta registrada! 🎉');
                } else {
                    alert(data.message || 'Erro ao registrar resposta.');
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.opcao === 'vou' ? '👍 Vou' :
                        btn.dataset.opcao === 'talvez' ? '🤔 Talvez' : '👎 Não vou';
                }
            })
            .catch(err => {
                console.error('[BALANGA] Erro:', err);
                alert('Erro de conexão.');
                btn.disabled = false;
                btn.innerHTML = btn.dataset.opcao === 'vou' ? '👍 Vou' :
                    btn.dataset.opcao === 'talvez' ? '🤔 Talvez' : '👎 Não vou';
            });
    }

    // ============================================================
    // 5. RADAR "BUSCAR EVENTOS" + TRANSIÇÃO SUAVE
    // ============================================================
    function exibirRadarBalanga() {
        if (!document.body.classList.contains('modo-tinder-ativo')) return;
        if (container.querySelector('.bt-radar')) return;

        const radarHtml = `
            <div class="bt-radar" style="text-align:center; padding:40px 20px; color:#ccc; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:100%;">
                <i class="fas fa-search" style="font-size:clamp(3rem, 8vw, 5rem); color:var(--bt-dourado); display:block; margin-bottom:16px;"></i>
                <strong style="font-size:clamp(1.1rem, 2vw, 1.6rem); display:block; margin-bottom:8px; color:#fff;">Fim da pilha!</strong>
                <p style="font-size:clamp(0.9rem, 1.2vw, 1.2rem); color:#888; margin-bottom:20px;">Que tal buscar novos eventos?</p>
                <button onclick="btRecarregarPilha()" class="bt-btn-empty" style="background:var(--bt-dourado); color:#000; border:none; padding:10px 28px; border-radius:30px; font-weight:700; cursor:pointer;">
                    <i class="fas fa-sync-alt"></i> Buscar Eventos
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', radarHtml);
        if (loadWrapper) loadWrapper.style.display = 'none';
    }

    window.btRecarregarPilha = function() {
        const radar = container.querySelector('.bt-radar');
        if (radar) radar.remove();

        container.style.transition = 'opacity 0.2s ease';
        container.style.opacity = '0';

        setTimeout(() => {
            btCarregarEventos(true);
            requestAnimationFrame(() => {
                container.style.opacity = '1';
                container.style.transition = 'opacity 0.25s ease';
            });
            document.dispatchEvent(new CustomEvent('btCardsCarregados'));
            if (document.body.classList.contains('modo-tinder-ativo')) {
                if (loadWrapper) loadWrapper.style.display = 'none';
            }
        }, 200);
    };

    // ============================================================
    // 6. ABASTECER PILHA (chamado pelo bt-swipe)
    // ============================================================
    window.btAbastecerPilha = function() {
        if (!container || !document.body.classList.contains('modo-tinder-ativo')) return;
        const cardsRestantes = container.querySelectorAll('.bt-card').length;
        if (cardsRestantes === 0 && btAcabou) {
            exibirRadarBalanga();
        } else if (cardsRestantes <= 3 && !btAcabou && !btCarregando) {
            btCarregarEventos(false);
        }
    };

    // ============================================================
    // 7. OBSERVADOR DE ALTERNÂNCIA GRID ↔ SWIPE
    // ============================================================
    const observerModoTinder = new MutationObserver(() => {
        const isSwipe = document.body.classList.contains('modo-tinder-ativo');
        if (isSwipe) {
            if (loadWrapper) loadWrapper.style.display = 'none';
            if (container.querySelectorAll('.bt-card').length === 0 && btAcabou) exibirRadarBalanga();
        } else {
            if (!btAcabou && loadWrapper) loadWrapper.style.display = 'block';
            const radar = container.querySelector('.bt-radar');
            if (radar) radar.remove();
        }
        btRecalcularLayout();
    });
    observerModoTinder.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // ============================================================
// 8. LONG PRESS – MENU DE AÇÕES (com overlay, igual ao feed)
// ============================================================
let longPressTimer = null;
let longPressCard = null;
let startXLP = 0, startYLP = 0;
const MOVE_THRESHOLD_LP = 10;
let menuAberto = false;

function btMostrarMenuAcoes(card) {
    const eventoId = card.dataset.id;
    const criadorId = parseInt(card.dataset.criador || '0');
    const isOwner = (criadorId === usuarioId);

    if (!eventoId) return;
    if (menuAberto) return;

    // 🔥 CALCULA O TAMANHO IDEAL COM BASE NA VIEWPORT
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const isMobile = vw < 600;

    // 🔥 Valores escalonáveis (usando clamp manual)
    // Largura do popup: entre 160px e 320px, com 50vw como referência
    const popupWidth = Math.min(Math.max(160, vw * 0.5), 320);
    // Tamanho da fonte: entre 0.8rem e 1.1rem, com 1.2vw como referência
    const fontSize = Math.min(Math.max(0.8, vw * 0.015), 1.1);
    // Padding dos botões: entre 6px e 14px, com 1vw como referência
    const btnPadding = Math.min(Math.max(6, vw * 0.015), 14);

    // Cria o OVERLAY
    const overlay = document.createElement('div');
    overlay.className = 'bt-actions-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 99998;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    `;

    // Cria o MENU com dimensões escalonáveis
    const menu = document.createElement('div');
    menu.className = 'bt-actions-popup';
    // 🔥 Aplica os valores calculados no estilo inline (com fallback via CSS)
    menu.style.cssText = `
        background: rgba(10,10,10,0.92);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border: 1px solid rgba(255,188,0,0.3);
        border-radius: 16px;
        padding: 8px 0;
        -webkit-user-select: none;
        -ms-user-select: none;
        -moz-user-select: none;
        user-select: none;
        min-width: ${popupWidth}px;
        max-width: 85vw;
        box-shadow: 0 8px 30px rgba(0,0,0,0.6);
        animation: btPopupIn 0.2s ease-out;
        font-size: ${fontSize}rem;
    `;

    // 🔥 Conteúdo do menu com botões usando padding escalonável
    menu.innerHTML = `
        <button class="bt-action-item" data-acao="detalhes" style="display:flex; align-items:center; gap:10px; width:100%; padding: ${btnPadding}px 16px; background:transparent; border:none; color:#fff; cursor:pointer; font-family:inherit; font-size:inherit; transition:0.15s;">
            <i class="fas fa-info-circle"></i> Ver detalhes
        </button>
        <button class="bt-action-item" data-acao="favoritar" style="display:flex; align-items:center; gap:10px; width:100%; padding: ${btnPadding}px 16px; background:transparent; border:none; color:#fff; cursor:pointer; font-family:inherit; font-size:inherit; transition:0.15s;">
            <i class="fas fa-star"></i> Salvar nos favoritos
        </button>
        ${isOwner ? `
        <button class="bt-action-item" data-acao="excluir" style="display:flex; align-items:center; gap:10px; width:100%; padding: ${btnPadding}px 16px; background:transparent; border:none; color:#ff6b6b; cursor:pointer; font-family:inherit; font-size:inherit; transition:0.15s; border-top:1px solid rgba(255,255,255,0.05);">
            <i class="fas fa-trash-alt"></i> Cancelar evento
        </button>` : ''}
        <button class="bt-action-item" data-acao="cancelar" style="display:flex; align-items:center; gap:10px; width:100%; padding: ${btnPadding}px 16px; background:transparent; border:none; color:#888; cursor:pointer; font-family:inherit; font-size:inherit; transition:0.15s; border-top:1px solid rgba(255,255,255,0.05);">
            <i class="fas fa-times"></i> Cancelar
        </button>
    `;

    overlay.appendChild(menu);
    document.body.appendChild(overlay);
    menuAberto = true;

    // Eventos dos botões (mantido igual)
    menu.querySelectorAll('.bt-action-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const acao = this.dataset.acao;
            fecharMenu(overlay);
            if (acao === 'detalhes') {
                window.location.href = 'evento.php?id=' + eventoId;
            } else if (acao === 'favoritar') {
                if (typeof exibirToast === 'function') {
                    exibirToast('📌 Evento salvo nos favoritos! (em breve)');
                } else {
                    alert('Funcionalidade em desenvolvimento.');
                }
            } else if (acao === 'excluir') {
                const confirmado = confirm('Tem certeza que deseja cancelar este evento? Esta ação é definitiva e notificará os participantes.');
                if (confirmado) {
                    const csrfToken = document.getElementById('csrf_token')?.value || '';
                    const formData = new FormData();
                    formData.append('id', eventoId);
                    formData.append('csrf_token', csrfToken);

                    fetch('cancelar-evento.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof exibirToast === 'function') {
                                exibirToast('🗑️ Evento cancelado com sucesso!', 'sucesso');
                            } else {
                                alert('Evento cancelado!');
                            }
                            const cardEl = document.querySelector(`.bt-card[data-id="${eventoId}"]`);
                            if (cardEl) cardEl.remove();
                            if (typeof window.btAbastecerPilha === 'function') {
                                window.btAbastecerPilha();
                            }
                        } else {
                            alert(data.message || 'Erro ao cancelar evento.');
                        }
                    })
                    .catch(err => {
                        console.error('[BALANGA] Erro ao cancelar:', err);
                        alert('Erro de conexão. Tente novamente.');
                    });
                }
            }
        });
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            fecharMenu(overlay);
        }
    });

    function fecharMenu(overlayEl) {
        if (overlayEl && overlayEl.parentNode) overlayEl.remove();
        menuAberto = false;
        longPressCard = null;
    }

    // Estilos adicionais (fallback CSS para navegadores antigos)
    if (!document.getElementById('bt-popup-styles')) {
        const style = document.createElement('style');
        style.id = 'bt-popup-styles';
        style.textContent = `
            @keyframes fadeIn {
                from { opacity:0; }
                to { opacity:1; }
            }
            @keyframes btPopupIn {
                from { opacity:0; transform:scale(0.95) translateY(10px); }
                to { opacity:1; transform:scale(1) translateY(0); }
            }
            .bt-action-item:hover {
                background: rgba(255,255,255,0.06);
            }
            .bt-action-item[data-acao="excluir"]:hover {
                background: rgba(255,50,50,0.12);
                color: #ff8a8a;
            }
            /* 🔥 FALLBACK para navegadores antigos: usa valores fixos se o JS não conseguir */
            .bt-actions-popup {
                min-width: 200px ; /* fallback */
            }
            @media (max-width: 480px) {
                .bt-actions-popup {
                    min-width: 140px ;
                }
                .bt-action-item {
                    padding: 8px 12px ;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

    // Configura o long press no container
    container.addEventListener('pointerdown', function(e) {
        if (menuAberto) return; // 🔥 Se menu estiver aberto, não inicia novo
        const card = e.target.closest('.bt-card');
        if (!card) return;
        if (e.target.closest('.bt-btn-resposta') || e.target.closest('.bt-btn-detalhes') || e.target.closest('a')) return;
        if (!document.body.classList.contains('modo-tinder-ativo')) return;

        longPressCard = card;
        startXLP = e.clientX;
        startYLP = e.clientY;
        longPressTimer = setTimeout(() => {
            if (longPressCard && !menuAberto) {
                btMostrarMenuAcoes(longPressCard);
                longPressCard = null;
            }
        }, 400); // 🔥 Aumentado para 400ms
    });

    container.addEventListener('pointermove', function(e) {
        if (!longPressCard) return;
        const dx = Math.abs(e.clientX - startXLP);
        const dy = Math.abs(e.clientY - startYLP);
        if (dx > MOVE_THRESHOLD_LP || dy > MOVE_THRESHOLD_LP) {
            clearTimeout(longPressTimer);
            longPressCard = null;
        }
    });

    container.addEventListener('pointerup', function() {
        clearTimeout(longPressTimer);
        // Não limpa longPressCard se o menu estiver aberto
        if (!menuAberto) longPressCard = null;
    });

    container.addEventListener('pointercancel', function() {
        clearTimeout(longPressTimer);
        if (!menuAberto) longPressCard = null;
    });

    // ============================================================
    // 9. INICIALIZAÇÃO
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        btCarregarEventos(true);
    });

    console.log('[BALANGA] Balanga Teras inicializado com sucesso!');
</script>

<?php include 'includes/footer.php'; ?>
<script src="js/bt-swipe.js?v=<?= time() ?>"></script>