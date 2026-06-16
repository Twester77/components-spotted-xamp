<?php
// auth_check.php - Middleware de autenticação
// Inclui a conexão e verifica se o usuário está logado.
// Se não estiver, redireciona para a página inicial.

include_once __DIR__ . '/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
?>