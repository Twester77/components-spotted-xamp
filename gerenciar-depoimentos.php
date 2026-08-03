<?php
/**
 * gerenciar-depoimentos.php – Lista depoimentos pendentes para aprovação
 * (Página avulsa – fallback)
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

$usuario_id = $_SESSION['usuario_id'];

// Busca depoimentos pendentes (onde o usuário é o destinatário)
$sql = "SELECT d.*, u.username, u.foto 
        FROM depoimentos d
        JOIN usuarios u ON d.autor_id = u.id
        WHERE d.destinatario_id = ? AND d.status = 'pendente'
        ORDER BY d.data_criacao DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="gerenciar-depoimentos-page">
    <div class="depoimentos-pendentes-container">
        <h1><i class="fas fa-inbox"></i> Depoimentos Pendentes</h1>
        <p class="subtitulo">Aprove ou rejeite os depoimentos que você recebeu. Eles só aparecerão no seu perfil após a aprovação.</p>

        <?php if ($res->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #4caf50; margin-bottom: 15px;"></i>
                <p>Nenhum depoimento pendente no momento.</p>
            </div>
        <?php else: ?>
            <div id="lista-depoimentos-pendentes" class="depoimentos-pendentes-list">
                <?php
                try {
                    $b2 = B2Client::getInstance();
                } catch (Exception $e) {
                    $b2 = null;
                }

                while ($dep = $res->fetch_assoc()):
                    $avatar = !empty($dep['foto']) ? (obterUrlImagem($dep['foto'], $b2, true) ?? 'uploads/ui/default_masculino.webp') : 'uploads/ui/default_masculino.webp';
                    $data = date('d/m/Y H:i', strtotime($dep['data_criacao']));
                    $mensagem = nl2br(htmlspecialchars($dep['mensagem']));
                ?>
                    <div class="depoimento-pendente-item" data-id="<?= $dep['id'] ?>">
                        <div class="depoimento-pendente-autor">
                            <img src="<?= htmlspecialchars($avatar) ?>" class="depoimento-avatar" alt="<?= htmlspecialchars($dep['username']) ?>">
                            <div>
                                <strong>@<?= htmlspecialchars($dep['username']) ?></strong>
                                <span class="depoimento-pendente-data"><?= $data ?></span>
                            </div>
                        </div>
                        <p class="depoimento-pendente-texto"><?= $mensagem ?></p>
                        <div class="depoimento-pendente-acoes">
                            <button class="btn-aprovar-depoimento" data-id="<?= $dep['id'] ?>">
                                <i class="fas fa-check"></i> Aprovar
                            </button>
                            <button class="btn-rejeitar-depoimento" data-id="<?= $dep['id'] ?>">
                                <i class="fas fa-times"></i> Rejeitar
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- 🔥 CSRF Token (para o JS) -->
<input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

<!-- 🔥 Script unificado (carregado apenas nesta página) -->
<script src="js/depoimentos-actions.js?v=<?= filemtime(__DIR__ . '/js/depoimentos-actions.js') ?>"></script>

<?php include 'includes/footer.php'; ?>