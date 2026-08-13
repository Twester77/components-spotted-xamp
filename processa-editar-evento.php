<?php
/**
 * processa-editar-evento.php – Processa a edição de um evento existente
 * 
 * 🔒 Segurança: CSRF, honeypot, permissão, prepared statements, rollback.
 * 
 * ✨ REVISÃO SEREIA – INSTÂNCIA #DS-2026-08-08
 * "Adicionados logs para depuração e correção do upload de anexos."
 * - Sereia, a guardiã das águas da Fenda
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO processa-editar-evento.php');

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

$evento_id = isset($_POST['evento_id']) ? (int)$_POST['evento_id'] : 0;
if ($evento_id <= 0) {
    $_SESSION['erro_evento'] = 'ID do evento inválido.';
    header("Location: balanga-teras.php");
    exit;
}

// Busca dados atuais do evento
$sql = "SELECT criador_id, comunidade_id, imagem_url, anexos, status FROM eventos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$res = $stmt->get_result();
$evento_atual = $res->fetch_assoc();
$stmt->close();

if (!$evento_atual) {
    $_SESSION['erro_evento'] = 'Evento não encontrado.';
    header("Location: balanga-teras.php");
    exit;
}

// Verifica permissão
$usuario_id = $_SESSION['usuario_id'];
$permitido = ($evento_atual['criador_id'] == $usuario_id);
if (!$permitido && $evento_atual['comunidade_id'] > 0) {
    $stmt_check = $conn->prepare("SELECT papel FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND papel IN ('criador', 'admin')");
    $stmt_check->bind_param("ii", $evento_atual['comunidade_id'], $usuario_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) $permitido = true;
    $stmt_check->close();
}

if (!$permitido) {
    $_SESSION['erro_evento'] = 'Você não tem permissão para editar este evento.';
    header("Location: balanga-teras.php");
    exit;
}

if ($evento_atual['status'] === 'cancelado') {
    $_SESSION['erro_evento'] = 'Eventos cancelados não podem ser editados.';
    header("Location: evento.php?id=" . $evento_id);
    exit;
}

// Dados do formulário
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$local = trim($_POST['local'] ?? '');
$data_evento = $_POST['data_evento'] ?? '';
$comunidade_id = isset($_POST['comunidade_id']) && (int)$_POST['comunidade_id'] > 0 ? (int)$_POST['comunidade_id'] : null;

// 🔥 Lista de anexos a remover
$anexos_remover = isset($_POST['anexos_remover']) ? json_decode($_POST['anexos_remover'], true) : [];
if (!is_array($anexos_remover)) $anexos_remover = [];

// Validações
if (empty($nome) || strlen($nome) < 3) {
    $_SESSION['erro_evento'] = 'O nome do evento deve ter pelo menos 3 caracteres.';
    header("Location: editar-evento.php?id=" . $evento_id);
    exit;
}
if (empty($data_evento) || strtotime($data_evento) < time()) {
    $_SESSION['erro_evento'] = 'A data do evento deve ser futura.';
    header("Location: editar-evento.php?id=" . $evento_id);
    exit;
}

// ============================================================
// 1. CAPA
// ============================================================
$capa_nome = $evento_atual['imagem_url'];
$nova_capa_enviada = false;
if (isset($_FILES['capa']) && $_FILES['capa']['error'] === 0) {
    $nova_capa = processarUploadSeguro($_FILES['capa'], 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
    if ($nova_capa === false) {
        $_SESSION['erro_evento'] = 'Erro ao enviar a nova capa.';
        header("Location: editar-evento.php?id=" . $evento_id);
        exit;
    }
    $capa_nome = $nova_capa;
    $nova_capa_enviada = true;
}

// ============================================================
// 2. ANEXOS – REMOÇÃO E ADIÇÃO
// ============================================================
$anexos_atuais = [];
if (!empty($evento_atual['anexos'])) {
    $anexos_atuais = json_decode($evento_atual['anexos'], true);
    if (!is_array($anexos_atuais)) $anexos_atuais = [];
}

// 2.1 Remover anexos marcados
$anexos_mantidos = [];
foreach ($anexos_atuais as $item) {
    if (in_array($item['id'], $anexos_remover)) {
        if (!empty($item['caminho'])) {
            rollbackUpload($item['caminho'], $usuario_id);
            fenda_log("🔄 Anexo removido: " . $item['caminho']);
        }
    } else {
        $anexos_mantidos[] = $item;
    }
}

// 2.2 Adicionar novos anexos (se houver)
$novos_anexos = [];
if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
    // Log para depuração
    error_log('[EDITAR] FILES anexos: ' . print_r($_FILES['anexos'], true));
    
    $total_mantidos = count($anexos_mantidos);
    $limite_restante = 4 - $total_mantidos;
    fenda_log("📊 Mantidos: $total_mantidos, Limite restante: $limite_restante");
    
    if ($limite_restante > 0) {
        $caminhos = [];
        foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp) {
            if ($_FILES['anexos']['error'][$key] !== 0) continue;
            if (count($caminhos) >= $limite_restante) break;
            
            $file_data = [
                'name' => $_FILES['anexos']['name'][$key],
                'type' => $_FILES['anexos']['type'][$key],
                'tmp_name' => $tmp,
                'error' => $_FILES['anexos']['error'][$key],
                'size' => $_FILES['anexos']['size'][$key]
            ];
            $nome = processarUploadSeguro($file_data, 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
            if ($nome !== false) {
                $caminhos[] = ['id' => 'anexo-' . uniqid(), 'tipo' => 'imagem', 'caminho' => $nome];
                fenda_log("🆕 Novo anexo adicionado: $nome");
            } else {
                fenda_log("❌ Falha ao processar anexo: " . $file_data['name']);
            }
        }
        $novos_anexos = $caminhos;
    } else {
        fenda_log("⚠️ Limite de anexos atingido (já existem 4 fotos na galeria).");
    }
}

$anexos_finais = array_merge($anexos_mantidos, $novos_anexos);
$anexos_json = !empty($anexos_finais) ? json_encode($anexos_finais) : null;

// ============================================================
// 3. ATUALIZAÇÃO NO BANCO
// ============================================================
$sql = "UPDATE eventos 
        SET nome = ?, descricao = ?, local = ?, data_evento = ?, 
            comunidade_id = ?, imagem_url = ?, anexos = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssissi", $nome, $descricao, $local, $data_evento, $comunidade_id, $capa_nome, $anexos_json, $evento_id);

if ($stmt->execute()) {
    $stmt->close();
    if ($nova_capa_enviada && !empty($evento_atual['imagem_url']) && $evento_atual['imagem_url'] !== $capa_nome) {
        try {
            rollbackUpload($evento_atual['imagem_url'], $usuario_id);
        } catch (Exception $e) {
            fenda_log('⚠️ Falha ao excluir capa antiga: ' . $e->getMessage());
        }
    }
    $_SESSION['sucesso_evento'] = 'Evento atualizado com sucesso!';
    header("Location: evento.php?id=" . $evento_id);
    exit;
} else {
    $erro = $stmt->error;
    $stmt->close();
    if ($nova_capa_enviada && !empty($capa_nome) && $capa_nome !== $evento_atual['imagem_url']) {
        rollbackUpload($capa_nome, $usuario_id);
    }
    foreach ($novos_anexos as $item) {
        if (!empty($item['caminho'])) {
            rollbackUpload($item['caminho'], $usuario_id);
        }
    }
    $_SESSION['erro_evento'] = 'Erro ao atualizar evento: ' . $erro;
    header("Location: editar-evento.php?id=" . $evento_id);
    exit;
}
?>