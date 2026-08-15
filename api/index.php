<?php
chdir(__DIR__ . '/..');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = ltrim($path, '/');

$filePath = realpath(__DIR__ . '/../' . $file);

// Se for um arquivo válido dentro da raiz do projeto, inclui e encerra
if ($filePath && strpos($filePath, realpath(__DIR__ . '/..')) === 0 && is_file($filePath)) {
    require_once $filePath;
    exit;
}

// Fallback: se não encontrou nada, carrega o index.php (página inicial)
require_once 'index.php';