<?php
/**
 * processa-evento.php – Processa a criação de um novo evento
 * 
 * 🔒 Segurança: CSRF, honeypot, validação, rollback.
 * 
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔔 Adicionado: notificações para membros da comunidade (com nome e link direto).
 * 🌙 LUZ – 2026-08-15: adicionado campo `tipo = 'evento'` nas notificações.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO processa-evento.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: balanga-teras.php");
    exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Token de segurança inválido.');
}

// Honeypot
if (!empty($_POST['honeypot'])) {
    die('Acesso negado.');
}

// ============================================================
// 1. DADOS DO FORMULÁRIO
// ============================================================
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$local = trim($_POST['local'] ?? '');
$data_evento = $_POST['data_evento'] ?? '';
$comunidade_id = isset($_POST['comunidade_id']) && (int)$_POST['comunidade_id'] > 0 ? (int)$_POST['comunidade_id'] : null;
$usuario_id = $_SESSION['usuario_id'];

fenda_log("📝 Nome recebido: '$nome'");
fenda_log("📝 Comunidade ID: " . ($comunidade_id ?? 'NENHUMA'));

// ============================================================
// 2. VALIDAÇÕES
// ============================================================
if (empty($nome) || strlen($nome) < 3) {
    fenda_log("❌ Nome inválido: '$nome'");
    $_SESSION['erro_evento'] = 'O nome do evento deve ter pelo menos 3 caracteres.';
    header("Location: criar-evento.php");
    exit;
}
if (empty($data_evento) || strtotime($data_evento) < time()) {
    fenda_log("❌ Data inválida: '$data_evento'");
    $_SESSION['erro_evento'] = 'A data do evento deve ser futura.';
    header("Location: criar-evento.php");
    exit;
}

// ============================================================
// 3. UPLOAD DA CAPA
// ============================================================
$capa_nome = null;
$capa_enviada = false;
if (isset($_FILES['capa']) && $_FILES['capa']['error'] === 0) {
    $capa_nome = processarUploadSeguro($_FILES['capa'], 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
    if ($capa_nome === false) {
        fenda_log("❌ Falha no upload da capa");
        $_SESSION['erro_evento'] = 'Erro ao enviar a capa (formato/tamanho inválido).';
        header("Location: criar-evento.php");
        exit;
    }
    $capa_enviada = true;
    fenda_log("🖼️ Capa enviada: $capa_nome");
}

// ============================================================
// 4. UPLOAD DA GALERIA (ANEXOS)
// ============================================================
$anexos = [];
$anexos_enviados = [];
if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
    $caminhos = [];
    foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp) {
        if ($_FILES['anexos']['error'][$key] !== 0) continue;
        if (count($caminhos) >= 4) break;
        $file_data = [
            'name' => $_FILES['anexos']['name'][$key],
            'type' => $_FILES['anexos']['type'][$key],
            'tmp_name' => $tmp,
            'error' => $_FILES['anexos']['error'][$key],
            'size' => $_FILES['anexos']['size'][$key]
        ];
        $nome_anexo = processarUploadSeguro($file_data, 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
        if ($nome_anexo !== false) {
            $caminhos[] = $nome_anexo;
            fenda_log("🆕 Anexo enviado: $nome_anexo");
        }
    }
    $anexos = array_map(function($path) {
        return ['id' => 'anexo-' . uniqid(), 'tipo' => 'imagem', 'caminho' => $path];
    }, $caminhos);
    $anexos_enviados = $caminhos;
}
$anexos_json = !empty($anexos) ? json_encode($anexos) : null;

// ============================================================
// 5. INSERÇÃO NO BANCO
// ============================================================
fenda_log("✅ Salvando evento com nome: '$nome'");

$sql = "INSERT INTO eventos (criador_id, comunidade_id, nome, descricao, local, data_evento, imagem_url, anexos, status, data_criacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissssss", $usuario_id, $comunidade_id, $nome, $descricao, $local, $data_evento, $capa_nome, $anexos_json);

if ($stmt->execute()) {
    $evento_id = $conn->insert_id;
    $stmt->close();
    fenda_log("✅ Evento criado com sucesso! ID: $evento_id, Nome: '$nome'");

    // ============================================================
    // 🔔 NOTIFICAÇÕES: avisa todos os membros ativos da comunidade
    // 🔥 CORREÇÃO: adicionado campo `tipo = 'evento'`
    // ============================================================
    if ($comunidade_id !== null) {
        // 🔥 1. Busca o nome da comunidade (ajuste da Djê)
        $stmt_nome = $conn->prepare("SELECT nome FROM comunidades WHERE id = ?");
        $stmt_nome->bind_param("i", $comunidade_id);
        $stmt_nome->execute();
        $res_nome = $stmt_nome->get_result();
        $comunidade = $res_nome->fetch_assoc();
        $stmt_nome->close();

        if ($comunidade) {
            $nome_comunidade = $comunidade['nome'];

            // 🔥 2. Insere notificação com post_id = evento_id E tipo = 'evento'
            $stmt_notif = $conn->prepare("
                INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida, data_criacao)
                SELECT cm.usuario_id, ?, 'evento', CONCAT('📢 Novo evento em \"', ?, '\": ', ?), 0, NOW()
                FROM comunidade_membros cm
                WHERE cm.comunidade_id = ? AND cm.status = 'ativo' AND cm.usuario_id != ?
            ");
            $stmt_notif->bind_param("issii", $evento_id, $nome_comunidade, $nome, $comunidade_id, $usuario_id);
            $stmt_notif->execute();
            $notificacoes_enviadas = $stmt_notif->affected_rows;
            $stmt_notif->close();
            fenda_log("🔔 Notificações de evento enviadas: $notificacoes_enviadas membros da comunidade $comunidade_id (tipo = 'evento')");
        } else {
            fenda_log("⚠️ Comunidade não encontrada para notificação: $comunidade_id");
        }
    }

    // ============================================================
    // REDIRECIONAMENTO
    // ============================================================
    $_SESSION['sucesso_evento'] = 'Evento criado com sucesso!';
    header("Location: evento.php?id=$evento_id");
    exit;

} else {
    // ============================================================
    // ROLLBACK: Se o banco falhou, remove os arquivos do B2
    // ============================================================
    $erro = $stmt->error;
    $stmt->close();
    fenda_log("❌ Erro no banco: $erro");

    if ($capa_enviada && !empty($capa_nome)) {
        rollbackUpload($capa_nome, $usuario_id);
        fenda_log("🔄 Rollback da capa: $capa_nome");
    }
    foreach ($anexos_enviados as $caminho) {
        if (!empty($caminho)) {
            rollbackUpload($caminho, $usuario_id);
            fenda_log("🔄 Rollback de anexo: $caminho");
        }
    }

    $_SESSION['erro_evento'] = 'Erro ao criar evento: ' . $erro;
    header("Location: criar-evento.php");
    exit;
}
