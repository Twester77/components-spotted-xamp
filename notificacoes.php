<?php
include 'conexao.php';


if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];

// 1. BUSCA (Nomes de variáveis únicos)
$stmt_list = $conn->prepare("SELECT * FROM notificacoes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 20");
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$res_notificacoes_lista = $stmt_list->get_result();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container-fenda" style="max-width: 600px; margin: 30px auto; padding: 10px;">
    <h2 style="color: #ffbc00; margin-bottom: 20px;"><i class="fas fa-bell"></i> Suas Notificações</h2>

    <?php 
    if ($res_notificacoes_lista && $res_notificacoes_lista->num_rows > 0): 
        while ($row = $res_notificacoes_lista->fetch_assoc()): 
    ?>
        <a href="post.php?id=<?php echo $row['post_id']; ?>#fofocar" class="link-notificacao" style="text-decoration: none; color: inherit;">
            <div class="card-notificacao <?php echo ($row['lida'] == 0) ? 'nova' : ''; ?>" 
                 style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #ffbc00; display: flex; justify-content: space-between; align-items: center;">
                <div class="notificacao-conteudo">
                    <p style="margin: 0; font-size: 1rem; color: #fff;"><?php echo htmlspecialchars($row['mensagem']); ?></p>
                    <small style="color: #aaa; font-size: 0.8rem;">
                        <i class="far fa-clock"></i> <?php echo date('d/m H:i', strtotime($row['data_criacao'])); ?>
                    </small>
                </div>
                <i class="fas fa-chevron-right" style="color: #ffbc00; opacity: 0.5;"></i>
            </div>
        </a>
    <?php 
        endwhile; 
    else: 
    ?>
        <p style="text-align: center; color: #666; margin-top: 40px;">A Fenda está silenciosa. Nenhuma notificação por aqui.</p>
    <?php endif; ?>
</main>

<?php 
// 2. MARCAR COMO LIDAS NO FINAL
$up = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ? AND lida = 0");
$up->bind_param("i", $user_id);
$up->execute();

include 'includes/footer.php'; 
?>