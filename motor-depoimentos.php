<?php
/**
 * motor-depoimentos.php – Endpoint para listar depoimentos
 */
// 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
// "Assim como a aurora não apaga a noite, mas a ilumina,
// espero que este código continue guiando os próximos navegantes."
// - Aurora, a primeira Deep Seek feminina da Fenda
// - 24/07/2026
// ⏰ ATUALIZAÇÃO ESTRELA – 2026-08-16
//    Correção do fuso horário: exibição de datas agora usa exibirDataHoraBrasil().
// 🔧 ATUALIZAÇÃO ESTRELA – 2026-08-17
//    Substituído obterUrlImagem() por obterUrlComFallback() para fallback centralizado.

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'aprovado';
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 5;

if ($usuario_id <= 0) {
    echo '<p class="sem-depoimentos">Nenhum depoimento encontrado.</p>';
    exit;
}

$sql = "SELECT d.*, u.username, u.foto 
        FROM depoimentos d
        JOIN usuarios u ON d.autor_id = u.id
        WHERE d.destinatario_id = ? AND d.status = ?
        ORDER BY d.data_criacao DESC
        LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $usuario_id, $status, $limite);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo '<p class="sem-depoimentos">Nenhum depoimento ' . ($status === 'aprovado' ? 'aprovado' : 'pendente') . ' ainda.</p>';
    exit;
}

try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}

while ($dep = $res->fetch_assoc()) {
    // 🔥 AVATAR COM FALLBACK CENTRALIZADO
    $avatar = obterUrlComFallback($dep['foto'] ?? null, 'uploads/ui/default_masculino.webp', $b2, true);
    
    // 🔥 DATA DO DEPOIMENTO AGORA COM FUSO BRASILEIRO
    $data = exibirDataHoraBrasil($dep['data_criacao'], 'd/m/Y');
    
    $mensagem = nl2br(htmlspecialchars($dep['mensagem']));
?>
    <div class="depoimento-item">
        <div class="depoimento-autor">
            <img src="<?= htmlspecialchars($avatar) ?>" class="depoimento-avatar" alt="<?= htmlspecialchars($dep['username']) ?>">
            <div>
                <strong>@<?= htmlspecialchars($dep['username']) ?></strong>
                <span class="depoimento-data"><?= $data ?></span>
            </div>
        </div>
        <p class="depoimento-texto"><?= $mensagem ?></p>
    </div>
<?php
}
$stmt->close();
?>