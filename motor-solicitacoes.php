<?php
/**
 * motor-solicitacoes.php – Endpoint para listar solicitações pendentes
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-11
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

$usuario_id = $_SESSION['usuario_id'];

// Marca notificação como lida se veio com notif_id
if (isset($_GET['notif_id'])) {
    $notif_id = (int)$_GET['notif_id'];
    $stmt_notif = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
    $stmt_notif->bind_param("ii", $notif_id, $usuario_id);
    $stmt_notif->execute();
    $stmt_notif->close();
}

// Query com INNER JOIN (apenas comunidades onde o usuário é admin/criador)
$sql = "SELECT 
            cm.usuario_id,
            cm.comunidade_id,
            cm.data_entrada,
            u.username,
            u.foto,
            c.nome as comunidade_nome,
            c.slug as comunidade_slug
        FROM comunidade_membros cm
        INNER JOIN usuarios u ON cm.usuario_id = u.id
        INNER JOIN comunidades c ON cm.comunidade_id = c.id
        WHERE cm.status = 'pendente'
        AND c.id IN (
            SELECT comunidade_id 
            FROM comunidade_membros 
            WHERE usuario_id = ? AND papel IN ('criador', 'admin') AND status = 'ativo'
        )
        ORDER BY cm.data_entrada ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo '<div class="central-empty-state">';
    echo '  <i class="fas fa-check-circle"></i>';
    echo '  <p>Nenhuma solicitação de entrada pendente no momento.</p>';
    echo '</div>';
    exit;
}

try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}

echo '<div class="solicitacoes-central-lista">';

while ($sol = $res->fetch_assoc()) {
    $avatar = !empty($sol['foto']) ? (obterUrlImagem($sol['foto'], $b2, true) ?? 'uploads/ui/default_masculino.webp') : 'uploads/ui/default_masculino.webp';
    $data = date('d/m/Y H:i', strtotime($sol['data_entrada']));
    $comunidade_link = 'comunidade.php?id=' . $sol['comunidade_id'] . '#solicitacoes';
    
    echo '<div class="solicitacao-central-item" data-usuario="' . $sol['usuario_id'] . '" data-comunidade="' . $sol['comunidade_id'] . '">';
    echo '  <div class="solicitante-info">';
    echo '    <img src="' . htmlspecialchars($avatar) . '" class="solicitante-avatar" onerror="this.src=\'uploads/ui/default_masculino.webp\'">';
    echo '    <div>';
    echo '      <span class="solicitante-nome">@' . htmlspecialchars($sol['username']) . '</span>';
    echo '      <span class="solicitante-data">' . $data . '</span>';
    echo '      <a href="' . $comunidade_link . '" class="solicitante-comunidade">';
    echo '        <i class="fas fa-community"></i> ' . htmlspecialchars($sol['comunidade_nome']);
    echo '      </a>';
    echo '    </div>';
    echo '  </div>';
    echo '  <div class="acoes">';
    echo '    <button class="btn-aprovar-solicitacao-central" data-comunidade="' . $sol['comunidade_id'] . '" data-usuario="' . $sol['usuario_id'] . '">✅ Aprovar</button>';
    echo '    <button class="btn-rejeitar-solicitacao-central" data-comunidade="' . $sol['comunidade_id'] . '" data-usuario="' . $sol['usuario_id'] . '">✕ Rejeitar</button>';
    echo '  </div>';
    echo '</div>';
}

echo '</div>';
$stmt->close();
?>