<?php

/**
 * balanga-teras.php – Página principal do Balanga Teras (Swipe de Eventos)
 *
 * 🔧 ÚLTIMA REVISÃO – LUZ (2026-08-14)
 * - Adicionado filtro por comunidade (select com comunidades do usuário)
 * - Integração com swipe-eventos.php via parâmetro comunidade_id
 * - Listener para recarregar eventos ao mudar a comunidade selecionada
 * 
 * 🔧 REVISÃO ANTERIOR – MARÉ (2026-08-10)
 * - Transição suave ao resetar pilha (fade-out/in).
 * - Long press com menu de ações (Ver detalhes, Salvar favoritos, Cancelar evento).
 * - Cancelar evento integrado via AJAX com CSRF e confirmação.
 * - Radar "Buscar Eventos" com animação.
 * - Separação grid/swipe com observador de classe.
 * 
 * ✅ ATUALIZAÇÃO ESTRELA – 2026-08-17
 * - Este arquivo não possui chamadas diretas a obterUrlImagem() ou obterUrlComFallback.
 * - A renderização dos cards é feita via swipe-eventos.php (já corrigido).
 * - Nenhuma alteração necessária neste arquivo.
 *
 * 🐚 CALMARIA – 2026-10-08 (Sprint 2, item #5)
 *    - Defesa em profundidade contra o bug do CSRF rolling token.
 *      A função btHandlerResposta (usada no modo grid, sem swipe) não lia
 *      `data.csrf_token` da resposta do enviar-resposta-evento.php. Em
 *      erros de validação, o backend rotacionava o token, mas o front
 *      seguia com o antigo — a próxima resposta sem recarregar a página
 *      recebia 403 "Token inválido".
 *      Agora lê `data.csrf_token` e atualiza TODOS os input[name="csrf_token"]
 *      da página. Fix complementar ao do enviar-resposta-evento.php (que
 *      agora só rotaciona após validações passarem, no sucesso) e ao
 *      do evento.php inline.
 *
 * 🚀 FASE 2.3: Integração com o Protocolo translateL (Swipe Acessível).
 *    - O motor bt-swipe.js foi aposentado.
 *    - Função de cálculo de layout (btRecalcularLayout) restaurada e blindada.
 *    - TranslateL assume física, acessibilidade e portal de comandos.
 *    - Adicionado controle de performance (menu-aberto) para evitar blur duplo.
 *    - Correção do typeof TranslateL (objeto) e injeção de data-owner.
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

// ============================================================
// 🔍 BUSCA COMUNIDADES DO USUÁRIO (para o filtro)
// ============================================================
$comunidades_usuario = [];
$sql_com = "SELECT c.id, c.nome FROM comunidades c
            JOIN comunidade_membros cm ON c.id = cm.comunidade_id
            WHERE cm.usuario_id = ? AND cm.status = 'ativo'
            ORDER BY c.nome ASC";
$stmt_com = $conn->prepare($sql_com);
$stmt_com->bind_param("i", $_SESSION['usuario_id']);
$stmt_com->execute();
$res_com = $stmt_com->get_result();
while ($com = $res_com->fetch_assoc()) {
    $comunidades_usuario[] = $com;
}
$stmt_com->close();
?>

<div class="bt-controles" role="toolbar" aria-label="Controles do Balanga Teras">
    <div class="bt-filtro-comunidade">
        <select id="bt-filtro-comunidade" class="bt-select-comunidade">
            <option value="0">🌐 Todas as comunidades</option>
            <?php foreach ($comunidades_usuario as $com): ?>
                <option value="<?= $com['id'] ?>" <?= ($comunidade_filtro == $com['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($com['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
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
    <div class="bt-feedback bt-feedback-direita" aria-hidden="true">✅ EU VOU!</div>
    <div class="bt-feedback bt-feedback-esquerda" aria-hidden="true">❌ NÃO VOU</div>
    <div class="bt-feedback bt-feedback-cima" aria-hidden="true">☝️ TALVEZ EU VÁ</div>
    <div class="bt-container" id="bt-container-eventos" role="feed" aria-busy="false" aria-live="polite"></div>
</main>

<div class="bt-load-more-wrapper" id="bt-load-more-wrapper">
    <button id="bt-btn-load-more" class="bt-btn-load-more">Exibir Mais Eventos</button>
</div>

<!-- Integração Protocolo translateL (Fase 2.3) -->
<script src="<?= asset_versao('js/translateL-core.js') ?>"></script>
<link rel="stylesheet" href="<?= asset_versao('css/translateL.css') ?>">

<script>
    // ============================================================
    // 1. CÁLCULO DE LAYOUT DOS CARDS (Restaurado Completo do bt-swipe.js)
    // ============================================================
    let recalculando = false;
    let layoutTimeout;

    window.btRecalcularLayout = function() {
        if (recalculando) return;
        if (!document.body.classList.contains('modo-tinder-ativo')) return;

        const container = document.getElementById('bt-container-eventos');
        if (!container) return;

        recalculando = true;

        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const isLandscape = vw > vh;
        const isCompactLandscape = window.matchMedia('(orientation: landscape) and (max-height: 500px)').matches;

        const cardWidth = isCompactLandscape ?
            Math.round(Math.max(320, Math.min(vw * 0.90, 1000))) :
            isLandscape ?
            Math.round(Math.max(320, Math.min(vw * 0.60, 600))) :
            Math.round(Math.max(240, Math.min(vw * 0.70, 550)));

        let maxCardHeight;
        if (isCompactLandscape) {
            maxCardHeight = Math.round(Math.min(vh * 0.82, 420));
        } else if (isLandscape) {
            maxCardHeight = Math.round(Math.min(vh * 0.85, 650));
        } else {
            maxCardHeight = Math.round(Math.min(vh * 0.92, 700));
        }

        const cardPadding = isCompactLandscape ?
            Math.round(Math.max(10, Math.min(24, cardWidth * 0.035))) :
            Math.round(Math.max(12, cardWidth * 0.05));
        const fontSize = Math.max(0.9, Math.min(cardWidth / 240, 1.6));

        const tituloSize = fontSize * 1.2;
        const metaSize = fontSize * 0.8;
        const textMaxHeight = Math.round(Math.max(80, cardWidth * 0.25));
        const capaHeight = Math.round(Math.min(cardWidth * 0.5625, maxCardHeight * 0.65));

        const root = document.documentElement;
        root.style.setProperty('--bt-card-width', cardWidth + 'px');
        root.style.setProperty('--bt-card-padding', cardPadding + 'px');
        root.style.setProperty('--bt-card-font-size', fontSize + 'rem');
        root.style.setProperty('--bt-titulo-size', tituloSize + 'rem');
        root.style.setProperty('--bt-meta-size', metaSize + 'rem');
        root.style.setProperty('--bt-text-max-height', textMaxHeight + 'px');
        root.style.setProperty('--bt-card-max-height', maxCardHeight + 'px');
        root.style.setProperty('--bt-capa-height', capaHeight + 'px');
        root.style.setProperty('--bt-container-height', Math.min(vh * 0.8, 800) + 'px');

        document.querySelectorAll('.bt-card').forEach(card => {
            card.style.width = cardWidth + 'px';
            card.style.maxWidth = cardWidth + 'px';
        });

        recalculando = false;
    };

    window.addEventListener('resize', function() {
        clearTimeout(layoutTimeout);
        layoutTimeout = setTimeout(window.btRecalcularLayout, 120);
    });
    window.addEventListener('orientationchange', function() {
        clearTimeout(layoutTimeout);
        layoutTimeout = setTimeout(window.btRecalcularLayout, 120);
    });

    // ============================================================
    // 2. TOGGLE FILTROS
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
    // 3. FUNÇÃO GLOBAL DE ENVIO DE RESPOSTA
    // ============================================================
    window.enviarRespostaEvento = function(eventoId, opcao, card) {
        const csrfToken = document.getElementById('csrf_token')?.value || '';
        if (!csrfToken) {
            console.error('[BALANGA] CSRF token não encontrado.');
            return;
        }

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
                if (data.csrf_token) {
                    document.querySelectorAll('input[name="csrf_token"]').forEach(inp => inp.value = data.csrf_token);
                }

                if (data.success) {
                    if (card && data.contagens) {
                        const vouSpan = card.querySelector('.bt-participacao span:nth-child(1)');
                        const naoVouSpan = card.querySelector('.bt-participacao span:nth-child(2)');
                        const talvezSpan = card.querySelector('.bt-participacao span:nth-child(3)');
                        if (vouSpan) vouSpan.innerHTML = '<i class="fas fa-user-check"></i> ' + (data.contagens.vou || 0) + ' Sim';
                        if (naoVouSpan) naoVouSpan.innerHTML = '<i class="fas fa-user-minus"></i> ' + (data.contagens.nao_vou || 0) + ' Não';
                        if (talvezSpan) talvezSpan.innerHTML = '<i class="fas fa-user-clock"></i> ' + (data.contagens.talvez || 0) + ' Talvez';
                    }

                    if (card) {
                        card.querySelectorAll('.bt-btn-resposta').forEach(b => {
                            b.classList.remove('ativo-vou', 'ativo-talvez', 'ativo-nao');
                        });
                        const btnMap = {
                            'vou': 'ativo-vou',
                            'talvez': 'ativo-talvez',
                            'nao_vou': 'ativo-nao'
                        };
                        const btnAtivo = card.querySelector(`.bt-btn-resposta[data-opcao="${opcao}"]`);
                        if (btnAtivo) btnAtivo.classList.add(btnMap[opcao]);
                    }

                    if (typeof exibirToast === 'function') {
                        exibirToast('Resposta registrada! 🎉');
                    }
                } else {
                    console.warn('[BALANGA] Erro ao registrar resposta:', data.message);
                }
            })
            .catch(err => {
                console.error('[BALANGA] Erro na requisição:', err);
            });
    };

    // ============================================================
    // 4. CARREGAR EVENTOS (AJAX)
    // ============================================================
    let btOffset = 0;
    let btCarregando = false;
    let btAcabou = false;
    const btnLoad = document.getElementById('bt-btn-load-more');
    const loadWrapper = document.getElementById('bt-load-more-wrapper');
    const container = document.getElementById('bt-container-eventos');
    const usuarioId = <?= (int)$_SESSION['usuario_id'] ?>;

    const presetBalanga = TranslateL.presets.balanga({
        hooks: {
            onLongPress: function(card, id, isOwner) {
                window.btMostrarMenuAcoes(card);
            }
        }
    });

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
        const comunidadeSelect = document.getElementById('bt-filtro-comunidade');
        const comunidadeId = comunidadeSelect ? comunidadeSelect.value : 0;

        fetch(`swipe-eventos.php?offset=${btOffset}&status=${status}&comunidade_id=${comunidadeId}`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    btAcabou = true;
                    // 🔥 FIX: Verifica se o container está vazio, e não o offset
                    if (container && container.querySelectorAll('.bt-card').length === 0) {
                        if (document.body.classList.contains('modo-tinder-ativo')) {
                            exibirRadarBalanga();
                        } else {
                            container.innerHTML = `
                <div class="bt-empty">
                    <i class="fas fa-calendar-plus"></i>
                    <strong>Nenhum evento encontrado!</strong>
                    <p>Que tal criar um evento para movimentar a Fenda?</p>
                    <button onclick="window.location.href='criar-evento.php'" class="bt-btn-empty">Criar Evento</button>
                </div>
            `;
                        }
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

                    // 🔥 PONTE: Injeta data-owner nos cards com base no data-criador
                    container.querySelectorAll('.bt-card').forEach(card => {
                        const criadorId = parseInt(card.dataset.criador || '0');
                        if (criadorId === usuarioId) {
                            card.setAttribute('data-owner', 'true');
                        } else {
                            card.setAttribute('data-owner', 'false');
                        }
                    });

                    if (window.TranslateL) {
                        TranslateL.rebind(container, presetBalanga);
                    }

                    if (typeof window.btRecalcularLayout === 'function') {
                        window.btRecalcularLayout();
                    }

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
    // 5. BOTÃO "EXIBIR MAIS" (grid)
    // ============================================================
    if (btnLoad) {
        btnLoad.addEventListener('click', function(e) {
            e.preventDefault();
            if (btCarregando || document.body.classList.contains('modo-tinder-ativo')) return;
            btCarregarEventos(false);
        });
    }

    // ============================================================
    // 6. RESPOSTAS RÁPIDAS NO MODO GRID
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
                if (data && typeof data.csrf_token === 'string' && data.csrf_token !== '') {
                    document.querySelectorAll('input[name="csrf_token"]').forEach(inp => {
                        inp.value = data.csrf_token;
                    });
                }

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
    // 7. RADAR "BUSCAR EVENTOS" + TRANSIÇÃO SUAVE
    // ============================================================
    function exibirRadarBalanga() {
        if (!document.body.classList.contains('modo-tinder-ativo')) return;
        if (container.querySelector('.bt-radar')) return;

        const radarHtml = `
            <div class="bt-radar">
                <i class="fas fa-search"></i>
                <strong>Fim da pilha!</strong>
                <p>Que tal buscar novos eventos?</p>
                <button onclick="btRecarregarPilha()" class="bt-btn-empty" style="border:none; cursor:pointer;">
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
    // 8. ABASTECER PILHA
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
    // 9. OBSERVADOR DE ALTERNÂNCIA GRID ↔ SWIPE
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
        if (typeof TranslateL !== 'undefined' && isSwipe) {
            TranslateL.rebind(container, presetBalanga);
        }
        if (typeof window.btRecalcularLayout === 'function') window.btRecalcularLayout();
    });
    observerModoTinder.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // ============================================================
    // 10. LONG PRESS – MENU DE AÇÕES
    // ============================================================
    let menuAberto = false;

    window.btMostrarMenuAcoes = function(card) {
        const eventoId = card.dataset.id;
        const criadorId = parseInt(card.dataset.criador || '0');
        const isOwner = (criadorId === usuarioId);

        if (!eventoId) return;
        if (menuAberto) return;
        // 🔥 CORREÇÃO: typeof de objeto, não função
        if (window.TranslateL && typeof window.TranslateL.stop === 'function') {
            window.TranslateL.stop(card);
        }

        const previouslyFocused = document.activeElement;
        const vw = window.innerWidth;
        const popupWidth = Math.min(Math.max(160, vw * 0.5), 320);
        const fontSize = Math.min(Math.max(0.8, vw * 0.015), 1.05);
        const btnPadding = Math.min(Math.max(6, vw * 0.015), 14);

        const overlay = document.createElement('div');
        overlay.className = 'bt-actions-overlay';
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
            z-index: 1000000; /* Z-INDEX ACIMA DO PORTAL (999999) */
            display: flex; align-items: center; justify-content: center; animation: fadeIn 0.2s ease;
        `;

        const menu = document.createElement('div');
        menu.className = 'bt-actions-popup';
        menu.style.cssText = `
            background: rgba(10, 10, 10, 0.95); border: 1px solid rgba(255,188,0,0.3); border-radius: 16px;
            padding: 8px 0; user-select: none; min-width: ${popupWidth}px; max-width: 85vw;
            box-shadow: 0 8px 25px rgba(0,0,0,0.6); animation: btPopupIn 0.2s ease-out; font-size: ${fontSize}rem;
            position: relative;
        `;

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

        // 🔥 Desliga o blur dos cards do fundo para performance
        document.body.classList.add('menu-aberto');

        menuAberto = true;

        // Focus Trap
        setTimeout(() => {
            const firstBtn = menu.querySelector('button');
            if (firstBtn) firstBtn.focus();
        }, 50);

        function fecharMenu(overlayEl) {
            if (overlayEl && overlayEl.parentNode) overlayEl.remove();

            // 🔥 Restaura o blur dos cards
            document.body.classList.remove('menu-aberto');

            menuAberto = false;
            if (card && window.TranslateL) TranslateL.clearLongPress(card);
            document.removeEventListener('keydown', trapFocus);
            if (previouslyFocused && previouslyFocused.focus) previouslyFocused.focus();
        }

        function trapFocus(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                fecharMenu(overlay);
                return;
            }
            if (e.key !== 'Tab') return;
            const focusable = Array.from(menu.querySelectorAll('button'));
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }
        document.addEventListener('keydown', trapFocus);

        menu.querySelectorAll('.bt-action-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                const acao = this.dataset.acao;
                fecharMenu(overlay);
                if (acao === 'detalhes') {
                    window.location.href = 'evento.php?id=' + eventoId;
                } else if (acao === 'favoritar') {
                    if (typeof exibirToast === 'function') exibirToast('📌 Evento salvo nos favoritos! (em breve)');
                    else alert('Funcionalidade em desenvolvimento.');
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
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    if (typeof exibirToast === 'function') exibirToast('🗑️ Evento cancelado com sucesso!', 'sucesso');
                                    else alert('Evento cancelado!');
                                    const cardEl = document.querySelector(`.bt-card[data-id="${eventoId}"]`);
                                    if (cardEl) cardEl.remove();
                                    if (typeof window.btAbastecerPilha === 'function') window.btAbastecerPilha();
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
            if (e.target === overlay) fecharMenu(overlay);
        });

        if (!document.getElementById('bt-popup-styles')) {
            const style = document.createElement('style');
            style.id = 'bt-popup-styles';
            style.textContent = `
                @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
                @keyframes btPopupIn { from { opacity:0; transform:scale(0.95) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
                .bt-actions-item:hover { background: rgba(255, 255, 255, 0.20); }
                .bt-action-item[data-acao="excluir"]:hover { background: rgba(255,50,50,0.12); color: #ff8a8a; }
                .bt-actions-popup { min-width: 200px; }
                @media (max-width: 480px) {
                    .bt-actions-popup { min-width: 140px; }
                    .bt-action-item { padding: 8px 12px; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // ============================================================
    // 11. LISTENER FILTRO COMUNIDADE E INICIALIZAÇÃO
    // ============================================================
    document.getElementById('bt-filtro-comunidade')?.addEventListener('change', function() {
        btCarregarEventos(true);
    });

    document.addEventListener('DOMContentLoaded', function() {
        btCarregarEventos(true);
        if (window.TranslateL) {
            TranslateL.init(container, presetBalanga);
        }
    });

    console.log('[BALANGA] Balanga Teras inicializado com sucesso (Fase 2.3 + Acessibilidade Corrigida)!');
</script>

<?php include 'includes/footer.php'; ?>