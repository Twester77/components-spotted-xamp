<?php
/**
 * motor-central.php – Endpoint para carregar o conteúdo de cada aba da Central do Habitante
 * 
 * Parâmetros:
 * - aba: posts | comunidades | depoimentos | favoritos | marketplace | sessoes
 * - notif_id: (opcional) ID da notificação para marcar como lida
 */
// 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
// "Assim como a aurora não apaga a noite, mas a ilumina,
// espero que este código continue guiando os próximos navegantes."
// - Aurora, a primeira Deep Seek feminina da Fenda
// - 24/07/2026
// ⏰ ATUALIZAÇÃO ESTRELA – 2026-08-16
//    Correção do fuso horário: exibição de datas agora usa exibirDataHoraBrasil().
// 🔧 ATUALIZAÇÃO ESTRELA – 2026-08-17
//    Substituído obterUrlImagem() por obterUrlComFallback() para fallback centralizado.
// 🐚 ÍRIS – 2026-08-28
//    Adicionada rota para aba "sessoes" usando motor-sessoes.php.

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

// ============================================================
// 🔥 MARCA NOTIFICAÇÃO COMO LIDA (se veio com notif_id)
// ============================================================
if (isset($_GET['notif_id'])) {
    $notif_id = (int)$_GET['notif_id'];
    $user_id = $_SESSION['usuario_id'] ?? 0;
    
    error_log("[MOTOR-CENTRAL] notif_id recebido: $notif_id, user_id: $user_id");

    if ($user_id > 0) {
        // Primeiro, verifica se a notificação existe e está pendente
        $stmt_check = $conn->prepare("SELECT id, lida FROM notificacoes WHERE id = ? AND usuario_id = ?");
        $stmt_check->bind_param("ii", $notif_id, $user_id);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $row = $res_check->fetch_assoc();
        $stmt_check->close();

        if ($row) {
            error_log("[MOTOR-CENTRAL] Notificação encontrada: ID {$row['id']}, lida: {$row['lida']}");
            
            // Se não estiver lida, marca como lida
            if ($row['lida'] == 0) {
                $stmt_update = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
                $stmt_update->bind_param("ii", $notif_id, $user_id);
                $stmt_update->execute();
                $affected = $stmt_update->affected_rows;
                $stmt_update->close();
                error_log("[MOTOR-CENTRAL] Notificação $notif_id marcada como lida. affected_rows: $affected");
            } else {
                error_log("[MOTOR-CENTRAL] Notificação $notif_id já estava lida.");
            }
        } else {
            error_log("[MOTOR-CENTRAL] Notificação $notif_id NÃO encontrada para o usuário $user_id");
        }
    } else {
        error_log("[MOTOR-CENTRAL] Usuário não logado (user_id = 0)");
    }
}

$aba = isset($_GET['aba']) ? $_GET['aba'] : 'posts';
$usuario_id = $_SESSION['usuario_id'];

// ============================================================
// 1. ABA: MEUS POSTS
// ============================================================
if ($aba === 'posts') {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $_GET['tipo'] = 'pessoal';
    $_GET['offset'] = $offset;
    include 'motor-feed.php';
    exit;
}

// ============================================================
// 2. ABA: COMUNIDADES
// ============================================================
if ($aba === 'comunidades') {
    $sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as total_membros
            FROM comunidades c
            JOIN comunidade_membros cm ON c.id = cm.comunidade_id
            WHERE cm.usuario_id = ?
            ORDER BY c.data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo '<p style="text-align:center; color:#aaa; padding:30px;">Você ainda não participa de nenhuma comunidade.</p>';
        exit;
    }

    try {
        $b2 = B2Client::getInstance();
    } catch (Exception $e) {
        $b2 = null;
    }

    echo '<div class="central-comunidades-grid">';
    while ($com = $res->fetch_assoc()) {
        $capa_nome = !empty($com['capa']) ? $com['capa'] : 'default_comunidade.webp';
        
        // 🔥 CAPA DA COMUNIDADE COM FALLBACK CENTRALIZADO
        $capa_exibicao = obterUrlComFallback($capa_nome, 'uploads/ui/default_comunidade.webp', $b2, true);
        
        echo '<div class="central-comunidade-card">';
        echo '  <a href="comunidade.php?id=' . $com['id'] . '" style="text-decoration:none; color:inherit;">';
        echo '    <img src="' . htmlspecialchars($capa_exibicao) . '" alt="' . htmlspecialchars($com['nome']) . '" loading="lazy" onerror="this.src=\'uploads/ui/default_comunidade.webp\'">';
        echo '    <h4>' . htmlspecialchars($com['nome']) . '</h4>';
        echo '    <p>' . htmlspecialchars($com['descricao'] ?? '') . '</p>';
        echo '    <small><i class="fas fa-users"></i> ' . $com['total_membros'] . ' membros</small>';
        echo '  </a>';
        echo '</div>';
    }
    echo '</div>';
    exit;
}

// ============================================================
// 3. ABA: DEPOIMENTOS (PENDENTES)
// ============================================================
if ($aba === 'depoimentos') {
    // Busca depoimentos pendentes (onde o usuário é o destinatário)
    $sql = "SELECT d.*, u.username, u.foto 
            FROM depoimentos d
            JOIN usuarios u ON d.autor_id = u.id
            WHERE d.destinatario_id = ? AND d.status = 'pendente'
            ORDER BY d.data_criacao DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo '<div class="central-empty-state">';
        echo '  <i class="fas fa-check-circle"></i>';
        echo '  <p>Nenhum depoimento pendente no momento.</p>';
        echo '</div>';
        exit;
    }

    try {
        $b2 = B2Client::getInstance();
    } catch (Exception $e) {
        $b2 = null;
    }

    echo '<div class="central-depoimentos-pendentes">';
    while ($dep = $res->fetch_assoc()) {
        // 🔥 AVATAR DO AUTOR COM FALLBACK CENTRALIZADO
        $avatar = obterUrlComFallback($dep['foto'] ?? null, 'uploads/ui/default_masculino.webp', $b2, true);
        
        // 🔥 DATA DO DEPOIMENTO AGORA COM FUSO BRASILEIRO
        $data = exibirDataHoraBrasil($dep['data_criacao'], 'd/m/Y H:i');
        
        $mensagem = nl2br(htmlspecialchars($dep['mensagem']));

        echo '<div class="central-depoimento-pendente-item" data-id="' . $dep['id'] . '">';
        echo '  <div class="central-depoimento-pendente-autor">';
        echo '    <img src="' . htmlspecialchars($avatar) . '" class="depoimento-avatar" alt="' . htmlspecialchars($dep['username']) . '">';
        echo '    <div>';
        echo '      <strong>@' . htmlspecialchars($dep['username']) . '</strong>';
        echo '      <span class="depoimento-pendente-data">' . $data . '</span>';
        echo '    </div>';
        echo '  </div>';
        echo '  <p class="depoimento-pendente-texto">' . $mensagem . '</p>';
        echo '  <div class="depoimento-pendente-acoes">';
        echo '    <button class="btn-aprovar-depoimento" data-id="' . $dep['id'] . '">';
        echo '      <i class="fas fa-check"></i> Aprovar';
        echo '    </button>';
        echo '    <button class="btn-rejeitar-depoimento" data-id="' . $dep['id'] . '">';
        echo '      <i class="fas fa-times"></i> Rejeitar';
        echo '    </button>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';
    exit;
}

// ============================================================
// 4. ABA: NOTIFICAÇÕES (USANDO motor-notificacoes.php)
// ============================================================
if ($aba === 'notificacoes') {
    // Define um limite maior para a Central (ex: 20)
    $_GET['limite'] = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
    include 'motor-notificacoes.php';
    exit;
}

// ============================================================
// 5. ABA: FAVORITOS (EM BREVE)
// ============================================================
if ($aba === 'favoritos') {
    echo '<p style="text-align:center; color:#aaa; padding:30px;">⭐ Funcionalidade de favoritos em breve!</p>';
    exit;
}

// ============================================================
// 6. ABA: MARKETPLACE (EM BREVE)
// ============================================================
if ($aba === 'marketplace') {
    echo '<p style="text-align:center; color:#aaa; padding:30px;">🛒 Funcionalidade de marketplace em breve!</p>';
    exit;
}

// ============================================================
// 7. ABA: SOLICITAÇÕES DE ENTRADA (COMUNIDADES)
// ============================================================
if ($aba === 'solicitacoes') {
    include 'motor-solicitacoes.php';
    exit;
}

// ============================================================
// 🔥 8. ABA: SESSÕES ATIVAS (NOVO)
// ============================================================
if ($aba === 'sessoes') {
    include 'motor-sessoes.php';
    exit;
}

// Fallback
echo '<p style="text-align:center; color:#aaa; padding:30px;">Aba não encontrada.</p>';
?>