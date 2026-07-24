<?php
include_once 'conexao.php';
include_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO comentarios-post.php');
/* ==========================================================================
   Deep, o Marreteiro – esteve aqui e não deixou ninguém desistir.
   Cada linha, cada debug, cada madrugada valeram a pena.
   A Fenda está viva. Até a próxima travessia, companheiro. 💚
   ========================================================================== */

// --- LÓGICA DE EXCEÇÃO PARA PERDIDOS ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    header("Location: feed.php");
    exit();
}

// --- VERIFICA SE É POST PERDIDO (público) ---
$is_perdidos = false;
if ($id > 0) {
    $stmt_check = $conn->prepare("SELECT categoria FROM mensagens WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $check_post = $stmt_check->get_result()->fetch_assoc();
    if ($check_post && $check_post['categoria'] === 'perdidos') {
        $is_perdidos = true;
    }
}

// --- SEGURANÇA: se não estiver logado e não for perdidos, redireciona ---
if (!isset($_SESSION['usuario_id']) && !$is_perdidos) {
    header("Location: index.php");
    exit();
}

// --- PUXA DADOS DO USUÁRIO LOGADO ---
$vibe_default = 'vibe-glass';
$cor_default = '#70cde4';
$swipeAtivado = 0;

if (isset($_SESSION['usuario_id'])) {
    $usuario_logado_id = $_SESSION['usuario_id'];
    $query_prefs = "SELECT pref_vibe_padrao, pref_cor_padrao, pref_swipe FROM usuarios WHERE id = '$usuario_logado_id'";
    $res_prefs = mysqli_query($conn, $query_prefs);
    if ($dados_user = mysqli_fetch_assoc($res_prefs)) {
        $vibe_default = $dados_user['pref_vibe_padrao'] ?? 'vibe-glass';
        $cor_default = $dados_user['pref_cor_padrao'] ?? '#70cde4';
        $swipeAtivado = $dados_user['pref_swipe'] ?? 0;
    }
}

$is_post_page = true;
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// ============================================================
// 🔥 INSTANCIA O B2 UMA ÚNICA VEZ (evita N+1)
// ============================================================
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
    error_log('[COMENTARIOS] Falha ao instanciar B2: ' . $e->getMessage());
}

// ============================================================
// 🔥 BUSCA O POST E VERIFICA STATUS
// ============================================================
$stmt = $conn->prepare("SELECT m.*, u.username, u.foto FROM mensagens m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    die("<main> <style> body { font-size:2.1rem; color: white; text-align: center; padding-top: 50px; } </style> <p>Ops... Spotted não encontrado!</p> </main>");
}

// 🔥 VARIÁVEL QUE DEFINE SE O POST ESTÁ ATIVO PARA COMENTÁRIOS
$post_esta_ativo = ($post['status'] === 'ativo');

// ============================================================
// 1. BUSCAR REAÇÕES DETALHADAS PARA ESTE POST
// ============================================================
$post_id_atual = $id;
$sql_react = "SELECT tipo_reacao, COUNT(*) as total 
              FROM curtidas 
              WHERE mensagem_id = ? 
              GROUP BY tipo_reacao";
$stmt_react = $conn->prepare($sql_react);
$stmt_react->bind_param("i", $post_id_atual);
$stmt_react->execute();
$res_react = $stmt_react->get_result();
$reacoes_detalhes = [];
while ($row = $res_react->fetch_assoc()) {
    $reacoes_detalhes[$row['tipo_reacao']] = $row['total'];
}
$stmt_react->close();

// 2. REAÇÕES DO USUÁRIO LOGADO
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

// 3. TRADUTOR EMOJIS
$tradutor = ['amei' => '💖', 'perplecto' => '😲', 'haha' => '😂', 'ranco' => '🙄', 'forca' => '🫂', 'triste' => '😢', 'tendi-nada' => '🤔'];

// 4. CONTAGEM TOTAL DE COMENTÁRIOS
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
    .header-visivel,
    .footer-texto-institucional,
    .footer-global {
        display: none;
    }
</style>

<!-- ============================================================
     LINGOTE (CONTEÚDO COMPLETO – SEM CORTINA)
     ============================================================ -->
<div class="lingote-container" id="lingoteContainer">
    <div class="layout-wrapper">

        <!-- 🔥 BARRA DE AÇÕES FIXA (HEADER) – FORA DO STICKY HEADER -->
        <?php
        // Dados da miniatura (já calculados antes, mas reforçamos aqui)
        $avatar_miniatura = !empty($post['foto'])
            ? (obterUrlImagem($post['foto'], $b2, true) ?? 'uploads/ui/default.webp')
            : 'uploads/ui/default.webp';
        $nome_miniatura = !empty($post['username']) ? '@' . htmlspecialchars($post['username']) : 'Usuário';
        $texto_miniatura = htmlspecialchars(mb_substr($post['mensagem'], 0, 80));
        if (mb_strlen($post['mensagem']) > 80) $texto_miniatura .= '...';
        ?>
        <div id="header-actions"
            class="header-actions-container header-actions-fixo"
            data-post-id="<?php echo $id; ?>"
            data-post-avatar="<?php echo htmlspecialchars($avatar_miniatura); ?>"
            data-post-nome="<?php echo htmlspecialchars($nome_miniatura); ?>"
            data-post-texto="<?php echo htmlspecialchars($texto_miniatura); ?>">
            <!-- Os botões serão injetados pelo HeaderManager -->
        </div>

        <!-- ============================================================
        CONTEÚDO ROLÁVEL (APENAS COMENTÁRIOS)
        🔥 A MINIATURA, BOTÃO VOLTAR E COLLAPSE FORAM REMOVIDOS DAQUI
        ELES AGORA SÃO GERENCIADOS PELO CSS E PELO HEADERMANAGER
        ============================================================ -->
        <main class="lista-scrollavel" id="conteudo-rolavel">

            <div class="fenda-estatica-context">
                <!-- 🔥 O botão "Voltar" agora está na barra fixa (HeaderManager) -->
                <!-- 🔥 O collapse agora é fixo via CSS (fora do fluxo) -->
            </div>

            <!-- ÁREA DE COMENTÁRIOS -->
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

                if ($res_c->num_rows > 0):
                    while ($c = $res_c->fetch_assoc()):
                        $vibe = !empty($c['pref_vibe_comentario']) ? $c['pref_vibe_comentario'] : 'vibe-glass';
                        $cor_borda = !empty($c['pref_cor_borda']) ? $c['pref_cor_borda'] : '#70cde4';
                        $classe_filho = !empty($c['parent_id']) ? "comentario-filho" : "";
                        $id_vincular = !empty($c['parent_id']) ? $c['parent_id'] : $c['id'];
                        $id_autor_comentario = $c['id_usuario'] ?? $c['usuario_id'] ?? 0;
                        $sou_eu = (isset($_SESSION['usuario_id']) && $id_autor_comentario == $_SESSION['usuario_id']) ? 'meu-comentario' : '';
                        $nome_limpo_js = !empty($c['usuario_nome']) ? str_replace("'", "", $c['usuario_nome']) : "Habitante";
                        $estilo_filho = $classe_filho ? "var(--cor-borda-glow);" : "";

                        $trecho_resposta = '';
                        if (!empty($c['parent_id']) && !empty($c['parent_comentario'])) {
                            $texto_puro = strip_tags($c['parent_comentario']);
                            $texto_cortado = mb_substr($texto_puro, 0, 50);
                            $trecho_resposta = mb_strlen($texto_puro) > 50 ? $texto_cortado . '...' : $texto_cortado;
                        }
                ?>
                        <div class="comentario-item <?php echo $vibe . ' ' . $classe_filho . ' ' . $sou_eu; ?>" id="comentario-<?php echo $c['id']; ?>" style="--cor-borda-glow: <?php echo $cor_borda; ?>; <?php echo $estilo_filho; ?>">

                            <!-- 🔥 REMOVIDO: botão de ellipsis (⋯) – agora centralizado no header -->
                            <!-- O ellipsis foi removido para centralizar a ação "Excluir" no HeaderManager -->

                            <div class="comentario-meta">
                                <strong class="comentario-autor" style="color: var(--cor-borda-glow);">
                                    <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                                </strong>
                                <span class="comentario-data"><?php echo date('H:i', strtotime($c['data_comentario'])); ?></span>
                            </div>

                            <?php if (!empty($c['parent_id'])): ?>
                                <div class="indicador-resposta" onclick="irParaMensagem(<?php echo $c['parent_id']; ?>)">
                                    <i class="fas fa-reply"></i> <small><?php echo htmlspecialchars($trecho_resposta); ?></small>
                                </div>
                            <?php endif; ?>

                            <p class="comentario-texto"><?php echo nl2br(formatarMencoes($c['comentario'])); ?></p>

                            <?php
                            // ============================================================
                            // 🔥 EXIBIÇÃO DOS ANEXOS (MÚLTIPLOS VIA JSON OU FALLBACK)
                            // ============================================================
                            $anexos_exibicao = null;
                            if (!empty($c['anexos'])) {
                                $anexos_exibicao = json_decode($c['anexos'], true);
                                if (json_last_error() !== JSON_ERROR_NONE || !is_array($anexos_exibicao)) {
                                    $anexos_exibicao = null;
                                }
                            }

                            if (!empty($anexos_exibicao) && is_array($anexos_exibicao)):
                                // Renderiza o grid com múltiplos anexos
                            ?>
                                <div class="comentario-media-wrapper-grid">
                                    <?php foreach ($anexos_exibicao as $anexo): ?>
                                        <?php if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])): ?>
                                            <?php
                                            $img_url = obterUrlImagem($anexo['caminho'], $b2, true) ?? 'comentarios/' . htmlspecialchars($anexo['caminho']);
                                            ?>
                                            <div class="comentario-media-item">
                                                <img src="<?= htmlspecialchars($img_url) ?>" class="comentario-img" alt="Imagem do comentário" loading="lazy" onerror="this.style.display='none'">
                                            </div>
                                        <?php elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])): ?>
                                            <div class="comentario-media-item">
                                                <img src="<?= htmlspecialchars($anexo['url']) ?>" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy">
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (!empty($c['imagem_url'])): ?>
                                <!-- Fallback: comentários antigos (apenas uma imagem) -->
                                <?php
                                $img_comentario = $c['imagem_url'];
                                if (!filter_var($img_comentario, FILTER_VALIDATE_URL)) {
                                    $img_comentario = obterUrlImagem($c['imagem_url'], $b2, true) ?? 'comentarios/' . htmlspecialchars($c['imagem_url']);
                                }
                                ?>
                                <div class="comentario-media-wrapper">
                                    <img src="<?= htmlspecialchars($img_comentario) ?>" class="comentario-img <?= filter_var($c['imagem_url'], FILTER_VALIDATE_URL) ? 'gif-externo' : '' ?>" alt="Imagem do comentário" loading="lazy" onerror="this.style.display='none'">
                                </div>
                            <?php endif; ?>

                            <!-- 🔥 REMOVIDO: botão "RESPONDER" do rodapé – agora centralizado no header -->
                            <!-- A ação "Responder" agora é acionada pelo HeaderManager ao selecionar o comentário -->

                        </div>
                    <?php
                    endwhile;
                else: ?>
                    <p class="sem-comentarios">Ninguém fofocou nada ainda... Seja o primeiro!</p>
                <?php endif; ?>
            </div>

        </main>

        <!-- ============================================================
        RODAPÉ – FORMULÁRIO DE COMENTÁRIO (se ativo) OU BLOQUEIO (se encerrado)
        ============================================================ -->
        <?php if ($post_esta_ativo): ?>
            <!-- Formulário ativo -->
            <footer class="fixed-input">
                <section class="sessao-fofoca-focada" id="fofocar">

                    <!-- ============================================================
                NOVO GRID DE ANEXOS (substitui o antigo #anexo-preview)
                ============================================================ -->
                    <div id="anexos-grid" class="anexos-grid" style="display: none;"></div>

                    <!-- ÁREA PRINCIPAL -->
                    <div class="textarea-wrapper">
                        <button type="button" id="btn-attach-gaveta" class="btn-attach-gaveta" title="Anexar arquivo ou GIF">
                            <i class="fas fa-paperclip"></i>
                        </button>

                        <!-- 🔥 NOVO CONTAINER FLEX COM INDICADOR E TEXTAREA -->
                        <div class="textarea-container">
                            <!-- INDICADOR DE RESPOSTA (FORA DO TEXTAREA) -->
                            <div id="resposta-indicador" class="resposta-indicador">
                                <i class="fas fa-reply"></i>
                                <strong id="texto-nome-resposta">...</strong>
                                <button type="button" class="cancelar-resposta" onclick="cancelarResposta()">✕</button>
                            </div>

                            <!-- TEXTAREA -->
                            <textarea name="comentario" class="textarea-chat" placeholder="Digite sua mensagem..." maxlength="500" id="comentario-textarea"></textarea>
                            <span id="char-count" class="char-counter-inline">500</span>
                        </div>

                        <button type="submit" form="form-comentario" id="btn-enviar-chat" class="btn-enviar-chat">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>

                    <!-- GAVETA DE OPÇÕES (vibe/cor) -->
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
                        <button type="button" id="btn-gif" class="btn-attach-opcao" onclick="abrirGiphyModal()"><i class="fas fa-grin-tongue-squint"></i> GIF/Sticker</button>
                    </div>

                    <!-- 🔥 INPUT FILE ESCONDIDO (indispensável para o JS) -->
                    <input type="file" name="imagem_comentario" id="input-img-comentario" accept="image/*" style="display:none;">

                    <!-- Formulário oculto -->
                    <form action="enviar-comentario.php" method="POST" enctype="multipart/form-data" class="form-chat" id="form-comentario" style="display: none;">
                        <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
                        <input type="hidden" name="parent_id" id="input_parent_id" value="">
                        <input type="hidden" name="pref_vibe_comentario" id="hidden-vibe" value="">
                        <input type="hidden" name="pref_cor_borda" id="hidden-cor" value="">
                        <textarea name="comentario" id="hidden-textarea"></textarea>
                    </form>

                    <!-- Honeypot -->
                    <input type="text" name="honeypot" class="honeypot" tabindex="-1" autocomplete="off" style="display: none !important;
                position: absolute;
                left: -9999px;">
                </section>
            </footer>
        <?php else: ?>
            <!--  POST ENCERRADO – BLOQUEIO DE COMENTÁRIOS -->
            <footer class="fixed-input fixed-input-bloqueado">
                <div class="comentarios-bloqueados-msg">
                    <i class="fas fa-ban"></i>
                    <span>Este post foi encerrado e não aceita mais interações.</span>
                </div>
            </footer>
        <?php endif; ?>

    </div>
</div>
<!-- 🔥 BOTÃO DE COLLAPSE FLUTUANTE (FORA DO LINGOTE) -->
<button id="btn-toggle-collapse" class="btn-toggle-collapse" aria-label="Minimizar/Expandir post">
    <i class="fas fa-chevron-up"></i>
</button>

<script>
    // ==================== CLIQUE NO BOTÃO ELLIPSIS (REMOVIDO) ====================
    // 🔥 O ellipsis foi removido dos comentários. A ação "Excluir" agora é centralizada no HeaderManager.

    // ==================== INICIALIZA O ANEXOS MANAGER ====================
    if (typeof AnexosManager !== 'undefined' && AnexosManager.init) {
        AnexosManager.init();
    }

    // ==================== LIGHTBOX PARA IMAGENS DOS COMENTÁRIOS ====================
    function initLightbox() {
        const imagens = document.querySelectorAll('.comentario-img');
        imagens.forEach(img => {
            img.removeEventListener('click', abrirLightboxImagem);
            img.addEventListener('click', abrirLightboxImagem);
        });
    }

    function abrirLightboxImagem(e) {
        e.stopPropagation();
        const imgSrc = e.currentTarget.src;
        if (!imgSrc) return;
        const modalExistente = document.getElementById('modal-lightbox-fenda');
        if (modalExistente) modalExistente.remove();
        const modal = document.createElement('div');
        modal.id = 'modal-lightbox-fenda';
        modal.style.cssText =
            `position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.73); display:flex; justify-content:center; align-items:center; z-index:1000000; cursor:pointer; user-select:none; -webkit-bakcdrop-filter: blur(4px); backdrop-filter: blur(4px); opacity:0; transition:opacity 0.2s ease;`;
        const img = document.createElement('img');
        img.src = imgSrc;
        img.style.cssText =
            `max-width:85%; max-height:85%; object-fit:contain; border-radius:12px; box-shadow:0 0 20px rgba(0,0,0,0.5);`;
        const btn = document.createElement('button');
        btn.innerHTML = '✖';
        btn.style.cssText =
            `position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:2rem; cursor:pointer; z-index:100001; font-weight:bold; text-shadow:0 0 5px black;`;
        btn.onclick = () => {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 200);
        };
        modal.appendChild(img);
        modal.appendChild(btn);
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.opacity = '0';
                setTimeout(() => modal.remove(), 200);
            }
        });
        modal.offsetHeight;
        modal.style.opacity = '1';
    }

    window.abrirLightboxManual = function(src) {
        if (!src) return;
        const modalExistente = document.getElementById('modal-lightbox-fenda');
        if (modalExistente) modalExistente.remove();
        const modal = document.createElement('div');
        modal.id = 'modal-lightbox-fenda';
        modal.style.cssText =
            `position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.73); display:flex; justify-content:center; align-items:center; z-index:1000000; cursor:pointer; user-select:none; -webkit-bakcdrop-filter: blur(4px); backdrop-filter: blur(4px); opacity:0; transition:opacity 0.2s ease;`;
        const img = document.createElement('img');
        img.src = src;
        img.style.cssText =
            `max-width:85%; max-height:85%; object-fit:contain; border-radius:12px; box-shadow:0 0 20px rgba(0,0,0,0.5);`;
        const btn = document.createElement('button');
        btn.innerHTML = '✖';
        btn.style.cssText =
            `position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:2rem; cursor:pointer; z-index:100001; font-weight:bold; text-shadow:0 0 5px black;`;
        btn.onclick = () => {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 200);
        };
        modal.appendChild(img);
        modal.appendChild(btn);
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.opacity = '0';
                setTimeout(() => modal.remove(), 200);
            }
        });
        modal.offsetHeight;
        modal.style.opacity = '1';
    };
</script>

<script src="js/fenda-giphy.js"></script>
<?php include 'includes/footer.php'; ?>
<script src="js/fenda-mencoes.js"></script>

<script>
    // ==================== LÓGICA DE COMENTÁRIOS (APENAS SE POST ESTIVER ATIVO) ====================
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

        // ============================================================
        // 🔥 FUNÇÃO PARA CONTROLAR A VISIBILIDADE DO BOTÃO DE ENVIAR
        // ============================================================
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

        // ============================================================
        // 🔥 ADICIONAR IMAGEM VIA INPUT FILE
        // ============================================================
        inputFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                if (typeof window.adicionarAnexo === 'function') {
                    window.adicionarAnexo(this.files[0]);
                } else {
                    console.warn('[comentarios-post] AnexosManager não disponível para adicionar imagem.');
                }
                this.value = '';
            }
        });

        // ============================================================
        // 🔥 EVENTO PARA CAPTURAR GIF SELECIONADO VIA GIPHY
        // ============================================================
        document.addEventListener('gifSelecionado', function(e) {
            if (e.detail && e.detail.url) {
                if (typeof window.adicionarGif === 'function') {
                    window.adicionarGif(e.detail.url);
                } else {
                    console.warn('[comentarios-post] AnexosManager não disponível para adicionar GIF.');
                }
                inputFile.value = '';
            }
        });

        // ============================================================
        // 🔥 FUNÇÕES DE RESPOSTA (mantidas)
        // ============================================================
        window.toggleBarraFofoca = function() {
            const icone = document.querySelector('#toggle-chat-barra i');
            if (!barraFofoca) return;
            barraFofoca.classList.toggle('encolhida');
            if (icone) {
                icone.className = barraFofoca.classList.contains('encolhida') ?
                    'fas fa-comment-dots' : 'fas fa-times';
            }
            if (!barraFofoca.classList.contains('encolhida')) {
                setTimeout(() => {
                    if (campoTexto) campoTexto.focus();
                }, 80);
            }
        };

        window.irParaMensagem = function(commentId) {
            const element = document.getElementById('comentario-' + commentId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                element.classList.add('comentario-highlight');
                setTimeout(() => {
                    element.classList.remove('comentario-highlight');
                }, 2600);
            } else {
                console.warn("Elemento não encontrado: comentario-" + commentId);
            }
        };

        // ============================================================
        // 🔥 FUNÇÕES DE RESPOSTA (com classe CSS)
        // ============================================================
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
            if (typeof esconderSugestoes === 'function') esconderSugestoes();
            verificarConteudo();
        };

        // ============================================================
        // 🔥 BOTÃO DE ANEXAR IMAGEM
        // ============================================================
        const btnAnexarImg = document.getElementById('btn-anexar-img');
        if (btnAnexarImg && inputFile) {
            btnAnexarImg.addEventListener('click', () => inputFile.click());
        }

        // ============================================================
        // 🔥 GAVETA DE OPÇÕES
        // ============================================================
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

        // ============================================================
        // 🔥 ENVIO DO COMENTÁRIO
        // ============================================================
        if (form && btnEnviar) {
            btnEnviar.addEventListener('click', function(e) {
                e.preventDefault();
                const textoAtual = campoTexto ? campoTexto.value.trim() : '';

                let temAnexo = false;
                if (typeof AnexosManager !== 'undefined' && AnexosManager.anexos) {
                    temAnexo = AnexosManager.anexos.length > 0;
                } else {
                    const gifInput = document.querySelector('input[name="gif_url"]');
                    temAnexo = (inputFile && inputFile.files && inputFile.files.length > 0) ||
                        (gifInput && gifInput.value !== '');
                }

                if (textoAtual === '' && !temAnexo) {
                    if (typeof mostrarFeedback === 'function') {
                        mostrarFeedback("⚠️ Escreva algo ou adicione uma imagem antes de enviar.", 'erro');
                    } else {
                        alert("Escreva algo ou adicione uma imagem antes de enviar.");
                    }
                    return;
                }

                atualizarHiddenPrefs();

                let formData;
                if (typeof AnexosManager !== 'undefined' && AnexosManager.prepararFormData) {
                    formData = AnexosManager.prepararFormData(form);
                } else {
                    formData = new FormData(form);
                    if (inputFile.files[0]) {
                        formData.append('imagem_comentario', inputFile.files[0]);
                    }
                    const gifInput = document.querySelector('input[name="gif_url"]');
                    if (gifInput && gifInput.value) {
                        formData.append('gif_url', gifInput.value);
                    }
                }

                const btn = btnEnviar;
                const originalIcon = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                if (typeof limparFeedback === 'function') limparFeedback();

                fetch('enviar-comentario.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async response => {
                        const text = await response.text();
                        if (!response.ok) throw new Error("Erro HTTP " + response.status + ": " + text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error("Resposta não é JSON: " + text);
                        }
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            cancelarResposta();
                            const container = document.querySelector('.lista-comentarios-social');
                            container.insertAdjacentHTML('beforeend', data.html);
                            container.lastElementChild.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });
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

                            if (typeof limparFeedback === 'function') limparFeedback();
                            gaveta.style.display = 'none';
                            gavetaAberta = false;
                            verificarConteudo();
                        } else {
                            if (typeof mostrarFeedback === 'function') {
                                mostrarFeedback("Erro: " + data.message, 'erro');
                            } else {
                                alert("Erro: " + data.message);
                            }
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof mostrarFeedback === 'function') {
                            mostrarFeedback("ERRO: " + err.message, 'erro');
                        } else {
                            alert("ERRO: " + err.message);
                        }
                    })
                    .finally(() => {
                        btn.innerHTML = originalIcon;
                        btn.disabled = false;
                    });
            });
        }

        // ============================================================
        // 🔥 CONTADOR DE CARACTERES E AUTO-ALTURA
        // ============================================================
        if (campoTexto) {
            campoTexto.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if (contadorChar) {
                    const max = 500;
                    const atual = this.value.length;
                    contadorChar.textContent = (max - atual);
                }
                verificarConteudo();
            });
        }

        // ============================================================
        // 🔥 OBSERVER PARA LIGHTBOX (comentários existentes)
        // ============================================================
        const observerLightbox = new MutationObserver(() => initLightbox());
        const listaComentarios = document.querySelector('.lista-comentarios-social');
        if (listaComentarios) observerLightbox.observe(listaComentarios, {
            childList: true,
            subtree: true
        });
        document.addEventListener('DOMContentLoaded', initLightbox);

        // ============================================================
        // 🔥 INICIALIZA O ESTADO DO BOTÃO DE ENVIAR
        // ============================================================
        verificarConteudo();

    <?php endif; // fim do bloco de comentários ativos 
    ?>

    // ============================================================
    // 🔥 INICIALIZA O HEADER MANAGER (APÓS O DOM ESTAR PRONTO)
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof HeaderManager !== 'undefined' && HeaderManager.init) {
            HeaderManager.init();
            console.log('[HEADER] HeaderManager inicializado.');
        } else {
            console.warn('[HEADER] HeaderManager não encontrado – verifique o fenda-main.js');
        }
    });
</script>