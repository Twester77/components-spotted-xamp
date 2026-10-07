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
 *      motor-central.php, evento.php) validam a assinatura antes de marcar.
 *
 * 🐚 MARESIA – 2026-10-05 (Sprint 2, item 2/4 – Bloco B)
 *    - Trocado FENDA_CRYPT_KEY por FENDA_HMAC_KEY na assinatura HMAC.
 *      A FENDA_CRYPT_KEY deriva da SUPABASE_ANON_KEY (pública), então
 *      assinar com ela dava falsa sensação de segurança. A FENDA_HMAC_KEY
 *      é env var dedicada e secreta (definida em conexao.php, Bloco A,
 *      com fallback pra FENDA_CRYPT_KEY se ainda não configurada na Vercel).
 *    - HMAC é stateless — links antigos em abas abertas falham ao validar;
 *      recarregar a página regenera com a chave nova. Sem migração.
 *
 * 🐚 CALMARIA – 2026-10-07 (Sprint 2, item #18)
 *    - Notificações de evento agora usam a coluna `evento_id` (criada por
 *      migração) em vez de `post_id` para montar o link do dropdown.
 *      Motivo: `post_id` é sempre NULL em notificações de evento, e o link
 *      gerado (`evento.php?id=`) redirecionava pra balanga-teras.php porque
 *      o `evento.php` recebia id vazio. Agora o SELECT inclui `evento_id`
 *      e o switch case 'evento' usa essa coluna — link correto.
 *      Os outros tipos (post, depoimento, solicitacao, sistema) continuam
 *      usando `post_id` (vivem em `mensagens`, está correto).
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
$na_central = $limite > 5;

// ============================================================
// CABEÇALHO COM BOTÃO "MARCAR TODAS" (apenas se limite > 5)
// ============================================================
if ($na_central) {
    echo '<div class="notif-actions" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; grid-column: 1 / -1;">';
    echo '  <span style="color: #333333d9; font-weight: bold; font-family: inherit; font-size: 0.8rem; font-size:clamp(0.85rem, 2cqw, 1.2rem);">Suas notificações</span>';
    echo '  <button id="btn-marcar-todas-lidas" class="btn-fenda-padrao" style=" pointer-events:auto ; cursor:pointer;">';
    echo '    <i class="fas fa-check-double"></i> Marcar todas como lidas';
    echo '  </button>';
    echo '</div>';
}

// 🔥 INCLUI O CAMPO `tipo` NA CONSULTA
// 🐚 CALMARIA – 2026-10-07: adicionado evento_id (item #18)
$sql = "SELECT id, post_id, evento_id, tipo, mensagem, lida, data_criacao 
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
    //    Fórmula: hash_hmac('sha256', "<id>|<user_id>", FENDA_HMAC_KEY).
    //    O receptor recalcula e compara com hash_equals.
    //    Sprint 2, Bloco B (2026-10-05): usa FENDA_HMAC_KEY em vez de
    //    FENDA_CRYPT_KEY. Fallback pra FENDA_CRYPT_KEY mora em conexao.php.
    $sig = hash_hmac('sha256', $n['id'] . '|' . $usuario_id, FENDA_HMAC_KEY);

    // 🔥 LINK BASEADO NO TIPO (SEM CONSULTAS EXTRAS!)
    // 🐚 CALMARIA – 2026-10-07: case 'evento' usa evento_id (item #18).
    switch ($n['tipo']) {
        case 'evento':
            $link = "evento.php?id=" . $n['evento_id'] . "&notif_id=" . $n['id'] . "&sig=" . $sig;
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
    <div class="item-notif-rapida-wrap notificacao-item" data-notif-id="<?= (int)$n['id'] ?>">
        <a href="<?= htmlspecialchars($link) ?>" class="item-notif-rapida <?= $lida_classe ?>" aria-label="Abrir notificação">
            <div class="notif-avatar">
                <i class="fa-solid fa-water"></i>
            </div>
            <div class="notif-txt">
                <span><?= htmlspecialchars($n['mensagem']) ?></span>
                <small><?= exibirDataHoraBrasil($n['data_criacao'], 'd/m H:i') ?></small>
            </div>
        </a>
        <?php if ($na_central): ?>
            <div
                class="notificacao-acoes"
                id="notificacao-acoes-<?= (int)$n['id'] ?>"
                inert>
                <button
                    type="button"
                    class="btn-excluir-notificacao"
                    data-notif-id="<?= (int)$n['id'] ?>"
                    aria-label="Excluir notificação: <?= htmlspecialchars(mb_substr($n['mensagem'], 0, 50, 'UTF-8')) ?>"
                    title="Excluir notificação"
                    disabled>
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                </button>
            </div>
            <button
                type="button"
                class="btn-notificacao-acoes-toggle"
                aria-label="Mostrar ações da notificação: <?= htmlspecialchars(mb_substr($n['mensagem'], 0, 50, 'UTF-8')) ?>"
                aria-controls="notificacao-acoes-<?= (int)$n['id'] ?>"
                aria-expanded="false"
                title="Mostrar ações">
                <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
    </div>
<?php
endwhile;

// Link "Ver todos" (se for o dropdown)
if ($limite <= 5) {
    echo '<a href="central.php?aba=notificacoes" class="ver-todas-notif">Ver todo o oceano...</a>';
}