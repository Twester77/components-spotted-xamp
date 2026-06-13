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
        var maxCardHeight = vh * 0.85; // 80% da altura da tela

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

    window.mostrarMenuAcoes = function(postId, isOwner, cardElement) {
    // Se já houver um modal aberto, não cria outro
    if (window._activeModal) return;
    
    // Guarda a referência do card para remover o efeito depois
    const targetCard = cardElement || null;
    
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(8px);
        z-index: 30000;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 15%;
        font-family: 'Inter', system-ui, sans-serif;
    `;
    
    const modal = document.createElement('div');
    modal.style.cssText = `
        background: rgba(20, 20, 32, 0.92);
        backdrop-filter: blur(20px);
        border-radius: 28px;
        padding: 28px 24px;
        width: 90%;
        max-width: 450px;
        text-align: center;
        border: 1px solid rgba(255, 140, 0, 0.5);
        box-shadow: 0 25px 45px rgba(0,0,0,0.4);
    `;
    
    const title = document.createElement('div');
    title.textContent = isOwner ? ' GERENCIAR POST ' : ' SINALIZAR POST ';
    title.style.cssText = `font-size:0.85rem; letter-spacing:2px; color:#ffbc00; margin-bottom:20px; text-transform:uppercase; font-weight:600;`;
    modal.appendChild(title);
    
    const buttonStyle = `
        background: rgba(255,255,255,0.05);
        border: none;
        border-radius: 60px;
        padding: 12px 20px;
        margin: 10px 0;
        color: #fff;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        width: 100%;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    `;
    
    if (isOwner) {
        const btnDelete = document.createElement('button');
        btnDelete.innerHTML = '🗑️ Expurgar da Fenda';
        btnDelete.style.cssText = buttonStyle + `background: rgba(220,53,69,0.15); border:1px solid rgba(220,53,69,0.5); color:#ff8a8a;`;
        btnDelete.onclick = () => {
            if (confirm("⚠️ Isso removerá o post permanentemente. Continuar?")) {
                window.location.href = `includes/excluir.php?id=${postId}`;
            }
            closeGlobalModal();
        };
        modal.appendChild(btnDelete);
    } else {
        const btnReport = document.createElement('button');
        btnReport.innerHTML = '🚨 Chamar o Camburão';
        btnReport.style.cssText = buttonStyle + `background: rgba(255,188,0,0.12); border:1px solid rgba(255,188,0,0.5); color:#ffde9e;`;
        btnReport.onclick = () => {
            alert("📢 Agradecemos o aviso! A moderação foi notificada.");
            closeGlobalModal();
        };
        modal.appendChild(btnReport);
    }
    
    const btnClose = document.createElement('button');
    btnClose.innerHTML = '✖️ Voltar ao Feed';
    btnClose.style.cssText = buttonStyle + `background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.2); color:#ccc;`;
    btnClose.onclick = closeGlobalModal;
    modal.appendChild(btnClose);
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    window._activeModal = overlay;
    
    function closeGlobalModal() {
        if (window._activeModal) {
            window._activeModal.remove();
            window._activeModal = null;
        }
        // 🔥 Remove o efeito Prisma do card que abriu o modal
        if (targetCard && targetCard.classList) {
            targetCard.classList.remove('card-long-press-active');
        }
    }
    
    // Fechar ao clicar fora
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeGlobalModal();
    });
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

    window.enviarReacao = async function(postId, tipoReacao) {
    const tradutorEmojis = {
        'amei': '💖',
        'perplecto': '😲',
        'haha': '😂',
        'ranco': '🙄',
        'forca': '🫂',
        'triste': '😢',
        'tendi-nada': '🤔'
    };
    
    try {
        const response = await fetch(`includes/reagir.php?id=${postId}&tipo=${tipoReacao}`);
        
        if (response.status === 429) {
            alert("Calma lá! O motor da Fenda precisa respirar.");
            throw new Error("Rate limit exceeded");
        }
        
        const data = await response.json();
        
        if (data.status === 'success') {
            const containerReacoes = document.getElementById(`reacoes-post-${postId}`);
            if (containerReacoes) {
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
        }
        return data; // retorna o resultado para quem chamou (opcional)
    } catch (err) {
        if (err.message !== "Rate limit exceeded") {
            console.error("[AJAX Error]", err);
        }
        throw err; // repassa o erro para o chamador lidar
    }
};

    // Chute inicial
    document.addEventListener("DOMContentLoaded", function() {
        carregarFeedGeral();
    });

</script>

<?php include 'includes/footer.php'; ?>