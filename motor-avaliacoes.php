<?php
/**
 * motor-avaliacoes.php – Votação e exibição de avaliações (Legal, Confiável, Sexy)
 * 
 * GET: Retorna o HTML das estrelas e médias para um perfil.
 * POST: Processa um voto (requer CSRF e rate limiting).
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

// ============================================================
// 1. GET – EXIBIR AVALIAÇÕES (HTML)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
    if ($usuario_id <= 0) {
        echo '<p class="sem-avaliacoes">Nenhuma avaliação disponível.</p>';
        exit;
    }

    // Busca médias e contagens por tipo
    $sql = "SELECT tipo, COUNT(*) as total, AVG(nota) as media 
            FROM avaliacoes_perfil 
            WHERE usuario_avaliado_id = ? 
            GROUP BY tipo";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $avaliacoes = [];
    while ($row = $res->fetch_assoc()) {
        $avaliacoes[$row['tipo']] = [
            'total' => (int)$row['total'],
            'media' => round((float)$row['media'], 1)
        ];
    }
    $stmt->close();

    // Inicializa com zeros para os três tipos
    $tipos = ['legal' => '💚', 'confiavel' => '⭐', 'sexy' => '🔥'];
    foreach ($tipos as $tipo => $emoji) {
        if (!isset($avaliacoes[$tipo])) {
            $avaliacoes[$tipo] = ['total' => 0, 'media' => 0];
        }
    }

    // Verifica se o usuário logado já votou em cada categoria
    $ja_votou = [];
    if (isset($_SESSION['usuario_id'])) {
        $meu_id = $_SESSION['usuario_id'];
        $sql_check = "SELECT tipo FROM avaliacoes_perfil 
                      WHERE usuario_avaliado_id = ? AND usuario_avaliador_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $usuario_id, $meu_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        while ($row = $res_check->fetch_assoc()) {
            $ja_votou[] = $row['tipo'];
        }
        $stmt_check->close();
    }

    // 🔥 SEMPRE GERA O HTML DAS ESTRELAS, MESMO COM ZERO VOTOS
    $html = '';
    $total_votos = array_sum(array_column($avaliacoes, 'total'));

    // Mensagem amigável se não houver votos (mas NÃO substitui o HTML)
    if ($total_votos === 0) {
        if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $usuario_id) {
            $html .= '<p class="avaliacao-aviso">✨ Você ainda não recebeu avaliações. Compartilhe seu perfil com a galera!</p>';
        } else {
            $html .= '<p class="avaliacao-aviso">🌟 Este habitante ainda não foi avaliado. Seja o primeiro a votar!</p>';
        }
    }

    // Gera o HTML das estrelas (sempre)
    $html .= '<div class="avaliacoes-container">';
    foreach ($tipos as $tipo => $emoji) {
        $media = $avaliacoes[$tipo]['media'];
        $total = $avaliacoes[$tipo]['total'];
        $ja_votei = in_array($tipo, $ja_votou);
        $estrelas_cheias = floor($media);
        $estrelas_meia = ($media - $estrelas_cheias) >= 0.5 ? 1 : 0;
        $estrelas_vazias = 5 - $estrelas_cheias - $estrelas_meia;

        $html .= '<div class="avaliacao-item" data-tipo="' . $tipo . '">';
        $html .= '  <div class="avaliacao-header">';
        $html .= '    <span class="avaliacao-emoji">' . $emoji . '</span>';
        $html .= '    <span class="avaliacao-nome">' . ucfirst($tipo) . '</span>';
        $html .= '    <span class="avaliacao-media">' . number_format($media, 1) . ' ⭐</span>';
        $html .= '  </div>';
        $html .= '  <div class="estrelas-container" data-tipo="' . $tipo . '">';
        for ($i = 1; $i <= 5; $i++) {
            $classe = 'estrela';
            if ($i <= $estrelas_cheias) {
                $classe .= ' cheia';
            } elseif ($i == $estrelas_cheias + 1 && $estrelas_meia) {
                $classe .= ' meia';
            } else {
                $classe .= ' vazia';
            }
            $html .= '    <span class="' . $classe . '" data-nota="' . $i . '" data-tipo="' . $tipo . '">★</span>';
        }
        $html .= '  </div>';
        $html .= '  <div class="avaliacao-footer">';
        $html .= '    <span class="avaliacao-total">' . $total . ' voto' . ($total != 1 ? 's' : '') . '</span>';
        if (!$ja_votei && isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] != $usuario_id) {
            $html .= '    <button class="btn-votar-estrela" data-tipo="' . $tipo . '">Votar</button>';
        } elseif ($ja_votei) {
            $html .= '    <span class="ja-votou">✅ Você já votou</span>';
        }
        $html .= '  </div>';
        $html .= '</div>';
    }
    $html .= '</div>';

    echo $html;
    exit;
}

// ============================================================
// 2. POST – PROCESSAR VOTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2.1 Verifica login
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['erro' => 'Faça login para votar.']);
        exit;
    }

    // 2.2 CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['erro' => 'Token de segurança inválido.']);
        exit;
    }

    // 2.3 Honeypot
    if (!empty($_POST['honeypot'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Bot detectado.']);
        exit;
    }

    // 2.4 Rate Limiting (por IP) – com INSERT IGNORE para evitar race condition
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rate_limit = 5; // 5 votos por hora
    $conn->query("CREATE TABLE IF NOT EXISTS rate_limiter_avaliacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_tentativa (tentativa)
    )");
    $sql_rate = "SELECT COUNT(*) as total FROM rate_limiter_avaliacoes WHERE ip_address = ? AND tentativa > NOW() - INTERVAL 1 HOUR";
    $stmt_rate = $conn->prepare($sql_rate);
    $stmt_rate->bind_param("s", $ip);
    $stmt_rate->execute();
    $res_rate = $stmt_rate->get_result();
    $row_rate = $res_rate->fetch_assoc();
    $stmt_rate->close();
    if ($row_rate['total'] >= $rate_limit) {
        http_response_code(429);
        echo json_encode(['erro' => 'Você já votou muitas vezes recentemente. Aguarde um pouco.']);
        exit;
    }

    // 🔥 CORREÇÃO: Usa INSERT IGNORE para evitar race condition
    $stmt_log = $conn->prepare("INSERT IGNORE INTO rate_limiter_avaliacoes (ip_address) VALUES (?)");
    $stmt_log->bind_param("s", $ip);
    $stmt_log->execute();
    $stmt_log->close();

    // 2.5 Valida dos dados
    $usuario_avaliado_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $nota = isset($_POST['nota']) ? (int)$_POST['nota'] : 0;
    $usuario_avaliador_id = $_SESSION['usuario_id'];

    if ($usuario_avaliado_id <= 0 || !in_array($tipo, ['legal', 'confiavel', 'sexy']) || $nota < 1 || $nota > 5) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados inválidos.']);
        exit;
    }
    if ($usuario_avaliador_id == $usuario_avaliado_id) {
        http_response_code(403);
        echo json_encode(['erro' => 'Você não pode se autoavaliar.']);
        exit;
    }

    // 2.6 Verifica se já votou
    $sql_check = "SELECT id FROM avaliacoes_perfil 
                  WHERE usuario_avaliado_id = ? AND usuario_avaliador_id = ? AND tipo = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("iis", $usuario_avaliado_id, $usuario_avaliador_id, $tipo);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['erro' => 'Você já votou nesta categoria para este perfil.']);
        exit;
    }
    $stmt_check->close();

    // 2.7 Insere o voto
    $sql = "INSERT INTO avaliacoes_perfil (usuario_avaliado_id, usuario_avaliador_id, tipo, nota, data_avaliacao) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $usuario_avaliado_id, $usuario_avaliador_id, $tipo, $nota);
    if ($stmt->execute()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Voto registrado com sucesso!']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar voto.']);
    }
    $stmt->close();
    exit;
}