<?php
chdir(__DIR__ . '/..');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = ltrim($path, '/');
echo "Arquivo requisitado: " . $file;