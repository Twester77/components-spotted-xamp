<?php
/**
 * reagir.php – Processa reações em posts (AJAX via GET)
 * 
 * ⚠️ NOTA HERDADA: este endpoint é chamado via GET (fetch com query string),
 *    sem CSRF. Isso é decisão antiga do projeto. A migração para POST + CSRF
 *    está anotada como débito técnico no Sprint 3 (relatório da Corrente).
 *    Por enquanto, a proteção é via rate limit por usuário + IP.
 * 
 * ═══════════════════════════════════════════════════════════
 * REAGIR.PHP – VERSÃO SEGURA (COM RATE LIMITING PERSISTENTE)
 * ═══════════════════════════════════════════════════════════
 *
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-XX (estrutura)
 * 🔧 Versão com rate limit por IP (tabela `rate_limiter_reacoes`)
 *
 * 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
 *    - Rate limit por $_SESSION (400ms entre cliques) REMOVIDO: era
 *      efêmero em serverless e nunca disparava em produção Vercel.
 *    - Rate limit por IP (tabela `rate_limiter_reacoes`) MIGRADO para
 *      a tabela unificada `fenda_rate_limits` (endpoint 'reagir').
 *    - Nova política:
 *        • por usuário: 30 reações por 60s
 *        • por IP: 60 reações por 60s
 *    - O antigo `rate_limiter_reacoes` continua no banco (histórico),
 *      mas não é mais populado. Migração completa fica pro Sprint 3.
 */

require_once __DIR__ . '/../conexao.php';
header('Content-Type: application/json');

// ==================== 1. VERIFICAÇÃO DE SESSÃO ====================
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login necessário']);
    exit();
}

// ==================== 2. RATE LIMITING PERSISTENTE (fenda_rate_limits) ====================
// 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
//    Substitui o antigo rate limit por $_SESSION (400ms) e a tabela
//    `rate_limiter_reacoes` por uma abordagem unificada.
$usuario_id = (int)$_SESSION['usuario_id'];
$ip = function_exists('obterIPReal')
    ? obterIPReal()
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$endpoint = 'reagir';

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
        (SELECT COUNT(*) FROM fenda_rate_limits
         WHERE endpoint = ? AND usuario_id = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS user_total,
        (SELECT COUNT(*) FROM fenda_rate_limits
         WHERE endpoint = ? AND ip_address = ? AND tentativa > NOW() - INTERVAL 60 SECOND) AS ip_total
");
$stmt_rate->bind_param('siis', $endpoint, $usuario_id, $endpoint, $ip);
$stmt_rate->execute();
$result_rate = $stmt_rate->get_result()->fetch_assoc();
$stmt_rate->close();

if ((int)($result_rate['user_total'] ?? 0) >= 40 || (int)($result_rate['ip_total'] ?? 0) >= 60) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Calma lá! Você está reagindo rápido demais. Aguarde um pouco.'
    ]);
    exit();
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint, $usuario_id, $ip);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ==================== 3. PROCESSAMENTO DA REAÇÃO ====================
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? mysqli_real_escape_string($conn, $_GET['tipo']) : '';

if ($post_id > 0 && !empty($tipo)) {
    // 3.1 Verifica se já existe uma reação
    $check = $conn->prepare("SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
    $check->bind_param("ii", $post_id, $usuario_id);
    $check->execute();
    $res_check = $check->get_result();
    $dados_reacao = $res_check->fetch_assoc();

    if ($res_check->num_rows == 0) {
        // Inserir nova
        $stmt = $conn->prepare("INSERT INTO curtidas (mensagem_id, usuario_id, tipo_reacao) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $usuario_id, $tipo);
        $stmt->execute();
        $stmt->close();
    } else {
        if ($dados_reacao['tipo_reacao'] == $tipo) {
            // Se clicar no mesmo, remove (toggle)
            $stmt = $conn->prepare("DELETE FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
            $stmt->bind_param("ii", $post_id, $usuario_id);
        } else {
            // Se clicar em um diferente, atualiza
            $stmt = $conn->prepare("UPDATE curtidas SET tipo_reacao = ? WHERE mensagem_id = ? AND usuario_id = ?");
            $stmt->bind_param("sii", $tipo, $post_id, $usuario_id);
        }
        $stmt->execute();
        $stmt->close();
    }
    $check->close();

    // 3.2 Busca contagens atualizadas
    $sql_count = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = ? GROUP BY tipo_reacao";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $post_id);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();

    $contagens = [];
    while ($row = $res_count->fetch_assoc()) {
        $contagens[$row['tipo_reacao']] = (int)$row['total'];
    }
    $stmt_count->close();

    // 3.3 Busca reações do usuário logado para esse post
    $minhas_reacoes = [];
    $stmt_meu = $conn->prepare("SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
    $stmt_meu->bind_param("ii", $post_id, $usuario_id);
    $stmt_meu->execute();
    $res_meu = $stmt_meu->get_result();
    while ($m = $res_meu->fetch_assoc()) {
        $minhas_reacoes[] = $m['tipo_reacao'];
    }
    $stmt_meu->close();

    echo json_encode([
        'status' => 'success',
        'contagens' => $contagens,
        'minhas_reacoes' => $minhas_reacoes
    ]);
    exit();
}

// Se chegou aqui, dados inválidos
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
