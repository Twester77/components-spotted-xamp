<?php
include_once 'conexao.php'; 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

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
        <i class="fas fa-filter" aria-hidden="true"></i> FILTRAR CATEGORIAS
    </button>

    <!-- Adicionado aria-hidden e role para esconder a gaveta do leitor enquanto estiver fechada -->
    <div id="gaveta-filtros-swipe" class="filtros-wrapper-retratil" role="region" aria-label="Painel de Filtros por Categoria" aria-hidden="true">
        <?php include 'includes/filtros.php'; ?>
    </div>
</div>

<main class="main-fenda-total" id="conteudo-principal">
    <!-- O container começa vazio e o Motor preenche -->
    <!-- Adicionado aria-live para anunciar novos posts injetados dinamicamente via AJAX sem interromper a navegação -->
    <div class="container-feed" role="feed" aria-busy="false" aria-live="polite"></div>
    
    <!-- Feedbacks visuais ocultados do leitor de tela para não causar poluição auditiva durante o arraste -->
    <div class="feedback-swipe feedback-direita" aria-hidden="true">🩶 AMEI</div>
    <div class="feedback-swipe feedback-esquerda" aria-hidden="true">🗑️ DESCARTAR</div>
    <div class="feedback-swipe feedback-cima" aria-hidden="true">💬 COMENTAR</div>
</main>

<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais Resultados</button>
</div>

<script>
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
        if(btnLoad) btnLoad.innerText = "CARREGANDO...";
        // Atualiza que o container está recebendo dados novos
        if(feedContainer) feedContainer.setAttribute('aria-busy', 'true');
        
        const urlParams = new URLSearchParams(window.location.search);
        const categoria = urlParams.get('categoria') || '';

        fetch(`motor-feed.php?offset=${offset}&categoria=${categoria}&tipo=geral`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if(offset === 0) feedContainer.innerHTML = "<p style='text-align:center; color:#ccc;'>Nenhum post encontrado.</p>";
                    if(btnLoad) {
                        btnLoad.innerText = "FIM DO FEED";
                        btnLoad.disabled = true;
                    }
                } else {
                    feedContainer.insertAdjacentHTML('beforeend', data);
                    offset += 30;
                    if(btnLoad) btnLoad.innerText = "EXIBIR MAIS RESULTADOS";
                }
                // Concluiu a injeção do HTML do feed
                if(feedContainer) feedContainer.setAttribute('aria-busy', 'false');
            });
    }

    // Carrega os primeiros posts assim que abre
    carregarFeedGeral();

    if (btnLoad) {
        btnLoad.addEventListener('click', carregarFeedGeral);
    }
</script>

<?php include 'includes/footer.php'; ?>
