<?php
/**
 * processa-depoimento.php – Salva um depoimento como pendente com segurança
 * 
 * 🔔 Notificação: tipo = 'depoimento' (adicionado pela Lua)
 * 
 * Suporta requisições normais (POST) e AJAX (com redirecionamento ou JSON)
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Faça login para continuar.']);
        exit;
    }
    header("Location: index.php");
    exit();
}

// Verifica se os dados foram enviados via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método inválido.']);
        exit;
    }
    header("Location: feed.php");
    exit();
}

// ============================================================
// 1. CSRF TOKEN
// ============================================================
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'Token de segurança inválido. Tente novamente.';
    header("Location: feed.php");
    exit();
}

// ============================================================
// 2. HONEYPOT
// ============================================================
if (!empty($_POST['honeypot'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Bot detectado.']);
        exit;
    }
    header("Location: feed.php");
    exit();
}

// ============================================================
// 3. RATE LIMITING (por IP) – com INSERT IGNORE
// ============================================================
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_limit = 5;
$time_window = 3600;

$conn->query("CREATE TABLE IF NOT EXISTS rate_limiter_depoimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_tentativa (tentativa)
)");

$sql_rate = "SELECT COUNT(*) as total FROM rate_limiter_depoimentos WHERE ip_address = ? AND tentativa > NOW() - INTERVAL 1 HOUR";
$stmt_rate = $conn->prepare($sql_rate);
$stmt_rate->bind_param("s", $ip);
$stmt_rate->execute();
$res_rate = $stmt_rate->get_result();
$row_rate = $res_rate->fetch_assoc();
$stmt_rate->close();

if ($row_rate['total'] >= $rate_limit) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Você já enviou muitos depoimentos recentemente. Aguarde um pouco.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'Você já enviou muitos depoimentos recentemente. Aguarde um pouco.';
    header("Location: feed.php");
    exit();
}

$stmt_log = $conn->prepare("INSERT IGNORE INTO rate_limiter_depoimentos (ip_address) VALUES (?)");
$stmt_log->bind_param("s", $ip);
$stmt_log->execute();
$stmt_log->close();

// ============================================================
// 4. VALIDAÇÃO DOS DADOS
// ============================================================
$autor_id = $_SESSION['usuario_id'];
$destinatario_id = isset($_POST['destinatario_id']) ? (int)$_POST['destinatario_id'] : 0;
$mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';

if ($destinatario_id <= 0 || empty($mensagem)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'Preencha todos os campos.';
    header("Location: escrever-depoimento.php?destinatario=" . $destinatario_id);
    exit();
}

if (strlen($mensagem) < 3) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'O depoimento deve ter pelo menos 3 caracteres.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'O depoimento deve ter pelo menos 3 caracteres.';
    header("Location: escrever-depoimento.php?destinatario=" . $destinatario_id);
    exit();
}

if (strlen($mensagem) > 500) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'O depoimento excede o limite de 500 caracteres.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'O depoimento excede o limite de 500 caracteres.';
    header("Location: escrever-depoimento.php?destinatario=" . $destinatario_id);
    exit();
}

if ($autor_id == $destinatario_id) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Você não pode escrever um depoimento para si mesmo.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'Você não pode escrever um depoimento para si mesmo.';
    header("Location: feed.php");
    exit();
}

// ============================================================
// 5. VERIFICA SE O DESTINATÁRIO EXISTE
// ============================================================
$stmt_check = $conn->prepare("SELECT id, username FROM usuarios WHERE id = ?");
$stmt_check->bind_param("i", $destinatario_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();
$destinatario = $res_check->fetch_assoc();
$stmt_check->close();

if (!$destinatario) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Destinatário não encontrado.']);
        exit;
    }
    $_SESSION['erro_depoimento'] = 'Destinatário não encontrado.';
    header("Location: feed.php");
    exit();
}

// ============================================================
// 6. INSERE O DEPOIMENTO
// ============================================================
$sql = "INSERT INTO depoimentos (autor_id, destinatario_id, mensagem, status, data_criacao) VALUES (?, ?, ?, 'pendente', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $autor_id, $destinatario_id, $mensagem);
$stmt->execute();
$depoimento_id = $conn->insert_id;
$stmt->close();

if ($depoimento_id) {
    // ============================================================
    // 🔔 NOTIFICAÇÃO (com tipo = 'depoimento')
    // ============================================================
    $mensagem_notif = "@" . $_SESSION['usuario_username'] . " escreveu um depoimento para você!";
    
    // 🔥 INSERE COM TIPO 'depoimento' (post_id = NULL)
    $stmt_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida, data_criacao) 
                                   VALUES (?, NULL, 'depoimento', ?, 0, NOW())");
    $stmt_notif->bind_param("is", $destinatario_id, $mensagem_notif);
    $stmt_notif->execute();
    $stmt_notif->close();

    //  VERIFICA SE É REQUISIÇÃO AJAX
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Depoimento enviado com sucesso! Aguarde a aprovação.']);
        exit;
    } else {
        $_SESSION['sucesso_depoimento'] = 'Depoimento enviado com sucesso! Aguarde a aprovação do destinatário.';
        header("Location: ver-perfil.php?user=" . urlencode($destinatario['username']));
        exit();
    }
} else {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar depoimento. Tente novamente.']);
        exit;
    } else {
        $_SESSION['erro_depoimento'] = 'Erro ao enviar depoimento. Tente novamente.';
        header("Location: escrever-depoimento.php?destinatario=" . $destinatario_id);
        exit();
    }
}