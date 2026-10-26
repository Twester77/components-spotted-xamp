<?php
/**
 * motor-perfil.php – Roteador das abas do Perfil Público
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$aba = isset($_GET['aba']) ? $_GET['aba'] : 'posts';

if ($usuario_id <= 0) {
    echo '<p style="text-align:center; color:#aaa; padding:20px;">Usuário inválido.</p>';
    exit;
}

try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}

// ============================================================
// ROTEADOR DAS ABAS
// ============================================================
if ($aba === 'posts') {
    // Define o offset para paginação
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Define o tipo de feed no motor-feed.php
    $_GET['tipo'] = 'perfil';
    $_GET['offset'] = $offset;
    $_GET['user'] = ''; // Para não pegar pela URL principal
    
    // Inclui o motor-feed.php e passamos o usuário_id via variável global (simulada)
    // No motor-feed.php, precisamos adicionar suporte a $usuario_id direto
    $user_alvo_id = $usuario_id; 
    
    // Como o motor-feed.php atual usa $user_alvo via $_GET['user'], vamos fazer um hack:
    // Buscamos o username do usuário e injetamos no $_GET['user']
    $stmt_user = $conn->prepare("SELECT username FROM usuarios WHERE id = ?");
    $stmt_user->bind_param("i", $usuario_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    
    if ($user_data) {
        $_GET['user'] = $user_data['username'];
    }

    include 'motor-feed.php';
    exit;
}

if ($aba === 'depoimentos') {
    // Reaproveita o motor-depoimentos.php existente
    $_GET['usuario_id'] = $usuario_id;
    $_GET['status'] = 'aprovado';
    $_GET['limite'] = isset($_GET['limite']) ? (int)$_GET['limite'] : 3;
    
    include 'motor-depoimentos.php';
    exit;
}

if ($aba === 'avaliacoes') {
    // Reaproveita o motor-avaliacoes.php existente (verifica se o arquivo está em includes/)
    if (file_exists(__DIR__ . '/includes/motor-avaliacoes.php')) {
        $_GET['usuario_id'] = $usuario_id;
        include __DIR__ . '/includes/motor-avaliacoes.php';
        exit;
    } else if (file_exists(__DIR__ . '/motor-avaliacoes.php')) {
        $_GET['usuario_id'] = $usuario_id;
        include __DIR__ . '/motor-avaliacoes.php';
        exit;
    }
    
    echo '<p style="text-align:center; color:#aaa; padding:20px;">Avaliações não encontradas.</p>';
    exit;
}

// Fallback
echo '<p style="text-align:center; color:#aaa; padding:20px;">Aba não encontrada.</p>';
exit;