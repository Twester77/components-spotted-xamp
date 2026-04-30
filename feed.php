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

<div style="margin-top: 30px;">
    <?php include 'includes/filtros.php'; ?>
</div>

<main class="main-fenda-total">
    <!-- O container começa vazio e o Motor preenche -->
     <!-- O CARD DE POSTAGEM ENTRA AQUI -->
    <?php include 'includes/card-postar.php'; ?>
    <div class="container-feed"></div>
</main>

<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais Resultados</button>
</div>

<script>
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