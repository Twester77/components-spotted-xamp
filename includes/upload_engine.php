<?php

/**
 * MOTOR DE UPLOAD PADRONIZADO - "A FENDA" (VERSÃO SEGURA)
 * - Validação por bytes mágicos (finfo)
 * - Validação por exif_imagetype (fallback)
 * - Bloqueia arquivos com tags PHP (polyglot)
 * - GIFs preservam animação; demais imagens convertidas para WebP
 */

function processarUploadSeguro($file_data, $destino, $prefixo, $max_size = 2097152)
{
    // 1. Validar se o arquivo existe e não tem erro
    if (!isset($file_data) || $file_data['error'] !== 0) return false;

    // 2. Validar tamanho
    if ($file_data['size'] > $max_size) return false;

    // 3. Validar Mime-Type real (Bytes Mágicos)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_data['tmp_name']);
    $formatos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime_type, $formatos)) return false;

    // 4. Segunda validação com exif_imagetype (fallback)
    $exif_type = exif_imagetype($file_data['tmp_name']);
    $valid_exif = in_array($exif_type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF]);
    if (!$valid_exif) return false;

    // 5. BLOQUEIA POLYGLOT (arquivos com código PHP)
    $content = file_get_contents($file_data['tmp_name'], false, null, 0, 100); // lê apenas 100 bytes
    if (strpos($content, '<?php') !== false || strpos($content, '<?') !== false) {
        return false; // potencial polyglot – rejeita
    }

    // 6. Processamento conforme tipo
    $img = null;
    $extensao = 'webp'; // padrão para conversão

    switch ($mime_type) {
        case 'image/jpeg':
            $img = imagecreatefromjpeg($file_data['tmp_name']);
            break;
        case 'image/png':
            $img = imagecreatefrompng($file_data['tmp_name']);
            break;
        case 'image/webp':
            $img = imagecreatefromwebp($file_data['tmp_name']);
            break;
        case 'image/gif':
            // GIF animado não pode ser convertido para WebP – copia o arquivo original
            $extensao = 'gif';
            $novo_nome = $prefixo . "_" . bin2hex(random_bytes(8)) . "_" . time() . "." . $extensao;
            $caminho_completo = $destino . "/" . $novo_nome;

            // Garante que a pasta existe
            if (!is_dir($destino)) {
                mkdir($destino, 0755, true);
                // Protege com .htaccess (caso não exista)
                $htaccess = $destino . "/.htaccess";
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\.(php|phtml|php3|php4|php5|phar|shtml|cgi|pl|py|jsp|asp|htm|html|js|css)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>");
                }
            }

            if (copy($file_data['tmp_name'], $caminho_completo)) {
                return $novo_nome;
            }
            return false;
        default:
            return false;
    }

    // Para JPEG, PNG e WEBP: converte para WebP
    $novo_nome = $prefixo . "_" . bin2hex(random_bytes(8)) . "_" . time() . ".webp";
    $caminho_completo = $destino . "/" . $novo_nome;

    // Garante a pasta
    if (!is_dir($destino)) {
        mkdir($destino, 0755, true);
        $htaccess = $destino . "/.htaccess";
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\.(php|phtml|php3|php4|php5|phar|shtml|cgi|pl|py|jsp|asp|htm|html|js|css)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>");
        }
    }

    if ($img) {
        $sucesso = imagewebp($img, $caminho_completo, 75);
        imagedestroy($img);
        return $sucesso ? $novo_nome : false;
    }
    return false;
}
?>
