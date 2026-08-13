<?php
/**
 * swipe-eventos.php – Endpoint para carregar cards de eventos (Balanga Teras)
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 CORREÇÃO: Eventos de comunidades privadas só são exibidos para membros ativos.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$comunidade_id = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'todos';
$usuario_id = $_SESSION['usuario_id'];

// ============================================================
// CONSULTA COM PREPARED STATEMENTS
// ============================================================
$sql = "SELECT e.*, 
        u.username as criador_username,
        u.foto as criador_foto,
        (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id AND resposta = 'vou') as total_vou,
        (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id AND resposta = 'nao_vou') as total_nao_vou,
        (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id AND resposta = 'talvez') as total_talvez,
        (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id) as total_respostas
        FROM eventos e
        LEFT JOIN usuarios u ON e.criador_id = u.id
        WHERE e.status = 'ativo'";

$params = [];
$types = '';

if ($comunidade_id > 0) {
    $sql .= " AND e.comunidade_id = ?";
    $params[] = $comunidade_id;
    $types .= 'i';
}

if ($status_filter !== 'todos') {
    if ($status_filter === 'agendado') {
        $sql .= " AND e.data_evento > NOW()";
    } elseif ($status_filter === 'em-andamento') {
        $sql .= " AND e.data_evento > NOW() - INTERVAL 2 HOUR AND e.data_evento <= NOW() + INTERVAL 2 HOUR";
    } elseif ($status_filter === 'expirado') {
        $sql .= " AND e.data_evento < NOW() - INTERVAL 1 HOUR";
    }
}

$sql .= " ORDER BY e.data_evento ASC LIMIT 10 OFFSET ?";
$params[] = $offset;
$types .= 'i';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo "FIM_DADOS";
    exit;
}

// ============================================================
// FUNÇÃO PARA CALCULAR STATUS
// ============================================================
function calcularStatusEvento($data_evento) {
    $now = time();
    $evento_time = strtotime($data_evento);
    $diff = $evento_time - $now;
    if ($diff < -3600) return 'expirado';
    if ($diff <= 7200) return 'em-andamento';
    return 'agendado';
}

// ============================================================
// LOOP DE EXIBIÇÃO COM FILTRO POR COMUNIDADE PRIVADA
// ============================================================
while ($evento = $res->fetch_assoc()) {
    $status = calcularStatusEvento($evento['data_evento']);
    if ($status_filter !== 'todos' && $status_filter !== $status) continue;

    // 🔥 FILTRO: Se o evento pertence a uma comunidade, verifica se o usuário pode vê-lo
    if ($evento['comunidade_id'] > 0) {
        $comunidade_id_evento = (int)$evento['comunidade_id'];

        // Busca o tipo da comunidade
        $stmt_tipo = $conn->prepare("SELECT tipo FROM comunidades WHERE id = ?");
        $stmt_tipo->bind_param("i", $comunidade_id_evento);
        $stmt_tipo->execute();
        $res_tipo = $stmt_tipo->get_result();
        $com_tipo = $res_tipo->fetch_assoc();
        $stmt_tipo->close();

        // Se a comunidade for privada, verifica se o usuário é membro ativo
        if ($com_tipo && $com_tipo['tipo'] === 'privada') {
            $stmt_membro = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
            $stmt_membro->bind_param("ii", $comunidade_id_evento, $usuario_id);
            $stmt_membro->execute();
            $is_membro = $stmt_membro->get_result()->num_rows > 0;
            $stmt_membro->close();

            if (!$is_membro) {
                continue; // Não exibe o evento para não membros
            }
        }
    }

    $avatar = !empty($evento['criador_foto']) ? obterUrlImagem($evento['criador_foto']) : 'uploads/ui/default.webp';
    $capa = !empty($evento['imagem_url']) ? obterUrlImagem($evento['imagem_url']) : 'uploads/ui/default_evento.webp';

    $selo = match($status) {
        'expirado' => '<span class="bt-status-selo expirado">⚫ Encerrado</span>',
        'em-andamento' => '<span class="bt-status-selo em-andamento">🔴 Acontecendo agora</span>',
        'agendado' => '<span class="bt-status-selo agendado">🟡 Em breve</span>',
        default => ''
    };

    // Resposta do usuário
    $resposta_usuario = '';
    $stmt_resp = $conn->prepare("SELECT resposta FROM evento_respostas WHERE evento_id = ? AND usuario_id = ?");
    $stmt_resp->bind_param("ii", $evento['id'], $usuario_id);
    $stmt_resp->execute();
    if ($row = $stmt_resp->get_result()->fetch_assoc()) {
        $resposta_usuario = $row['resposta'];
    }
    $stmt_resp->close();

    $total_vou = $evento['total_vou'] ?? 0;
    $total_talvez = $evento['total_talvez'] ?? 0;
    $total_nao_vou = $evento['total_nao_vou'] ?? 0;
    $data_formatada = date('d/m/Y H:i', strtotime($evento['data_evento']));
?>
    <!-- 🔥 CARD DE EVENTO -->
    <div class="bt-card <?php echo $status; ?>" data-id="<?php echo $evento['id']; ?>" data-criador="<?php echo $evento['criador_id']; ?>">
        <div class="bt-capa">
            <img src="<?php echo htmlspecialchars($capa); ?>" alt="Capa do evento" loading="lazy" onerror="this.onerror=null; this.src='uploads/ui/default_evento.webp'">
            <?php echo $selo; ?>
        </div>
        <div class="bt-info">
            <h3 class="bt-titulo"><?php echo htmlspecialchars($evento['nome']); ?></h3>
            <p class="bt-local"><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($evento['local'] ?? 'Local a definir'); ?></p>
            <p class="bt-data"><i class="fas fa-calendar-alt"></i> <?php echo $data_formatada; ?></p>
            <p class="bt-descricao"><?php echo nl2br(htmlspecialchars($evento['descricao'] ?? '')); ?></p>
            <div class="bt-participacao">
                <span><i class="fas fa-user-check"></i> <?php echo $total_vou; ?> Sim</span>
                <span><i class="fas fa-user-minus"></i> <?php echo $total_nao_vou; ?> Não</span>
                <span><i class="fas fa-user-clock"></i> <?php echo $total_talvez; ?> Talvez</span>
            </div>
            <?php if ($status !== 'expirado'): ?>
                <div class="bt-acoes">
                    <button class="bt-btn-resposta <?php echo ($resposta_usuario === 'vou') ? 'ativo-vou' : ''; ?>" data-evento="<?php echo $evento['id']; ?>" data-opcao="vou">👍 Vou</button>
                    <button class="bt-btn-resposta <?php echo ($resposta_usuario === 'talvez') ? 'ativo-talvez' : ''; ?>" data-evento="<?php echo $evento['id']; ?>" data-opcao="talvez">🤔 Talvez</button>
                    <button class="bt-btn-resposta <?php echo ($resposta_usuario === 'nao_vou') ? 'ativo-nao' : ''; ?>" data-evento="<?php echo $evento['id']; ?>" data-opcao="nao_vou">👎 Não vou</button>
                </div>
            <?php else: ?>
                <div class="bt-expirado-msg"><i class="fas fa-lock"></i> Evento encerrado</div>
            <?php endif; ?>
            <a href="evento.php?id=<?php echo $evento['id']; ?>" class="bt-btn-detalhes"><i class="fas fa-chevron-right"></i> Ver detalhes</a>
        </div>
    </div>
<?php
}
$stmt->close();
?>