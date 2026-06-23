<?php
/**
 * Sistema de rastreio de fluxo – "Debug Fenda"
 * Registra timestamp, página, status da sessão, usuário e mensagem
 * em um arquivo debug_fenda.log na raiz.
 */
function fenda_log($mensagem) {
    // Arquivo de log na raiz do projeto
    $logFile = __DIR__ . '/debug_fenda.log';
    
    // Timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    // Página atual
    $pagina = $_SERVER['REQUEST_URI'] ?? 'CLI';
    
    // Status da sessão
    $sessionStatus = session_status();
    $sessionStatusText = match($sessionStatus) {
        PHP_SESSION_DISABLED => 'DESABILITADA',
        PHP_SESSION_NONE     => 'NÃO INICIADA',
        PHP_SESSION_ACTIVE   => 'ATIVA'
    };
    
    // ID do usuário (se logado)
    $usuarioId = $_SESSION['usuario_id'] ?? 'NÃO LOGADO';
    
    // Monta a linha de log
    $linha = "[$timestamp] PÁGINA: $pagina | SESSÃO: $sessionStatusText | USUÁRIO: $usuarioId | MSG: $mensagem" . PHP_EOL;
    
    // Escreve no arquivo (append)
    file_put_contents($logFile, $linha, FILE_APPEND | LOCK_EX);
}
?>