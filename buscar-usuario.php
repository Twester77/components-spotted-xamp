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

<main class="container-busca container-fenda-flex">
    <h2>🔍 Buscar Estudantes</h2>
    
    <form action="buscar-usuario.php" method="GET" class="form-busca-fenda">
        <input type="text" name="q" value="<?php echo htmlspecialchars($busca); ?>" 
               placeholder="Digite o nome ou @username..." autocomplete="off">
        <button type="submit">IR</button>
    </form>

    <div class="lista-resultados"> <?php if (!empty($busca)): ?>
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $user): 
                    $foto = !empty($user['foto']) ? "uploads/" . $user['foto'] : "imagensfoto/img_avatar_generico.jpg";
                ?>
                    <a href="ver-perfil.php?user=<?php echo $user['username']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="user-card">
                            <img src="<?php echo $foto; ?>" class="avatar-busca">
                            <div>
                                <strong class="nome-user"><?php echo $user['nome']; ?></strong>
                                <span class="username-user">@<?php echo $user['username']; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #aaa; padding: 20px;">Nenhum estudante encontrado com "<?php echo htmlspecialchars($busca); ?>".</p>
            <?php endif; ?>
        <?php endif; ?>
    </div> </main>

<?php include 'includes/footer.php'; ?>