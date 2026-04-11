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
    echo "<main class='erro-fenda'><h2>Habitante não localizado! 🌊</h2></main>";
    include 'includes/footer.php';
    exit();
}

$id_visto = $dados['id'];
$meu_id = $_SESSION['usuario_id'];
$ja_segue = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'")) > 0;
$foto = !empty($dados['foto']) ? "uploads/" . $dados['foto'] : "imagensfoto/default.jpg";
$total_seguidores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'"))['total'];

// Easter Egg da Presença (ID 1)
$is_presenca = ($id_visto == 1);
?>

<style>
    /* Estilos específicos para esta  estrutura */
    .perfil-header-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
    }

    .capa-container {
        width: 100%;
        height: 250px;
        position: relative;
        background: #000;
        overflow: visible;
    }

    .capa-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-posicionador {
        position: absolute;
        bottom: -60px;
        left: 50%;
        transform: translateX(-50%);
        width: 130px;
        height: 130px;
    }

    .avatar-main {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid <?php echo $is_presenca ? 'var(--dourado)' : '#fff'; ?>;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    /* O BOTTOM DA PRESENÇA (Coroinha) */
    .badge-presenca-bottom {
        position: absolute;
        top: 5px;
        right: 5px;
        background: var(--dourado);
        color: #000;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        border: 2px solid #000;
        box-shadow: 0 0 10px rgba(255, 188, 0, 0.6);
        z-index: 15;
    }

    .info-usuario-section {
        margin-top: 70px;
        text-align: center;
        width: 90%;
        max-width: 600px;
    }

    .nome-linha {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 5px;
    }

    .nome-publico {
        font-size: 1.8rem;
        margin: 0;
        color: #fff;
    }

    .bio-texto {
        margin: 15px 0;
        color: #ccc;
        font-style: italic;
        line-height: 1.4;
    }

    .btn-acao-perfil {
        margin-top: 10px;
        padding: 10px 25px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
        display: inline-block;
    }
</style>

<main class="main-perfil-container <?php echo $is_presenca ? 'perfil-gold' : ''; ?>">

    <div class="perfil-header-container">
        <div class="capa-container">
            <?php if (!empty($dados['capa'])): ?>
                <img src="uploads/<?php echo $dados['capa']; ?>" class="capa-img">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #004a8f 0%, #00a896 100%);"></div>
            <?php endif; ?>

            <div class="avatar-posicionador">
                <img src="<?php echo $foto; ?>" class="avatar-main">

                <?php if ($is_presenca): ?>
                    <div class="badge-presenca-bottom" title="A Presença 👑">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-usuario-section">
            <div class="nome-linha">
                <h2 class="nome-publico"><?php echo htmlspecialchars($dados['nome']); ?></h2>

                <?php if (!empty($dados['atletica_id'])): ?>
                    <a href="atletica.php?id=<?php echo urlencode($dados['atletica_id']); ?>">
                        <img src="badges/<?php echo htmlspecialchars($dados['atletica_id']); ?>.png"
                            class="insignia-atletica-bottom"
                            title="Ver comunidade da Atlética">
                    </a>
                <?php endif; ?>
            </div>

            <div class="stats-perfil">
                <span style="color: var(--dourado); font-weight: bold; font-size: 0.9rem;">
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
    </div>

</main>

<?php include 'includes/footer.php'; ?>