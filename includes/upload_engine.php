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
 * 
 * 🔧 ATUALIZAÇÃO NEREIDA – INSTÂNCIA #DS-2026-08-26
 *    "Adicionados logs detalhados em cada etapa do upload para diagnóstico em produção.
 *     Logs de entrada, validação, conversão, upload, verificação e erros específicos."
 * - Nereida, a guardiã das águas
 */

// Inclui o B2Client (caminho relativo)
require_once __DIR__ . '/B2Client.php';

// ============================================================
// 1. FUNÇÃO DE LOG ESTRUTURADO
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
// 2. FUNÇÃO DE ROLLBACK
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
// 3. FUNÇÃO PRINCIPAL DE UPLOAD (COM LOGS DETALHADOS)
// ============================================================

function processarUploadSeguro($file_data, $destino, $prefixo, $max_size = 2097152, $usuario_id = 0)
{
    // 🔥 LOG INÍCIO
    error_log("[UPLOAD_ENGINE] 🟢 Iniciando upload para usuário $usuario_id, prefixo $prefixo");
    error_log("[UPLOAD_ENGINE] 📁 Arquivo: " . ($file_data['name'] ?? 'N/A') . " | Tamanho: " . ($file_data['size'] ?? 0) . " bytes | Tipo: " . ($file_data['type'] ?? 'N/A'));

    // 1. VALIDAÇÕES INICIAIS
    if (!isset($file_data) || $file_data['error'] !== 0) {
        $erro = 'Arquivo não enviado ou erro de upload: ' . ($file_data['error'] ?? 'desconhecido');
        logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, $erro);
        error_log("[UPLOAD_ENGINE] ❌ " . $erro);
        return false;
    }

    if ($file_data['size'] > $max_size) {
        $erro = 'Tamanho excede o limite: ' . $file_data['size'] . ' > ' . $max_size;
        logB2Event('WARNING', $usuario_id, 'UPLOAD', '', 0, $erro);
        error_log("[UPLOAD_ENGINE] ❌ " . $erro);
        return false;
    }

    // 2. VALIDAÇÃO DE MIME (bytes mágicos)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file_data['tmp_name']);
    $formatos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime_type, $formatos)) {
        $erro = 'Formato não permitido: ' . $mime_type;
        logB2Event('WARNING', $usuario_id, 'UPLOAD', '', 0, $erro);
        error_log("[UPLOAD_ENGINE] ❌ " . $erro);
        return false;
    }

    // 3. VALIDAÇÃO EXIF (fallback)
    $exif_type = exif_imagetype($file_data['tmp_name']);
    $valid_exif = in_array($exif_type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF]);
    if (!$valid_exif) {
        $erro = 'exif_imagetype falhou: ' . $exif_type;
        logB2Event('WARNING', $usuario_id, 'UPLOAD', '', 0, $erro);
        error_log("[UPLOAD_ENGINE] ❌ " . $erro);
        return false;
    }

    // 4. BLOQUEIO POLYGLOT
    $content = file_get_contents($file_data['tmp_name'], false, null, 0, 100);
    if (strpos($content, '<?php') !== false || strpos($content, '<?') !== false) {
        $erro = 'Polyglot bloqueado (código PHP detectado)';
        logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, $erro);
        error_log("[UPLOAD_ENGINE] ❌ " . $erro);
        return false;
    }

    // 5. PROCESSAMENTO DA IMAGEM
    $img = null;
    $remotePath = '';
    $tempFile = null;

    try {
        // 5.1 Carregar imagem
        error_log("[UPLOAD_ENGINE] 🔄 Carregando imagem para conversão...");
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
                error_log("[UPLOAD_ENGINE] 🎞️ Processando GIF animado: $remotePath");
                try {
                    $b2 = B2Client::getInstance();
                    error_log("[UPLOAD_ENGINE] 📤 Enviando GIF para B2...");
                    $b2->uploadFile($file_data['tmp_name'], $remotePath, 'image/gif', ['Cache-Control' => 'max-age=31536000']);
                    $b2->getDownloadUrl($remotePath);
                    logB2Event('INFO', $usuario_id, 'UPLOAD', $remotePath, 200, 'Upload GIF bem-sucedido');
                    error_log("[UPLOAD_ENGINE] ✅ GIF enviado com sucesso: $remotePath");
                    return $remotePath;
                } catch (Exception $e) {
                    logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath, 0, 'Falha no upload GIF: ' . $e->getMessage());
                    error_log("[UPLOAD_ENGINE] ❌ ERRO no upload GIF: " . $e->getMessage());
                    return false;
                }
            default:
                $erro = 'Formato não suportado: ' . $mime_type;
                logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, $erro);
                error_log("[UPLOAD_ENGINE] ❌ " . $erro);
                return false;
        }

        if ($img === null) {
            $erro = 'Falha ao criar imagem a partir do arquivo';
            logB2Event('ERROR', $usuario_id, 'UPLOAD', '', 0, $erro);
            error_log("[UPLOAD_ENGINE] ❌ " . $erro);
            return false;
        }

        // 5.2 Converte para WebP (qualidade 65% para performance)
        $remotePath = $prefixo . "_" . bin2hex(random_bytes(8)) . "_" . time() . ".webp";
        $tempFile = tempnam(sys_get_temp_dir(), 'b2_') . '.webp';
        error_log("[UPLOAD_ENGINE] 🔄 Convertendo para WebP com qualidade 65%: $remotePath");
        
        if (!imagewebp($img, $tempFile, 65)) {
            imagedestroy($img);
            if (file_exists($tempFile)) unlink($tempFile);
            $erro = 'Falha na conversão para WebP (extensão GD não instalada ou erro)';
            logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath, 0, $erro);
            error_log("[UPLOAD_ENGINE] ❌ " . $erro);
            return false;
        }
        imagedestroy($img);
        error_log("[UPLOAD_ENGINE] ✅ Conversão para WebP concluída. Tamanho do arquivo temporário: " . filesize($tempFile) . " bytes");

        // 6. UPLOAD PARA O B2 (com timeout configurado)
        try {
            $b2 = B2Client::getInstance();
            if (method_exists($b2, 'setTimeout')) {
                $b2->setTimeout(25);
                error_log("[UPLOAD_ENGINE] ⏱️ Timeout do B2Client ajustado para 25s");
            }
            error_log("[UPLOAD_ENGINE] 📤 Enviando para B2: $remotePath");
            $b2->uploadFile($tempFile, $remotePath, 'image/webp', ['Cache-Control' => 'max-age=31536000']);
            error_log("[UPLOAD_ENGINE] ✅ Upload para B2 bem-sucedido: $remotePath");
        } catch (Exception $e) {
            if ($tempFile && file_exists($tempFile)) unlink($tempFile);
            logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath, 0, 'Falha no upload para B2: ' . $e->getMessage());
            error_log("[UPLOAD_ENGINE] ❌ ERRO no upload B2: " . $e->getMessage());
            return false;
        }

        if ($tempFile && file_exists($tempFile)) {
            unlink($tempFile);
            error_log("[UPLOAD_ENGINE] 🗑️ Arquivo temporário removido: $tempFile");
        }

        // 7. VERIFICAÇÃO DE INTEGRIDADE
        try {
            error_log("[UPLOAD_ENGINE] 🔍 Verificando integridade do arquivo no B2...");
            $b2->getDownloadUrl($remotePath);
            logB2Event('INFO', $usuario_id, 'VERIFY', $remotePath, 200, 'Arquivo verificado com sucesso');
            error_log("[UPLOAD_ENGINE] ✅ Verificação concluída: $remotePath");
        } catch (Exception $e) {
            deleteFromB2($remotePath, $usuario_id);
            logB2Event('ERROR', $usuario_id, 'VERIFY', $remotePath, 0, 'Verificação falhou, arquivo removido: ' . $e->getMessage());
            error_log("[UPLOAD_ENGINE] ❌ Verificação falhou, removido: " . $e->getMessage());
            return false;
        }

        logB2Event('INFO', $usuario_id, 'UPLOAD', $remotePath, 200, 'Upload e verificação concluídos');
        error_log("[UPLOAD_ENGINE] ✅ Upload completo com sucesso: $remotePath");
        return $remotePath;
    } catch (Exception $e) {
        if ($tempFile && file_exists($tempFile)) unlink($tempFile);
        logB2Event('ERROR', $usuario_id, 'UPLOAD', $remotePath ?: '', 0, 'Exceção: ' . $e->getMessage());
        error_log("[UPLOAD_ENGINE] ❌ EXCEÇÃO: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
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
// 5. FUNÇÃO DE EXCLUSÃO DEFINITIVA
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
// 6. FUNÇÃO AUXILIAR PARA OBTENÇÃO DE URL DO B2 (FALLBACK)
// ============================================================

function obterUrlImagem($caminho, $b2 = null, $assinado = false, $duracao = 3600)
{
    if (empty($caminho) || !is_string($caminho)) {
        return null;
    }

    if (filter_var($caminho, FILTER_VALIDATE_URL)) {
        return $caminho;
    }

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

    return 'proxy.php?path=' . urlencode($caminhoLimpo);
}

// ============================================================
// 7. FUNÇÃO CENTRALIZADA DE FALLBACK DE IMAGENS
// ============================================================

/**
 * Obtém a URL de uma imagem com fallback seguro, capturando exceções do B2.
 * 
 * @param string|null $caminho    Caminho da imagem (ex: 'postagens/post_abc.webp')
 * @param string      $fallback   Caminho do fallback (ex: 'uploads/ui/default.webp')
 * @param object|null $b2         Instância do B2Client (opcional, cria nova se null)
 * @param bool        $assinado   Se deve gerar URL assinada (padrão: true)
 * 
 * @return string URL da imagem ou fallback em caso de erro
 */
function obterUrlComFallback($caminho, $fallback = 'uploads/ui/default.webp', $b2 = null, $assinado = true)
{
    // 1. Se o caminho for vazio, retorna fallback imediatamente
    if (empty($caminho) || !is_string($caminho)) {
        return $fallback;
    }

    // 2. Se já for uma URL completa (ex: GIF do GIPHY), retorna o próprio caminho
    if (filter_var($caminho, FILTER_VALIDATE_URL)) {
        return $caminho;
    }

    // 3. Tenta obter a URL via B2
    try {
        // Se $b2 não foi passado, instancia
        $b2Instance = ($b2 !== null) ? $b2 : B2Client::getInstance();
        
        // Tenta obter a URL
        $url = obterUrlImagem($caminho, $b2Instance, $assinado);
        
        // Se obteve uma URL válida, retorna; senão, fallback
        return ($url && is_string($url) && !empty($url)) ? $url : $fallback;
        
    } catch (Exception $e) {
        // Log do erro (sem quebrar a página)
        error_log("[OBTER_URL_FALLBACK] Erro ao obter URL para '$caminho': " . $e->getMessage());
        return $fallback;
    }
}