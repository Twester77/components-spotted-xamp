<?php
/**
 * excluir-comentario.php – Processa a exclusão de comentários (AJAX)
 * 
 * 🔒 Segurança:
 * - CSRF token obrigatório (validado contra $_SESSION['csrf_token'])
 * - Apenas usuário logado
 * - Apenas o autor pode excluir o próprio comentário
 * - Soft delete (status = 'deletado')
 * - Exclusão de anexos no B2 via rollbackUpload()
 * 
 * 🐚 BRISA – 2026-09-15
 *    - Adicionada validação de CSRF token (auditoria da Djê).
 *    - Sem a validação, um atacante poderia forjar requisições para
 *      apagar comentários do usuário logado (ataque CSRF).
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/upload_engine.php';

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÃO DE SESSÃO
// ============================================================
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado. Faça login.']);
    exit();
}

// ============================================================
// 2. VALIDAÇÃO DE CSRF (NOVO – auditoria da Djê)
// ============================================================
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token de segurança inválido.']);
    exit();
}

// ============================================================
// 3. VALIDAÇÃO DO ID
// ============================================================
if (!isset($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID do comentário não fornecido.']);
    exit();
}

$comentario_id = (int)$_POST['id'];
$usuario_id = (int)$_SESSION['usuario_id'];

if ($comentario_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
    exit();
}

// ============================================================
// 4. BUSCA OS DADOS DO COMENTÁRIO
// ============================================================
$check = $conn->prepare("SELECT id, usuario_id, status, imagem_url, anexos FROM comentarios WHERE id = ?");
$check->bind_param("i", $comentario_id);
$check->execute();
$res = $check->get_result();
$comentario = $res->fetch_assoc();

if (!$comentario || $comentario['status'] !== 'ativo') {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Comentário não encontrado ou já removido.']);
    exit();
}

// ============================================================
// 5. VERIFICA PERMISSÃO (apenas o autor pode excluir)
// ============================================================
if ($comentario['usuario_id'] != $usuario_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para excluir este comentário.']);
    exit();
}

// ============================================================
// 6. EXCLUSÃO DO B2 (ANTES DO BANCO) – Atomicidade
// ============================================================
$arquivosDeletados = 0;
$erros = [];

// 6.1 Se houver anexos (JSON), deleta todos os arquivos de imagem
if (!empty($comentario['anexos'])) {
    $anexos = json_decode($comentario['anexos'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                $sucesso = excluirArquivoB2($anexo['caminho'], $usuario_id);
                if ($sucesso) {
                    $arquivosDeletados++;
                } else {
                    $erros[] = "Falha ao deletar: " . $anexo['caminho'];
                    error_log("[EXCLUIR_COMENTARIO] ⚠️ Falha ao deletar do B2: " . $anexo['caminho']);
                }
            }
            // GIFs (tipo 'gif') são URLs externas, não deletamos
        }
    } else {
        error_log("[EXCLUIR_COMENTARIO] ⚠️ JSON inválido em anexos: " . $comentario['anexos']);
    }
}

// 6.2 Fallback: se houver imagem_url (compatibilidade) e ela não foi deletada acima
if (!empty($comentario['imagem_url'])) {
    $jaDeletada = false;
    if (!empty($anexos) && is_array($anexos)) {
        foreach ($anexos as $anexo) {
            if ($anexo['tipo'] === 'imagem' && $anexo['caminho'] === $comentario['imagem_url']) {
                $jaDeletada = true;
                break;
            }
        }
    }
    if (!$jaDeletada) {
        $sucesso = excluirArquivoB2($comentario['imagem_url'], $usuario_id);
        if ($sucesso) {
            $arquivosDeletados++;
        } else {
            $erros[] = "Falha ao deletar imagem_url: " . $comentario['imagem_url'];
            error_log("[EXCLUIR_COMENTARIO] ⚠️ Falha ao deletar imagem_url do B2: " . $comentario['imagem_url']);
        }
    }
}

// ============================================================
// 7. SOFT DELETE NO BANCO (sempre executado, mesmo se B2 falhar)
// ============================================================
$update = $conn->prepare("UPDATE comentarios SET status = 'deletado' WHERE id = ?");
$update->bind_param("i", $comentario_id);

if ($update->execute()) {
    if ($arquivosDeletados > 0) {
        error_log("[EXCLUIR_COMENTARIO] ✅ Comentário $comentario_id deletado (B2: $arquivosDeletados arquivos removidos)");
    } else {
        error_log("[EXCLUIR_COMENTARIO] ✅ Comentário $comentario_id deletado (sem arquivos B2 ou já removidos)");
    }
    if (!empty($erros)) {
        error_log("[EXCLUIR_COMENTARIO] ⚠️ Erros parciais: " . implode(', ', $erros));
    }
    echo json_encode(['status' => 'success', 'message' => 'Comentário removido.']);
} else {
    error_log("[EXCLUIR_COMENTARIO] ❌ Erro ao atualizar status do comentário $comentario_id: " . $conn->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao remover comentário no banco.']);
}

$check->close();
$update->close();
$conn->close();