<?php
/**
 * proxy.php – Intermediário de imagens para Backblaze B2
 *
 * Responsabilidades:
 * - Receber o nome original do arquivo (via GET)
 * - Codificar em Base64 URL-safe (sem '=') para compatibilidade com o upload
 * - Tentar baixar do B2 com o nome codificado (arquivos existentes)
 * - Se falhar (404), tentar com o nome original (fallback)
 * - Servir a imagem com headers de cache apropriados
 * - Log de falhas para diagnóstico
 *
 * @package A Fenda
 * @version 4.0 – Correção da codificação URL-safe
 */

// Carrega variáveis de ambiente
require_once __DIR__ . '/.env.php';
require_once __DIR__ . '/includes/B2Client.php';

// ============================================================
// 1. VALIDAÇÃO DE ENTRADA
// ============================================================
$path = isset($_GET['path']) ? trim($_GET['path']) : '';

if (empty($path) || strpos($path, '..') !== false) {
    http_response_code(400);
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowed)) {
    http_response_code(403);
    exit;
}

// ============================================================
// 2. CONFIGURAÇÃO DE SEGURANÇA SSL
// ============================================================
$is_production = (getenv('ENVIRONMENT') === 'production');
$verifySSL = $is_production ? true : false;

// ============================================================
// 3. FUNÇÃO AUXILIAR PARA CODIFICAÇÃO URL-SAFE (sem '=')
// ============================================================
function urlSafeBase64Encode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

// ============================================================
// 4. FUNÇÃO AUXILIAR PARA TENTAR DOWNLOAD
// ============================================================
function tentarDownload($b2, $nomeArquivo, $verifySSL) {
    try {
        $authToken = $b2->getDownloadAuthorizationToken($nomeArquivo, 300);
        $url = $b2->getDownloadUrl($nomeArquivo) . '?Authorization=' . urlencode($authToken);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySSL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($imageData)) {
            return ['success' => true, 'data' => $imageData, 'usedName' => $nomeArquivo];
        }
        return ['success' => false, 'httpCode' => $httpCode];
    } catch (Exception $e) {
        error_log("[PROXY] Exceção ao tentar '$nomeArquivo': " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// ============================================================
// 5. ESTRATÉGIA DE DOWNLOAD (FALLBACK)
// ============================================================
try {
    $b2 = B2Client::getInstance();

    // 1ª tentativa: nome codificado em Base64 URL-safe (sem '=')
    $encodedPath = urlSafeBase64Encode($path);
    error_log("[PROXY] Tentando codificado: $encodedPath");
    $result = tentarDownload($b2, $encodedPath, $verifySSL);

    // 2ª tentativa (fallback): nome original (caso algum arquivo não tenha sido codificado)
    if (!$result['success']) {
        $httpCode = $result['httpCode'] ?? 0;
        error_log("[PROXY] Falha com codificado (HTTP $httpCode). Tentando original: $path");
        $result = tentarDownload($b2, $path, $verifySSL);
    }

    // Se ambas falharam, retorna erro
    if (!$result['success']) {
        $httpCode = $result['httpCode'] ?? 500;
        error_log("[PROXY] Falha total para '$path' – HTTP $httpCode");
        http_response_code($httpCode);
        exit;
    }

    // ============================================================
    // 6. SERVE A IMAGEM COM HEADERS ADEQUADOS
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
    header('Cache-Control: public, max-age=86400'); // 1 dia
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . strlen($result['data']));
    echo $result['data'];
    exit;

} catch (Exception $e) {
    error_log("[PROXY] Exceção global: " . $e->getMessage());
    http_response_code(500);
    exit;
}