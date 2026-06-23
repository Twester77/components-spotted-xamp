<?php
// auth_check.php - Middleware de autenticação
// Inclui a conexão e verifica se o usuário está logado.
// Se não estiver, redireciona para a página inicial.

include_once __DIR__ . '/fenda_debug.php';
fenda_log('🔵 INÍCIO auth_check.php');

include_once __DIR__ . '/conexao.php'; // conexao.php já inicia a sessão

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    fenda_log('🔴 REDIRECIONANDO para index.php (usuário não logado)');
    header("Location: index.php");
    exit();
}

fenda_log('🟢 FIM auth_check.php (usuário autorizado: ' . $_SESSION['usuario_id'] . ')');
?>