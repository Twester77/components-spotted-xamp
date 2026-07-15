<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../includes/upload_engine.php'; // 🔥 Inclui o motor com excluirArquivoB2()

// ============================================================
// 1. DETECTA SE É REQUISIÇÃO AJAX (POST com X-Requested-With)
// ============================================================
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Pega o ID (via GET ou POST)
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
// 2. VALIDAÇÃO DO ID
// ============================================================
if ($post_id <= 0) {
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

$check = $conn->prepare("SELECT usuario_id, imagem_url, anexos FROM mensagens WHERE id = ? AND status = 'ativo'");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if (!$dados_post || $dados_post['usuario_id'] != $usuario_id) {
    if ($is_ajax) {
        responderJSON('error', 'Você não tem permissão para excluir este post.');
    } else {
        header("Location: ../feed.php");
        exit;
    }
}

// ============================================================
// 4. EXCLUSÃO DOS ARQUIVOS NO B2 (ANTES DO BANCO)
// ============================================================
$arquivosDeletados = 0;
$erros = [];

// 🔥 4.1 - Deleta todos os arquivos listados no campo 'anexos' (JSON)
if (!empty($dados_post['anexos'])) {
    $anexos = json_decode($dados_post['anexos'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                if (excluirArquivoB2($anexo['caminho'], $usuario_id)) {
                    $arquivosDeletados++;
                } else {
                    $erros[] = "Falha ao deletar: " . $anexo['caminho'];
                    error_log("[EXCLUIR_POST] ⚠️ Falha ao deletar do B2: " . $anexo['caminho']);
                }
            }
            // GIFs (tipo 'gif') são URLs externas, não deletamos
        }
    } else {
        error_log("[EXCLUIR_POST] ⚠️ JSON inválido em anexos: " . $dados_post['anexos']);
    }
}

// 🔥 4.2 - Fallback: imagem_url (se não foi deletada via anexos)
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
        } else {
            $erros[] = "Falha ao deletar imagem_url: " . $dados_post['imagem_url'];
            error_log("[EXCLUIR_POST] ⚠️ Falha ao deletar imagem_url do B2: " . $dados_post['imagem_url']);
        }
    }
}

// ============================================================
// 5. SOFT DELETE NO BANCO
// ============================================================
$soft_delete = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ?");
$soft_delete->bind_param("i", $post_id);
$executou = $soft_delete->execute();

if ($executou) {
    // Limpa as notificações que apontam para este post
    $limpar_notif = $conn->prepare("UPDATE notificacoes SET post_id = NULL WHERE post_id = ?");
    $limpar_notif->bind_param("i", $post_id);
    $limpar_notif->execute();
    $limpar_notif->close();

    // Log do resultado
    if ($arquivosDeletados > 0) {
        error_log("[EXCLUIR_POST] ✅ Post $post_id deletado (B2: $arquivosDeletados arquivos removidos)");
    } else {
        error_log("[EXCLUIR_POST] ✅ Post $post_id deletado (sem arquivos B2 ou já removidos)");
    }
    if (!empty($erros)) {
        error_log("[EXCLUIR_POST] ⚠️ Erros parciais: " . implode(', ', $erros));
    }
} else {
    // Se o banco falhou, registra erro (não faz rollback dos arquivos, pois foram deletados do B2)
    error_log("[EXCLUIR_POST] ❌ Erro ao atualizar status do post $post_id: " . $conn->error);
}

$soft_delete->close();
$check->close();

// ============================================================
// 6. RESPOSTA FINAL
// ============================================================
if ($is_ajax) {
    if ($executou) {
        responderJSON('success', 'Post excluído com sucesso.');
    } else {
        responderJSON('error', 'Falha ao excluir no banco de dados.');
    }
} else {
    // Modo tradicional (GET) – fallback
    if ($executou) {
        header("Location: ../feed.php?msg=deletado");
    } else {
        header("Location: ../feed.php?erro=delete_fail");
    }
    exit;
}
?>