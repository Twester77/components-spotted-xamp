<?php
/**
 * transferir-dono.php – Transfere a posse de uma comunidade
 *
 * 🔒 Segurança:
 * - CSRF token obrigatório (hash_equals)
 * - Apenas o criador atual pode transferir
 * - O novo dono deve ser membro ativo da comunidade
 * - Não é possível transferir para si mesmo
 * - Rate limiting persistente (fenda_rate_limits)
 * - Transação atômica (tudo ou nada)
 * - Log de auditoria
 *
 * ⚓ PORTO – 2026-10-30 (v9.0 / F6)
 *    - CORRIGIDO bug de atomicidade: `CREATE TABLE IF NOT EXISTS logs_auditoria`
 *      estava DENTRO do begin_transaction(). Em MySQL/TiDB, DDL causa COMMIT
 *      implícito → se uma das queries seguintes falhasse, o rollback() não
 *      desfazia nada. Movido pra antes do begin_transaction.
 *    - ADICIONADO `if (!$stmt->execute()) throw` nos 5 execute() da
 *      transação. Antes, com MYSQLI_REPORT_OFF, falhas retornavam false
 *      silenciosamente e o commit acontecia com estado parcial.
 *    - `catch (Exception)` → `catch (Throwable)` (captura Error também).
 *    - `CREATE TABLE IF NOT EXISTS fenda_rate_limits` também movido pra
 *      fora do try (era redundante e contribuía pro problema).
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
$csrf_sessao = $_SESSION['csrf_token'] ?? '';
if (!is_string($csrf) || $csrf === '' || !is_string($csrf_sessao) || !hash_equals($csrf_sessao, $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token inválido.']);
    exit;
}

$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$novo_dono_id  = isset($_POST['novo_dono_id']) ? (int)$_POST['novo_dono_id'] : 0;
$meu_id        = (int)$_SESSION['usuario_id'];

if ($comunidade_id <= 0 || $novo_dono_id <= 0 || $meu_id == $novo_dono_id) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos. Você não pode transferir para si mesmo.']);
    exit;
}

// ============================================================
// 1. RATE LIMITING PERSISTENTE (3 ações por minuto)
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
    echo json_encode(['success' => false, 'message' => 'Aguarde um momento antes de transferir a posse novamente.']);
    exit;
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint, $meu_id, $ip);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ============================================================
// 2. VERIFICAÇÕES DE SEGURANÇA
// ============================================================
// Verifica se sou o criador atual
$stmt = $conn->prepare("SELECT criador_id, nome FROM comunidades WHERE id = ?");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$comunidade = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$comunidade || $comunidade['criador_id'] != $meu_id) {
    echo json_encode(['success' => false, 'message' => 'Apenas o criador atual pode transferir a posse.']);
    exit;
}

// Verifica se o novo dono é membro ativo (não pode transferir para quem foi banido ou não está na comunidade)
$stmt = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $novo_dono_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_row()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'O novo dono precisa ser um membro ativo da comunidade.']);
    exit;
}
$stmt->close();

// ============================================================
// 3. GARANTE TABELAS AUXILIARES FORA DA TRANSAÇÃO
//    ⚓ F6: DDL (CREATE TABLE) dentro de begin_transaction causa COMMIT
//    implícito em MySQL/TiDB. Isso quebrava a atomicidade. Agora a tabela
//    é garantida ANTES de abrir a transação.
// ============================================================
$conn->query("CREATE TABLE IF NOT EXISTS logs_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    acao VARCHAR(50) NOT NULL,
    detalhes TEXT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_data (data_hora)
)");

// ============================================================
// 4. TRANSAÇÃO (Tudo ou nada)
// ============================================================
$conn->begin_transaction();
try {
    // 4.1 Atualiza a tabela comunidades
    $stmt1 = $conn->prepare("UPDATE comunidades SET criador_id = ? WHERE id = ?");
    $stmt1->bind_param("ii", $novo_dono_id, $comunidade_id);
    if (!$stmt1->execute()) {
        throw new Exception('Falha no UPDATE comunidades: ' . $stmt1->error);
    }
    $stmt1->close();

    // 4.2 Promove o novo dono
    $stmt2 = $conn->prepare("UPDATE comunidade_membros SET papel = 'criador' WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt2->bind_param("ii", $comunidade_id, $novo_dono_id);
    if (!$stmt2->execute()) {
        throw new Exception('Falha no UPDATE comunidade_membros (promover): ' . $stmt2->error);
    }
    $stmt2->close();

    // 4.3 Rebaixa o antigo dono para Admin (para não perder o acesso)
    $stmt3 = $conn->prepare("UPDATE comunidade_membros SET papel = 'admin' WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt3->bind_param("ii", $comunidade_id, $meu_id);
    if (!$stmt3->execute()) {
        throw new Exception('Falha no UPDATE comunidade_membros (rebaixar): ' . $stmt3->error);
    }
    $stmt3->close();

    // 4.4 Notifica o novo dono
    $msg = "👑 Você agora é o criador da comunidade \"" . $comunidade['nome'] . "\"!";
    $stmt4 = $conn->prepare("INSERT INTO notificacoes (usuario_id, tipo, mensagem, lida) VALUES (?, 'sistema', ?, 0)");
    $stmt4->bind_param("is", $novo_dono_id, $msg);
    if (!$stmt4->execute()) {
        throw new Exception('Falha no INSERT notificacoes: ' . $stmt4->error);
    }
    $stmt4->close();

    // 4.5 Log de auditoria
    $detalhes_log = "comunidade_id=$comunidade_id, antigo_dono=$meu_id, novo_dono=$novo_dono_id";
    $stmt5 = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, acao, detalhes) VALUES (?, 'transferir_dono', ?)");
    $stmt5->bind_param("is", $meu_id, $detalhes_log);
    if (!$stmt5->execute()) {
        throw new Exception('Falha no INSERT logs_auditoria: ' . $stmt5->error);
    }
    $stmt5->close();

    $conn->commit();
    fenda_log("🟢 [TRANSFERIR_DONO] Comunidade $comunidade_id: $meu_id → $novo_dono_id");
    echo json_encode(['success' => true, 'message' => 'Posse transferida com sucesso!']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log("[TRANSFERIR_DONO] Erro: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao transferir. Tente novamente.']);
}
exit;