<?php
// 1. CONEXÃO E ESTRUTURA (Lógica)
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php'; 

// 2. FILTRO (O SQL que te dei)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: feed.php");
    exit();
}

$atletica_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT id, nome, username, foto, bio FROM usuarios 
        WHERE atletica_id = '$atletica_id' 
        ORDER BY nome ASC";
$res = mysqli_query($conn, $sql);
$total_habitantes = mysqli_num_rows($res);
?>


<main>
    <section class="comunidade-topo">
        <img src="badges/<?php echo $atletica_id; ?>.png" class="img-comunidade-grande" onerror="this.src='badges/default.png'">
        <h1 style="color: var(--dourado); text-transform: uppercase;">
            Comunidade <?php echo str_replace('-', ' ', $atletica_id); ?>
        </h1>
        <p style="color: #ccc;">Há <?php echo $total_habitantes; ?> habitantes nesta área da Fenda</p>
    </section>

    <div class="grid-habitantes">
        <?php if ($total_habitantes > 0): ?>
            <?php while ($h = mysqli_fetch_assoc($res)): 
                $foto_h = !empty($h['foto']) ? "uploads/".$h['foto'] : "imagensfoto/default.jpg";
            ?>
                <a href="ver-perfil.php?user=<?php echo $h['username']; ?>" class="card-habitante">
                    <img src="<?php echo $foto_h; ?>" class="avatar-lista">
                    <div class="info-h">
                        <h3><?php echo htmlspecialchars($h['nome']); ?></h3>
                        <p>@<?php echo htmlspecialchars($h['username']); ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #666; grid-column: 1/-1;">Ninguém apareceu por aqui ainda... 🌊</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
