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

<div class="controles-feed-topo">
    <button type="button" id="btn-abrir-filtros" class="btn-fenda-padrao" onclick="toggleFiltrosMobile()">
        <i class="fas fa-filter"></i> FILTRAR CATEGORIAS
    </button>

    <div id="gaveta-filtros-swipe" class="filtros-wrapper-retratil">
        <?php include 'includes/filtros.php'; ?>
    </div>
</div>

    
<main class="main-fenda-total">
    <!-- O container começa vazio e o Motor preenche -->
    <div class="container-feed"></div>
    <!-- Feedback visual para o modo swipe (aparece ao arrastar) -->
<div class="feedback-swipe feedback-direita">🩶 AMEI</div>
<div class="feedback-swipe feedback-esquerda">🗑️ DESCARTAR</div>
<div class="feedback-swipe feedback-cima">💬 COMENTAR</div>
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
        
        // Muda o ícone do botão para dar feedback
        if (gaveta.classList.contains('aberto')) {
            btn.innerHTML = '<i class="fas fa-times"></i> FECHAR FILTROS';
        } else {
            btn.innerHTML = '<i class="fas fa-filter"></i> FILTRAR CATEGORIAS';
        }
    }
};

    let offset = 0; // Começa do zero agora
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    function carregarFeedGeral() {
        if(btnLoad) btnLoad.innerText = "CARREGANDO...";
        
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
            });
    }

    // Carrega os primeiros posts assim que abre
    carregarFeedGeral();

    if (btnLoad) {
        btnLoad.addEventListener('click', carregarFeedGeral);
    }
</script>

<?php include 'includes/footer.php'; ?>