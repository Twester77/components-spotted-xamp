<?php
chdir(__DIR__ . '/..');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = ltrim($path, '/');

$filePath = realpath(__DIR__ . '/../' . $file);

if ($filePath && strpos($filePath, realpath(__DIR__ . '/..')) === 0 && is_file($filePath)) {
    echo "Arquivo encontrado: " . $filePath;
} else {
    echo "Arquivo não encontrado ou fora da raiz: " . $file;
}