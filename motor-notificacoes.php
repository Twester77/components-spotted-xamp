<?php
/**
 * motor-notificacoes.php – Endpoint para listar notificações (sem marcar como lidas)
 * 
 * Parâmetros:
 * - limite (int): número de notificações a exibir (padrão: 5)
 * 
 * 🔥 VERSÃO COM LINKS INCLUINDO notif_id PARA MARCAÇÃO INDIVIDUAL
 * 🚀 OTIMIZADO: usa campo `tipo` em vez de consultas extras (Lua, 2026-08-13)
 * ⏰ ATUALIZAÇÃO ESTRELA – 2026-08-16
 *    Correção do fuso horário: exibição de datas agora usa exibirDataHoraBrasil().
 *
 * 🐚 MARESIA – 2026-09-27 (Sprint 1, item 4/7)
 *    - Adicionada assinatura HMAC (`sig`) nos links com `notif_id`.
 *      Sem isso, um site terceiro podia fazer `<img src=".../notificacoes.php?notif_id=X">`
 *      e marcar notificações do usuário logado como lidas (CSRF via GET).
 *      Os receptores (notificacoes.php, comentarios-post.php, central.php,
 *      motor-central.php) validam a assinatura antes de marcar.
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: text/html; charset=utf-8');

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if ($usuario_id === 0) {
    echo '<p style="padding:15px; color:#fff; text-align:center;">Faça login para ver as notificações...</p>';
    exit;
}

$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 5;

// ============================================================
// CABEÇALHO COM BOTÃO "MARCAR TODAS" (apenas se limite > 5)
// ============================================================
if ($limite > 5) {
    echo '<div class="notif-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; grid-column: 1 / -1;">';
    echo '  <span style="color: #333333d9; font-weight: bold; font-family: inherit; font-size: 0.8rem; font-size:clamp(0.85rem, 2cqw, 1.2rem);">Suas notificações</span>';
    echo '  <button id="btn-marcar-todas-lidas" class="btn-fenda-padrao" style=" pointer-events:auto ; cursor:pointer;">';
    echo '    <i class="fas fa-check-double"></i> Marcar todas como lidas';
    echo '  </button>';
    echo '</div>';
}

// 🔥 INCLUI O CAMPO `tipo` NA CONSULTA
$sql = "SELECT id, post_id, tipo, mensagem, lida, data_criacao 
        FROM notificacoes 
        WHERE usuario_id = ? 
        ORDER BY data_criacao DESC 
        LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $usuario_id, $limite);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo '<p style="padding:20px; color:#ccc; text-align:center;">Nenhuma marola por aqui ainda.</p>';
    exit;
}

while ($n = $res->fetch_assoc()):
    $lida_classe = ($n['lida'] == 0) ? 'notif-nova' : '';

    // 🐚 MARESIA – 2026-09-27: assinatura HMAC pra links com notif_id.
    //    Fórmula: hash_hmac('sha256', "<id>|<user_id>", FENDA_CRYPT_KEY).
    //    O receptor recalcula e compara com hash_equals.
    $sig = hash_hmac('sha256', $n['id'] . '|' . $usuario_id, FENDA_CRYPT_KEY);

    // 🔥 LINK BASEADO NO TIPO (SEM CONSULTAS EXTRAS!)
    switch ($n['tipo']) {
        case 'evento':
            $link = "evento.php?id=" . $n['post_id'] . "&notif_id=" . $n['id'] . "&sig=" . $sig;
            break;
        case 'post':
            $link = "comentarios-post.php?id=" . $n['post_id'] . "&notif_id=" . $n['id'] . "&sig=" . $sig . "#fofocar";
            break;
        case 'depoimento':
            $link = "central.php?aba=depoimentos&notif_id=" . $n['id'] . "&sig=" . $sig;
            break;
        case 'solicitacao':
            $link = "central.php?aba=solicitacoes&notif_id=" . $n['id'] . "&sig=" . $sig;
            break;
        default:
            // Fallback: tipo 'sistema' ou desconhecido
            $link = "notificacoes.php?notif_id=" . $n['id'] . "&sig=" . $sig;
            break;
    }
?>
    <a href="<?= htmlspecialchars($link) ?>" class="item-notif-rapida <?= $lida_classe ?>">
        <div class="notif-avatar">
            <i class="fa-solid fa-water"></i>
        </div>
        <div class="notif-txt">
            <span><?= htmlspecialchars($n['mensagem']) ?></span>
            <small><?= exibirDataHoraBrasil($n['data_criacao'], 'd/m H:i') ?></small>
        </div>
    </a>
<?php
endwhile;

// Link "Ver todos" (se for o dropdown)
if ($limite <= 5) {
    echo '<a href="central.php?aba=notificacoes" class="ver-todas-notif">Ver todo o oceano...</a>';
}
