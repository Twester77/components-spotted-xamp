<?php
/**
 * notificacoes-rapidas.php – Dropdown de notificações (agora sem marcar como lidas)
 * Usa motor-notificacoes.php para renderizar o conteúdo.
 */

require_once __DIR__ . '/auth_check.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if ($usuario_id == 0) {
    echo "<p style='padding:15px; color:#fff; text-align:center;'>Faça login para ver as notificações...</p>";
    exit();
}

// 🔥 Inclui o motor (que NÃO marca como lidas)
include 'motor-notificacoes.php';
?>