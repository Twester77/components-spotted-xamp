<?php
/**
 * api/index.php – Roteador Serverless da Vercel
 *
 * Toda requisição que não bate em rota explícita do vercel.json cai aqui.
 * O arquivo resolve o path da URL para um arquivo real dentro da raiz
 * do projeto, valida e inclui.
 *
 * 🔥 ATUALIZAÇÃO IARA – 2026-09-23 (auditoria v5.0)
 *    Corrige 3 furos críticos identificados na revisão da Djê:
 *
 *    1. PATH TRAVERSAL POR PREFIXO
 *       A checagem original `strpos($filePath, realpath(__DIR__ . '/..')) === 0`
 *       era vulnerável a pastas irmãs com prefixo similar (ex: /app vs /app-evil).
 *       Agora usa DIRECTORY_SEPARATOR na validação, garantindo que o arquivo
 *       esteja estritamente dentro da raiz.
 *
 *    2. require_once CEGO
 *       O original incluía qualquer arquivo que existisse na raiz, sem
 *       checar extensão nem nome. Isso permitia execução/vazamento de
 *       .env.php, .json, .md, .git/config (se existissem no bundle).
 *       Agora exige extensão .php e aplica blacklist de arquivos internos
 *       (conexao.php, fenda_debug.php, auth_check.php) e diretórios
 *       internos (includes/, config/, api/, .vercel/).
 *
 *    3. FALLBACK QUEBRADO
 *       O `require_once 'index.php'` no fim do arquivo resolvia para o
 *       próprio api/index.php (caminho relativo contra o diretório do
 *       script), e require_once não re-inclui o mesmo arquivo. Resultado:
 *       silent no-op e página em branco. Agora retorna 404 HTML amigável
 *       (ou JSON quando a rota é da API).
 *
 * @package A Fenda
 */

// ============================================================
// 1. FUNÇÃO AUXILIAR: RESPOSTA 404
// ============================================================
function responder404(string $path): void
{
    http_response_code(404);

    // Rota da API: responde JSON
    if ($path === '/api' || strpos($path, '/api/') === 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Recurso não encontrado.'
        ]);
        exit;
    }

    // Rota de página: responde HTML amigável (sem depender de conexao.php)
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 — A Fenda</title>
        <style>
            body {
                background: #0a0a0a;
                color: #fff;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                text-align: center;
            }
            .container { max-width: 500px; }
            h1 {
                color: #ffbc00;
                font-size: clamp(3rem, 12vw, 5rem);
                margin: 0 0 10px 0;
                letter-spacing: 4px;
            }
            p {
                color: #ccc;
                font-size: 1.05rem;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            a {
                display: inline-block;
                background: #ffbc00;
                color: #000;
                padding: 12px 28px;
                border-radius: 30px;
                text-decoration: none;
                font-weight: bold;
                transition: transform 0.2s ease;
            }
            a:hover { transform: scale(1.05); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>404</h1>
            <p>Ops... essa página não existe na Fenda.<br>
               Talvez o link tenha se perdido no fundo do mar.</p>
            <a href="/feed.php">Voltar para o Feed</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// 2. PREPARAÇÃO: NORMALIZA O PATH DA REQUISIÇÃO
// ============================================================
chdir(__DIR__ . '/..');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = ltrim((string)$path, '/');

// Rota raiz: serve index.php
if ($file === '') {
    $file = 'index.php';
}

// ============================================================
// 3. RESOLVE O CAMINHO ABSOLUTO DO ARQUIVO
// ============================================================
$rootDir = realpath(__DIR__ . '/..');
if ($rootDir === false) {
    http_response_code(500);
    exit('Erro interno de configuração do roteador.');
}

$filePath = realpath($rootDir . DIRECTORY_SEPARATOR . $file);

// ============================================================
// 4. VALIDA QUE O ARQUIVO ESTÁ DENTRO DA RAIZ
//     (com DIRECTORY_SEPARATOR — corrige o bug de prefixo)
// ============================================================
$rootWithSep = $rootDir . DIRECTORY_SEPARATOR;

if ($filePath === false) {
    responder404($path);
}

if (strpos($filePath . DIRECTORY_SEPARATOR, $rootWithSep) !== 0) {
    responder404($path);
}

// ============================================================
// 5. VALIDA QUE É ARQUIVO (não diretório)
// ============================================================
if (!is_file($filePath)) {
    responder404($path);
}

// ============================================================
// 6. VALIDA EXTENSÃO (whitelist: apenas .php)
// ============================================================
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if ($ext !== 'php') {
    responder404($path);
}

// ============================================================
// 7. BLACKLIST: ARQUIVOS INTERNOS E SENSÍVEIS
// ============================================================
$basename = basename($filePath);
$relative = str_replace('\\', '/', substr($filePath, strlen($rootWithSep)));

// 7.1 Arquivos proibidos na raiz (includes internos)
$arquivos_bloqueados = [
    'conexao.php',
    'fenda_debug.php',
    'auth_check.php',
];
if (in_array($basename, $arquivos_bloqueados, true)) {
    responder404($path);
}

// 7.2 Arquivos com prefixo '.' (ocultos) ou 'teste-'
if (strpos($basename, '.') === 0 || strpos($basename, 'teste-') === 0) {
    responder404($path);
}

// 7.3 Diretórios internos proibidos (recursivo)
$diretorios_bloqueados = ['includes/', 'config/', 'api/', '.vercel/'];
foreach ($diretorios_bloqueados as $dir) {
    if (strpos($relative, $dir) === 0) {
        responder404($path);
    }
}

// ============================================================
// 8. INCLUI O ARQUIVO VALIDADO
// ============================================================
require $filePath;
exit;