<?php
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Pegando dados básicos para a gaveta
$meu_id_sessao = $_SESSION['usuario_id'];
$query_user = mysqli_query($conn, "SELECT username, foto, pref_cor_padrao FROM usuarios WHERE id = '$meu_id_sessao'");
$dados_user = mysqli_fetch_assoc($query_user);
$foto_perfil = !empty($dados_user['foto']) ? "uploads/".$dados_user['foto'] : "img/default-avatar.png";
$cor_aura = $dados_user['pref_cor_padrao'] ?? '#ffbc00';
?>

<div class="painel-controle-container">
    
    <aside class="sidebar-fenda" id="gaveta-pessoal">
        <div class="perfil-resumo-gaveta" style="border-bottom: 1px solid <?php echo $cor_aura; ?>55;">
            <img src="<?php echo $foto_perfil; ?>" class="avatar-gaveta" style="border: 2px solid <?php echo $cor_aura; ?>;">
            <span style="color: <?php echo $cor_aura; ?>;">@<?php echo $dados_user['username']; ?></span>
        </div>

        <nav class="menu-gaveta">
            <a href="feed.pessoal.php" class="item-menu active"><i class="fas fa-comment-dots"></i> Meus Posts</a>
            <a href="ver-perfil.php?user=<?php echo $dados_user['username']; ?>" class="item-menu"><i class="fas fa-eye"></i> Ver meu Perfil</a>
            <a href="configuracoes.php" class="item-menu"><i class="fas fa-user-edit"></i> Editar Aura</a>
            <hr style="opacity: 0.1; margin: 15px 0;">
            <p style="font-size: 10px; color: #555; text-transform: uppercase; margin-left: 10px;">Em breve</p>
            <a href="#" class="item-menu disabled" style="opacity: 0.4; cursor: not-allowed;"><i class="fas fa-store"></i> Marketplace</a>
            <a href="#" class="item-menu disabled" style="opacity: 0.4; cursor: not-allowed;"><i class="fas fa-home"></i> Repúblicas</a>
        </nav>
    </aside>

    <main class="conteudo-painel">
        <div class="container-feed">
            <h2 class="titulo-sessao-pessoal">🚩 MEUS DESABAFOS</h2>
            </div>

        <div class="container-load-more">
            <button id="btn-load-more" class="btn-fenda-padrao">Explorar mais registros</button>
        </div>
    </main>
</div>

<script>
    // O seu script de fetch continua o mesmo, 
    // mas agora ele chama o tipo "pessoal" para filtrar os posts do usuário logado
    let offset = 0;
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    function carregarMeuFeed() {
        fetch(`motor-feed.php?offset=${offset}&tipo=pessoal`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if(offset === 0) feedContainer.innerHTML += "<p style='text-align:center; padding: 20px;'>A Fenda ainda não recebeu ecos seus...</p>";
                    btnLoad.style.display = "none";
                } else {
                    feedContainer.insertAdjacentHTML('beforeend', data);
                    offset += 30;
                }
            });
    }

    carregarMeuFeed();
    btnLoad.addEventListener('click', carregarMeuFeed);
</script>

<?php include 'includes/footer.php'; ?>