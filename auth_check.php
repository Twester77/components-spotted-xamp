<?php
// auth_check.php - Middleware de autenticação
// Inclui a conexão e verifica se o usuário está logado.
// Se não estiver, redireciona para a página inicial.

include_once __DIR__ . '/fenda_debug.php';
fenda_log('🔵 INÍCIO auth_check.php');

include_once __DIR__ . '/conexao.php';

// ============================================================
// 🔥 GARANTE QUE O CSRF TOKEN SEMPRE EXISTA NA SESSÃO
// ============================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// Verifica se o usuário está logado
// ============================================================
// 🔥 ATUALIZAÇÃO IARA – 2026-09-24 (auditoria v5.0)
//    Substitui o redirect cego por fenda_resposta_sessao_expirada(),
//    que diferencia AJAX (401 + JSON) de navegação (redirect).
//    Sem isso, chamadas AJAX (motor-avaliacoes, contar_alertas,
//    etc) recebiam HTML em resposta e quebravam com SyntaxError.
if (!isset($_SESSION['usuario_id'])) {
    fenda_log('🔴 Sessão ausente. Resposta diferenciada (AJAX vs navegação).');
    fenda_resposta_sessao_expirada($cookieDomain ?? null, $is_real_production ?? false);
}

fenda_log('🟢 FIM auth_check.php (usuário autorizado: ' . $_SESSION['usuario_id'] . ')');