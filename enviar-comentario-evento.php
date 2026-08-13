<?php
/**
 * enviar-comentario-evento.php – Processa envio de comentários em eventos
 * 
 * 🔒 Segurança: CSRF, honeypot, prepared statements, sanitização, validação.
 * 🔔 Menções: suporte a @usuario com notificação (tipo = 'evento').
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-10 (estrutura)
 * 🌙 LUZ – ATUALIZAÇÃO 2026-08-13: adicionado campo `tipo` nas notificações.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO enviar-comentario-evento.php');

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÕES INICIAIS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

if (!empty($_POST['honeypot'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bot detectado.']);
    exit;
}

// ============================================================
// 2. CAPTURA E SANITIZAÇÃO
// ============================================================
$evento_id = isset($_POST['evento_id']) ? (int)$_POST['evento_id'] : 0;
$comentario_raw = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
$usuario_id = $_SESSION['usuario_id'];

$comentario_sanitizado = strip_tags($comentario_raw);
$comentario_sanitizado = htmlspecialchars($comentario_sanitizado, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

fenda_log("📝 Comentário (sanitizado): " . $comentario_sanitizado);

// ============================================================
// 3. VALIDAÇÕES
// ============================================================
if ($evento_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do evento inválido.']);
    exit;
}

if (empty($comentario_sanitizado) && !isset($_FILES['anexos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Escreva algo ou adicione uma imagem.']);
    exit;
}

if (strlen($comentario_sanitizado) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comentário excede 500 caracteres.']);
    exit;
}

// 🔥 BUSCA DADOS DO EVENTO (incluindo comunidade_id)
$stmt = $conn->prepare("SELECT id, nome, data_evento, status, comunidade_id FROM eventos WHERE id = ?");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$res = $stmt->get_result();
$evento = $res->fetch_assoc();
$stmt->close();

if (!$evento) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Evento não encontrado.']);
    exit;
}

if ($evento['status'] === 'cancelado') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este evento foi cancelado.']);
    exit;
}

if ($evento['status'] === 'encerrado' || strtotime($evento['data_evento']) < time()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este evento já foi encerrado.']);
    exit;
}

// 🔥 VERIFICA BANIMENTO (se o evento pertence a uma comunidade)
if ($evento['comunidade_id'] > 0) {
    $comunidade_id = (int)$evento['comunidade_id'];
    $stmt_ban = $conn->prepare("SELECT status FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt_ban->bind_param("ii", $comunidade_id, $usuario_id);
    $stmt_ban->execute();
    $res_ban = $stmt_ban->get_result();
    $membro = $res_ban->fetch_assoc();
    $stmt_ban->close();

    if (!$membro || $membro['status'] !== 'ativo') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para comentar neste evento (banido da comunidade).']);
        exit;
    }
}

// ============================================================
// 4. PROCESSAMENTO DE ANEXOS (com rollback)
// ============================================================
$anexos = [];
$caminhosEnviados = [];

if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
    $limite = 4;
    $contador = 0;
    foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp) {
        if ($contador >= $limite) break;
        if ($_FILES['anexos']['error'][$key] !== 0) continue;

        $file_data = [
            'name'     => $_FILES['anexos']['name'][$key],
            'type'     => $_FILES['anexos']['type'][$key],
            'tmp_name' => $tmp,
            'error'    => $_FILES['anexos']['error'][$key],
            'size'     => $_FILES['anexos']['size'][$key]
        ];
        $nome = processarUploadSeguro($file_data, 'comentarios', 'evcom', 2 * 1024 * 1024, $usuario_id);
        if ($nome !== false) {
            $caminhosEnviados[] = $nome;
            $anexos[] = ['id' => 'anexo-' . uniqid(), 'tipo' => 'imagem', 'caminho' => $nome];
            $contador++;
        } else {
            foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao enviar anexos.']);
            exit;
        }
    }
}
$anexos_json = !empty($anexos) ? json_encode($anexos) : null;

// ============================================================
// 5. INSERE COMENTÁRIO NO BANCO
// ============================================================
$sql = "INSERT INTO evento_comentarios (evento_id, usuario_id, comentario, anexos, data_criacao)
        VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $evento_id, $usuario_id, $comentario_sanitizado, $anexos_json);

if ($stmt->execute()) {
    $com_id = $conn->insert_id;
    $stmt->close();

    // ============================================================
    // 🔔 PROCESSAR MENÇÕES (@usuario) – AGORA COM TIPO 'evento'
    // ============================================================
    if (!empty($comentario_raw) && preg_match_all('/@([a-zA-Z0-9\._]+)/', $comentario_raw, $matches)) {
        $mencoes = array_unique($matches[1]);
        $autor_nome = $_SESSION['usuario_username'] ?? 'Alguém';
        $autor_id = $usuario_id;
        $evento_nome = $evento['nome'];

        foreach ($mencoes as $username) {
            $username_limpo = strtolower($username);
            $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = ?");
            $stmt_busca->bind_param("s", $username_limpo);
            $stmt_busca->execute();
            $res_busca = $stmt_busca->get_result();
            if ($alvo = $res_busca->fetch_assoc()) {
                $destinatario_id = $alvo['id'];
                if ($destinatario_id != $autor_id) {
                    $mensagem_notif = "@$autor_nome mencionou você em um comentário no evento \"$evento_nome\"";
                    
                    // 🔥 INSERE COM TIPO 'evento' E post_id = evento_id
                    $stmt_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida) VALUES (?, ?, 'evento', ?, 0)");
                    $stmt_notif->bind_param("iis", $destinatario_id, $evento_id, $mensagem_notif);
                    $stmt_notif->execute();
                    $stmt_notif->close();
                    fenda_log("🔔 Notificação de menção enviada para $destinatario_id (evento $evento_id)");
                }
            }
            $stmt_busca->close();
        }
    }

    // ============================================================
    // 6. RENDERIZA O HTML DO COMENTÁRIO
    // ============================================================
    $sql_user = "SELECT username, foto FROM usuarios WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $usuario_id);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    $avatar = !empty($user['foto']) ? obterUrlImagem($user['foto']) : 'uploads/ui/default.webp';
    $avatar_html = '<img src="' . htmlspecialchars($avatar) . '" class="avatar-p" style="border-radius:50%; margin-right:8px;" onerror="this.src=\'uploads/ui/default.webp\'">';

    $html = '
    <div class="bt-comentario-item" style="--cor-borda-glow: #ffbc00;">
        <div class="bt-comentario-meta">
            ' . $avatar_html . '
            <strong class="bt-comentario-autor" style="color:#ffbc00;">@' . htmlspecialchars($user['username']) . '</strong>
            <span class="bt-comentario-data">' . date('H:i') . '</span>
        </div>
        <p class="bt-comentario-texto">' . nl2br(htmlspecialchars($comentario_sanitizado)) . '</p>
    </div>';

    fenda_log("🟢 Comentário ID $com_id inserido com sucesso para evento $evento_id");

    echo json_encode([
        'success' => true,
        'message' => 'Comentário enviado!',
        'html' => $html
    ]);
} else {
    foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
    fenda_log("🔴 Erro ao inserir comentário: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário.']);
}
exit;