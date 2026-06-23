<?php
// ver-log.php - Visualizador temporário do debug_fenda.log
// 🔥 REMOVA ESTE ARQUIVO APÓS O TESTE!

$logFile = __DIR__ . '/debug_fenda.log';

if (!file_exists($logFile)) {
    die("Arquivo debug_fenda.log ainda não foi criado.");
}

header('Content-Type: text/plain; charset=utf-8');
echo file_get_contents($logFile);
?>