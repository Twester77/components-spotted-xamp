<?php
/**
 * comunidade-actions.php – Processa ações de entrada/saída em comunidades
 * 
 * Parâmetros via GET:
 * - comunidade_id (int) – ID da comunidade
 * - acao (string) – 'entrar' ou 'sair'
 * 
 * Retorna JSON:
 * - success (bool)
 * - message (string)
 */

// ============================================================
// 1. CONFIGURAÇÃO E SEGURANÇA
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../auth_check.php'; // Garante que o usuário está logado

// ============================================================
// 2. VALIDAÇÃO DOS PARÂMETROS
// ============================================================
$comunidade_id = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
$acao = isset($_GET['acao']) ? trim($_GET['acao']) : '';

if ($comunidade_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID da comunidade inválido.']);
    exit();
}

if (!in_array($acao, ['entrar', 'sair'])) {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// ============================================================
// 3. VERIFICA SE A COMUNIDADE EXISTE
// ============================================================
$stmt = $conn->prepare("SELECT id FROM comunidades WHERE id = ?");
$stmt->bind_param("i", $comunidade_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Comunidade não encontrada.']);
    $stmt->close();
    exit();
}
$stmt->close();

// ============================================================
// 4. EXECUTA A AÇÃO
// ============================================================
try {
    if ($acao === 'entrar') {
        // Verifica se já é membro
        $stmt = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $comunidade_id, $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Você já é membro desta comunidade.']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // Insere como membro
        $stmt = $conn->prepare("INSERT INTO comunidade_membros (comunidade_id, usuario_id, papel) VALUES (?, ?, 'membro')");
        $stmt->bind_param("ii", $comunidade_id, $usuario_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Você entrou na comunidade!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao entrar na comunidade.']);
        }
        $stmt->close();

    } elseif ($acao === 'sair') {
        // Verifica se é o criador da comunidade (não pode sair se for o único admin?)
        // Regra: criador não pode sair, só pode deletar a comunidade
        $stmt = $conn->prepare("SELECT criador_id FROM comunidades WHERE id = ?");
        $stmt->bind_param("i", $comunidade_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $criador = $res->fetch_assoc();
        $stmt->close();

        if ($criador && $criador['criador_id'] == $usuario_id) {
            echo json_encode(['success' => false, 'message' => 'O criador da comunidade não pode sair. Você pode deletar a comunidade ou transferir a liderança.']);
            exit();
        }

        // Remove da tabela de membros
        $stmt = $conn->prepare("DELETE FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $comunidade_id, $usuario_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Você saiu da comunidade.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Você não é membro desta comunidade.']);
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log('[COMUNIDADE-ACTIONS] Erro: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
?>