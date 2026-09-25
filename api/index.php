<?php

/**
 * api/index.php – Roteador Serverless da Vercel
 *
 * 🔥 ATUALIZAÇÃO IARA – 2026-09-23 (auditoria v5.0)
 *    Corrige 3 furos críticos identificados na revisão da Djê:
 *      1. Path traversal por prefixo ingênuo → DIRECTORY_SEPARATOR
 *      2. require_once cego → whitelist estrita de extensão + arquivos
 *      3. Fallback quebrado → 404 amigável (HTML/JSON)
 *
 * 🔧 PATCH IARA – 2026-09-23 (v5.1 — após testes em produção)
 *    A whitelist por diretório inteiro ('includes/') bloqueava endpoints
 *    públicos usados pelo app (contar_alertas.php, checar-notificacoes.php,
 *    reagir.php, etc). Substituída por whitelist seletiva:
 *      - Arquivos sensíveis bloqueados por nome (conexao.php, auth_check.php,
 *        fenda_debug.php, upload_engine.php, B2Client.php).
 *      - Dentro de includes/, apenas endpoints explicitamente permitidos.
 *
 * @package A Fenda
 */

// ============================================================
// 1. FUNÇÃO AUXILIAR: RESPOSTA 404
// ============================================================
function responder404(string $path): void
{
    http_response_code(404);

    if ($path === '/api' || strpos($path, '/api/') === 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Recurso não encontrado.'
        ]);
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 — A Fenda</title>
        <style>
             * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            * {
                -webkit-tap-highlight-color: transparent;
                /* Chrome, Safari, Opera, Edge, iOS */
                tap-highlight-color: transparent;
                /* Padrão futuro / Outros navegadores */

            }

            body {
                background: #0a0a0a;
                background: hsl(0, 0%, 4%);
                color: #fff;
                font-family: 'Inter', system-ui, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                text-align: center;
            }

            .container {
                max-width: 600px;
            }

            h1 {
                color: #ffbc00;
                font-size: 3rem;
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

            a:hover {
                transform: scale(1.05);
            }

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
// ============================================================
$rootWithSep = $rootDir . DIRECTORY_SEPARATOR;

if ($filePath === false) {
    responder404($path);
}

if (strpos($filePath . DIRECTORY_SEPARATOR, $rootWithSep) !== 0) {
    responder404($path);
}

if (!is_file($filePath)) {
    responder404($path);
}

// ============================================================
// 5. VALIDA EXTENSÃO (whitelist: apenas .php)
// ============================================================
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if ($ext !== 'php') {
    responder404($path);
}

// ============================================================
// 6. BLACKLIST — ARQUIVOS INTERNOS SENSÍVEIS
// ============================================================
$basename  = basename($filePath);
$relative  = str_replace('\\', '/', substr($filePath, strlen($rootWithSep)));

// 6.1 Arquivos que NUNCA devem ser servidos diretamente pela URL
$arquivos_bloqueados = [
    // Núcleo do sistema
    'conexao.php',
    'fenda_debug.php',
    'auth_check.php',
    'auth-bridge.php',        // só via POST interno, não por URL
    // Upload engine e client B2 (só via require)
    'upload_engine.php',
    'B2Client.php',
];
if (in_array($basename, $arquivos_bloqueados, true)) {
    responder404($path);
}

// 6.2 Arquivos ocultos e testes
if (strpos($basename, '.') === 0 || strpos($basename, 'teste-') === 0) {
    responder404($path);
}

// ============================================================
// 7. WHITELIST — ENDPOINTS PERMITIDOS EM includes/
// ============================================================
// 🔧 PATCH IARA – 2026-09-24 (v5.2)
//    A whitelist original esqueceu includes/excluir.php — endpoint
//    legítimo usado pelo fenda-main.js para exclusão via long press
//    no feed/ver-perfil. Sem ele, o feed caía no 404 amigável.
//    Adicionado à lista de permitidos. NÃO unificar com
//    excluir-post.php: são fluxos com regras de permissão distintas
//    (autor no feed, autor OU admin/criador na comunidade).
if (strpos($relative, 'includes/') === 0) {
    $endpoints_publicos = [
        'includes/excluir.php',              // 🔥 ADICIONADO (feed/ver-perfil via long press)
        'includes/excluir-comentario.php',
        'includes/contar_alertas.php',
        'includes/checar-notificacoes.php',
        'includes/reagir.php',
        'includes/comunidade-actions.php',
        'includes/comentarios-novos.php',   // 🔥 ADICIONADO endpoint novo de comentarios
    ];
    
    if (!in_array($relative, $endpoints_publicos, true)) {
        responder404($path);
    }
}

// ============================================================
// 8. BLACKLIST — DIRETÓRIOS INTERNOS
// ============================================================
$diretorios_bloqueados = ['config/', 'api/', '.vercel/'];
foreach ($diretorios_bloqueados as $dir) {
    if (strpos($relative, $dir) === 0) {
        responder404($path);
    }
}

// ============================================================
// 9. INCLUI O ARQUIVO VALIDADO
// ============================================================
require $filePath;
exit;
