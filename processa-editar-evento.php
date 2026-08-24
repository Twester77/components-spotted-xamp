<?php
/**
 * processa-editar-evento.php – Processa a edição de um evento existente
 *
 * 🔒 Segurança: CSRF, honeypot, permissão, prepared statements, rollback.
 *
 * 🔧 CORREÇÃO NEREIDA/DJÊ – INSTÂNCIA #DS-2026-08-24 (v2)
 *    "Isolamento de variável $nome_anexo para evitar sobrescrita do título.
 *     Processamento de GIFs (gif_urls[]) com limite de 4 anexos.
 *     Suporte a resposta AJAX com JSON e redirecionamento suave.
 *     Rollback atômico em caso de falha."
 * - Nereida & Djê, as guardiãs das águas
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

// ============================================================
// 1. DADOS DO FORMULÁRIO
// ============================================================
$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$local = trim($_POST['local'] ?? '');
$data_evento = $_POST['data_evento'] ?? '';
$comunidade_id = isset($_POST['comunidade_id']) && (int)$_POST['comunidade_id'] > 0 ? (int)$_POST['comunidade_id'] : null;

// 🔥 Lista de anexos a remover (JSON enviado pelo front-end)
$anexos_remover = isset($_POST['anexos_remover']) ? json_decode($_POST['anexos_remover'], true) : [];
if (!is_array($anexos_remover)) $anexos_remover = [];

// ============================================================
// 2. VALIDAÇÕES BÁSICAS
// ============================================================
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
// 3. UPLOAD DA CAPA (se enviada)
// ============================================================
$capa_nome = $evento_atual['imagem_url'];
$nova_capa_enviada = false;
if (isset($_FILES['capa']) && $_FILES['capa']['error'] === 0) {
    $nova_capa = processarUploadSeguro($_FILES['capa'], 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
    if ($nova_capa === false) {
        $_SESSION['erro_evento'] = 'Erro ao enviar a nova capa (formato/tamanho inválido).';
        header("Location: editar-evento.php?id=" . $evento_id);
        exit;
    }
    $capa_nome = $nova_capa;
    $nova_capa_enviada = true;
}

// ============================================================
// 4. ANEXOS – REMOÇÃO E ADIÇÃO (COM GIFs)
// ============================================================
// 4.1 Decodifica os anexos atuais do evento
$anexos_atuais = [];
if (!empty($evento_atual['anexos'])) {
    $anexos_atuais = json_decode($evento_atual['anexos'], true);
    if (!is_array($anexos_atuais)) $anexos_atuais = [];
}

// 4.2 Remove os anexos marcados (atualiza $anexos_mantidos)
$anexos_mantidos = [];
foreach ($anexos_atuais as $item) {
    if (in_array($item['id'], $anexos_remover)) {
        // Se for imagem, deleta do B2 (GIFs são URLs externas, não deletamos)
        if (!empty($item['caminho']) && $item['tipo'] === 'imagem') {
            rollbackUpload($item['caminho'], $usuario_id);
            fenda_log("🔄 Anexo removido (imagem): " . $item['caminho']);
        } else {
            fenda_log("🔄 Anexo removido (GIF/URL): " . ($item['url'] ?? 'desconhecido'));
        }
    } else {
        $anexos_mantidos[] = $item;
    }
}

// 4.3 Inicializa array de novos anexos
$novos_anexos = [];

// 🔥 4.4 - GIFs externos (via POST)
$gif_urls = isset($_POST['gif_urls']) && is_array($_POST['gif_urls']) ? $_POST['gif_urls'] : [];
if (!empty($gif_urls)) {
    fenda_log("🎬 GIFs recebidos na edição: " . count($gif_urls));
    foreach ($gif_urls as $gif_url) {
        $gif_url = trim($gif_url);
        if (count($anexos_mantidos) + count($novos_anexos) >= 4) {
            fenda_log("⚠️ Limite de 4 anexos atingido (GIFs)");
            break;
        }
        if (filter_var($gif_url, FILTER_VALIDATE_URL) &&
            (strpos($gif_url, 'giphy.com') !== false || strpos($gif_url, 'media.giphy.com') !== false)) {
            $novos_anexos[] = [
                'id' => 'anexo-' . uniqid(),
                'tipo' => 'gif',
                'url' => $gif_url
            ];
            fenda_log("✅ GIF adicionado na edição: $gif_url");
        } else {
            fenda_log("⚠️ GIF ignorado (URL inválida): $gif_url");
        }
    }
}

// 4.5 - Múltiplos arquivos (imagens) – 🔥 USANDO $nome_anexo
if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
    $files = array_filter($_FILES['anexos']['name']);
    if (!empty($files)) {
        $total_mantidos = count($anexos_mantidos);
        $total_novos_gifs = count($novos_anexos);
        $limite_restante = 4 - $total_mantidos - $total_novos_gifs;
        fenda_log("📊 Mantidos: $total_mantidos, GIFs novos: $total_novos_gifs, Limite restante: $limite_restante");

        if ($limite_restante > 0) {
            foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp) {
                if ($_FILES['anexos']['error'][$key] !== 0) continue;
                if ($limite_restante <= 0) break;

                $file_data = [
                    'name'     => $_FILES['anexos']['name'][$key],
                    'type'     => $_FILES['anexos']['type'][$key],
                    'tmp_name' => $tmp,
                    'error'    => $_FILES['anexos']['error'][$key],
                    'size'     => $_FILES['anexos']['size'][$key]
                ];

                // 🔥 CORREÇÃO: usar $nome_anexo em vez de $nome
                $nome_anexo = processarUploadSeguro($file_data, 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
                if ($nome_anexo !== false) {
                    $novos_anexos[] = [
                        'id' => 'anexo-' . uniqid(),
                        'tipo' => 'imagem',
                        'caminho' => $nome_anexo
                    ];
                    fenda_log("🆕 Nova imagem adicionada (edição): $nome_anexo");
                    $limite_restante--;
                } else {
                    fenda_log("❌ Falha ao processar anexo: " . $file_data['name']);
                }
            }
        } else {
            fenda_log("⚠️ Limite de anexos atingido (já existem 4 fotos/GIFs na galeria).");
        }
    }
}

// 4.6 - Monta o array final de anexos
$anexos_finais = array_merge($anexos_mantidos, $novos_anexos);
$anexos_json = !empty($anexos_finais) ? json_encode($anexos_finais) : null;

// ============================================================
// 5. ATUALIZAÇÃO NO BANCO
// ============================================================
$sql = "UPDATE eventos 
        SET nome = ?, descricao = ?, local = ?, data_evento = ?, 
            comunidade_id = ?, imagem_url = ?, anexos = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssissi", $nome, $descricao, $local, $data_evento, $comunidade_id, $capa_nome, $anexos_json, $evento_id);

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($stmt->execute()) {
    $stmt->close();
    // Se nova capa foi enviada e é diferente da antiga, deleta a antiga do B2
    if ($nova_capa_enviada && !empty($evento_atual['imagem_url']) && $evento_atual['imagem_url'] !== $capa_nome) {
        try {
            rollbackUpload($evento_atual['imagem_url'], $usuario_id);
        } catch (Exception $e) {
            fenda_log('⚠️ Falha ao excluir capa antiga: ' . $e->getMessage());
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'redirect' => "evento.php?id={$evento_id}"]);
        exit;
    }

    $_SESSION['sucesso_evento'] = 'Evento atualizado com sucesso!';
    header("Location: evento.php?id=" . $evento_id);
    exit;
} else {
    $erro = $stmt->error;
    $stmt->close();
    fenda_log("❌ Erro no banco: $erro");

    // Rollback: deleta os arquivos já enviados para o B2
    if ($nova_capa_enviada && !empty($capa_nome) && $capa_nome !== $evento_atual['imagem_url']) {
        rollbackUpload($capa_nome, $usuario_id);
        fenda_log("🔄 Rollback da nova capa: $capa_nome");
    }
    // Rollback das novas imagens
    foreach ($novos_anexos as $item) {
        if (!empty($item['caminho']) && $item['tipo'] === 'imagem') {
            rollbackUpload($item['caminho'], $usuario_id);
            fenda_log("🔄 Rollback de nova imagem: " . $item['caminho']);
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar evento: ' . $erro]);
        exit;
    }

    $_SESSION['erro_evento'] = 'Erro ao atualizar evento: ' . $erro;
    header("Location: editar-evento.php?id=" . $evento_id);
    exit;
}