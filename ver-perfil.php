<?php
include 'conexao.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'includes/header.php';
include 'includes/navbar.php';

if (!isset($_GET['user'])) {
    header("Location: feed.php");
    exit();
}

$user_get = mysqli_real_escape_string($conn, $_GET['user']);
// BUSCA COMPLETA: Agora pegamos as preferências de visual
$sql = "SELECT * FROM usuarios WHERE username = '$user_get'";
$res = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($res);

if (!$dados) {
    echo "<main class= 'erro-fenda'><h2>Habitante não localizado, tente outro nome por favor! </h2></main>";
    include 'includes/footer.php';
    exit();
}

$id_visto = $dados['id'];
$meu_id = $_SESSION['usuario_id'];
$ja_segue = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'")) > 0;

// PREFERÊNCIAS DE AURA
$vibe_user = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
$cor_user  = $dados['pref_cor_padrao'] ?? '#ffffff';
$foto = !empty($dados['foto']) ? "uploads/" . $dados['foto'] : "imagensfoto/default.jpg";
$is_presenca = ($id_visto == 1);

$total_seguidores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'"))['total'];
?>

<style>
    /* Aplicando a cor e brilho dinâmico no Avatar */
    .avatar-main {
        border: 3px solid <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?> !important;
        box-shadow: 0 0 15px <?php echo $cor_user; ?>55; /* Cor com transparência */
    }
    
    <?php if ($is_presenca): ?>
    .avatar-main { box-shadow: 0 0 25px rgba(255, 188, 0, 0.7) !important; }
    <?php endif; ?>
</style>

<!-- Adicionamos a classe da Vibe e variavel de cor diretamente no container principal[cite: 17] -->
<main class="main-perfil-container-publico <?php echo $vibe_user; ?> <?php echo $is_presenca ? 'perfil-gold' : ''; ?>" 
      style="--aura-user: <?php echo $cor_user; ?>;">

    <div class="perfil-header-container">
        <div class="capa-container">
            <?php if (!empty($dados['capa'])): ?>
                <img src="uploads/<?php echo $dados['capa']; ?>" class="capa-img">
            <?php else: ?>
                <div class="capa-default" style="background: linear-gradient(135deg, <?php echo $cor_user; ?>88 0%, #000 100%); width: 100%; height: 100%;"></div>
            <?php endif; ?>

            <div class="avatar-posicionador">
                <img src="<?php echo $foto; ?>" class="avatar-main" alt="Avatar">
                <?php if ($is_presenca): ?>
                    <div class="badge-presenca-bottom"><i class="fa-solid fa-crown"></i></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-usuario-section">
            <div class="nome-linha">
                <h1 class="nome-publico"><?php echo htmlspecialchars($dados['nome']); ?></h1>
                <?php if (!empty($dados['atletica_id'])): ?>
                    <a href="atleticas.php?id=<?php echo urlencode($dados['atletica_id']); ?>">
                        <img src="badges/<?php echo htmlspecialchars($dados['atletica_id']); ?>.png" class="insignia-atletica-bottom">
                    </a>
                <?php endif; ?>
            </div>

            <div class="stats-perfil">
                <span style="color: <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?>; font-weight: bold;">
                    <?php echo $total_seguidores; ?> SEGUIDORES
                </span>
            </div>

            <div class="bio-texto">
                <?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'])) : "Habitante da Fenda..."; ?>
            </div>

            <div class="perfil-controles">
                <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
                    <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>"
                        class="btn-seguir-fenda <?php echo $ja_segue ? 'seguindo' : ''; ?>"
                        style="background: <?php echo $ja_segue ? 'transparent' : $cor_user; ?>; border-color: <?php echo $cor_user; ?>;">
                        <?php echo $ja_segue ? '<i class="fa-solid fa-check"></i> Seguindo' : '+ Seguir'; ?>
                    </a>
                <?php else: ?>
                    <a href="perfil.php" class="btn-editar-atalho">EDITAR MEU PERFIL</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ÁREA DO FEED PESSOAL -->
    <section class="feed-usuario-fenda" style="margin-top: 30px;">
        <h3 style="text-align: center; color: #ccc; margin-bottom: 20px;">ÚLTIMAS POSTAGENS DE @<?php echo strtoupper($user_get); ?></h3>
        <div class="container-feed">
            <!-- O Motor Universal vai preencher aqui -->
        </div>
        
        <div class="container-load-more" style="text-align: center; margin-top: 20px;">
            <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais</button>
        </div>
    </section>
</main>

<script>
    let offset = 0;
    const urlParams = new URLSearchParams(window.location.search);
    const usuarioAlvo = urlParams.get('user'); 
    const btnLoad = document.getElementById('btn-load-more');

    function carregarFeedPerfil() {
        if(btnLoad) btnLoad.innerText = "BUSCANDO NA FENDA...";
        
        // Chamada ao Motor Universal com filtro de perfil
        fetch(`motor-feed.php?offset=${offset}&tipo=perfil&user=${usuarioAlvo}`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if(btnLoad) btnLoad.style.display = "none";
                } else {
                    document.querySelector('.container-feed').insertAdjacentHTML('beforeend', data);
                    offset += 30;
                    if(btnLoad) btnLoad.innerText = "EXIBIR MAIS";
                }
            });
    }

    carregarFeedPerfil();

    if(btnLoad) {
        btnLoad.addEventListener('click', carregarFeedPerfil);
    }
</script>

<?php include 'includes/footer.php'; ?>