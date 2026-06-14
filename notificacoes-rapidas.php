<?php

include 'conexao.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($usuario_id == 0) {
    echo "<p style='padding:15px; color:#fff; text-align:center;'>Faça login para ver ondas...</p>";
    exit();
}

// SQL simplificado para bater com a sua estrutura real
$sql = "SELECT id, post_id, mensagem, lida, data_criacao 
        FROM notificacoes 
        WHERE usuario_id = ? 
        ORDER BY data_criacao DESC LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

// Marcar como lidas
$stmt_lida = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
$stmt_lida->bind_param("i", $usuario_id);
$stmt_lida->execute();

if ($res->num_rows > 0):
    while ($n = $res->fetch_assoc()):
        $lida_classe = ($n['lida'] == 0) ? 'notif-nova' : '';
        // Se não tiver post_id, manda para a página geral de notificações
        $link = ($n['post_id']) ? "comentarios-post.php?id=" . $n['post_id'] : "notificacoes.php";
?>
        <a href="<?php echo $link; ?>" class="item-notif-rapida <?php echo $lida_classe; ?>">
            <div class="notif-avatar">
                <i class="fa-solid fa-water"></i> <!-- Ícone padrão da Fenda -->
            </div>
            <div class="notif-txt">
                <span><?php echo htmlspecialchars($n['mensagem']); ?></span>
                <small><?php echo date('d/m H:i', strtotime($n['data_criacao'])); ?></small>
            </div>
        </a>
<?php
    endwhile;
    echo '<a href="notificacoes.php" class="ver-todas-notif">Ver todo o oceano...</a>';
else:
    echo "<p style='padding:20px; color:#ccc; text-align:center;'>Nenhuma marola por aqui ainda.</p>";
endif;
?>