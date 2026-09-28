<?php
/**
 * notificacoes.php – Página dedicada a notificações
 * 
 * 🚀 OTIMIZADO: usa campo `tipo` em vez de consultas extras (Lua, 2026-08-13)
 * ⏰ ATUALIZAÇÃO ESTRELA – 2026-08-16
 *    Correção do fuso horário: exibição de datas agora usa exibirDataHoraBrasil().
 *
 * 🐚 MARESIA – 2026-09-27 (Sprint 1, item 4/7)
 *    - Marcar como lida via GET agora exige assinatura HMAC (`sig`).
 *      Sem isso, um site terceiro podia fazer `<img src=".../notificacoes.php?notif_id=X">`
 *      e marcar notificações do usuário logado como lidas (CSRF via GET).
 *      Os links são gerados por motor-notificacoes.php com a assinatura.
 *    - Usa hash_equals (evita timing attack).
 */

require_once __DIR__ . '/auth_check.php';

$user_id = $_SESSION['usuario_id'];

// 🐚 MARESIA – 2026-09-27: valida assinatura HMAC antes de marcar como lida.
if (isset($_GET['notif_id']) && isset($_GET['sig'])) {
    $notif_id = (int)$_GET['notif_id'];
    $sig_recebida = $_GET['sig'];

    // Recalcula a assinatura esperada
    $sig_esperada = hash_hmac('sha256', $notif_id . '|' . $user_id, FENDA_CRYPT_KEY);

    // Só marca se a assinatura bater (timing-safe)
    if (hash_equals($sig_esperada, $sig_recebida)) {
        $stmt_mark = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
        $stmt_mark->bind_param("ii", $notif_id, $user_id);
        $stmt_mark->execute();
        $stmt_mark->close();
    } else {
        error_log("[NOTIFICACOES] ⚠️ Assinatura HMAC inválida para notif_id=$notif_id, user_id=$user_id");
    }
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
                // 🐚 MARESIA – 2026-09-27: assinatura HMAC nos links também.
                $sig = hash_hmac('sha256', $row['id'] . '|' . $user_id, FENDA_CRYPT_KEY);

                // 🔥 LINK BASEADO NO TIPO (SEM CONSULTAS EXTRAS!)
                switch ($row['tipo']) {
                    case 'evento':
                        $link = "evento.php?id=" . $row['post_id'] . "&notif_id=" . $row['id'] . "&sig=" . $sig;
                        break;
                    case 'post':
                        $link = "comentarios-post.php?id=" . $row['post_id'] . "&notif_id=" . $row['id'] . "&sig=" . $sig . "#fofocar";
                        break;
                    case 'depoimento':
                        $link = "central.php?aba=depoimentos&notif_id=" . $row['id'] . "&sig=" . $sig;
                        break;
                    case 'solicitacao':
                        $link = "central.php?aba=solicitacoes&notif_id=" . $row['id'] . "&sig=" . $sig;
                        break;
                    default:
                        $link = "notificacoes.php?notif_id=" . $row['id'] . "&sig=" . $sig;
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
                                <i class="far fa-clock"></i> <?= exibirDataHoraBrasil($row['data_criacao'], 'd/m H:i') ?>
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