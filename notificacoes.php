<?php
/**
 * notificacoes.php – Página dedicada a notificações
 * 
 * 🚀 OTIMIZADO: usa campo `tipo` em vez de consultas extras (Lua, 2026-08-13)
 */

require_once __DIR__ . '/auth_check.php';

$user_id = $_SESSION['usuario_id'];

// 🔥 MARCA NOTIFICAÇÃO ESPECÍFICA COMO LIDA (se veio com notif_id)
if (isset($_GET['notif_id'])) {
    $notif_id = (int)$_GET['notif_id'];
    $stmt_mark = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt_mark->bind_param("ii", $notif_id, $user_id);
    $stmt_mark->execute();
    $stmt_mark->close();
}

// 🔥 INCLUI O CAMPO `tipo` NA CONSULTA
$stmt_list = $conn->prepare("SELECT id, post_id, tipo, mensagem, lida, data_criacao 
                             FROM notificacoes 
                             WHERE usuario_id = ? 
                             ORDER BY data_criacao DESC 
                             LIMIT 20");
$stmt_list->bind_param("i", $user_id);
$stmt_list->execute();
$res_notificacoes_lista = $stmt_list->get_result();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="notificacoes-page">
    <div class="notificacoes-header">
        <h2><i class="fas fa-bell"></i> Suas Notificações</h2>
        <button id="btn-marcar-todas-lidas" class="btn-marcar-todas">
            <i class="fas fa-check-double"></i> Marcar todas como lidas
        </button>
    </div>

    <div class="notificacoes-list">
        <?php if ($res_notificacoes_lista && $res_notificacoes_lista->num_rows > 0): ?>
            <?php while ($row = $res_notificacoes_lista->fetch_assoc()):
                // 🔥 LINK BASEADO NO TIPO (SEM CONSULTAS EXTRAS!)
                switch ($row['tipo']) {
                    case 'evento':
                        $link = "evento.php?id=" . $row['post_id'] . "&notif_id=" . $row['id'];
                        break;
                    case 'post':
                        $link = "comentarios-post.php?id=" . $row['post_id'] . "&notif_id=" . $row['id'] . "#fofocar";
                        break;
                    case 'depoimento':
                        $link = "central.php?aba=depoimentos&notif_id=" . $row['id'];
                        break;
                    case 'solicitacao':
                        $link = "central.php?aba=solicitacoes&notif_id=" . $row['id'];
                        break;
                    default:
                        $link = "notificacoes.php?notif_id=" . $row['id'];
                        break;
                }

                $classe_nova = ($row['lida'] == 0) ? 'nova' : '';
                $borda_esquerda = ($row['lida'] == 0) ? 'var(--dourado)' : 'transparent';
            ?>
                <a href="<?= htmlspecialchars($link) ?>" class="notificacao-link">
                    <div class="notificacao-card <?= $classe_nova ?>" style="--borda-esquerda: <?= $borda_esquerda ?>;">
                        <div class="notificacao-conteudo">
                            <p><?= htmlspecialchars($row['mensagem']) ?></p>
                            <small class="notificacao-data">
                                <i class="far fa-clock"></i> <?= date('d/m H:i', strtotime($row['data_criacao'])) ?>
                            </small>
                        </div>
                        <?php if ($row['lida'] == 0): ?>
                            <span class="notificacao-indicador"></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="notificacao-empty">
                <p>A Fenda está silenciosa. Nenhuma notificação por aqui.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>