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
    // Atualiza a variável --vh com a altura real da viewport (mobile)
    function setDynamicVh() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    setDynamicVh();
    window.addEventListener('resize', setDynamicVh);
    window.addEventListener('orientationchange', setDynamicVh);
    console.log("[UI] --vh dinâmico configurado.");
    window.toggleFiltrosMobile = function() {
        const gaveta = document.getElementById('gaveta-filtros-swipe');
        const btn = document.getElementById('btn-abrir-filtros');

        if (gaveta) {
            gaveta.classList.toggle('aberto');

            // Muda o ícone do botão e atualiza os estados ARIA em tempo real
            if (gaveta.classList.contains('aberto')) {
                btn.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i> FECHAR FILTROS';
                btn.setAttribute('aria-expanded', 'true');
                gaveta.setAttribute('aria-hidden', 'false');
            } else {
                btn.innerHTML = '<i class="fas fa-filter" aria-hidden="true"></i> FILTRAR CATEGORIAS';
                btn.setAttribute('aria-expanded', 'false');
                gaveta.setAttribute('aria-hidden', 'true');
            }
        }
    };


    let offset = 0; // Começa do zero agora
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    function carregarFeedGeral() {
        if (btnLoad) btnLoad.innerText = "CARREGANDO...";
        // Atualiza que o container está recebendo dados novos
        if (feedContainer) feedContainer.setAttribute('aria-busy', 'true');

        const urlParams = new URLSearchParams(window.location.search);
        const categoria = urlParams.get('categoria') || '';

        fetch(`motor-feed.php?offset=${offset}&categoria=${categoria}&tipo=geral`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if (offset === 0) feedContainer.innerHTML = "<p style='text-align:center; color:#ccc;'>Nenhum post encontrado.</p>";
                    if (btnLoad) {
                        btnLoad.innerText = "FIM DO FEED";
                        btnLoad.disabled = true;
                    }
                } else {
                    feedContainer.insertAdjacentHTML('beforeend', data);
                    offset += 10;
                    if (btnLoad) btnLoad.innerText = "EXIBIR MAIS RESULTADOS";
                }
                // Concluiu a injeção do HTML do feed
                if (feedContainer) feedContainer.setAttribute('aria-busy', 'false');
            });
    }

   // ==================== REEMPILHAMENTO E RESET DO SWIPE ====================
window.abastecerPilhaFenda = function() {
    const cardsRestantes = feedContainer.querySelectorAll('.spotted-card').length;
    console.log(`[SWIPE] Cards restantes na pilha: ${cardsRestantes}`);
    
    // 🔥 Mudamos de 2 para 4: o AJAX acorda muito mais cedo!
    if (cardsRestantes <= 4) {
        if (btnLoad && !btnLoad.disabled) {
            console.log("[SWIPE] Pilha baixa detectada. Antecipando recarga via AJAX...");
            carregarFeedGeral();
        } 
        // Só mostra a tela de vazio se zerar de ABSOLUTO fato
        else if (cardsRestantes === 0) {
            feedContainer.innerHTML = `
                <div class="fim-dos-cards-vibe" style="text-align:center; padding:40px 20px; color:#ccc;">
                    <i class="fas fa-ghost" style="font-size:3rem; color:#ff8c00; margin-bottom:15px; display:block;"></i>
                    <strong style="font-size:1.2rem; display:block; margin-bottom:5px; color:#fff;">A Fenda foi Limpa!</strong>
                    <p style="font-size:0.9rem; color:#888; margin-bottom:20px;">Você leu tudo por aqui ou o feed chegou ao fim.</p>
                    
                    <button onclick="window.reiniciarPilhaFenda()" class="btn-fenda-padrao" style="background:#ff8c00; color:#fff; border:none; padding:10px 20px; border-radius:20px; cursor:pointer; font-weight:bold;">
                        <i class="fas fa-sync-alt" style="margin-right:8px;"></i> REBOBINAR PILHA
                    </button>
                </div>
            `;
            if(btnLoad) btnLoad.style.display = 'none';
        }
    }
};

    // Carrega os primeiros posts assim que abre
    carregarFeedGeral();
</script>

<?php include 'includes/footer.php'; ?>