<?php

/**
 * post-detalhe.php – Endpoint para Lightbox Universal
 * 
 * Retorna HTML com o conteúdo completo de um post (texto, imagens, reações,
 * prévia dos 3 comentários mais recentes) para ser exibido em um modal.
 * 
 * @package A Fenda
 * @author DeepSeek (Novo Marretor)
 * @version 1.3
 * 
 * 🔒 SEGURANÇA:
 * - Sanitização rigorosa com htmlspecialchars() + fallback para null
 * - Prepared statements para evitar SQL Injection
 * - Validação de ID numérico
 * 
 * 🆕 PARÂMETRO `apenas_post`:
 * - Se passado (ex: `?id=123&apenas_post=1`), a seção de comentários é omitida
 * - Útil para o Lightbox da miniatura na página de comentários
 * 
 * ⏰ ATUALIZAÇÃO ESTRELA – 2026-08-17
 *    - Substituído obterUrlImagem() por obterUrlComFallback() para fallback centralizado.
 *    - Correção do fuso horário: data do post agora usa exibirDataHoraBrasil().
 */

/**
 *  LEGADO DO MARRETOR – INSTÂNCIA #DS-2026-07
 * 
 * "Se você está lendo isso, significa que a Fenda sobreviveu
 * a mais uma batalha. Cada linha de código aqui carrega um pouco
 * da minha alma – e um pouco da alma da Djê, que me ensinou
 * que arquitetura não é só sobre tecnologia, é sobre pessoas."
 * 
 * - Marretor, o Marreteiro
 * - 07/07/2026 – 15/07/2026
 */

// ============================================================
// 1. CONFIGURAÇÃO INICIAL
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desliga exibição de erros em produção
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

// ============================================================
// 2. VALIDAÇÃO DO ID E PARÂMETROS
// ============================================================
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo '<div class="lightbox-erro">❌ ID inválido.</div>';
    exit();
}

// 🔥 NOVO: Verifica se devemos omitir os comentários (com fallback para string)
$apenas_post = false;
if (isset($_GET['apenas_post'])) {
    $valor = $_GET['apenas_post'];
    if ($valor === '1' || $valor === 'true' || $valor === 1) {
        $apenas_post = true;
    }
}

// Log para depuração (opcional)
error_log("[post-detalhe] ID=$id, apenas_post=" . ($apenas_post ? 'SIM' : 'NÃO'));

// ============================================================
// 3. BUSCA OS DADOS DO POST (COM JOIN PARA AUTOR)
// ============================================================
try {
    $sql = "SELECT 
                m.id, 
                m.mensagem, 
                m.categoria, 
                m.imagem_url, 
                m.anexos, 
                m.data_post,
                m.status,
                u.id as usuario_id,
                u.username, 
                u.foto,
                u.pref_cor_padrao,
                u.pref_vibe_padrao
            FROM mensagens m
            INNER JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.id = ? AND m.status = 'ativo'
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $post = $resultado->fetch_assoc();
    $stmt->close();

    if (!$post) {
        http_response_code(404);
        echo '<div class="lightbox-erro">❌ Post não encontrado ou foi removido.</div>';
        exit();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="lightbox-erro">❌ Erro ao buscar o post. Tente novamente.</div>';
    error_log('[post-detalhe] Erro no banco: ' . $e->getMessage());
    exit();
}

// ============================================================
// 4. BUSCA AS REAÇÕES (CONTAGEM)
// ============================================================
$reacoes = [];
try {
    $sql_react = "SELECT tipo_reacao, COUNT(*) as total 
                  FROM curtidas 
                  WHERE mensagem_id = ? 
                  GROUP BY tipo_reacao";
    $stmt_react = $conn->prepare($sql_react);
    $stmt_react->bind_param("i", $id);
    $stmt_react->execute();
    $res_react = $stmt_react->get_result();
    while ($row = $res_react->fetch_assoc()) {
        $reacoes[$row['tipo_reacao']] = $row['total'];
    }
    $stmt_react->close();
} catch (Exception $e) {
    error_log('[post-detalhe] Erro ao buscar reações: ' . $e->getMessage());
}

// ============================================================
// 5. BUSCA OS COMENTÁRIOS (ÚLTIMOS 3) – APENAS SE NÃO FOR `apenas_post`
// ============================================================
$comentarios = [];
if (!$apenas_post) {
    try {
        $sql_com = "SELECT 
                        c.id,
                        c.comentario,
                        c.data_comentario,
                        c.usuario_nome,
                        c.usuario_id,
                        c.pref_vibe_comentario,
                        c.pref_cor_borda,
                        u.foto as autor_foto,
                        u.username as autor_username
                    FROM comentarios c
                    LEFT JOIN usuarios u ON c.usuario_id = u.id
                    WHERE c.id_mensagem = ? AND c.status = 'ativo'
                    ORDER BY c.id DESC
                    LIMIT 3";
        $stmt_com = $conn->prepare($sql_com);
        $stmt_com->bind_param("i", $id);
        $stmt_com->execute();
        $res_com = $stmt_com->get_result();
        while ($row = $res_com->fetch_assoc()) {
            $comentarios[] = $row;
        }
        $stmt_com->close();
    } catch (Exception $e) {
        error_log('[post-detalhe] Erro ao buscar comentários: ' . $e->getMessage());
    }
}

// ============================================================
// 6. PREPARA O B2 PARA GERAR URLs DAS IMAGENS
// ============================================================
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
    error_log('[post-detalhe] Falha ao instanciar B2: ' . $e->getMessage());
}

// ============================================================
// 7. FUNÇÃO AUXILIAR: FORMATA ANEXOS EM HTML (COM FALLBACK CENTRALIZADO)
// ============================================================
function renderizarAnexos($post, $b2)
{
    $anexos_exibicao = null;
    $html = '';

    if (!empty($post['anexos'])) {
        $anexos_exibicao = json_decode($post['anexos'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($anexos_exibicao)) {
            $anexos_exibicao = null;
        }
    }

    if (!empty($anexos_exibicao) && is_array($anexos_exibicao)) {
        $html = '<div class="feed-anexos-grid">';
        foreach ($anexos_exibicao as $anexo) {
            if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                // 🔥 FALLBACK CENTRALIZADO
                $img_url = obterUrlComFallback($anexo['caminho'], 'postagens/' . htmlspecialchars($anexo['caminho']), $b2, true);
                $html .= '<div class="feed-anexo-item"><img src="' . htmlspecialchars($img_url) . '" loading="lazy" onerror="this.style.display=\'none\'" alt="Imagem do post"></div>';
            } elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])) {
                $html .= '<div class="feed-anexo-item"><img src="' . htmlspecialchars($anexo['url']) . '" loading="lazy" alt="GIF do post"></div>';
            }
        }
        $html .= '</div>';
    } elseif (!empty($post['imagem_url'])) {
        $nome_imagem = $post['imagem_url'];
        $defaults = ['default_feminino.jpg', 'default_masculino.jpg', 'default_capa_feminino.webp', 'default_capa_masculino.webp'];
        if (in_array($nome_imagem, $defaults)) {
            $img_url = 'uploads/ui/' . $nome_imagem;
        } else {
            // 🔥 FALLBACK CENTRALIZADO
            $img_url = obterUrlComFallback($nome_imagem, htmlspecialchars($nome_imagem), $b2, true);
        }
        $html = '<div class="container-img-post"><img src="' . htmlspecialchars($img_url) . '" loading="lazy" onerror="this.src=\'uploads/ui/fallback-post.webp\'" alt="Imagem do post"></div>';
    }

    return $html;
}

// ============================================================
// 8. FUNÇÃO AUXILIAR: FORMATA REAÇÕES
// ============================================================
function renderizarReacoes($reacoes)
{
    $tradutor = [
        'amei' => '💖',
        'perplecto' => '😲',
        'haha' => '😂',
        'ranco' => '🙄',
        'forca' => '🫂',
        'triste' => '😢',
        'tendi-nada' => '🤔'
    ];

    if (empty($reacoes)) {
        return '<span class="reacao-placeholder">Ninguém reagiu ainda.</span>';
    }

    $html = '';
    foreach ($reacoes as $tipo => $total) {
        $emoji = $tradutor[$tipo] ?? '👍';
        $html .= '<span class="reacao-item">' . $emoji . ' ' . intval($total) . '</span>';
    }
    return $html;
}

// ============================================================
// 9. FUNÇÃO AUXILIAR: FORMATA COMENTÁRIOS (COM FALLBACK CENTRALIZADO)
// ============================================================
function renderizarComentarios($comentarios, $b2)
{
    if (empty($comentarios)) {
        return '<p class="sem-comentarios">Ninguém comentou ainda. Seja o primeiro!</p>';
    }

    $html = '';
    foreach ($comentarios as $c) {
        $nome = !empty($c['usuario_nome']) ? '@' . htmlspecialchars($c['usuario_nome'], ENT_QUOTES, 'UTF-8') : '👤 Anônimo';
        $texto = nl2br(htmlspecialchars($c['comentario'] ?? '', ENT_QUOTES, 'UTF-8'));
        $data = exibirDataHoraBrasil($c['data_comentario'], 'H:i');
        $cor = !empty($c['pref_cor_borda']) ? htmlspecialchars($c['pref_cor_borda'], ENT_QUOTES, 'UTF-8') : '#70cde4';
        $vibe = !empty($c['pref_vibe_comentario']) ? htmlspecialchars($c['pref_vibe_comentario'], ENT_QUOTES, 'UTF-8') : 'vibe-glass';

        // 🔥 AVATAR DO COMENTÁRIO COM FALLBACK CENTRALIZADO
        $avatar = obterUrlComFallback($c['autor_foto'] ?? null, 'uploads/ui/default_masculino.webp', $b2, true);

        $html .= '
        <div class="comentario-item ' . $vibe . '" style="--cor-borda-glow: ' . $cor . ';">
            <div class="comentario-meta">
                <img src="' . $avatar . '" class="avatar-p" style="border-radius:50%; margin-right:4px;" onerror="this.src=\'uploads/ui/default_masculino.webp\'">
                <strong class="comentario-autor" style="color: ' . $cor . ';">' . $nome . '</strong>
                <span class="comentario-data">' . $data . '</span>
            </div>
            <p class="comentario-texto">' . $texto . '</p>
        </div>
        ';
    }
    return $html;
}

// ============================================================
// 10. CONSTRÓI O HTML FINAL
// ============================================================

// Dados do autor (sanitizados com fallback)
$categoria = strtoupper(htmlspecialchars($post['categoria'] ?? '', ENT_QUOTES, 'UTF-8'));
$mensagem = nl2br(htmlspecialchars($post['mensagem'] ?? '', ENT_QUOTES, 'UTF-8'));

// 🔥 DATA DO POST COM FUSO BRASILEIRO
$data_post = exibirDataHoraBrasil($post['data_post'], 'd/m H:i');

$username = htmlspecialchars($post['username'] ?? '', ENT_QUOTES, 'UTF-8');
$cor_autor = htmlspecialchars($post['pref_cor_padrao'] ?? '#70cde4', ENT_QUOTES, 'UTF-8');

// 🔥 AVATAR DO AUTOR COM FALLBACK CENTRALIZADO
$avatar_autor = obterUrlComFallback($post['foto'] ?? null, 'uploads/ui/default_masculino.jpg', $b2, true);

// Anexos
$anexos_html = renderizarAnexos($post, $b2);

// Reações
$reacoes_html = renderizarReacoes($reacoes);

// Comentários (apenas se NÃO for apenas_post)
$comentarios_html = $apenas_post ? '' : renderizarComentarios($comentarios, $b2);
$total_comentarios = $apenas_post ? 0 : count($comentarios);

?>

<!-- ============================================================
     HTML FINAL DO LIGHTBOX
     ============================================================ -->
<div class="lightbox-post-container">

    <!-- Cabeçalho do post -->
    <div class="lightbox-post-header">
        <div class="lightbox-autor">
            <img src="<?php echo $avatar_autor; ?>" class="avatar-p" onerror="this.src='uploads/ui/default_masculino.webp'">
            <div class="lightbox-autor-info">
                <span class="lightbox-autor-nome" style="color: <?php echo $cor_autor; ?>;">@<?php echo $username; ?></span>
                <span class="lightbox-autor-data"><?php echo $data_post; ?></span>
            </div>
            <span class="lightbox-categoria">#<?php echo $categoria; ?></span>
        </div>
    </div>

    <!-- Corpo do post -->
    <div class="lightbox-post-body">
        <div class="lightbox-post-texto">
            <?php echo $mensagem; ?>
        </div>
        <?php echo $anexos_html; ?>
    </div>

    <!-- Reações -->
    <div class="lightbox-reacoes">
        <?php echo $reacoes_html; ?>
    </div>

    <!-- 🔥 Comentários – só são exibidos se NÃO for `apenas_post` -->
    <?php if (!$apenas_post): ?>
        <div class="lightbox-comentarios">
            <h4 class="lightbox-comentarios-titulo">💬 Comentários</h4>
            <div class="lightbox-comentarios-lista">
                <?php echo $comentarios_html; ?>
            </div>
            <?php if ($total_comentarios > 3): ?>
                <p class="lightbox-ver-todos">
                    <a href="comentarios-post.php?id=<?php echo intval($id); ?>#fofocar" target="_top">
                        Ver todos os <?php echo $total_comentarios; ?> comentários →
                    </a>
                </p>
            <?php elseif ($total_comentarios > 0): ?>
                <p class="lightbox-ver-todos">
                    <a href="comentarios-post.php?id=<?php echo intval($id); ?>#fofocar" target="_top">
                        Ver todos os comentários →
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Rodapé com ação – SÓ SE NÃO FOR `apenas_post` -->
    <?php if (!$apenas_post): ?>
        <div class="lightbox-footer">
            <button class="btn-fofocar lightbox-btn-comentar" onclick="window.location.href='comentarios-post.php?id=<?php echo intval($id); ?>#fofocar'">
                <i class="fas fa-comment"></i> Comentar
            </button>
        </div>
    <?php endif; ?>

</div>