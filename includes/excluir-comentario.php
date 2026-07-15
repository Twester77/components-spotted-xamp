<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../includes/upload_engine.php'; // 🔥 Inclui o motor com excluirArquivoB2()

header('Content-Type: application/json');

// ============================================================
// 1. VALIDAÇÃO DE SESSÃO E ID
// ============================================================
if (!isset($_SESSION['usuario_id']) || !isset($_POST['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit();
}

$comentario_id = (int)$_POST['id'];
$usuario_id = (int)$_SESSION['usuario_id'];

// ============================================================
// 2. BUSCA OS DADOS DO COMENTÁRIO (incluindo imagem_url e anexos)
// ============================================================
$check = $conn->prepare("SELECT id, usuario_id, status, imagem_url, anexos FROM comentarios WHERE id = ?");
$check->bind_param("i", $comentario_id);
$check->execute();
$res = $check->get_result();
$comentario = $res->fetch_assoc();

if (!$comentario || $comentario['status'] !== 'ativo') {
    echo json_encode(['status' => 'error', 'message' => 'Comentário não encontrado ou já removido.']);
    exit();
}

if ($comentario['usuario_id'] != $usuario_id) {
    echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para excluir este comentário.']);
    exit();
}

// ============================================================
// 3. EXCLUSÃO DO B2 (ANTES DO BANCO) – Atomicidade!
// ============================================================
$arquivosDeletados = 0;
$erros = [];

// 🔥 3.1 Se houver anexos (JSON), deleta todos os arquivos de imagem
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

// 🔥 3.2 Fallback: se houver imagem_url (compatibilidade) e ela não foi deletada acima
if (!empty($comentario['imagem_url'])) {
    // Verifica se já foi deletada via anexos (evita deletar duas vezes)
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
// 4. SOFT DELETE NO BANCO (sempre executado, mesmo se B2 falhar)
// ============================================================
$update = $conn->prepare("UPDATE comentarios SET status = 'deletado' WHERE id = ?");
$update->bind_param("i", $comentario_id);

if ($update->execute()) {
    // Log do resultado
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
    // Se o banco falhou, não faz rollback (pois já deletamos do B2, mas o comentário fica ativo)
    // Isso é um trade-off: o arquivo foi deletado, mas o comentário ainda existe. Pode ser corrigido manualmente.
    error_log("[EXCLUIR_COMENTARIO] ❌ Erro ao atualizar status do comentário $comentario_id: " . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao remover comentário no banco.']);
}

$check->close();
$update->close();
$conn->close();
?>