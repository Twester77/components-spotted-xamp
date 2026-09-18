<?php
/**
 * comunidade-actions.php – Processa ações de entrada/saída em comunidades
 *
 * 🔒 AUDITORIA PÉROLA – 2026-09-18
 *    - Mudou de GET para POST (impede CSRF via <img src> em sites terceiros).
 *    - Validação obrigatória de csrf_token.
 *    - Mantém redirect para GET antigo (bookmarks/links) sem quebrar UX.
 *
 * @package A Fenda
 */

// ============================================================
// 1. CONFIGURAÇÃO E SEGURANÇA
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../auth_check.php'; // Garante que o usuário está logado

// ============================================================
// 2. SÓ ACEITA POST (proteção CSRF)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // GET antigo (bookmark/link): redireciona pra não quebrar
    $comunidade_id_get = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
    if ($comunidade_id_get > 0) {
        header("Location: ../comunidade.php?id=" . $comunidade_id_get);
    } else {
        header("Location: ../lista-comunidades.php");
    }
    exit();
}

// ============================================================
// 3. VALIDAÇÃO CSRF (obrigatória)
// ============================================================
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || $csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit();
}

// ============================================================
// 4. VALIDAÇÃO DOS PARÂMETROS
// ============================================================
$comunidade_id = isset($_POST['comunidade_id']) ? (int)$_POST['comunidade_id'] : 0;
$acao = isset($_POST['acao']) ? trim($_POST['acao']) : '';

if ($comunidade_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da comunidade inválido.']);
    exit();
}

if (!in_array($acao, ['entrar', 'sair'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit();
}

$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
if ($usuario_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit();
}

// ============================================================
// 5. VERIFICA SE A COMUNIDADE EXISTE
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
// 6. EXECUTA A AÇÃO
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
        // Verifica se é o criador da comunidade (não pode sair)
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}