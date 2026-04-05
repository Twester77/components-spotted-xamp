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
$stmt_up = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
$stmt_up->bind_param("i", $user_id);
$stmt_up->execute();

// 2. BUSCAR AS NOTIFICAÇÕES PARA EXIBIR
$stmt = $conn->prepare("SELECT * FROM notificacoes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<main class="container-fenda" style="max-width: 600px; margin: 30px auto; padding: 10px;">
    <h2 style="color: #ffbc00; margin-bottom: 20px;"><i class="fas fa-bell"></i> Suas Notificações</h2>

    <?php if ($resultado->num_rows > 0): ?>
        <?php while ($row = $resultado->fetch_assoc()): ?>
       
       <a href="post.php?id=<?php echo $row['post_id']; ?>#fofocar" class="link-notificacao" style="text-decoration: none; display: block; margin-bottom: 10px;">
       <div class="card-notificacao" style="border-left: 4px solid #ffbc00;">
          <div class="comentario-item">
             <p style="margin: 0; font-size: 1.1rem; color: #eee;">
                 <?php echo $row['mensagem']; ?>
             </p>
             <small style="color: #666; display: block; margin-top: 5px;">
                 <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($row['data_criacao'])); ?>
             </small>
           </div>
        </div>
        </a>
        <?php endwhile; ?>
        
    <?php else: ?>
        <p style="text-align: center; color: #666;">A Fenda está silenciosa.</p>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>