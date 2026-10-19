<?php
/**
 * excluir.php – Soft delete de post (endpoint do autor)
 *
 * 🔒 Segurança (auditoria Corrente – 2026-10-13, Bloco 1.4):
 *    - Forçado POST (antes aceitava GET — CSRF trivial via <img src>)
 *    - Validação de CSRF obrigatória via hash_equals
 *    - Prepared statements em toda a query
 *    - display_errors desligado (vazava caminhos em produção)
 *
 * Mantém a semântica original: apenas o AUTOR pode excluir o próprio post.
 * O endpoint de comunidade (excluir-post.php) continua separado — lá,
 * autor OU admin da comunidade podem excluir, com regras próprias.
 *
 * @package A Fenda
 */

// 🔧 Correção: erros silenciosos em produção (antes era display_errors=1)
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../includes/upload_engine.php';
require_once __DIR__ . '/../fenda_debug.php';

// ============================================================
// 1. DETECTA SE É REQUISIÇÃO AJAX
// ============================================================
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ============================================================
// 2. FUNÇÃO AUXILIAR PARA RESPONDER JSON
// ============================================================
function responderJSON($status, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// ============================================================
// 3. SÓ ACEITA POST (proteção CSRF estrutural)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fenda_log('[EXCLUIR] 🔴 Método não permitido: ' . $_SERVER['REQUEST_METHOD']);
    if ($is_ajax) {
        http_response_code(405);
        responderJSON('error', 'Método não permitido.');
    }
    header("Location: ../feed.php");
    exit;
}

// ============================================================
// 4. VALIDAÇÃO CSRF (obrigatória) — auditoria Corrente Bloco 1.4
// ============================================================
$csrf_token = $_POST['csrf_token'] ?? '';
$csrf_sessao = $_SESSION['csrf_token'] ?? '';

if (empty($csrf_sessao) || empty($csrf_token) || !hash_equals((string)$csrf_sessao, (string)$csrf_token)) {
    fenda_log('[EXCLUIR] 🔴 CSRF inválido. Token recebido: ' . substr((string)$csrf_token, 0, 16) . '...');
    if ($is_ajax) {
        http_response_code(403);
        responderJSON('error', 'Token de segurança inválido ou expirado. Recarregue a página.');
    }
    header("Location: ../feed.php");
    exit;
}

// ============================================================
// 5. CAPTURA DO ID (apenas POST)
// ============================================================
$post_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// ============================================================
// 6. LOGS DE DIAGNÓSTICO
// ============================================================
error_log("[EXCLUIR] 🔵 Iniciando exclusão do post ID: $post_id | usuário: " . ($_SESSION['usuario_id'] ?? 'NÃO LOGADO') . " | AJAX: " . ($is_ajax ? 'sim' : 'não'));

// ============================================================
// 7. VALIDAÇÃO DO ID
// ============================================================
if ($post_id <= 0) {
    error_log("[EXCLUIR] ❌ ID inválido: $post_id");
    if ($is_ajax) {
        http_response_code(400);
        responderJSON('error', 'ID inválido.');
    }
    header("Location: ../feed.php");
    exit;
}

// ============================================================
// 8. VERIFICA PERMISSÃO E BUSCA DADOS DO POST
// ============================================================
$usuario_id = (int)$_SESSION['usuario_id'];
error_log("[EXCLUIR] 🔍 Buscando post ID $post_id para usuário $usuario_id");

// 🔒 Prepared statement (antes usava prepare também, mantido)
$check = $conn->prepare("SELECT usuario_id, imagem_url, anexos FROM mensagens WHERE id = ? AND status = 'ativo'");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if (!$dados_post) {
    error_log("[EXCLUIR] ❌ Post ID $post_id não encontrado ou já removido.");
    if ($is_ajax) {
        http_response_code(404);
        responderJSON('error', 'Post não encontrado.');
    }
    header("Location: ../feed.php");
    exit;
}

error_log("[EXCLUIR] 📄 Dados do post: usuario_id=" . $dados_post['usuario_id'] . ", imagem_url=" . ($dados_post['imagem_url'] ?? 'NENHUMA') . ", anexos=" . ($dados_post['anexos'] ?? 'NENHUM'));

if ($dados_post['usuario_id'] != $usuario_id) {
    error_log("[EXCLUIR] 🚫 Usuário $usuario_id não é o autor (autor: {$dados_post['usuario_id']}). Permissão negada.");
    if ($is_ajax) {
        http_response_code(403);
        responderJSON('error', 'Você não tem permissão para excluir este post.');
    }
    header("Location: ../feed.php");
    exit;
}

error_log("[EXCLUIR] ✅ Usuário autorizado. Prosseguindo com exclusão.");

// ============================================================
// 9. EXCLUSÃO DOS ARQUIVOS NO B2
// ============================================================
$arquivosDeletados = 0;
$erros = [];

if (!empty($dados_post['anexos'])) {
    $anexos = json_decode($dados_post['anexos'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if (($anexo['tipo'] ?? '') === 'imagem' && !empty($anexo['caminho'])) {
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
            if (($anexo['tipo'] ?? '') === 'imagem' && ($anexo['caminho'] ?? '') === $dados_post['imagem_url']) {
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
// 10. SOFT DELETE NO BANCO
// ============================================================
$soft_delete = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ?");
$soft_delete->bind_param("i", $post_id);
$executou = $soft_delete->execute();
error_log("[EXCLUIR] 💾 UPDATE executado: " . ($executou ? 'SUCESSO' : 'FALHA'));

if ($executou) {
    // Limpa notificações relacionadas
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
// 11. RESPOSTA FINAL
// ============================================================
if ($is_ajax) {
    if ($executou) {
        error_log("[EXCLUIR] ✅ Respondendo success para o front-end.");
        responderJSON('success', 'Post excluído com sucesso.');
    } else {
        error_log("[EXCLUIR] ❌ Respondendo error (falha no banco).");
        http_response_code(500);
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