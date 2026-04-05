<?php
session_start();
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';

// 1. PEGA O USERNAME DA URL (ex: ver-perfil.php?user=leo_ads)
if (!isset($_GET['user'])) {
    header("Location: feed.php");
    exit();
}

$user_get = mysqli_real_escape_string($conn, $_GET['user']);

// 2. BUSCA OS DADOS NO BANCO
$sql = "SELECT * FROM usuarios WHERE username = '$user_get'";
$res = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($res);

if (!$dados) {
    echo "<main style='text-align:center; padding:50px;'><h2>Usuário não encontrado na Fenda! 🌊</h2></main>";
    include 'includes/footer.php';
    exit();
}

$id_visto = $dados['id'];
$foto = !empty($dados['foto']) ? "uploads/".$dados['foto'] : "imagensfoto/img_avatar1.jpg";
$capa = !empty($dados['capa']) ? "uploads/".$dados['capa'] : "imagensfoto/capa_padrao.jpg";

// 3. CONTAGEM DE SEGUIDORES
$sql_seguidores = "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'";
$res_seg = mysqli_query($conn, $sql_seguidores);
$total_seguidores = mysqli_fetch_assoc($res_seg)['total'];
?>

<main class="perfil-container" style="max-width: 450px; margin: 20px auto; background: #1a1a1a; border-radius: 15px; overflow: hidden; border: 1px solid #333;">

    <div class="capa" style="height: 200px; background: url('<?php echo $capa; ?>') center/cover;"></div>

    <div style="padding: 20px; text-align: center; margin-top: -60px;">
        <img src="<?php echo $foto; ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid #1a1a1a; object-fit: cover;">
        
        <h2 style="margin-top: 10px; color: #ffbc00;">@<?php echo $dados['username']; ?></h2>
        <p style="color: #aaa; font-size: 14px;"><?php echo $dados['nome']; ?></p>
        
        <div style="margin: 15px 0; display: flex; justify-content: center; gap: 20px;">
            <span><strong><?php echo $total_seguidores; ?></strong> Seguidores</span>
        </div>

        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <p style="font-size: 14px; line-height: 1.5;">
                <?php echo !empty($dados['bio']) ? htmlspecialchars($dados['bio']) : "<i>Este habitante da Fenda ainda não escreveu uma bio...</i>"; ?>
            </p>
        </div>

        <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
            <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>" 
               style="display: block; background: #ffbc00; color: #000; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: bold;">
               Seguir / Deixar de Seguir
            </a>
        <?php else: ?>
            <a href="perfil.php" style="display: block; background: #333; color: #fff; padding: 10px; border-radius: 8px; text-decoration: none;">
               Editar Meu Perfil
            </a>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>