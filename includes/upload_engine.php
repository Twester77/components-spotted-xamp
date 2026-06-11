<?php
/**
 * MOTOR DE UPLOAD PADRONIZADO - "A FENDA"
 * * Assinatura obrigatória (não mude a ordem):
 * 1. $file_data: O array $_FILES['seu_input']
 * 2. $destino: Caminho da pasta (ex: './uploads')
 * 3. $prefixo: Prefixo do arquivo (ex: 'user', 'post', 'coment')
 * 4. $max_size: Tamanho máximo em bytes (padrão 2MB = 2097152)
 */

function processarUploadSeguro($file_data, $destino, $prefixo, $max_size = 2097152) {
    
    // 1. Validar se o arquivo existe e não tem erro
    if (!isset($file_data) || $file_data['error'] !== 0) return false;

    // 2. Validar tamanho
    if ($file_data['size'] > $max_size) return false;

    // 3. Validar Mime-Type real (Bytes Mágicos)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_data['tmp_name']);
    $formatos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime_type, $formatos)) return false;

    // 4. Carregar imagem conforme o tipo
    switch ($mime_type) {
        case 'image/jpeg': $img = imagecreatefromjpeg($file_data['tmp_name']); break;
        case 'image/png':  $img = imagecreatefrompng($file_data['tmp_name']); break;
        case 'image/webp': $img = imagecreatefromwebp($file_data['tmp_name']); break;
        case 'image/gif': $img = imagecreatefromgif($file_data['tmp_name']); break;
        default: return false;
    }

    // 5. Gerar nome único e salvar
    $novo_nome = $prefixo . "_" . bin2hex(random_bytes(8)) . "_" . time() . ".webp";
    $caminho_completo = $destino . "/" . $novo_nome;

    if ($img) {
        $sucesso = imagewebp($img, $caminho_completo, 75);
        imagedestroy($img);
        return $sucesso ? $novo_nome : false;
    }

    return false;
}
?>