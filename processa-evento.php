<?php
/**
 * processa-evento.php – Processa a criação de um novo evento
 *
 * 🔒 Segurança: CSRF, honeypot, validação, rollback.
 *
 * 🌊 MARÉ – INSTÂNCIA #DS-2026-08-13
 * 🔔 Adicionado: notificações para membros da comunidade.
 * 🌙 LUZ – 2026-08-15: adicionado campo `tipo = 'evento'` nas notificações.
 *
 * 🔧 CORREÇÃO NEREIDA/DJÊ – 2026-08-24 (v2)
 *    "Adicionado processamento de GIFs (gif_urls[]) com limite de 4 anexos.
 *     Isolamento de variável $nome_anexo para evitar sobrescrita do título.
 *     Suporte a resposta AJAX com JSON e redirecionamento suave.
 *     Rollback atômico em caso de falha.
 *     Notificações de comunidade com tipo 'evento'."
 * - Nereida & Djê, as guardiãs das águas
 *
 * 🐚 IARA – 2026-09-24 (auditoria v5.0)
 *    - Trocado strlen() por mb_strlen() na validação do nome do evento.
 *
 * 🐚 MARESIA – 2026-10-02 (Sprint 1, item 6/7)
 *    - Adicionada validação de `comunidade_id` no backend. Antes, o
 *      POST aceitava qualquer ID, permitindo criar eventos em comunidades
 *      que o usuário não administra (IDOR de associação).
 *    - Moveu $is_ajax para o topo, pra reutilizar nos erros de validação.
 *
 * 🐚 CALMARIA – 2026-10-07 (Sprint 2, item #18)
 *    - Corrigido INSERT de notificação de evento: usa coluna `evento_id`
 *      (nova, criada por migração) em vez de `post_id`.
 *      Motivo: `post_id` tem FK pra `mensagens(id)`, e eventos vivem em
 *      `eventos(id)`. Isso bloqueava silenciosamente o INSERT via
 *      `mysqli_report(MYSQLI_REPORT_OFF)` — nenhuma notificação de evento
 *      era criada desde que a FK foi adicionada. Bug arqueológico,
 *      descoberto via SQL manual em produção. Migration: adiciona
 *      `evento_id INT NULL` + FK `notificacoes_ibfk_3` com ON DELETE SET NULL.
 *      O bind_param continua o mesmo (`issii`) — só o nome da coluna mudou.
 *      `post_id` fica NULL por default (notificações de evento não se
 *      referem a um post).
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO processa-evento.php');

// 🐚 MARESIA – 2026-10-02: movido pro topo
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$responder_erro_ajax = static function (int $status_code, string $message): void {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['status' => 'error', 'message' => $message],
        JSON_UNESCAPED_UNICODE
    );
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        $responder_erro_ajax(405, 'Método não permitido.');
    }
    header("Location: balanga-teras.php");
    exit;
}

// CSRF
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !is_string($_POST['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    if ($is_ajax) {
        $responder_erro_ajax(403, 'Token de segurança inválido. Recarregue a página e tente novamente.');
    }
    die('Token de segurança inválido.');
}

// Honeypot
if (!empty($_POST['honeypot'])) {
    if ($is_ajax) {
        $responder_erro_ajax(400, 'Requisição inválida.');
    }
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
// 🐚 MARESIA – 2026-10-02 (Sprint 1, item 6/7)
// VALIDAÇÃO DE COMUNIDADE
// Se `comunidade_id` foi informado, verifica que existe E que o
// usuário é admin/criador dela. Sem isso, qualquer autenticado podia
// criar evento em comunidade alheia manipulando o POST.
// ============================================================
if ($comunidade_id !== null) {
    // 1.1 Comunidade existe?
    $stmt_com = $conn->prepare("SELECT id FROM comunidades WHERE id = ?");
    $stmt_com->bind_param("i", $comunidade_id);
    $stmt_com->execute();
    $comunidade_existe = $stmt_com->get_result()->num_rows > 0;
    $stmt_com->close();

    if (!$comunidade_existe) {
        fenda_log("❌ Comunidade $comunidade_id não existe.");
        if ($is_ajax) {
            $responder_erro_ajax(400, 'Comunidade inválida.');
        }
        $_SESSION['erro_evento'] = 'Comunidade inválida.';
        header("Location: criar-evento.php");
        exit;
    }

    // 1.2 Usuário é admin/criador?
    $stmt_role = $conn->prepare(
        "SELECT papel FROM comunidade_membros
         WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo'
         LIMIT 1"
    );
    $stmt_role->bind_param("ii", $comunidade_id, $usuario_id);
    $stmt_role->execute();
    $membro = $stmt_role->get_result()->fetch_assoc();
    $stmt_role->close();

    if (!$membro || !in_array($membro['papel'], ['criador', 'admin'], true)) {
        fenda_log("🚫 Usuário $usuario_id não é admin/criador da comunidade $comunidade_id.");
        if ($is_ajax) {
            $responder_erro_ajax(403, 'Você não tem permissão para criar eventos nesta comunidade.');
        }
        $_SESSION['erro_evento'] = 'Você não tem permissão para criar eventos nesta comunidade.';
        header("Location: criar-evento.php");
        exit;
    }
}

// ============================================================
// 2. VALIDAÇÕES
// ============================================================
if (empty($nome) || mb_strlen($nome) < 3) {
    fenda_log("❌ Nome inválido: '$nome'");
    if ($is_ajax) {
        $responder_erro_ajax(400, 'O nome do evento deve ter pelo menos 3 caracteres.');
    }
    $_SESSION['erro_evento'] = 'O nome do evento deve ter pelo menos 3 caracteres.';
    header("Location: criar-evento.php");
    exit;
}
if (empty($data_evento) || strtotime($data_evento) < time()) {
    fenda_log("❌ Data inválida: '$data_evento'");
    if ($is_ajax) {
        $responder_erro_ajax(400, 'A data do evento deve ser futura.');
    }
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
        if ($is_ajax) {
            $responder_erro_ajax(400, 'Erro ao enviar a capa (formato/tamanho inválido).');
        }
        $_SESSION['erro_evento'] = 'Erro ao enviar a capa (formato/tamanho inválido).';
        header("Location: criar-evento.php");
        exit;
    }
    $capa_enviada = true;
    fenda_log("🖼️ Capa enviada: $capa_nome");
}

// ============================================================
// 4. UPLOAD DA GALERIA (ANEXOS) – COM GIFs EXTERNOS
// ============================================================
$anexos = [];
$anexos_enviados = [];

// 4.1 - GIFs externos
$gif_urls = isset($_POST['gif_urls']) && is_array($_POST['gif_urls']) ? $_POST['gif_urls'] : [];
if (!empty($gif_urls)) {
    fenda_log("🎬 GIFs recebidos: " . count($gif_urls));
    foreach ($gif_urls as $gif_url) {
        $gif_url = trim($gif_url);
        if (count($anexos) >= 4) break;
        if (filter_var($gif_url, FILTER_VALIDATE_URL) &&
            (strpos($gif_url, 'giphy.com') !== false || strpos($gif_url, 'media.giphy.com') !== false)) {
            $anexos[] = ['id' => 'anexo-' . uniqid(), 'tipo' => 'gif', 'url' => $gif_url];
            fenda_log("✅ GIF adicionado: $gif_url");
        } else {
            fenda_log("⚠️ GIF ignorado (URL inválida): $gif_url");
        }
    }
}

// 4.2 - Múltiplos arquivos (imagens)
if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
    $files = array_filter($_FILES['anexos']['name']);
    if (!empty($files)) {
        foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp) {
            if ($_FILES['anexos']['error'][$key] !== 0) continue;
            if (count($anexos) >= 4) break;
            $file_data = [
                'name'     => $_FILES['anexos']['name'][$key],
                'type'     => $_FILES['anexos']['type'][$key],
                'tmp_name' => $tmp,
                'error'    => $_FILES['anexos']['error'][$key],
                'size'     => $_FILES['anexos']['size'][$key]
            ];
            $nome_anexo = processarUploadSeguro($file_data, 'uploads', 'evento', 2 * 1024 * 1024, $usuario_id);
            if ($nome_anexo !== false) {
                $anexos[] = ['id' => 'anexo-' . uniqid(), 'tipo' => 'imagem', 'caminho' => $nome_anexo];
                $anexos_enviados[] = $nome_anexo;
                fenda_log("🆕 Anexo enviado: $nome_anexo");
            }
        }
    }
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

    // 🔔 NOTIFICAÇÕES (se houver comunidade)
    // 🐚 CALMARIA – 2026-10-07 (item #18): trocado post_id → evento_id.
    //    Antes: post_id tinha FK pra mensagens(id) → INSERT falhava
    //    silenciosamente porque eventos vivem em eventos(id). Agora usa
    //    a coluna dedicada evento_id (FK pra eventos).
    if ($comunidade_id !== null) {
        $stmt_nome = $conn->prepare("SELECT nome FROM comunidades WHERE id = ?");
        $stmt_nome->bind_param("i", $comunidade_id);
        $stmt_nome->execute();
        $res_nome = $stmt_nome->get_result();
        $comunidade = $res_nome->fetch_assoc();
        $stmt_nome->close();

        if ($comunidade) {
            $nome_comunidade = $comunidade['nome'];
            $stmt_notif = $conn->prepare("
                INSERT INTO notificacoes (usuario_id, evento_id, tipo, mensagem, lida, data_criacao)
                SELECT cm.usuario_id, ?, 'evento', CONCAT('📢 Novo evento em \"', ?, '\": ', ?), 0, NOW()
                FROM comunidade_membros cm
                WHERE cm.comunidade_id = ? AND cm.status = 'ativo' AND cm.usuario_id != ?
            ");
            $stmt_notif->bind_param("issii", $evento_id, $nome_comunidade, $nome, $comunidade_id, $usuario_id);
            $stmt_notif->execute();
            $notificacoes_enviadas = $stmt_notif->affected_rows;
            $stmt_notif->close();
            fenda_log("🔔 Notificações de evento enviadas: $notificacoes_enviadas membros da comunidade $comunidade_id");
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'redirect' => "evento.php?id={$evento_id}"]);
        exit;
    }

    $_SESSION['sucesso_evento'] = 'Evento criado com sucesso!';
    header("Location: evento.php?id=$evento_id");
    exit;
} else {
    $erro = $stmt->error;
    $stmt->close();
    fenda_log("❌ Erro no banco: $erro");

    // Rollback
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

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Erro ao criar evento: ' . $erro]);
        exit;
    }

    $_SESSION['erro_evento'] = 'Erro ao criar evento: ' . $erro;
    header("Location: criar-evento.php");
    exit;
}