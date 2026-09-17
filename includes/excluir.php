<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../includes/upload_engine.php';

// ============================================================
// 1. DETECTA SE É REQUISIÇÃO AJAX
// ============================================================
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Pega o ID (via POST ou GET)
$post_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $post_id = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
}

// ============================================================
// FUNÇÃO AUXILIAR PARA RESPONDER JSON
// ============================================================
function responderJSON($status, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// ============================================================
// 🔥 LOGS DE DIAGNÓSTICO
// ============================================================
error_log("[EXCLUIR] 🔵 Iniciando exclusão do post ID: $post_id | usuário: " . ($_SESSION['usuario_id'] ?? 'NÃO LOGADO') . " | AJAX: " . ($is_ajax ? 'sim' : 'não'));

// ============================================================
// 2. VALIDAÇÃO DO ID
// ============================================================
if ($post_id <= 0) {
    error_log("[EXCLUIR] ❌ ID inválido: $post_id");
    if ($is_ajax) {
        responderJSON('error', 'ID inválido.');
    } else {
        header("Location: ../feed.php");
        exit;
    }
}

// ============================================================
// 3. VERIFICA PERMISSÃO E BUSCA DADOS DO POST
// ============================================================
$usuario_id = (int)$_SESSION['usuario_id'];
error_log("[EXCLUIR] 🔍 Buscando post ID $post_id para usuário $usuario_id");

$check = $conn->prepare("SELECT usuario_id, imagem_url, anexos FROM mensagens WHERE id = ? AND status = 'ativo'");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if (!$dados_post) {
    error_log("[EXCLUIR] ❌ Post ID $post_id não encontrado ou já removido.");
    if ($is_ajax) {
        responderJSON('error', 'Post não encontrado.');
    } else {
        header("Location: ../feed.php");
        exit;
    }
}

error_log("[EXCLUIR] 📄 Dados do post: usuario_id=" . $dados_post['usuario_id'] . ", imagem_url=" . ($dados_post['imagem_url'] ?? 'NENHUMA') . ", anexos=" . ($dados_post['anexos'] ?? 'NENHUM'));

if ($dados_post['usuario_id'] != $usuario_id) {
    error_log("[EXCLUIR] 🚫 Usuário $usuario_id não é o autor (autor: {$dados_post['usuario_id']}). Permissão negada.");
    if ($is_ajax) {
        responderJSON('error', 'Você não tem permissão para excluir este post.');
    } else {
        header("Location: ../feed.php");
        exit;
    }
}

error_log("[EXCLUIR] ✅ Usuário autorizado. Prosseguindo com exclusão.");

// ============================================================
// 4. EXCLUSÃO DOS ARQUIVOS NO B2
// ============================================================
$arquivosDeletados = 0;
$erros = [];

if (!empty($dados_post['anexos'])) {
    $anexos = json_decode($dados_post['anexos'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                if (excluirArquivoB2($anexo['caminho'], $usuario_id)) {
                    $arquivosDeletados++;
                    error_log("[EXCLUIR] 🗑️ Deletado do B2: " . $anexo['caminho']);
                } else {
                    $erros[] = "Falha ao deletar: " . $anexo['caminho'];
                    error_log("[EXCLUIR] ⚠️ Falha ao deletar do B2: " . $anexo['caminho']);
                }
            }
        }
    } else {
        error_log("[EXCLUIR] ⚠️ JSON inválido em anexos: " . $dados_post['anexos']);
    }
}

if (!empty($dados_post['imagem_url'])) {
    $jaDeletada = false;
    if (!empty($anexos) && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if ($anexo['tipo'] === 'imagem' && $anexo['caminho'] === $dados_post['imagem_url']) {
                $jaDeletada = true;
                break;
            }
        }
    }
    if (!$jaDeletada) {
        if (excluirArquivoB2($dados_post['imagem_url'], $usuario_id)) {
            $arquivosDeletados++;
            error_log("[EXCLUIR] 🗑️ Deletado imagem_url do B2: " . $dados_post['imagem_url']);
        } else {
            $erros[] = "Falha ao deletar imagem_url: " . $dados_post['imagem_url'];
            error_log("[EXCLUIR] ⚠️ Falha ao deletar imagem_url do B2: " . $dados_post['imagem_url']);
        }
    }
}

// ============================================================
// 5. SOFT DELETE NO BANCO
// ============================================================
$soft_delete = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ?");
$soft_delete->bind_param("i", $post_id);
$executou = $soft_delete->execute();
error_log("[EXCLUIR] 💾 UPDATE executado: " . ($executou ? 'SUCESSO' : 'FALHA'));

if ($executou) {
    $limpar_notif = $conn->prepare("UPDATE notificacoes SET post_id = NULL WHERE post_id = ?");
    $limpar_notif->bind_param("i", $post_id);
    $limpar_notif->execute();
    $limpar_notif->close();

    if ($arquivosDeletados > 0) {
        error_log("[EXCLUIR] ✅ Post $post_id deletado (B2: $arquivosDeletados arquivos removidos)");
    } else {
        error_log("[EXCLUIR] ✅ Post $post_id deletado (sem arquivos B2 ou já removidos)");
    }
    if (!empty($erros)) {
        error_log("[EXCLUIR] ⚠️ Erros parciais: " . implode(', ', $erros));
    }
} else {
    error_log("[EXCLUIR] ❌ Erro ao atualizar status do post $post_id: " . $conn->error);
}

$soft_delete->close();
$check->close();

// ============================================================
// 6. RESPOSTA FINAL
// ============================================================
if ($is_ajax) {
    if ($executou) {
        error_log("[EXCLUIR] ✅ Respondendo success para o front-end.");
        responderJSON('success', 'Post excluído com sucesso.');
    } else {
        error_log("[EXCLUIR] ❌ Respondendo error (falha no banco).");
        responderJSON('error', 'Falha ao excluir no banco de dados.');
    }
} else {
    if ($executou) {
        header("Location: ../feed.php?msg=deletado");
    } else {
        header("Location: ../feed.php?erro=delete_fail");
    }
    exit;
}