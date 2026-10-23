<?php
/**
 * comentarios-post.php – Página de comentários de um post (Limpo)
 */

include_once 'conexao.php';
include_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';
require_once __DIR__ . '/includes/cascata-helper.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

fenda_log('🟢 INÍCIO comentarios-post.php');

if (!function_exists('sanitizarVibe')) {
    function sanitizarVibe($vibe) {
        $vibes_validas = ['vibe-glass', 'vibe-neon', 'vibe-dark', 'vibe-light', 'vibe-ads'];
        return in_array($vibe, $vibes_validas, true) ? $vibe : 'vibe-glass';
    }
}
if (!function_exists('sanitizarCorBorda')) {
    function sanitizarCorBorda($cor) {
        if (is_string($cor) && preg_match('/^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/', $cor)) {
            return $cor;
        }
        return '#70cde4';
    }
}

if (isset($_GET['notif_id'], $_GET['sig']) && is_string($_GET['sig'])) {
    $notif_id = (int)$_GET['notif_id'];
    $sig_recebida = $_GET['sig'];
    $user_id = (int)($_SESSION['usuario_id'] ?? 0);

    if ($notif_id > 0 && $user_id > 0) {
        $sig_esperada = hash_hmac('sha256', $notif_id . '|' . $user_id, FENDA_HMAC_KEY);
        if (hash_equals($sig_esperada, $sig_recebida)) {
            $stmt_notif = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
            $stmt_notif->bind_param("ii", $notif_id, $user_id);
            $stmt_notif->execute();
            $stmt_notif->close();
            fenda_log("🟢 Notificação $notif_id marcada como lida para usuário $user_id (via comentarios-post)");
        }
    }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    if (isset($_GET['notif_id'])) {
        $_SESSION['toast_mensagem'] = 'Notificação marcada como lida.';
        $_SESSION['toast_tipo'] = 'sucesso';
        header("Location: feed.php");
    } else {
        header("Location: feed.php");
    }
    exit();
}

$is_perdidos = false;
if ($id > 0) {
    $stmt_check = $conn->prepare("SELECT categoria FROM mensagens WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $check_post = $stmt_check->get_result()->fetch_assoc();
    if ($check_post && $check_post['categoria'] === 'perdidos') {
        $is_perdidos = true;
    }
    $stmt_check->close();
}

if (!isset($_SESSION['usuario_id']) && !$is_perdidos) {
    if (isset($_GET['notif_id'])) {
        $_SESSION['toast_mensagem'] = 'Notificação marcada como lida.';
        $_SESSION['toast_tipo'] = 'sucesso';
        header("Location: feed.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$stmt = $conn->prepare("SELECT m.*, u.username, u.foto FROM mensagens m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    if (isset($_GET['notif_id'])) {
        $_SESSION['toast_mensagem'] = 'Postagem não encontrada ou excluída.';
        $_SESSION['toast_tipo'] = 'info';
        header("Location: feed.php");
    } else {
        die("<main> <style> body { font-size:2.1rem; color: white; text-align: center; padding-top: 50px; } </style> <p>Ops... Spotted não encontrado!</p> </main>");
    }
    exit();
}

if (!empty($post['comunidade_id'])) {
    $comunidade_id = (int)$post['comunidade_id'];
    $stmt_comunidade = $conn->prepare("SELECT tipo FROM comunidades WHERE id = ? LIMIT 1");
    $stmt_comunidade->bind_param("i", $comunidade_id);
    $stmt_comunidade->execute();
    $comunidade = $stmt_comunidade->get_result()->fetch_assoc();
    $stmt_comunidade->close();

    if (($comunidade['tipo'] ?? null) === 'privada') {
        $usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
        $stmt_membro = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND status = 'ativo' LIMIT 1");
        $stmt_membro->bind_param("ii", $comunidade_id, $usuario_id);
        $stmt_membro->execute();
        $eh_membro = $stmt_membro->get_result()->fetch_row() !== null;
        $stmt_membro->close();

        if (!$eh_membro) {
            header("Location: feed.php");
            exit();
        }
    }
}

$post_esta_ativo = ($post['status'] === 'ativo');

$vibe_default = 'vibe-glass';
$cor_default = '#70cde4';
$swipeAtivado = 0;
$pref_estilo_comentario = 'whatsapp'; 

if (isset($_SESSION['usuario_id'])) {
    $usuario_logado_id = $_SESSION['usuario_id'];
    $query_prefs = "SELECT pref_vibe_padrao, pref_cor_padrao, pref_swipe, pref_estilo_comentario FROM usuarios WHERE id = '$usuario_logado_id'";
    $res_prefs = mysqli_query($conn, $query_prefs);
    if ($dados_user = mysqli_fetch_assoc($res_prefs)) {
        $vibe_default = $dados_user['pref_vibe_padrao'] ?? 'vibe-glass';
        $cor_default = $dados_user['pref_cor_padrao'] ?? '#70cde4';
        $swipeAtivado = $dados_user['pref_swipe'] ?? 0;
        $pref_estilo_comentario = $dados_user['pref_estilo_comentario'] ?? 'whatsapp';
    }
}

$is_post_page = true;
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
    error_log('[COMENTARIOS] Falha ao instanciar B2: ' . $e->getMessage());
}

$post_id_atual = $id;
$sql_react = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = ? GROUP BY tipo_reacao";
$stmt_react = $conn->prepare($sql_react);
$stmt_react->bind_param("i", $post_id_atual);
$stmt_react->execute();
$res_react = $stmt_react->get_result();
$reacoes_detalhes = [];
while ($row = $res_react->fetch_assoc()) {
    $reacoes_detalhes[$row['tipo_reacao']] = $row['total'];
}
$stmt_react->close();

$minhas_reacoes = [];
if (isset($_SESSION['usuario_id'])) {
    $meu_id = $_SESSION['usuario_id'];
    $sql_my = "SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?";
    $stmt_my = $conn->prepare($sql_my);
    $stmt_my->bind_param("ii", $post_id_atual, $meu_id);
    $stmt_my->execute();
    $res_my = $stmt_my->get_result();
    while ($row = $res_my->fetch_assoc()) {
        $minhas_reacoes[] = $row['tipo_reacao'];
    }
    $stmt_my->close();
}

$tradutor = ['amei' => '💖', 'perplecto' => '😲', 'haha' => '😂', 'ranco' => '🙄', 'forca' => '🫂', 'triste' => '😢', 'tendi-nada' => '🤔'];

$total_comentarios = 0;
$sql_count = "SELECT COUNT(*) as total FROM comentarios WHERE id_mensagem = ? AND status = 'ativo'";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $id);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
if ($row_count = $res_count->fetch_assoc()) {
    $total_comentarios = $row_count['total'];
}
$stmt_count->close();

$total_reacoes = array_sum($reacoes_detalhes);
?>

<style>
    .header-visivel, .footer-texto-institucional, .footer-global { display: none; }
</style>

<div class="lingote-container" id="lingoteContainer">
    <div class="layout-wrapper">
        <?php
        if (($post['categoria'] ?? '') === 'anonimo') {
            $avatar_miniatura = 'uploads/ui/anonimo-default.webp';
            $nome_miniatura = 'Habitante Anônimo';
        } else {
            $avatar_miniatura = obterUrlComFallback($post['foto'] ?? null, 'uploads/ui/default.webp', $b2, true);
            $nome_miniatura = !empty($post['username']) ? '@' . htmlspecialchars($post['username']) : 'Usuário';
        }
        $texto_miniatura = htmlspecialchars(mb_substr($post['mensagem'], 0, 80));
        if (mb_strlen($post['mensagem']) > 80) $texto_miniatura .= '...';
        ?>
        <div id="header-actions"
            class="header-actions-container header-actions-fixo"
            data-post-id="<?php echo $id; ?>"
            data-post-avatar="<?php echo htmlspecialchars($avatar_miniatura); ?>"
            data-post-nome="<?php echo htmlspecialchars($nome_miniatura); ?>"
            data-post-texto="<?php echo htmlspecialchars($texto_miniatura); ?>">
        </div>

        <main class="lista-scrollavel" id="conteudo-rolavel">
            <div class="fenda-estatica-context"></div>
            <div class="lista-comentarios-social">
                <?php
                $sql_c = "SELECT c.*, 
                          (SELECT comentario FROM comentarios WHERE id = c.parent_id) as parent_comentario
                          FROM comentarios c 
                          WHERE c.id_mensagem = ? AND c.status = 'ativo'
                          ORDER BY COALESCE(c.parent_id, c.id), c.id ASC";
                $stmt_c = $conn->prepare($sql_c);
                $stmt_c->bind_param("i", $id);
                $stmt_c->execute();
                $res_c = $stmt_c->get_result();

                $todos_comentarios = [];
                while ($c = $res_c->fetch_assoc()) {
                    $todos_comentarios[] = $c;
                }

                if (count($todos_comentarios) > 0) {
                    if ($pref_estilo_comentario === 'cascata') {
                        echo renderizarArvoreCascata($todos_comentarios, null, 0, $b2);
                    } else {
                        foreach ($todos_comentarios as $c) {
                            $vibe = sanitizarVibe($c['pref_vibe_comentario'] ?? 'vibe-glass');
                            $cor_borda = sanitizarCorBorda($c['pref_cor_borda'] ?? '#70cde4');
                            $classe_filho = !empty($c['parent_id']) ? "comentario-filho" : "";
                            $id_autor_comentario = $c['id_usuario'] ?? $c['usuario_id'] ?? 0;
                            $sou_eu = (isset($_SESSION['usuario_id']) && $id_autor_comentario == $_SESSION['usuario_id']) ? 'meu-comentario' : '';
                            $estilo_filho = $classe_filho ? "var(--cor-borda-glow);" : "";

                            $trecho_resposta = '';
                            if (!empty($c['parent_id']) && !empty($c['parent_comentario'])) {
                                $texto_puro = strip_tags($c['parent_comentario']);
                                $texto_cortado = mb_substr($texto_puro, 0, 50);
                                $trecho_resposta = mb_strlen($texto_puro) > 50 ? $texto_cortado . '...' : $texto_cortado;
                            }
                    ?>
                            <div class="comentario-linha <?php echo $sou_eu === 'meu-comentario' ? 'comentario-linha-proprio' : ''; ?> <?php echo $classe_filho ? 'comentario-linha-filha' : ''; ?>">
                            <div class="comentario-item <?php echo $vibe . ' ' . $classe_filho . ' ' . $sou_eu; ?>" id="comentario-<?php echo $c['id']; ?>" style="--cor-borda-glow: <?php echo $cor_borda; ?>; <?php echo $estilo_filho; ?>">
                                <div class="comentario-meta">
                                    <strong class="comentario-autor">
                                        <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                                    </strong>
                                </div>
                                <?php if (!empty($c['parent_id'])): ?>
                                    <div class="indicador-resposta" onclick="event.stopPropagation(); irParaMensagem(<?php echo $c['parent_id']; ?>)">
                                        <i class="fas fa-reply"></i> <small><?php echo htmlspecialchars($trecho_resposta); ?></small>
                                    </div>
                                <?php endif; ?>
                                <p class="comentario-texto"><?php echo nl2br(formatarMencoes($c['comentario'])); ?></p>
                                <?php
                                $anexos_exibicao = null;
                                if (!empty($c['anexos'])) {
                                    $anexos_exibicao = json_decode($c['anexos'], true);
                                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($anexos_exibicao)) $anexos_exibicao = null;
                                }
                                if (!empty($anexos_exibicao) && is_array($anexos_exibicao)):
                                ?>
                                    <div class="comentario-media-wrapper-grid">
                                        <?php foreach ($anexos_exibicao as $anexo): ?>
                                            <?php if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])): ?>
                                                <?php $img_url = obterUrlComFallback($anexo['caminho'], 'comentarios/' . htmlspecialchars($anexo['caminho']), $b2, true); ?>
                                                <div class="comentario-media-item"><img src="<?= htmlspecialchars($img_url) ?>" class="comentario-img" loading="lazy" onerror="this.style.display='none'"></div>
                                            <?php elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])): ?>
                                                <div class="comentario-media-item"><img src="<?= htmlspecialchars($anexo['url']) ?>" class="comentario-img gif-externo" loading="lazy"></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (!empty($c['imagem_url'])): ?>
                                    <?php
                                    $img_comentario = $c['imagem_url'];
                                    if (!filter_var($img_comentario, FILTER_VALIDATE_URL)) {
                                        $img_comentario = obterUrlComFallback($c['imagem_url'], 'comentarios/' . htmlspecialchars($c['imagem_url']), $b2, true);
                                    }
                                    ?>
                                    <div class="comentario-media-wrapper">
                                        <img src="<?= htmlspecialchars($img_comentario) ?>" class="comentario-img <?= filter_var($c['imagem_url'], FILTER_VALIDATE_URL) ? 'gif-externo' : '' ?>" loading="lazy" onerror="this.style.display='none'">
                                    </div>
                                <?php endif; ?>
                                <div class="comentario-rodape">
                                    <span class="comentario-data"><?= exibirDataHoraBrasil($c['data_comentario'], 'H:i') ?></span>
                                </div>
                            </div>
                            <button type="button" id="comentario-acoes-<?php echo (int)$c['id']; ?>" class="comentario-menu-toggle" aria-label="Ações do comentário" aria-haspopup="true" aria-expanded="false" aria-controls="comentario-menu-<?php echo (int)$c['id']; ?>" title="Ações do comentário">
                                <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                            </button>
                            </div>
                    <?php
                        } 
                    } 
                } else {
                    echo '<p class="sem-comentarios">Ninguém fofocou nada ainda... Seja o primeiro!</p>';
                }
                ?>
            </div>
        </main>

        <?php if ($post_esta_ativo): ?>
            <footer class="fixed-input">
                <section class="sessao-fofoca-focada" id="fofocar">
                    <div id="anexos-grid" class="anexos-grid" style="display: none;"></div>
                    <div class="textarea-wrapper">
                        <button type="button" id="btn-attach-gaveta" class="btn-attach-gaveta" title="Anexar arquivo ou GIF">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <div class="textarea-container">
                            <div id="resposta-indicador" class="resposta-indicador">
                                <i class="fas fa-reply"></i>
                                <strong id="texto-nome-resposta">...</strong>
                                <button type="button" class="cancelar-resposta" onclick="cancelarResposta()">✕</button>
                            </div>
                            <textarea name="comentario" class="textarea-chat" placeholder="Digite sua mensagem..." maxlength="500" id="comentario-textarea"></textarea>
                            <span id="char-count" class="char-counter-inline">500</span>
                        </div>
                        <button type="submit" form="form-comentario" id="btn-enviar-chat" class="btn-enviar-chat">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div id="gaveta-opcoes" class="gaveta-opcoes">
                        <button type="button" id="btn-toggle-gaveta" class="btn-toggle-gaveta">
                            <i class="fas fa-palette"></i> Estilo
                        </button>
                        <div class="customizacao-rapida">
                            <select name="pref_vibe_comentario" id="vibe-comentario" class="input-mini">
                                <option value="vibe-glass" <?php echo ($vibe_default == 'vibe-glass') ? 'selected' : ''; ?>>Glass</option>
                                <option value="vibe-neon" <?php echo ($vibe_default == 'vibe-neon') ? 'selected' : ''; ?>>Neon</option>
                                <option value="vibe-dark" <?php echo ($vibe_default == 'vibe-dark') ? 'selected' : ''; ?>>Dark</option>
                                <option value="vibe-light" <?php echo ($vibe_default == 'vibe-light') ? 'selected' : ''; ?>>Light</option>
                                <option value="vibe-ads" <?php echo ($vibe_default == 'vibe-ads') ? 'selected' : ''; ?>>ADS (Overclock)</option>
                            </select>
                            <input type="color" name="pref_cor_borda" id="cor-borda" class="color-mini" value="<?php echo $cor_default; ?>">
                        </div>
                        <button type="button" id="btn-anexar-img" class="btn-attach-opcao"><i class="fas fa-image"></i> Imagem</button>
                        <button type="button" id="btn-gif" class="btn-attach-opcao" onclick="window.setGiphyTarget('gif-url-comentario'); abrirGiphyModal();">
                            <i class="fas fa-grin-tongue-squint"></i> GIF/Sticker
                        </button>
                    </div>
                    <input type="file" name="imagem_comentario" id="input-img-comentario" accept="image/*" style="display:none;">
                    <form action="enviar-comentario.php" method="POST" enctype="multipart/form-data" class="form-chat" id="form-comentario" style="display: none;">
                        <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
                        <input type="hidden" name="parent_id" id="input_parent_id" value="">
                        <input type="hidden" name="pref_vibe_comentario" id="hidden-vibe" value="">
                        <input type="hidden" name="pref_cor_borda" id="hidden-cor" value="">
                        <textarea name="comentario" id="hidden-textarea"></textarea>
                        <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="gif_url" id="gif-url-comentario" value="">
                    </form>
                    <input type="text" name="honeypot" class="honeypot" tabindex="-1" autocomplete="off" style="display: none !important; position: absolute; left: -9999px;">
                </section>
            </footer>
        <?php else: ?>
            <footer class="fixed-input fixed-input-bloqueado">
                <div class="comentarios-bloqueados-msg">
                    <i class="fas fa-ban"></i>
                    <span>Este post foi encerrado e não aceita mais interações.</span>
                </div>
            </footer>
        <?php endif; ?>
    </div>
</div>

<button id="btn-toggle-collapse" class="btn-toggle-collapse" aria-label="Minimizar/Expandir post">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
    if (typeof AnexosManager !== 'undefined' && AnexosManager.init) { AnexosManager.init(); }
</script>

<script src="js/fenda-giphy.js"></script>
<script src="js/fenda-mencoes.js"></script>
<?php include 'includes/footer.php'; ?>

<script>
    window.exibirBalao = function(mensagem, tipo, elementoRef, duracao = 2500) {
        try {
            const balaoAntigo = document.querySelector('.balao-fenda');
            if (balaoAntigo) balaoAntigo.remove();
            const balao = document.createElement('div');
            balao.className = 'balao-fenda ' + tipo;
            const icones = { sucesso: '✅', erro: '❌', info: 'ℹ️' };
            const icone = document.createElement('span');
            icone.className = 'balao-icone';
            icone.textContent = icones[tipo] || '💬';
            balao.appendChild(icone);
            const texto = document.createElement('span');
            texto.textContent = mensagem;
            balao.appendChild(texto);
            if (elementoRef) {
                const rect = elementoRef.getBoundingClientRect();
                let top = rect.top - 10;
                let left = rect.left + rect.width / 2 - 50;
                const balaoWidth = Math.min(300, window.innerWidth * 0.8);
                if (left + balaoWidth > window.innerWidth - 20) left = window.innerWidth - balaoWidth - 20;
                if (left < 20) left = 20;
                if (rect.top < 60) top = rect.bottom + 10; else top = rect.top - 60;
                balao.style.top = top + 'px';
                balao.style.left = left + 'px';
                balao.style.maxWidth = balaoWidth + 'px';
            } else {
                balao.style.top = '50%';
                balao.style.left = '50%';
                balao.style.transform = 'translate(-50%, -50%)';
            }
            document.body.appendChild(balao);
            setTimeout(() => {
                if (balao.parentNode) {
                    balao.style.opacity = '0';
                    setTimeout(() => balao.remove(), 300);
                }
            }, duracao);
        } catch (e) {
            alert(mensagem);
        }
    };

    if (typeof AnexosManager !== 'undefined' && AnexosManager.init) { AnexosManager.init(); }

    <?php if ($post_esta_ativo): ?>
        const barraFofoca = document.querySelector('.sessao-fofoca-focada');
        const campoTexto = document.querySelector('.textarea-chat');
        const contadorChar = document.getElementById('char-count');
        const form = document.getElementById('form-comentario');
        const btnEnviar = document.querySelector('.btn-enviar-chat');
        const btnGaveta = document.getElementById('btn-attach-gaveta');
        const gaveta = document.getElementById('gaveta-opcoes');
        const inputFile = document.getElementById('input-img-comentario');
        const hiddenVibe = document.getElementById('hidden-vibe');
        const hiddenCor = document.getElementById('hidden-cor');
        const hiddenTextarea = document.getElementById('hidden-textarea');
        const selectVibe = document.getElementById('vibe-comentario');
        const inputCor = document.getElementById('cor-borda');

        let gavetaAberta = false;

        function verificarConteudo() {
            if (typeof AnexosManager !== 'undefined' && AnexosManager.verificarConteudo) {
                AnexosManager.verificarConteudo();
                return;
            }
            const temTexto = campoTexto.value.trim().length > 0;
            const temImagem = inputFile && inputFile.files && inputFile.files.length > 0;
            const gifInput = document.querySelector('input[name="gif_url"]');
            const temGif = gifInput && gifInput.value !== '';
            const temMidia = temImagem || temGif;
            if (temTexto || temMidia) {
                btnEnviar.classList.add('visivel');
                btnEnviar.style.display = 'flex';
                btnGaveta.style.display = 'none';
            } else {
                btnEnviar.classList.remove('visivel');
                btnEnviar.style.display = 'none';
                btnGaveta.style.display = 'flex';
            }
        }

        function atualizarHiddenPrefs() {
            if (hiddenVibe) hiddenVibe.value = selectVibe.value;
            if (hiddenCor) hiddenCor.value = inputCor.value;
            if (hiddenTextarea) hiddenTextarea.value = campoTexto.value;
        }

        inputFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const maxSize = 2 * 1024 * 1024;
                    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                    if (file.size > maxSize) {
                        window.exibirBalao(`❌ Arquivo excede 2MB`, 'erro', btnEnviar);
                        this.value = ''; return;
                    }
                    if (!tiposPermitidos.includes(file.type)) {
                        window.exibirBalao('❌ Formato não suportado.', 'erro', btnEnviar);
                        this.value = ''; return;
                    }
                    if (typeof window.adicionarAnexo === 'function') window.adicionarAnexo(file);
                }
                this.value = '';
            }
        });

        document.addEventListener('gifSelecionado', function(e) {
            if (e.detail && e.detail.targetId && e.detail.targetId !== 'gif-url-comentario') return;
            if (e.detail && e.detail.url) {
                if (typeof window.adicionarGif === 'function') window.adicionarGif(e.detail.url);
                inputFile.value = '';
            }
        });

        window.toggleBarraFofoca = function() {
            const icone = document.querySelector('#toggle-chat-barra i');
            if (!barraFofoca) return;
            barraFofoca.classList.toggle('encolhida');
            if (icone) icone.className = barraFofoca.classList.contains('encolhida') ? 'fas fa-comment-dots' : 'fas fa-times';
            if (!barraFofoca.classList.contains('encolhida')) {
                setTimeout(() => { if (campoTexto) campoTexto.focus(); }, 80);
            }
        };

        window.prepararResposta = function(id, username) {
            const inputParent = document.getElementById('input_parent_id');
            const indicador = document.getElementById('resposta-indicador');
            const textoNome = document.getElementById('texto-nome-resposta');
            const textarea = document.querySelector('.textarea-chat');
            if (inputParent) inputParent.value = parseInt(id);
            if (indicador && textoNome) {
                textoNome.textContent = `Respondendo a ${username}...`;
                indicador.classList.add('visivel');
            }
            if (textarea) {
                textarea.placeholder = "Escreva sua resposta...";
                textarea.classList.add('com-resposta');
                textarea.focus();
            }
            if (gaveta && !gavetaAberta) {
                gaveta.style.display = 'flex';
                gavetaAberta = true;
            }
            verificarConteudo();
        };

        window.cancelarResposta = function() {
            const inputParent = document.getElementById('input_parent_id');
            const indicador = document.getElementById('resposta-indicador');
            const textarea = document.querySelector('.textarea-chat');
            if (inputParent) inputParent.value = '';
            if (indicador) indicador.classList.remove('visivel');
            if (textarea) {
                textarea.value = '';
                textarea.placeholder = "Digite sua mensagem...";
                textarea.classList.remove('com-resposta');
            }
            if (contadorChar) contadorChar.textContent = '500';
            verificarConteudo();
        };

        const btnAnexarImg = document.getElementById('btn-anexar-img');
        if (btnAnexarImg && inputFile) btnAnexarImg.addEventListener('click', () => inputFile.click());

        if (btnGaveta && gaveta) {
            btnGaveta.addEventListener('click', (e) => {
                e.stopPropagation();
                gavetaAberta = !gavetaAberta;
                gaveta.style.display = gavetaAberta ? 'flex' : 'none';
            });
            document.addEventListener('click', (e) => {
                if (!btnGaveta.contains(e.target) && !gaveta.contains(e.target)) {
                    gaveta.style.display = 'none';
                    gavetaAberta = false;
                }
            });
        }

        if (form && btnEnviar) {
            btnEnviar.addEventListener('click', function(e) {
                e.preventDefault();
                const textoAtual = campoTexto ? campoTexto.value.trim() : '';
                let temAnexo = false;
                if (typeof AnexosManager !== 'undefined' && AnexosManager.anexos) {
                    temAnexo = AnexosManager.anexos.length > 0;
                } else {
                    const gifInput = document.querySelector('input[name="gif_url"]');
                    temAnexo = (inputFile && inputFile.files && inputFile.files.length > 0) || (gifInput && gifInput.value !== '');
                }
                if (textoAtual === '' && !temAnexo) {
                    window.exibirBalao('Escreva algo ou adicione uma imagem/GIF.', 'info', btnEnviar);
                    return;
                }
                atualizarHiddenPrefs();
                let formData;
                if (typeof AnexosManager !== 'undefined' && AnexosManager.prepararFormData) {
                    formData = AnexosManager.prepararFormData(form);
                } else {
                    formData = new FormData(form);
                    if (inputFile.files[0]) formData.append('imagem_comentario', inputFile.files[0]);
                    const gifInput = document.querySelector('input[name="gif_url"]');
                    if (gifInput && gifInput.value) formData.append('gif_url', gifInput.value);
                }
                const csrfInput = document.getElementById('csrf_token');
                if (csrfInput && csrfInput.value) {
                    formData.set('csrf_token', csrfInput.value);
                } else {
                    window.exibirBalao('Erro de segurança. Recarregue a página.', 'erro', btnEnviar);
                    return;
                }
                const btn = btnEnviar;
                const originalIcon = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                document.querySelectorAll('.btn-remover-anexo').forEach(b => b.disabled = true);
                window.exibirBalao('Enviando comentário...', 'info', btn, 1500);

                fetch('enviar-comentario.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(async response => {
                    const text = await response.text();
                    if (!response.ok) throw new Error("Erro HTTP " + response.status + ": " + text);
                    try { return JSON.parse(text); } catch (e) { throw new Error("Resposta não é JSON: " + text); }
                })
                .then(data => {
                    if (data.status === 'success') {
                        window.exibirBalao('Comentário enviado! 🎉', 'sucesso', btn);
                        const parentIdEnviado = document.getElementById('input_parent_id').value;
                        const isCascata = <?= json_encode($pref_estilo_comentario === 'cascata') ?>;
                        
                        cancelarResposta(); 
                        
                        const container = document.querySelector('.lista-comentarios-social');

                        if (isCascata && parentIdEnviado) {
                            const paiItem = document.getElementById('comentario-' + parentIdEnviado);
                            if (paiItem) {
                                const paiLinha = paiItem.closest('.comentario-linha');
                                if (paiLinha) {
                                    const nivelPaiClass = Array.from(paiLinha.classList).find(c => c.startsWith('nivel-'));
                                    let nivel = nivelPaiClass ? Math.min(parseInt(nivelPaiClass.replace('nivel-', '')) + 1, 2) : 1;
                                    
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = data.html;
                                    const novaLinha = tempDiv.firstElementChild;
                                    if (novaLinha) {
                                        novaLinha.classList.remove('comentario-linha-filha', 'comentario-linha-proprio'); 
                                        novaLinha.classList.add('nivel-' + nivel);
                                        paiLinha.appendChild(novaLinha);
                                    }
                                } else {
                                    container.insertAdjacentHTML('beforeend', data.html);
                                }
                            } else {
                                container.insertAdjacentHTML('beforeend', data.html);
                            }
                        } else {
                            container.insertAdjacentHTML('beforeend', data.html);
                        }

                        container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        campoTexto.value = '';
                        campoTexto.style.height = 'auto';
                        contadorChar.textContent = '500';
                        if (typeof AnexosManager !== 'undefined' && AnexosManager.limparTodos) {
                            AnexosManager.limparTodos();
                        } else {
                            inputFile.value = '';
                            const gifInput = document.querySelector('input[name="gif_url"]');
                            if (gifInput) gifInput.value = '';
                        }
                        gaveta.style.display = 'none';
                        gavetaAberta = false;
                        verificarConteudo();
                        if (typeof window.initLightboxGlobal === 'function') window.initLightboxGlobal();
                    } else {
                        window.exibirBalao('Erro: ' + (data.message || 'Falha ao enviar.'), 'erro', btn);
                    }
                })
                .catch(err => {
                    window.exibirBalao('ERRO: ' + err.message, 'erro', btn);
                })
                .finally(() => {
                    btn.innerHTML = originalIcon;
                    btn.disabled = false;
                    document.querySelectorAll('.btn-remover-anexo').forEach(b => b.disabled = false);
                });
            });
        }

        if (campoTexto) {
            campoTexto.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if (contadorChar) contadorChar.textContent = (500 - this.value.length);
                verificarConteudo();
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.initLightboxGlobal === 'function') window.initLightboxGlobal();
        });
        verificarConteudo();

        (function inicializarPollingComentarios() {
            const listaComentarios = document.querySelector('.lista-comentarios-social');
            if (!listaComentarios) return;

            const POST_ID = <?php echo (int)$id; ?>;
            const INTERVALO_MS = 4000;
            const IS_CASCATA = <?= json_encode($pref_estilo_comentario === 'cascata') ?>;

            sessionStorage.setItem('fenda_post_sendo_visto', String(POST_ID));
            window.addEventListener('beforeunload', () => {
                const atual = parseInt(sessionStorage.getItem('fenda_post_sendo_visto')) || 0;
                if (atual === POST_ID) sessionStorage.removeItem('fenda_post_sendo_visto');
            });

            let ultimoId = 0;
            listaComentarios.querySelectorAll('.comentario-item').forEach(el => {
                const match = el.id && el.id.match(/^comentario-(\d+)$/);
                if (match) {
                    const num = parseInt(match[1], 10);
                    if (!isNaN(num) && num > ultimoId) ultimoId = num;
                }
            });

            let intervaloAtivo = null;
            let abortAtual = null;

            function contextoEstaOk() {
                if (document.hidden) return false;
                if (document.body.classList.contains('modal-aberto')) return false;
                if (document.body.classList.contains('modo-swipe-ativo')) return false;
                const gaveta = document.getElementById('gaveta-opcoes');
                if (gaveta && gaveta.style.display === 'flex') return false;
                return true;
            }

            function buscarNovos() {
                if (!contextoEstaOk()) return;
                if (abortAtual) abortAtual.abort();
                abortAtual = new AbortController();
                const url = `includes/comentarios-novos.php?id_mensagem=${POST_ID}&ultimo_id=${ultimoId}&_=${Date.now()}`;

                fetch(url, { signal: abortAtual.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(data => {
                    if (!data || data.status !== 'success') return;
                    if (data.post_inativo) { pararPolling(); return; }
                    if (!data.novos || data.novos.length === 0) return;

                    const main = document.querySelector('.lista-scrollavel');
                    const pertoDoFim = main ? (main.scrollTop + main.clientHeight) >= (main.scrollHeight - 120) : true;
                    const semComentarios = listaComentarios.querySelector('.sem-comentarios');
                    if (semComentarios) semComentarios.remove();

                    data.novos.forEach(novo => {
                        if (document.getElementById('comentario-' + novo.id)) return;
                        
                        if (IS_CASCATA && novo.parent_id) {
                            const paiItem = document.getElementById('comentario-' + novo.parent_id);
                            if (paiItem) {
                                const paiLinha = paiItem.closest('.comentario-linha');
                                if (paiLinha) {
                                    const nivelPaiClass = Array.from(paiLinha.classList).find(c => c.startsWith('nivel-'));
                                    let nivel = nivelPaiClass ? Math.min(parseInt(nivelPaiClass.replace('nivel-', '')) + 1, 2) : 1;
                                    
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = novo.html;
                                    const novaLinha = tempDiv.firstElementChild;
                                    if (novaLinha) {
                                        novaLinha.classList.remove('comentario-linha-filha', 'comentario-linha-proprio');
                                        novaLinha.classList.add('nivel-' + nivel);
                                        paiLinha.appendChild(novaLinha);
                                    }
                                } else {
                                    listaComentarios.insertAdjacentHTML('beforeend', novo.html);
                                }
                            } else {
                                listaComentarios.insertAdjacentHTML('beforeend', novo.html);
                            }
                        } else {
                            listaComentarios.insertAdjacentHTML('beforeend', novo.html);
                        }
                    });

                    ultimoId = data.ultimo_id;
                    if (typeof window.initLightboxGlobal === 'function') window.initLightboxGlobal();

                    if (pertoDoFim && main) {
                        const ultimoEl = listaComentarios.lastElementChild;
                        if (ultimoEl) ultimoEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                })
                .catch(err => { if (err.name !== 'AbortError') console.warn('[POLLING] Erro:', err.message); });
            }

            function pararPolling() { if (intervaloAtivo) { clearInterval(intervaloAtivo); intervaloAtivo = null; } }
            function iniciarPolling() { if (intervaloAtivo) return; intervaloAtivo = setInterval(buscarNovos, INTERVALO_MS); setTimeout(buscarNovos, 500); }

            iniciarPolling();
            document.addEventListener('visibilitychange', () => { if (document.hidden) pararPolling(); else { iniciarPolling(); buscarNovos(); } });
        })();
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof HeaderManager !== 'undefined' && HeaderManager.init) {
            HeaderManager.init();
        }
    });
</script>