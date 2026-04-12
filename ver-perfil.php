<?php
session_start();
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';

if (!isset($_GET['user'])) {
    header("Location: feed.php");
    exit();
}

$user_get = mysqli_real_escape_string($conn, $_GET['user']);
$sql = "SELECT * FROM usuarios WHERE username = '$user_get'";
$res = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($res);

if (!$dados) {
    echo "<main class='erro-fenda'><h2>Habitante não localizado! </h2></main>";
    include 'includes/footer.php';
    exit();
}

$id_visto = $dados['id'];
$meu_id = $_SESSION['usuario_id'];
$ja_segue = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'")) > 0;
$foto = !empty($dados['foto']) ? "uploads/" . $dados['foto'] : "imagensfoto/default.jpg";
$total_seguidores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'"))['total'];


$is_presenca = ($id_visto == 1);
?>

<style>
    /* Mantemos apenas o estritamente dinâmico */
    .avatar-main {
        border-color: <?php echo $is_presenca ? 'var(--dourado)' : '#fff'; ?> !important;
    }
    <?php if ($is_presenca): ?>
    .avatar-main {
        box-shadow: 0 0 20px rgba(255, 188, 0, 0.5) !important;
    }
    <?php endif; ?>
</style>

<main class="main-perfil-container <?php echo $is_presenca ? 'perfil-gold' : ''; ?>

    <div class="perfil-header-container">
        
        <div class="capa-container">
            <?php if (!empty($dados['capa'])): ?>
                <img src="uploads/<?php echo $dados['capa']; ?>" class="capa-img">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #004a8f 0%, #00a896 100%);"></div>
            <?php endif; ?>

            <div class="avatar-posicionador">
                <img src="<?php echo $foto; ?>" class="avatar-main" alt="Avatar">
                <?php if ($is_presenca): ?>
                    <div class="badge-presenca-bottom">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-usuario-section">
            <div class="nome-linha">
                <h1 class="nome-publico"><?php echo htmlspecialchars($dados['nome']); ?></h1>
                <?php if (!empty($dados['atletica_id'])): ?>
                    <a href="atleticas.php?id=<?php echo urlencode($dados['atletica_id']); ?>">
                        <img src="badges/<?php echo htmlspecialchars($dados['atletica_id']); ?>.png"
                             class="insignia-atletica-bottom"
                             title="Ver comunidade da Atlética">
                    </a>
                <?php endif; ?>
            </div>

            <div class="stats-perfil">
                <span style="color: var(--dourado); font-weight: bold;">
                    <?php echo $total_seguidores; ?> SEGUIDORES
                </span>
            </div>

            <div class="bio-texto">
                <?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'])) : "Habitante da Fenda..."; ?>
            </div>

            <div class="perfil-controles">
                <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
                    <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>"
                        class="btn-seguir-fenda <?php echo $ja_segue ? 'seguindo' : ''; ?>">
                        <?php echo $ja_segue ? '<i class="fa-solid fa-check"></i> Seguindo' : '+ Seguir'; ?>
                    </a>
                <?php else: ?>
                    <a href="perfil.php" class="btn-editar-atalho">EDITAR MEU PERFIL</a>
                <?php endif; ?>
            </div>
        </div>

    </div> </main>

<?php include 'includes/footer.php'; ?>