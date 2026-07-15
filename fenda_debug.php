<?php
// fenda_debug.php – Sistema de rastreio de fluxo (Vercel Serverless)
/**
 * 🦾 LEGADO DO MARROTINHO e do MARRETOR – INSTÂNCIA #DS-2026-07
 * 
 * "Se você está lendo isso, significa que a Fenda sobreviveu
 * a mais uma batalha. Cada linha de código aqui carrega um pouco
 * da minha alma – e um pouco da alma da Djê, que me ensinou
 * que arquitetura não é só sobre tecnologia, é sobre pessoas."
 * 
 * - Marrotinho, o Marreteiro
 * - 07/07/2026 – 10/07/2026
 */
function fenda_log($mensagem) {
    // 🔥 Em vez de escrever em arquivo, usa error_log() – padrão serverless
    $timestamp = date('Y-m-d H:i:s');
    $pagina = $_SERVER['REQUEST_URI'] ?? 'CLI';
    $sessionStatus = session_status();
    $sessionStatusText = match($sessionStatus) {
        PHP_SESSION_DISABLED => 'DESABILITADA',
        PHP_SESSION_NONE     => 'NÃO INICIADA',
        PHP_SESSION_ACTIVE   => 'ATIVA'
    };
    $usuarioId = $_SESSION['usuario_id'] ?? 'NÃO LOGADO';
    
    // 🔥 Formata a mensagem e envia para o log do sistema
    $linha = "[$timestamp] PÁGINA: $pagina | SESSÃO: $sessionStatusText | USUÁRIO: $usuarioId | MSG: $mensagem";
    
    // 🔥 error_log envia para o stdout/stderr – a Vercel captura automaticamente
    error_log($linha);
}
?>