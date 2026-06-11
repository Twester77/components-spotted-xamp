<?php
include_once 'conexao.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<!-- Adicionado aria-label para indicar que este bloco é uma barra de ferramentas de controle -->
<div class="controles-feed-topo" role="toolbar" aria-label="Controles do Feed">
    <!-- Adicionado aria-expanded e aria-controls vinculando dinamicamente o botão à gaveta de filtros -->
    <button type="button" id="btn-abrir-filtros" class="btn-fenda-padrao" onclick="toggleFiltrosMobile()" aria-expanded="false" aria-controls="gaveta-filtros-swipe">
        <i class="fas fa-filter" aria-hidden="true"></i> CATEGORIAS
    </button>

    <!-- Adicionado aria-hidden e role para esconder a gaveta do leitor enquanto estiver fechada -->
    <div id="gaveta-filtros-swipe" class="filtros-wrapper-retratil" role="region" aria-label="Painel de Filtros por Categoria" aria-hidden="true">
        <?php include 'includes/filtros.php'; ?>
    </div>
</div>

<main class="main-fenda-total" id="conteudo-principal">
    <!-- Feedbacks visuais ocultados do leitor de tela para não causar poluição auditiva durante o arraste -->
    <div class="feedback-swipe feedback-direita" aria-hidden="true">🩶 AMEI</div>
    <div class="feedback-swipe feedback-esquerda" aria-hidden="true">🗑️ DESCARTAR</div>
    <div class="feedback-swipe feedback-cima" aria-hidden="true">💬 COMENTAR</div>
    <!-- O container começa vazio e o Motor preenche -->
    <!-- Adicionado aria-live para anunciar novos posts injetados dinamicamente via AJAX sem interromper a navegação -->
    <div class="container-feed" role="feed" aria-busy="false" aria-live="polite">
    </div>
</main>

<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais Resultados</button>
</div>

<script src="js/fenda-init.js"></script>

<script>
    // ==================== MOTOR DE LAYOUT UNIVERSAL (JS PURO) ====================
    // Compatível com qualquer navegador (inclusive iOS 7+, Android 4.4+)
    // NÃO usa dvh, clamp, CSS variables – apenas pixels diretos no style.
    function recalcFeedLayout() {
        // Só roda se o modo swipe estiver ativo
        if (!document.body.classList.contains('modo-swipe-ativo')) return;

        var vw = window.innerWidth;
        var vh = window.innerHeight;

        // Cálculos base – ajuste os fatores aqui conforme necessidade
        var cardWidth = Math.max(280, Math.min(vw * 0.85, 400)); // entre 280px e 400px
        var cardPadding = cardWidth * 0.05; // 5% da largura
        var fontSize = cardWidth * 0.05; // 5% da largura (base)
        var avatarSize = cardWidth * 0.12; // 12% da largura
        var maxCardHeight = vh * 0.80; // 80% da altura da tela

        // Aplica em todos os cards atuais (e futuros – chamamos novamente quando novos cards entram)
        var cards = document.querySelectorAll('.feed-empilhado .spotted-card');
        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            card.style.width = cardWidth + 'px';
            card.style.padding = cardPadding + 'px';
            card.style.maxHeight = maxCardHeight + 'px';
            card.style.fontSize = fontSize + 'px';
            // Remove qualquer altura fixa que possa ter sido herdada
            card.style.height = 'auto';
            card.style.overflowY = 'auto';

            // ... dentro do seu loop for (let i = 0; i < cards.length; i++) {
            var card = cards[i];
            var imgContainer = card.querySelector('.container-img-post');
            var img = imgContainer ? imgContainer.querySelector('img') : null;

            if (img && imgContainer) {
                if (vw > vh) {
                    // LANDSCAPE: Foca em mostrar a imagem toda (Contain)
                    img.style.objectFit = 'contain';
                    img.style.maxHeight = (maxCardHeight * 0.6) + 'px';
                    // O segredo do "cinema": fundo preto para não ficar "buraco" branco
                    imgContainer.style.background = '#000';
                } else {
                    // PORTRAIT: Preenche o card (Cover)
                    img.style.objectFit = 'cover';
                    img.style.maxHeight = 'none';
                    imgContainer.style.background = 'transparent'; // Fundo transparente para o portrait
                }
            }

        }

        // Ajusta avatares (se existirem)
        var avatares = document.querySelectorAll('.feed-empilhado .spotted-card .avatar-p, .feed-empilhado .spotted-card .avatar');
        for (var j = 0; j < avatares.length; j++) {
            avatares[j].style.width = avatarSize + 'px';
            avatares[j].style.height = avatarSize + 'px';
        }
    }

    // Debounce para não recalc a cada pixel durante redimensionamento
    var layoutTimeout;

    function debounceLayout() {
        clearTimeout(layoutTimeout);
        layoutTimeout = setTimeout(recalcFeedLayout, 150);
    }

    // Eventos que disparam o recálculo
    window.addEventListener('load', recalcFeedLayout);
    window.addEventListener('resize', debounceLayout);
    window.addEventListener('orientationchange', debounceLayout);

    // Função auxiliar para ser chamada após inserir novos cards (via AJAX)
    function reforcarLayoutNosCards() {
        recalcFeedLayout();
    }

    // ==================== TOGGLE FILTROS (mantido original) ====================
    window.toggleFiltrosMobile = function() {
        const gaveta = document.getElementById('gaveta-filtros-swipe');
        const btn = document.getElementById('btn-abrir-filtros');
        if (gaveta) {
            gaveta.classList.toggle('aberto');
            if (gaveta.classList.contains('aberto')) {
                btn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i> FECHAR FILTROS';
                btn.setAttribute('aria-expanded', 'true');
            } else {
                btn.innerHTML = '<i class="fas fa-filter" aria-hidden="true"></i> FILTRAR CATEGORIAS';
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    };

    // ==================== GERENCIAMENTO DO FEED (AJAX) ====================
    let offset = 0;
    let radarBloqueado = false;
    const btnLoad = document.getElementById('btn-load-more') || document.getElementById('btn-load-mais');
    const feedContainer = document.querySelector('.container-feed');

    function carregarFeedGeral() {
        if (feedContainer) feedContainer.setAttribute('aria-busy', 'true');

        const urlParams = new URLSearchParams(window.location.search);
        const categoria = urlParams.get('categoria') || '';

        fetch(`motor-feed.php?offset=${offset}&categoria=${categoria}&tipo=geral`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if (offset === 0 && feedContainer) {
                        feedContainer.innerHTML = "<p style='text-align:center; color:#ccc;'>Nenhum post encontrado.</p>";
                    }
                    if (btnLoad) {
                        btnLoad.innerText = "FIM DO FEED";
                        btnLoad.disabled = true;
                    }
                } else {
                    if (feedContainer) {
                        if (offset === 0) feedContainer.innerHTML = '';
                        feedContainer.insertAdjacentHTML('beforeend', data);
                        // 🔥 APÓS INSERIR NOVOS CARDS, APLICA O LAYOUT INLINE
                        reforcarLayoutNosCards();
                    }
                    offset += 10;
                }
                if (feedContainer) feedContainer.setAttribute('aria-busy', 'false');
                setTimeout(() => {
                    radarBloqueado = false;
                }, 1500);
            })
            .catch(err => {
                console.error("[AJAX] Erro no feed:", err);
                radarBloqueado = false;
            });
    }

    // ==================== REEMPILHAMENTO E RESET DO SWIPE ====================
    window.abastecerPilhaFenda = function() {
        if (!feedContainer) return;
        const cardsRestantes = feedContainer.querySelectorAll('.spotted-card').length;

        if (cardsRestantes <= 4) {
            if (btnLoad && !btnLoad.disabled) {
                carregarFeedGeral();
            } else if (cardsRestantes === 0) {
                feedContainer.innerHTML = `
                    <div class="fim-dos-cards-vibe" style="text-align:center; padding:40px 20px; color:#ccc;">
                        <i class="fas fa-ghost" style="font-size:3rem; color:#ff8c00; margin-bottom:15px; display:block;"></i>
                        <strong style="font-size:1.2rem; display:block; margin-bottom:5px; color:#fff;">A Fenda foi Limpa!</strong>
                        <p style="font-size:0.9rem; color:#888; margin-bottom:20px;">Você leu tudo por aqui ou o feed chegou ao fim.</p>
                        <button onclick="window.reiniciarPilhaFenda()" class="btn-fenda-padrao" style="background:#ff8c00; color:#fff; border:none; padding:10px 20px; border-radius:20px; cursor:pointer; font-weight:bold;">
                            <i class="fas fa-sync-alt" style="margin-right:8px;"></i> RADAR DE MARACUTAIA
                        </button>
                    </div>
                `;
            }
        }
    };

    window.reiniciarPilhaFenda = function() {
        if (radarBloqueado) {
            console.log("[RADAR] Calma lá, muito rolo estraga o feed!");
            return;
        }
        radarBloqueado = true;

        offset = 0;
        if (feedContainer) {
            feedContainer.innerHTML = `
                <div style="text-align:center; padding:40px; color:#ff8c00;">
                    <i class="fas fa-sync-alt fa-spin" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p style="color:#fff;">Escaneando novas maracutaias...</p>
                </div>
            `;
        }
        carregarFeedGeral();
    };

    // ==================== ENGINE DE REAÇÕES ISOLADA (SEM CONFLITO) ====================
    document.addEventListener('click', function(e) {
        const btnReagir = e.target.closest('.btn-reagir');
        if (btnReagir) {
            e.stopPropagation();
            const wrapper = btnReagir.closest('.reacao-wrapper');
            document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
                if (w !== wrapper) w.classList.remove('popup-ativo');
            });
            wrapper.classList.toggle('popup-ativo');
            return;
        }
        if (e.target.closest('.reacoes-popup')) {
            return;
        }
        document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
            w.classList.remove('popup-ativo');
        });
    });

    window.enviarReacao = function(postId, tipoReacao) {
        const tradutorEmojis = {
            'amei': '💖',
            'perplecto': '😲',
            'haha': '😂',
            'ranco': '🙄',
            'forca': '🫂',
            'triste': '😢',
            'tendi-nada': '🤔'
        };
        fetch(`includes/reagir.php?id=${postId}&tipo=${tipoReacao}`)
            .then(response => {
                if (response.status === 429) {
                    alert("Calma lá! O motor da Fenda precisa respirar.");
                    throw new Error("Rate limit exceeded");
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    const containerReacoes = document.getElementById(`reacoes-post-${postId}`);
                    if (!containerReacoes) return;
                    containerReacoes.innerHTML = '';
                    Object.keys(data.contagens).forEach(tipo => {
                        const total = data.contagens[tipo];
                        if (total > 0) {
                            const emoji = tradutorEmojis[tipo] || '👍';
                            const classeVoted = data.minhas_reacoes.includes(tipo) ? 'voted' : '';
                            containerReacoes.insertAdjacentHTML('beforeend',
                                `<span class="reacao-item ${classeVoted}">${emoji} ${total}</span>`
                            );
                        }
                    });
                }
            })
            .catch(err => {
                if (err.message !== "Rate limit exceeded") {
                    console.error("[AJAX Error]", err);
                }
            });
    };

    // Chute inicial
    document.addEventListener("DOMContentLoaded", function() {
        carregarFeedGeral();
    });




</script>

<?php include 'includes/footer.php'; ?>