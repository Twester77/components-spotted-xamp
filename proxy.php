<?php
/**
 * proxy.php – Intermediário de imagens para Backblaze B2
 *
 * 🔧 ATUALIZAÇÃO NEREIDA – 2026-08-26
 *    "Adicionados logs detalhados para diagnóstico em produção.
 *     Incluídos logs de entrada, autenticação, tentativas de download e erros."
 */

// Carrega variáveis de ambiente
if (file_exists(__DIR__ . '/.env.php')) {
    require_once __DIR__ . '/.env.php';
}
require_once __DIR__ . '/includes/B2Client.php';

error_log("[PROXY] 🔵 INICIANDO proxy.php para path: " . ($_GET['path'] ?? 'NENHUM'));

// ============================================================
// 1. VALIDAÇÃO DE ENTRADA
// ============================================================
$path = isset($_GET['path']) ? trim($_GET['path']) : '';

if (empty($path) || strpos($path, '..') !== false) {
    error_log("[PROXY] ❌ Caminho inválido ou vazio: '$path'");
    http_response_code(400);
    exit;
}

$path = preg_replace('#/+#', '/', $path);
$path = ltrim($path, '/');

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowed)) {
    error_log("[PROXY] ❌ Extensão não permitida: '$ext' para path: '$path'");
    http_response_code(403);
    exit;
}

error_log("[PROXY] ✅ Path validado: '$path' (ext: $ext)");

// ============================================================
// 2. CONFIGURAÇÃO DE SEGURANÇA SSL
// ============================================================
$is_production = (getenv('ENVIRONMENT') === 'production');
$verifySSL = $is_production ? true : false;
error_log("[PROXY] verifySSL = " . ($verifySSL ? 'true' : 'false') . " (produção: " . ($is_production ? 'sim' : 'não') . ")");

// ============================================================
// 3. FUNÇÃO AUXILIAR PARA CODIFICAÇÃO URL-SAFE
// ============================================================
function urlSafeBase64Encode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

// ============================================================
// 4. FUNÇÃO AUXILIAR PARA TENTAR DOWNLOAD
// ============================================================
function tentarDownload($b2, $nomeArquivo, $verifySSL) {
    error_log("[PROXY] 📥 Tentando download de '$nomeArquivo' (verifySSL=" . ($verifySSL ? 'true' : 'false') . ")");
    try {
        error_log("[PROXY] 🔑 Solicitando token de autorização para '$nomeArquivo'...");
        $authToken = $b2->getDownloadAuthorizationToken($nomeArquivo, 300);
        error_log("[PROXY] ✅ Token obtido (primeiros 20 chars): " . substr($authToken, 0, 20) . "...");

        $baseUrl = $b2->getDownloadUrl($nomeArquivo);
        $url = $baseUrl . '?Authorization=' . urlencode($authToken);
        error_log("[PROXY] 🔗 URL gerada: " . substr($url, 0, 150) . "...");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySSL ? 2 : 0);

        error_log("[PROXY] ⏳ Executando requisição cURL para '$nomeArquivo'...");
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && !empty($imageData)) {
            error_log("[PROXY] ✅ Download bem-sucedido: '$nomeArquivo' (HTTP $httpCode, " . strlen($imageData) . " bytes)");
            return ['success' => true, 'data' => $imageData, 'usedName' => $nomeArquivo];
        }
        error_log("[PROXY] ⚠️ Falha no download: '$nomeArquivo' (HTTP $httpCode, cURL: " . ($curlError ?: 'nenhum') . ")");
        return ['success' => false, 'httpCode' => $httpCode, 'curlError' => $curlError];
    } catch (Exception $e) {
        error_log("[PROXY] ❌ Exceção ao tentar '$nomeArquivo': " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============================================================
// 5. ESTRATÉGIA DE DOWNLOAD (FALLBACK)
// ============================================================
try {
    error_log("[PROXY] 🔄 Obtendo instância do B2Client...");
    $b2 = B2Client::getInstance();
    error_log("[PROXY] ✅ B2Client instanciado com sucesso");

    // 1ª tentativa: nome codificado em Base64 URL-safe
    $encodedPath = urlSafeBase64Encode($path);
    error_log("[PROXY] 🔑 Tentando codificado: $encodedPath");
    $result = tentarDownload($b2, $encodedPath, $verifySSL);

    // 2ª tentativa (fallback): nome original
    if (!$result['success']) {
        $httpCode = $result['httpCode'] ?? 0;
        $curlError = $result['curlError'] ?? '';
        error_log("[PROXY] 🔄 Falha com codificado (HTTP $httpCode). Tentando original: $path");
        $result = tentarDownload($b2, $path, $verifySSL);
    }

    if (!$result['success']) {
        $httpCode = $result['httpCode'] ?? 500;
        error_log("[PROXY] ❌ Falha total para '$path' – HTTP $httpCode");
        http_response_code($httpCode);
        exit;
    }

    // ============================================================
    // 6. SERVE A IMAGEM
    // ============================================================
    $mimeMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif'
    ];
    $contentType = $mimeMap[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . strlen($result['data']));
    echo $result['data'];
    error_log("[PROXY] ✅ Imagem servida com sucesso: '$path' (" . strlen($result['data']) . " bytes)");
    exit;

} catch (Exception $e) {
    error_log("[PROXY] ❌ Exceção global: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    exit;
}