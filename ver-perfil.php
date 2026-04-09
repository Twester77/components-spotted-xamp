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
$meu_id = $_SESSION['usuario_id']; // ID de quem está logado

// Verifica se eu já sigo esse habitante
$sql_check_segue = mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'");
$ja_segue = mysqli_num_rows($sql_check_segue) > 0;
$foto = !empty($dados['foto']) ? "uploads/".$dados['foto'] : "imagensfoto/default.jpg";

// ---  CAPA EASTER EGG ---
$tem_capa = !empty($dados['capa']);
$capa_path = "uploads/".$dados['capa'];

$sql_seg = mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'");
$total_seguidores = mysqli_fetch_assoc($sql_seg)['total'];


// Lógica do Easter Egg da Presença
$is_presenca = ($id_visto == 1);
?>

<main class="main-perfil-container <?php echo $is_presenca ? 'perfil-gold' : ''; ?>">
    <div class="capa-wrapper viewer">
        <?php if(!empty($dados['capa'])): ?>
            <img src="uploads/<?php echo $dados['capa']; ?>" class="img-capa-preview">
        <?php else: ?>
            <div class="capa-default-fenda" style="background: linear-gradient(135deg, #004a8f 0%, #00a896 100%); height: 200px; display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-weight: bold; font-size: 1.2rem;">BEM-VINDO À FENDA!</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if($is_presenca): ?>
        <div class="badge-presenca">VOCÊ ESTÁ DIANTE DA PRESENÇA 👑</div>
    <?php endif; ?>

    <div class="avatar-wrapper viewer">
        <img src="<?php echo $foto; ?>" class="img-avatar-perfil">
    </div>

    <div class="perfil-info-publica">
        <h2 class="nome-publico"><?php echo htmlspecialchars($dados['nome']); ?></h2>
        <span class="user-handle">@<?php echo htmlspecialchars($dados['username']); ?></span>
        
        <div class="stats-perfil">
            <span><strong><?php echo $total_seguidores; ?></strong> Seguidores</span>
        </div>

        <div class="bio-box">
            <p><?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'])) : "<i>Habitante misterioso da Fenda...</i>"; ?></p>
        </div>


        <div class="perfil-info-publica">
        <div class="perfil-controles"> 
        <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
            <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>" 
               class="btn-seguir-fenda <?php echo $ja_segue ? 'seguindo' : ''; ?>">
                <?php if ($ja_segue): ?>
                    <i class="fa-solid fa-check"></i> Seguindo
                <?php else: ?>
                    + Seguir
                <?php endif; ?>
            </a>
        <?php else: ?>
            <a href="perfil.php" class="btn-editar-atalho">Editar meu perfil</a>
        <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>