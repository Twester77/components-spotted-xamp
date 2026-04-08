
 <?php
include 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$busca = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$resultados = [];

if (!empty($busca)) {
    // Busca por username ou nome real usando o LIKE
    $sql = "SELECT id, nome, username, foto FROM usuarios 
            WHERE username LIKE '%$busca%' OR nome LIKE '%$busca%' 
            LIMIT 20";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $resultados[] = $row;
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container-busca" style="padding: 20px; max-width: 600px; margin: 0 auto;">
    <h2 style="color: var(--dourado); font-size: 1.5rem; text-align: center; margin-bottom: 20px;">🔍 Buscar Estudantes</h2>
    
    <form action="buscar-usuario.php" method="GET" style="display: flex; gap: 10px; margin-bottom: 30px;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($busca); ?>" 
               placeholder="Digite o nome ou @username..." 
               style="flex: 1; padding: 15px; font-size: 14px; border-radius: 8px; border: 2px solid var(--laranja-fenda); background: #222; color: white;">
        <button type="submit" style="background: var(--laranja-fenda); border: none; padding: 10px 20px; border-radius: 8px; color: white; font-weight: bold; cursor: pointer;">
            IR
        </button>
    </form>

    <div class="lista-resultados">
        <?php if (!empty($busca)): ?>
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $user): 
                    $foto = !empty($user['foto']) ? "uploads/" . $user['foto'] : "imagensfoto/img_avatar_generico.jpg";
                ?>
                    <a href="ver-perfil.php?user=<?php echo $user['username']; ?>" style="text-decoration: none; color: inherit;">
                            <div class="user-card" style="display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,188,0,0.2);">
                            <img src="<?php echo $foto; ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #ffbc00;">
                            <div>
                                <strong style="display: block; color: white;"><?php echo $user['nome']; ?></strong>
                                <span style="color: #ffbc00; font-size: 0.9rem;">@<?php echo $user['username']; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #aaa;">Nenhum estudante encontrado com "<?php echo htmlspecialchars($busca); ?>".</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>