<?php
include 'conexao.php';[cite: 9]
include 'includes/header.php';
include 'includes/navbar.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
?>

<main class="main-fenda-total">
    <div class="container-feed">
        <h2 style="color: #ffbc00; text-align: center; margin-bottom: 20px;">🚩 Meus Desabafos</h2>
        <!-- O motor vai jogar os posts aqui dentro -->
    </div>
</main>

<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">Carregar mais meus posts</button>
</div>

<script>
    let offset = 0; // Começa do zero para carregar os primeiros
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    function carregarMeuFeed() {
        // AQUI ESTÁ O SEGREDO: tipo=pessoal
        fetch(`motor-feed.php?offset=${offset}&tipo=pessoal`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if(offset === 0) feedContainer.innerHTML = "<p style='text-align:center;'>Você ainda não postou nada.</p>";
                    btnLoad.style.display = "none";
                } else {
                    feedContainer.insertAdjacentHTML('beforeend', data);
                    offset += 30;
                }
            });
    }

    // Carrega os primeiros posts assim que a página abrir
    carregarMeuFeed();

    btnLoad.addEventListener('click', carregarMeuFeed);
</script>

<?php include 'includes/footer.php'; ?>