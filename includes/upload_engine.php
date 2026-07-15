<?php

/**
 * upload_engine.php – Motor de Upload Blindado (B2 + Atomicidade + Log + Vercel)
 * 
 * Responsabilidades:
 * - Validação de arquivos (bytes mágicos, polyglot, tamanho)
 * - Conversão para WebP (exceto GIFs animados)
 * - Upload para Backblaze B2 via B2Client
 * - Rollback automático se o banco falhar (atomicidade)
 * - Logging estruturado via error_log() (compatível com Vercel/Serverless)
 * - Tratamento graceful de exceções
 * -  Função auxiliar obterUrlImagem() para exibição (DRY)
 */

// Inclui o B2Client (caminho relativo)
require_once __DIR__ . '/B2Client.php';

// ============================================================
// 1. FUNÇÃO DE LOG ESTRUTURADO ("CAIXA PRETA" – VERCEL-FRIENDLY)
// ============================================================

function logB2Event($level, $userId, $action, $filename, $statusCode = 0, $message = '')
{
    $timestamp = date('Y-m-d H:i:s');
    $line = sprintf(
        "[%s] | %s | UID:%d | %s | %s | HTTP:%d | %s",
        $timestamp,
        strtoupper($level),
        $userId,
        $action,
        $filename,
        $statusCode,
        $message
    );
    error_log($line);
}

// ============================================================
// 2. FUNÇÃO DE ROLLBACK (DELETE DO B2)
// ============================================================

function deleteFromB2($remotePath, $userId = 0)
{
    if (empty($remotePath)) {
        logB2Event('WARNING', $userId, 'DELETE', '', 0, 'Tentativa de deletar caminho vazio');
        return true;
    }

    try {
        $b2 = B2Client::getInstance();
        $deleted = $b2->deleteFile($remotePath);
        logB2Event(
            'INFO',
            $userId,
            'DELETE',
            $remotePath,
            $deleted ? 200 : 404,
            $deleted ? 'Arquivo removido com sucesso' : 'Arquivo não encontrado (já removido?)'
        );
        return $deleted;
    } catch (Exception $e) {
        logB2Event(
            'ERROR',
            $userId,
            'DELETE',
            $remotePath,
            0,
            'Falha ao deletar: ' . $e->getMessage()
        );
        return false;
    }
}

// ============================================================
// 3. FUNÇÃO PRINCIPAL DE UPLOAD (COM ATOMICIDADE E VERIFICAÇÃO)
// ============================================================

function processarUploadSeguro($file_data, $destino, $prefixo, $max_size = 2097152, $usuario_id = 0)
{
    // 1. VALIDAÇÕES INICIAIS
    if (!isset($file_data) || $file_data['error'] !== 0) {
        logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, 'Arquivo não enviado ou erro de upload: ' . ($file_data['error'] ?? 'desconhecido'));
        return false;
    }

    if ($file_data['size'] > $max_size) {
        logB2Event('WARNING', $usuario_id, 'UPLOAD', '', 0, 'Tamanho excede o limite: ' . $file_data['size'] . ' > ' . $max_size);
        return false;
    }

    // 2. VALIDAÇÃO DE MIME (bytes mágicos)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_data['tmp_name']);
    $formatos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime_type, $formatos)) {
        logB2Event('WARNING', $usuario_id, 'UPLOAD', '', 0, 'Formato não permitido: ' . $mime_type);
        return false;
    }

    // 3. VALIDAÇÃO EXIF (fallback)
    $exif_type = exif_imagetype($file_data['tmp_name']);
    $valid_exif = in_array($exif_type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF]);
    if (!$valid_exif) {
        logB2Event('WARNING', $usuario_id, 'UPLOAD', '', 0, 'exif_imagetype falhou: ' . $exif_type);
        return false;
    }

    // 4. BLOQUEIO POLYGLOT (arquivos com código PHP)
    $content = file_get_contents($file_data['tmp_name'], false, null, 0, 100);
    if (strpos($content, '<?php') !== false || strpos($content, '<?') !== false) {
        logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, 'Polyglot bloqueado (código PHP detectado)');
        return false;
    }

    // 5. PROCESSAMENTO DA IMAGEM (conversão para WebP ou GIF)
    $img = null;
    $remotePath = '';
    $tempFile = null;

    try {
        // 5.1 Carregar imagem
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
                // GIF animado: mantém original
                $remotePath = $prefixo . "_" . bin2hex(random_bytes(8)) . "_" . time() . ".gif";
                try {
                    $b2 = B2Client::getInstance();
                    $b2->uploadFile($file_data['tmp_name'], $remotePath, 'image/gif', ['Cache-Control' => 'max-age=31536000']);
                    $b2->getDownloadUrl($remotePath); // verificação
                    logB2Event('INFO', $usuario_id, 'UPLOAD', $remotePath, 200, 'Upload GIF bem-sucedido');
                    return $remotePath;
                } catch (Exception $e) {
                    logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath, 0, 'Falha no upload GIF: ' . $e->getMessage());
                    return false;
                }
            default:
                logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, 'Formato não suportado: ' . $mime_type);
                return false;
        }

        if ($img === null) {
            logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, 'Falha ao criar imagem a partir do arquivo');
            return false;
        }

        // 5.2 Converte para WebP
        $remotePath = $prefixo . "_" . bin2hex(random_bytes(8)) . "_" . time() . ".webp";
        $tempFile = tempnam(sys_get_temp_dir(), 'b2_') . '.webp';
        if (!imagewebp($img, $tempFile, 75)) {
            imagedestroy($img);
            if (file_exists($tempFile)) unlink($tempFile);
            logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath, 0, 'Falha na conversão para WebP');
            return false;
        }
        imagedestroy($img);

        // 6. UPLOAD PARA O B2
        try {
            $b2 = B2Client::getInstance();
            $b2->uploadFile($tempFile, $remotePath, 'image/webp', ['Cache-Control' => 'max-age=31536000']);
        } catch (Exception $e) {
            if ($tempFile && file_exists($tempFile)) unlink($tempFile);
            logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath, 0, 'Falha no upload para B2: ' . $e->getMessage());
            return false;
        }

        if ($tempFile && file_exists($tempFile)) unlink($tempFile);

        // 7. VERIFICAÇÃO DE INTEGRIDADE (post-upload check)
        try {
            $b2->getDownloadUrl($remotePath);
            logB2Event('INFO', $usuario_id, 'VERIFY', $remotePath, 200, 'Arquivo verificado com sucesso');
        } catch (Exception $e) {
            deleteFromB2($remotePath, $usuario_id);
            logB2Event('ERROR', $usuario_id, 'VERIFY', $remotePath, 0, 'Verificação falhou, arquivo removido: ' . $e->getMessage());
            return false;
        }

        logB2Event('INFO', $usuario_id, 'UPLOAD', $remotePath, 200, 'Upload e verificação concluídos');
        return $remotePath;
    } catch (Exception $e) {
        if ($tempFile && file_exists($tempFile)) unlink($tempFile);
        logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath ?: '', 0, 'Exceção: ' . $e->getMessage());
        return false;
    }
}

// ============================================================
// 4. FUNÇÃO DE ROLLBACK (PARA SER CHAMADA SE O BANCO FALHAR)
// ============================================================

function rollbackUpload($remotePath, $usuario_id = 0)
{
    if (empty($remotePath)) return true;
    logB2Event('INFO', $usuario_id, 'ROLLBACK', $remotePath, 0, 'Iniciando rollback por falha no banco');
    return deleteFromB2($remotePath, $usuario_id);
}

// ============================================================
// 5. FUNÇÃO DE EXCLUSÃO DEFINITIVA (PARA USO GERAL)
// ============================================================

function excluirArquivoB2($remotePath, $usuario_id = 0)
{
    if (empty($remotePath)) {
        logB2Event('WARNING', $usuario_id, 'DELETE', '', 0, 'Tentativa de excluir caminho vazio');
        return true;
    }
    return deleteFromB2($remotePath, $usuario_id);
}

// ============================================================
// 🔥 6. FUNÇÃO AUXILIAR PARA OBTENÇÃO DE URL DO B2 (DRY)
// ============================================================

/**
 * Obtém a URL pública ou assinada de uma imagem no B2.
 * Normaliza o caminho (remove prefixos locais) e retorna a URL.
 * 
 * @param string     $caminho   Caminho salvo no banco (ex: 'postagens/foto.webp')
 * @param B2Client   $b2        Instância do B2 (se null, obtém via getInstance)
 * @param bool       $assinado  Se true, gera Signed URL (bucket privado)
 * @param int        $duracao   Duração da Signed URL em segundos (padrão: 3600)
 * 
 * @return string|null URL da imagem ou null se inválido
 */
function obterUrlImagem($caminho, $b2 = null, $assinado = false, $duracao = 3600)
{
    // 1. Se o caminho for vazio ou não for string, retorna null
    if (empty($caminho) || !is_string($caminho)) {
        return null;
    }

    // 2. Se já for URL externa (ex: GIF do Giphy), retorna ela mesma
    if (filter_var($caminho, FILTER_VALIDATE_URL)) {
        return $caminho;
    }

    // 3. Normaliza: remove prefixos locais (postagens/, comentarios/, uploads/)
    $prefixos = ['postagens/', 'comentarios/', 'uploads/'];
    $caminhoLimpo = trim($caminho);
    foreach ($prefixos as $prefixo) {
        if (strpos($caminhoLimpo, $prefixo) === 0) {
            $caminhoLimpo = substr($caminhoLimpo, strlen($prefixo));
            break;
        }
    }

    if (empty($caminhoLimpo)) {
        return null;
    }

    // Não precisamos mais do $b2, nem de $assinado, nem de $duracao.
    // O proxy na raiz do servidor resolve tudo.
    // Isso evita que o seu site tente "assinar" a URL no cliente (o que causa o erro de ORB).
    
    return 'proxy.php?path=' . urlencode($caminhoLimpo);
}