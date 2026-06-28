<?php
// /api/index.php – Roteador Inteligente
// Corrige o comportamento de todas as rotas apontarem para a home

chdir(__DIR__ . '/..');

// Pega a URL limpa (sem query strings tipo ?id=1)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove a barra inicial para localizar o arquivo
$file = ltrim($path, '/');

// Se for raiz ou index, carrega o index
if ($file === '' || $file === 'index.php') {
    require_once 'index.php';
    exit;
}

// Verifica se o arquivo existe fisicamente na pasta raiz
// Usamos realpath para evitar que alguém tente acessar arquivos fora da pasta
$filePath = realpath(__DIR__ . '/../' . $file);

// Garante que o arquivo existe e está dentro da pasta do projeto
if ($filePath && strpos($filePath, realpath(__DIR__ . '/..')) === 0 && is_file($filePath)) {
    require_once $filePath;
    exit;
}

// Fallback: se não encontrar nada, joga pra home
require_once 'index.php';
?>