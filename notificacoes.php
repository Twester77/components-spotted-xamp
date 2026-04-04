<?php
session_start();
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];

// 1. MARCAR TODAS AS NOTIFICAÇÕES COMO LIDAS AO ENTRAR
$sql_update = "UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?";
$stmt_up = $conn->prepare($sql_update);
$stmt_up->bind_param("i", $user_id);
$stmt_up->execute();

// 2. BUSCAR AS NOTIFICAÇÕES PARA EXIBIR
$sql = "SELECT * FROM notificacoes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<main class="container-fenda" style="max-width: 600px; margin: 30px auto; padding: 10px;">
    <h2 style="color: #ffbc00; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-bell"></i> Suas Notificações
    </h2>

    <?php if ($resultado->num_rows > 0): ?>
        <?php while ($row = $resultado->fetch_assoc()): ?>
            <div class="card-notificacao" style="background: #1a1a1a; border-left: 4px solid #ffbc00; padding: 15px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                <p style="margin: 0; font-size: 14px; color: #eee;">
                    <?php echo $row['mensagem']; ?>
                </p>
                <small style="color: #666; font-size: 11px;">
                    <?php echo date('d/m/Y H:i', strtotime($row['data_criacao'])); ?>
                </small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #666;">
            <i class="fas fa-ghost" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
            <p>Nada novo por aqui... A Fenda está silenciosa.</p>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="feed.php" style="color: #ffbc00; text-decoration: none; font-size: 14px;">← Voltar para o Feed</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>