<?php
/**
 * motor-sessoes.php – Endpoint para listar sessões ativas do usuário
 * 
 * Chamado via AJAX pela aba "Sessões" da Central.
 * Retorna HTML com a lista de sessões ativas.
 * 
 * 🔒 Segurança:
 * - Apenas usuário logado
 * - IP mascarado para privacidade
 * - User-Agent resumido
 * - Inclui atributo data-is-atual para o front-end
 * 
 * 🐚 BRISA – 2026-09-01 (v4 – com data-is-atual e logs estruturados)
 *    - Adicionado atributo data-is-atual nos botões (para o JS)
 *    - Logs para rastrear a obtenção do token e total de sessões
 *    - Código refatorado para consistência
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO motor-sessoes.php');

$usuario_id = $_SESSION['usuario_id'];

// ============================================================
// 1. OBTÉM O TOKEN DA SESSÃO ATUAL (para marcar a sessão atual)
// ============================================================
$token_atual = null;
if (!empty($_COOKIE['fenda_state_token'])) {
    $decrypted = fenda_decrypt_state($_COOKIE['fenda_state_token']);
    if ($decrypted) {
        $payload = json_decode($decrypted, true);
        if (isset($payload['token_sessao'])) {
            $token_atual = $payload['token_sessao'];
            fenda_log('🔵 [motor-sessoes] Token atual obtido: ' . substr($token_atual, 0, 16) . '...');
        } else {
            fenda_log('⚠️ [motor-sessoes] Payload decriptado não contém token_sessao');
        }
    } else {
        fenda_log('⚠️ [motor-sessoes] Falha na decriptação do cookie');
    }
} else {
    fenda_log('🔵 [motor-sessoes] Nenhum cookie fenda_state_token encontrado');
}

// ============================================================
// 2. BUSCA AS SESSÕES ATIVAS
// ============================================================
$sql = "SELECT id, token, data_criacao, ultima_atividade, user_agent, ip
        FROM sessoes_ativas
        WHERE usuario_id = ? AND ativo = 1
        ORDER BY ultima_atividade DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$total_sessoes = $result->num_rows;
fenda_log("🔵 [motor-sessoes] Total de sessões ativas: $total_sessoes");

// ============================================================
// 3. FUNÇÕES AUXILIARES (mantidas)
// ============================================================

function simplificarUserAgent($ua) {
    if (empty($ua)) return 'Desconhecido';
    $browser = 'Desconhecido';
    if (strpos($ua, 'Chrome') !== false && strpos($ua, 'Edg') === false) $browser = 'Chrome';
    elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false) $browser = 'Safari';
    elseif (strpos($ua, 'Edg') !== false) $browser = 'Edge';
    elseif (strpos($ua, 'OPR') !== false || strpos($ua, 'Opera') !== false) $browser = 'Opera';
    
    $versao = '';
    if (preg_match('/(Chrome|Firefox|Safari|Edg|OPR)\/(\d+)/', $ua, $matches)) {
        $versao = $matches[2] ?? '';
    }
    
    $so = 'Desconhecido';
    if (strpos($ua, 'Windows NT 10.0') !== false) $so = 'Windows 10/11';
    elseif (strpos($ua, 'Windows NT 6.1') !== false) $so = 'Windows 7';
    elseif (strpos($ua, 'Mac OS X') !== false) $so = 'macOS';
    elseif (strpos($ua, 'Linux') !== false && strpos($ua, 'Android') === false) $so = 'Linux';
    elseif (strpos($ua, 'Android') !== false) $so = 'Android';
    elseif (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) $so = 'iOS';
    
    $tipo = 'Desktop';
    if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false || strpos($ua, 'iPhone') !== false) {
        $tipo = 'Mobile';
    }
    
    return trim($browser . ' ' . $versao . ' / ' . $so . ' (' . $tipo . ')');
}

function mascararIP($ip) {
    if (empty($ip)) return 'IP não disponível';
    $partes = explode('.', $ip);
    if (count($partes) === 4) {
        $partes[3] = '***';
        return implode('.', $partes);
    }
    return substr($ip, 0, 8) . ':***';
}

// Usamos a função global exibirDataHoraBrasil() (definida em conexao.php)
// para formatar as datas de forma consistente

// ============================================================
// 4. RENDERIZA O HTML (com data-is-atual e classes)
// ============================================================
?>
<div class="sessoes-container">
    <div class="sessoes-header">
        <h3 class="sessoes-titulo">
            <i class="fas fa-laptop"></i> Sessões Ativas
            <span class="sessoes-contador">(<?php echo $total_sessoes; ?> dispositivo<?php echo $total_sessoes != 1 ? 's' : ''; ?>)</span>
        </h3>
        <?php if ($total_sessoes > 1): ?>
            <button class="btn-encerrar-todas" data-acao="encerrar-todas">
                <i class="fas fa-sign-out-alt"></i> Encerrar todas
            </button>
        <?php endif; ?>
    </div>

    <?php if ($total_sessoes === 0): ?>
        <div class="sem-sessoes">
            <i class="fas fa-check-circle"></i>
            <p>Nenhuma sessão ativa encontrada. Você só tem a sessão atual.</p>
        </div>
    <?php else: ?>
        <div class="sessoes-lista">
            <?php while ($sessao = $result->fetch_assoc()): 
                $is_atual = ($token_atual && $sessao['token'] === $token_atual);
                $dispositivo = simplificarUserAgent($sessao['user_agent']);
                $ip_mascarado = mascararIP($sessao['ip']);
                $data_criacao = exibirDataHoraBrasil($sessao['data_criacao'], 'd/m/Y H:i');
                $ultima_atividade = exibirDataHoraBrasil($sessao['ultima_atividade'], 'd/m/Y H:i');
                $classe_atual = $is_atual ? 'sessao-atual' : '';
                $sessao_id = (int)$sessao['id'];
            ?>
                <div class="sessao-item <?php echo $classe_atual; ?>" data-id="<?php echo $sessao_id; ?>">
                    <div class="sessao-info">
                        <div class="sessao-dispositivo">
                            <i class="fas fa-<?php echo strpos($dispositivo, 'Mobile') !== false ? 'mobile-alt' : 'desktop'; ?>"></i>
                            <?php echo htmlspecialchars($dispositivo); ?>
                        </div>
                        <div class="sessao-detalhes">
                            <span><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($ip_mascarado); ?></span>
                            <span><i class="fas fa-calendar-plus"></i> Criada <?php echo $data_criacao; ?></span>
                            <span><i class="fas fa-clock"></i> Última atividade <?php echo $ultima_atividade; ?></span>
                        </div>
                    </div>
                    <div class="sessao-actions">
                        <?php if (!$is_atual): ?>
                            <button class="btn-encerrar-sessao" data-id="<?php echo $sessao_id; ?>" data-is-atual="0" data-acao="encerrar-unica">
                                <i class="fas fa-times"></i> Encerrar
                            </button>
                        <?php else: ?>
                            <span class="sessao-badge-atual" data-is-atual="1">
                                <i class="fas fa-check-circle"></i> Atual
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
fenda_log('🟢 FIM motor-sessoes.php');
exit;
?>