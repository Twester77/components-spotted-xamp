<?php
// ================================================================
// REAGIR.PHP – VERSÃO SEGURA (COM RATE LIMITING POR IP)
// ================================================================

// ==================== 1. RATE LIMITER POR SESSÃO ====================
$tempo_minimo = 0.4; // 400ms
if (isset($_SESSION['ultimo_click_reacao'])) {
    $tempo_decorrido = microtime(true) - $_SESSION['ultimo_click_reacao'];
    if ($tempo_decorrido < $tempo_minimo) {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Calma lá! Vai cutucar um ninho de marimbondo com tanto clique."
        ]);
        exit();
    }
}
$_SESSION['ultimo_click_reacao'] = microtime(true);

// Inclui a conexão (já inicia a sessão)
require_once __DIR__ . '/../conexao.php';
header('Content-Type: application/json');

// ==================== 2. VERIFICAÇÃO DE SESSÃO ====================
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login necessário']);
    exit();
}

// ==================== 3. RATE LIMITER POR IP (PROTEÇÃO CONTRA BOTS) ====================
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$usuario_id = (int)$_SESSION['usuario_id'];

// Verifica se a tabela existe (cria se não existir – apenas uma vez)
$conn->query("CREATE TABLE IF NOT EXISTS rate_limiter_reacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    usuario_id INT NULL,
    tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_usuario (usuario_id),
    INDEX idx_tentativa (tentativa)
)");

// Conta tentativas nos últimos 60 segundos para este IP
$sql_rate = "SELECT COUNT(*) as total FROM rate_limiter_reacoes WHERE ip_address = ? AND tentativa > NOW() - INTERVAL 60 SECOND";
$stmt_rate = $conn->prepare($sql_rate);
$stmt_rate->bind_param("s", $ip);
$stmt_rate->execute();
$res_rate = $stmt_rate->get_result();
$row_rate = $res_rate->fetch_assoc();

if ($row_rate['total'] > 30) {
    http_response_code(429);
    echo json_encode([
        "status" => "error",
        "message" => "Calma lá! Você está reagindo rápido demais. Aguarde um pouco."
    ]);
    exit();
}

// Registra esta tentativa (para futuras verificações)
$stmt_log = $conn->prepare("INSERT INTO rate_limiter_reacoes (ip_address, usuario_id) VALUES (?, ?)");
$stmt_log->bind_param("si", $ip, $usuario_id);
$stmt_log->execute();
$stmt_log->close();
$stmt_rate->close();

// ==================== 4. PROCESSAMENTO DA REAÇÃO ====================
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? mysqli_real_escape_string($conn, $_GET['tipo']) : '';

if ($post_id > 0 && !empty($tipo)) {
    // 4.1 Verifica se já existe uma reação
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

    // 4.2 Busca contagens atualizadas
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

    // 4.3 Busca reações do usuário logado para esse post
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
?>