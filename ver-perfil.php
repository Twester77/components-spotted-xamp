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
// NOVA LÓGICA: Verifica se eu já sigo esse habitante
$sql_check_segue = mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'");
$ja_segue = mysqli_num_rows($sql_check_segue) > 0;
$foto = !empty($dados['foto']) ? "uploads/".$dados['foto'] : "imagensfoto/default.jpg";
$capa = !empty($dados['capa']) ? "uploads/".$dados['capa'] : "imagensfoto/capa_padrao.jpg";

$sql_seg = mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'");
$total_seguidores = mysqli_fetch_assoc($sql_seg)['total'];

// Lógica do Easter Egg da Presença
$is_presenca = ($id_visto == 1);
?>

<main class="main-perfil-container <?php echo $is_presenca ? 'perfil-gold' : ''; ?>">
    <div class="capa-wrapper viewer">
        <img src="<?php echo $capa; ?>" class="img-capa-preview">
    </div>

    <?php if($is_presenca): ?>
            <div class="badge-presenca">VOCÊ ESTÁ DIANTE DA PRESENÇA 👑</div>
        <?php endif; ?>

    <div class="avatar-wrapper viewer">
        <img src="<?php echo $foto; ?>" class="img-avatar-perfil">
    </div>

    <div class="perfil-info-publica">
        <h2 class="nome-publico"><?php echo $dados['nome']; ?></h2>
        <span class="user-handle">@<?php echo $dados['username']; ?></span>
        
        <div class="stats-perfil">
            <span><strong><?php echo $total_seguidores; ?></strong> Seguidores</span>
        </div>

        <div class="bio-box">
            <p><?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'])) : "<i>Habitante misterioso da Fenda...</i>"; ?></p>
        </div>

        <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
    <?php if ($ja_segue): ?>
        <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>" class="btn-seguir-fenda seguindo">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>
    Seguindo
</a>
    <?php else: ?>
        <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>" class="btn-seguir-fenda">
            + Seguir
        </a>
    <?php endif; ?>
<?php else: ?>
    <a href="perfil.php" class="btn-editar-atalho">Editar meu perfil</a>
<?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>