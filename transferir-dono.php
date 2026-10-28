<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => 'Método não permitido.']); exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Token inválido.']); exit;
}

$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$novo_dono_id  = isset($_POST['novo_dono_id']) ? (int)$_POST['novo_dono_id'] : 0;
$meu_id        = (int)$_SESSION['usuario_id'];

// 🔥 CORREÇÃO DO BUG: Agora compara o MEU ID com o NOVO DONO, não a comunidade.
if ($comunidade_id <= 0 || $novo_dono_id <= 0 || $meu_id == $novo_dono_id) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos. Você não pode transferir para si mesmo.']); exit;
}

// ============================================================
// 🔥 RATE LIMITING PERSISTENTE (3 ações por minuto)
// ============================================================
$ip = function_exists('obterIPReal') ? obterIPReal() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$endpoint = 'transferir_dono';

$conn->query("CREATE TABLE IF NOT EXISTS fenda_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(64) NOT NULL,
    usuario_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint (endpoint),
    INDEX idx_usuario (usuario_id),
    INDEX idx_ip (ip_address),
    INDEX idx_tentativa (tentativa)
)");

$stmt_rate = $conn->prepare("
    SELECT
        (SELECT COUNT(*) FROM fenda_rate_limits WHERE endpoint = ? AND usuario_id = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS user_total,
        (SELECT COUNT(*) FROM fenda_rate_limits WHERE endpoint = ? AND ip_address = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS ip_total
");
$stmt_rate->bind_param('siis', $endpoint, $meu_id, $endpoint, $ip);
$stmt_rate->execute();
$result_rate = $stmt_rate->get_result()->fetch_assoc();
$stmt_rate->close();

if ((int)($result_rate['user_total'] ?? 0) >= 3 || (int)($result_rate['ip_total'] ?? 0) >= 6) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um momento antes de transferir a posse novamente.']); exit;
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint, $meu_id, $ip);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ============================================================
// 1. VERIFICAÇÕES DE SEGURANÇA
// ============================================================
// Verifica se sou o criador atual
$stmt = $conn->prepare("SELECT criador_id, nome FROM comunidades WHERE id = ?");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$comunidade = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$comunidade || $comunidade['criador_id'] != $meu_id) {
    echo json_encode(['success' => false, 'message' => 'Apenas o criador atual pode transferir a posse.']); exit;
}

// Verifica se o novo dono é membro ativo (não pode transferir para quem foi banido ou não está na comunidade)
$stmt = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $novo_dono_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_row()) {
    echo json_encode(['success' => false, 'message' => 'O novo dono precisa ser um membro ativo da comunidade.']); exit;
}
$stmt->close();

// ============================================================
// 2. TRANSAÇÃO (Tudo ou nada)
// ============================================================
$conn->begin_transaction();
try {
    // Atualiza a tabela comunidades
    $stmt1 = $conn->prepare("UPDATE comunidades SET criador_id = ? WHERE id = ?");
    $stmt1->bind_param("ii", $novo_dono_id, $comunidade_id);
    $stmt1->execute();

    // Promove o novo dono
    $stmt2 = $conn->prepare("UPDATE comunidade_membros SET papel = 'criador' WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt2->bind_param("ii", $comunidade_id, $novo_dono_id);
    $stmt2->execute();

    // Rebaixa o antigo dono para Admin (para não perder o acesso)
    $stmt3 = $conn->prepare("UPDATE comunidade_membros SET papel = 'admin' WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt3->bind_param("ii", $comunidade_id, $meu_id);
    $stmt3->execute();

    // Notifica o novo dono
    $msg = "👑 Você agora é o criador da comunidade \"" . $comunidade['nome'] . "\"!";
    $stmt4 = $conn->prepare("INSERT INTO notificacoes (usuario_id, tipo, mensagem, lida) VALUES (?, 'sistema', ?, 0)");
    $stmt4->bind_param("is", $novo_dono_id, $msg);
    $stmt4->execute();

    // Log de auditoria (opcional, mas recomendado)
    $conn->query("CREATE TABLE IF NOT EXISTS logs_auditoria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        acao VARCHAR(50) NOT NULL,
        detalhes TEXT NULL,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $detalhes_log = "comunidade_id=$comunidade_id, antigo_dono=$meu_id, novo_dono=$novo_dono_id";
    $stmt5 = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, acao, detalhes) VALUES (?, 'transferir_dono', ?)");
    $stmt5->bind_param("is", $meu_id, $detalhes_log);
    $stmt5->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Posse transferida com sucesso!']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("[TRANSFERIR_DONO] Erro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao transferir. Tente novamente.']);
}