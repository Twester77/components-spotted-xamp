<?php
/**
 * promover-membro.php – Endpoint para promover/rebaixar administradores de uma comunidade
 * 
 * Método: POST
 * Parâmetros: comunidade_id, usuario_id, acao (promover/rebaixar), csrf_token
 * Retorno: JSON { success: true/false, message: string }
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório (hash_equals)
 * - Prepared statements
 * - APENAS O CRIADOR pode promover/rebaixar admins
 * - Criador NÃO pode ser rebaixado
 * - Usuário NÃO pode promover/rebaixar a si mesmo
 * - Rate limiting persistente (fenda_rate_limits)
 * - Logs via fenda_log()
 * 
 * 📌 Regras:
 * - Promover: membro → admin (apenas criador)
 * - Rebaixar: admin → membro (apenas criador)
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔧 Rate limit corrigido: array de timestamps (5 ações/minuto)
 *
 * 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
 *    - Rate limit trocado de $_SESSION (efêmera em serverless — nunca disparava
 *      em produção Vercel) por tabela `fenda_rate_limits`.
 *    - COTA COMPARTILHADA com remover-membro.php e banir-membro.php:
 *      todos usam o mesmo endpoint `gerenciar_membros`. Um admin que
 *      organiza a comunidade faz várias dessas em sequência, mas 5/min
 *      já é o limite razoável para ações destrutivas em lote.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÕES INICIAIS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

// ============================================================
// 2. RATE LIMITING PERSISTENTE (5 ações por minuto — cotas separadas por ação)
// 🐚 CORRENTE – 2026-10-09 (Sprint 2, rate limits persistentes)
//    ⚠️ COTA COMPARTILHADA: mesmo endpoint de remover/banir.
// ============================================================
$usuario_id = (int)$_SESSION['usuario_id'];
$ip = function_exists('obterIPReal')
    ? obterIPReal()
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

// 🔥 Ponto de mudança se quiser cota separada: trocar para 'promover_membro'
$endpoint = 'gerenciar_membros';

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

if ((int)($result_rate['user_total'] ?? 0) >= 5 || (int)($result_rate['ip_total'] ?? 0) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um momento antes de realizar outra ação.']);
    exit;
}

$stmt_log_rate = $conn->prepare("INSERT INTO fenda_rate_limits (endpoint, usuario_id, ip_address) VALUES (?, ?, ?)");
$stmt_log_rate->bind_param('sis', $endpoint, $usuario_id, $ip);
$stmt_log_rate->execute();
$stmt_log_rate->close();

// ============================================================
// 3. CAPTURA DOS PARÂMETROS
// ============================================================
$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$usuario_alvo_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

if ($comunidade_id <= 0 || $usuario_alvo_id <= 0 || !in_array($acao, ['promover', 'rebaixar'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

// ============================================================
// 4. VERIFICA SE O USUÁRIO LOGADO É O CRIADOR
// ============================================================
$stmt = $conn->prepare("SELECT papel FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'");
$stmt->bind_param("ii", $comunidade_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if (!$admin || $admin['papel'] !== 'criador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Apenas o criador da comunidade pode promover ou rebaixar administradores.']);
    exit;
}

// ============================================================
// 5. VERIFICA SE O ALVO EXISTE E NÃO É O CRIADOR
// ============================================================
$stmt = $conn->prepare("SELECT papel, status FROM comunidade_membros 
                         WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $comunidade_id, $usuario_alvo_id);
$stmt->execute();
$res = $stmt->get_result();
$alvo = $res->fetch_assoc();
$stmt->close();

if (!$alvo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado nesta comunidade.']);
    exit;
}

if ($alvo['papel'] === 'criador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Não é possível promover ou rebaixar o criador da comunidade.']);
    exit;
}

if ($usuario_alvo_id == $usuario_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Você não pode promover ou rebaixar a si mesmo.']);
    exit;
}

// ============================================================
// 6. VALIDA A AÇÃO COM BASE NO PAPEL ATUAL
// ============================================================
$papel_atual = $alvo['papel'];
$status_atual = $alvo['status'];

if ($status_atual !== 'ativo') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este usuário não está ativo na comunidade.']);
    exit;
}

if ($acao === 'promover' && $papel_atual === 'admin') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este usuário já é um administrador.']);
    exit;
}

if ($acao === 'rebaixar' && $papel_atual !== 'admin') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este usuário não é um administrador.']);
    exit;
}

// ============================================================
// 7. EXECUTA A AÇÃO (PROMOVER OU REBAIXAR)
// ============================================================
$novo_papel = ($acao === 'promover') ? 'admin' : 'membro';

$stmt = $conn->prepare("UPDATE comunidade_membros SET papel = ? WHERE comunidade_id = ? AND usuario_id = ?");
$stmt->bind_param("sii", $novo_papel, $comunidade_id, $usuario_alvo_id);

if ($stmt->execute()) {
    $stmt->close();
    $acao_texto = ($acao === 'promover') ? 'promovido a administrador' : 'rebaixado a membro';
    fenda_log("🟢 [$acao] Usuário $usuario_alvo_id $acao_texto na comunidade $comunidade_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuário ' . $acao_texto . ' com sucesso.',
        'novo_papel' => $novo_papel
    ]);
} else {
    fenda_log("🔴 Erro ao $acao: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao executar ação.']);
    $stmt->close();
}
exit;