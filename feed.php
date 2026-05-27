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

<script src="js/fenda-swipe.js"></script>

<script>

    // ==================== VIEWPORT DINÂMICA (MOBILE) ====================
    function setDynamicVh() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    setDynamicVh();
    window.addEventListener('resize', setDynamicVh);
    window.addEventListener('orientationchange', setDynamicVh);

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
    let radarBloqueado = false; // 🛡️ Trava Anti-Grosope global
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
                    }
                    offset += 10;
                }
                if (feedContainer) feedContainer.setAttribute('aria-busy', 'false');
                
                // Libera o Radar de Maracutaias após a resposta do servidor
                setTimeout(() => { radarBloqueado = false; }, 1500);
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
            } 
            else if (cardsRestantes === 0) {
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
            console.log("[RADAR] Calma lá, maracutaia demais engasga o Uno!");
            return;
        }
        radarBloqueado = true; // Ativa o escudo anti-spam temporário
        
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
    // 1. Abre/Fecha o Popup
    const btnReagir = e.target.closest('.btn-reagir');
    if (btnReagir) {
        e.stopPropagation(); // Impede que o clique suba e feche o popup imediatamente
        const wrapper = btnReagir.closest('.reacao-wrapper');
        
        // Fecha outros
        document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
            if (w !== wrapper) w.classList.remove('popup-ativo');
        });

        wrapper.classList.toggle('popup-ativo');
        return;
    }

    // 2. Se clicar DENTRO da área do popup, NÃO FECHA (importante!)
    if (e.target.closest('.reacoes-popup')) {
        return; 
    }

    // 3. Se clicar em qualquer outro lugar, fecha tudo
    document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
        w.classList.remove('popup-ativo');
    });
});

// Função global que recebe o clique do emoji do motor-feed.php
window.enviarReacao = function(postId, tipoReacao) {
    const tradutorEmojis = {
        'amei': '💖', 'perplecto': '😲', 'haha': '😂', 
        'ranco': '🙄', 'forca': '🫂', 'triste': '😢', 'tendi-nada': '🤔'
    };

    // Aponta direto para a pasta includes/ onde está o reagir.php
    fetch(`includes/reagir.php?id=${postId}&tipo=${tipoReacao}`)
        .then(response => {
            if (response.status === 429) {
                alert("Calma lá! O motor do Uno precisa respirar.");
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