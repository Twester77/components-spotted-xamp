<?php
include 'conexao.php';
session_start();

// MANTIVE SUA LÓGICA DE SEGURANÇA
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$busca = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$resultados = [];

// MANTIVE SEU SQL INTEGRALMENTE
if (!empty($busca)) {
    $sql = "SELECT id, nome, username, foto FROM usuarios 
            WHERE username LIKE '%$busca%' OR nome LIKE '%$busca%' 
            LIMIT 20";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $resultados[] = $row;
    }
}

// DEFINIÇÃO DO FUNDO AZUL DA FENDA
$body_class = "pg-fundo-azul";

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-perfil" style="max-width: 600px; margin: 20px auto; padding: 20px; min-height: 80vh;">
    
    <h2 style="color: #ffbc00; text-align: center; margin-bottom: 25px; text-shadow: 0 0 15px rgba(255,188,0,0.3);">
        🔍 Buscar Estudantes
    </h2>
    
    <form action="buscar-usuario.php" method="GET" style="display: flex; gap: 10px; margin-bottom: 30px;">
        <input type="text" name="q" value="<?php echo htmlspecialchars($busca); ?>" 
               placeholder="Digite o nome ou @username..." 
               style="flex: 1; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,188,0,0.3); background: rgba(0,0,0,0.4); color: white; outline: none; backdrop-filter: blur(5px);">
        
        <button type="submit" class="btn-lancar" style="border: none; padding: 0 25px; border-radius: 12px; cursor: pointer;">
            BUSCAR
        </button>
    </form>

    <div class="lista-resultados">
        <?php if (!empty($busca)): ?>
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $user): 
                    // MANTIVE SUAS REFERÊNCIAS DE PASTA
                    $foto = !empty($user['foto']) ? "uploads/" . $user['foto'] : "imagensfoto/img_avatar_generico.jpg";
                ?>
                    <a href="perfil.php?id=<?php echo $user['id']; ?>" style="text-decoration: none; color: inherit; display: block; transition: 0.3s;" class="card-resultado">
                        <div style="display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                            
                            <img src="<?php echo $foto; ?>" style="width: 55px; height: 55px; border-radius: 50%; object-fit: cover; border: 2px solid #ffbc00; box-shadow: 0 0 10px rgba(255,188,0,0.2);">
                            
                            <div style="flex: 1;">
                                <strong style="display: block; color: white; font-size: 1.1rem;"><?php echo $user['nome']; ?></strong>
                                <span style="color: #ffbc00; font-size: 0.85rem; font-weight: bold; opacity: 0.8;">@<?php echo $user['username']; ?></span>
                            </div>

                            <div style="color: #ffbc00; opacity: 0.5;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.1);">
                    <p style="color: #aaa;">Nenhum estudante encontrado com "<span style="color: #ffbc00;"><?php echo htmlspecialchars($busca); ?></span>".</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="text-align: center; color: #666; margin-top: 50px;">Digite algo acima para encontrar seus colegas de curso!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>