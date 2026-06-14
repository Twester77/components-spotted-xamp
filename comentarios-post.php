<?php
include_once 'conexao.php';
/* ==========================================================================
   Deep, o Marreteiro – esteve aqui e não deixou ninguém desistir.
   Cada linha, cada debug, cada madrugada valeram a pena.
   A Fenda está viva. Até a próxima travessia, companheiro. 💚
   ========================================================================== */
// --- LÓGICA DE EXCEÇÃO PARA PERDIDOS : 🚨 CURTO-CIRCUITO DE SEGURANÇA---

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    header("Location: feed.php");
    exit();
}

// --- LÓGICA DE EXCEÇÃO PARA PERDIDOS ---
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

if (!isset($_SESSION['usuario_id']) && !$is_perdidos) {
    header("Location: index.php");
    exit();
}

// Puxar dados do usuário logado
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

$is_post_page = true; // DEFINA A VARIÁVEL ANTES DO INCLUDE
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

$stmt = $conn->prepare("SELECT m.*, u.username, u.foto FROM mensagens m LEFT JOIN usuarios u ON m.usuario_id = u.id WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    die("<main> <style> body { font-size:2.1rem; color: white; text-align: center; padding-top: 50px; } </style> <p>Ops... Spotted não encontrado!</p> </main>");
}

// ==================================================
// 1. BUSCAR REAÇÕES DETALHADAS PARA ESTE POST
// ==================================================
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

// 2. REAÇÕES DO USUÁRIO LOGADO (para marcar como "voted")
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
$sql_count = "SELECT COUNT(*) as total FROM comentarios WHERE id_mensagem = ?";
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
    /* Esconde o cabeçalho e o texto do rodapé APENAS nesta página */
    .header-visivel,
    .footer-texto-institucional,
    .footer-global {
        display: none !important;

    }
</style>

<!-- PREVIEW (CORTINA) – aparece primeiro -->
<div class="preview-overlay" id="previewOverlay">
    <div class="preview-card">
        <a href="feed.php" class="btn-fechar-post">✖</a>
        <div class="preview-categoria">#<?php echo strtoupper($post['categoria']); ?></div>
        <div class="preview-avatar">
            <?php
            if ($post['categoria'] === 'anonimo') {
                echo '<img src="imagensfoto/anonimo-default.webp" class="avatar-p">';
                echo '<span class="preview-nome">Habitante Anônimo</span>';
            } else {
                $foto_autor = !empty($post['foto']) ? 'uploads/' . $post['foto'] : 'imagensfoto/default.webp';
                echo '<img src="' . $foto_autor . '" class="avatar-p">';
                echo '<span class="preview-nome">@' . htmlspecialchars($post['username']) . '</span>';
            }
            ?>
        </div>
        <div class="preview-mensagem">
            <?php echo nl2br(htmlspecialchars(mb_substr($post['mensagem'], 0, 150))) . (strlen($post['mensagem']) > 150 ? '...' : ''); ?>
        </div>
        <?php if (!empty($post['imagem_url'])): ?>
            <div class="preview-imagem">
                <?php if (filter_var($post['imagem_url'], FILTER_VALIDATE_URL)): ?>
                    <img src="<?php echo htmlspecialchars($post['imagem_url']); ?>" alt="Preview da imagem">
                <?php else: ?>
                    <img src="postagens/<?php echo htmlspecialchars($post['imagem_url']); ?>" alt="Preview da imagem">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="preview-engajamento">
            <span><i class="fas fa-comment"></i> <?php echo $total_comentarios; ?></span>
            <span><i class="fas fa-heart"></i> <?php echo $total_reacoes; ?></span>
        </div>
        <button class="preview-botao" id="btnRevelarConteudo"> Comentar</button>
    </div>
</div>

<!-- LINGOTE (CONTEÚDO) – escondido inicialmente -->
<div class="lingote-container" id="lingoteContainer">
    <div class="layout-wrapper">
        <header class="sticky-header">
            <div class="box-post-central">
                <!-- WRAPPER DE ISOLAMENTO ESTÁTICO -->
                <div class="fenda-estatica-context">
                    <a href="feed.php" class="btn-voltar-fenda">
                        <i class="fas fa-arrow-left"></i> Voltar para o Feed
                    </a>

                    <!-- CARD PRINCIPAL (agora isolado) -->
                    <article id="card-post-header" class="spotted-card <?php echo $post['categoria']; ?> card-focado">
                        <div class="card-header">
                            <span class="category-tag">#<?php echo strtoupper($post['categoria']); ?></span>
                            <span class="post-time"><?php echo date('d/m', strtotime($post['data_post'])); ?></span>
                        </div>
                        <div class="card-body">
                            <p class="post-content-focado"><?php echo nl2br(htmlspecialchars($post['mensagem'])); ?></p>
                            <?php if (!empty($post['imagem_url'])): ?>
                                <div class="container-img-post">
                                    <?php if (filter_var($post['imagem_url'], FILTER_VALIDATE_URL)): ?>
                                        <img src="<?php echo htmlspecialchars($post['imagem_url']); ?>" class="spotted-card-img" alt="Imagem do Post - Seção Comentários">
                                    <?php else: ?>
                                        <img src="postagens/<?php echo htmlspecialchars($post['imagem_url']); ?>" class="spotted-card-img" alt="Imagem do Post - Seção Comentários">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>

                    <!-- SEÇÃO DE REAÇÕES (ID que o JS espera) -->
                    <div id="reacoes-post-<?php echo $post_id_atual; ?>" class="reacoes-gravadas">
                        <?php if (!empty($reacoes_detalhes)): ?>
                            <?php foreach ($reacoes_detalhes as $tipo => $total): ?>
                                <span class="reacao-item <?php echo in_array($tipo, $minhas_reacoes) ? 'voted' : ''; ?>">
                                    <?php echo $tradutor[$tipo] ?? '👍'; ?> <?php echo $total; ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="reacao-placeholder"> Ninguém reagiu ainda. Seja o primeiro!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        <button id="btn-toggle-collapse" class="btn-toggle-collapse" aria-label="Minimizar/Expandir post">
            <i class="fas fa-chevron-up"></i>
        </button>

        <!-- ÁREA DE COMENTÁRIOS (ROLAVEL) -->
        <main class="lista-scrollavel">
            <div class="lista-comentarios-social">
                <?php
                // Query com subconsulta para buscar o texto do comentário pai (resposta)
                $sql_c = "SELECT c.*, 
                          (SELECT comentario FROM comentarios WHERE id = c.parent_id) as parent_comentario
                          FROM comentarios c 
                          WHERE c.id_mensagem = ? 
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

                        // Gera o trecho do comentário original (se for resposta)
                        $trecho_resposta = '';
                        if (!empty($c['parent_id']) && !empty($c['parent_comentario'])) {
                            $texto_puro = strip_tags($c['parent_comentario']);
                            $texto_cortado = mb_substr($texto_puro, 0, 50);
                            $trecho_resposta = mb_strlen($texto_puro) > 50 ? $texto_cortado . '...' : $texto_cortado;
                        }
                ?>
                        <div class="comentario-item <?php echo $vibe . ' ' . $classe_filho . ' ' . $sou_eu; ?>"
                            id="comentario-<?php echo $c['id']; ?>"
                            style="--cor-borda-glow: <?php echo $cor_borda; ?>; <?php echo $estilo_filho; ?>">
                            <div class="comentario-meta">
                                <strong class="comentario-autor" style="color: var(--cor-borda-glow);">
                                    <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                                </strong>
                                <span class="comentario-data"><?php echo date('H:i', strtotime($c['data_comentario'])); ?></span>
                            </div>

                            <!-- Indicador de resposta (clicável) -->
                            <?php if (!empty($c['parent_id'])): ?>
                                <div class="indicador-resposta" onclick="irParaMensagem(<?php echo $c['parent_id']; ?>)">
                                    <i class="fas fa-reply"></i> <small><?php echo htmlspecialchars($trecho_resposta); ?></small>
                                </div>
                            <?php endif; ?>

                            <p class="comentario-texto"><?php echo nl2br(formatarMencoes($c['comentario'])); ?></p>

                            <?php if (!empty($c['imagem_url'])): ?>
                                <div class="comentario-media-wrapper">
                                    <?php if (filter_var($c['imagem_url'], FILTER_VALIDATE_URL)): ?>
                                        <!-- GIF externo (GIPHY) -->
                                        <img src="<?php echo htmlspecialchars($c['imagem_url']); ?>" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy">
                                    <?php else: ?>
                                        <!-- Imagem local (upload) -->
                                        <img src="comentarios/<?php echo htmlspecialchars($c['imagem_url']); ?>" class="comentario-img" alt="Imagem do comentário" loading="lazy">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="acoes-bolha">
                                <button onclick="prepararResposta(<?php echo intval($id_vincular); ?>, '<?php echo htmlspecialchars($nome_limpo_js); ?>')" class="btn-responder-bolha">
                                    RESPONDER
                                </button>
                            </div>
                        </div>
                    <?php
                    endwhile;
                else: ?>
                    <p class="sem-comentarios">Ninguém fofocou nada ainda... Seja o primeiro!</p>
                <?php endif; ?>
            </div>
        </main>

        <!-- RODAPÉ FIXO (BARRA DE COMENTÁRIOS MINIMALISTA) -->
        <footer class="fixed-input">
            <section class="sessao-fofoca-focada" id="fofocar">
                <button type="button" id="btn-attach-gaveta" class="btn-attach-gaveta" title="Mais opções">+</button>
                <textarea name="comentario" class="textarea-chat" placeholder="Digite sua mensagem..." maxlength="500" id="comentario-textarea"></textarea>
                <button type="submit" form="form-comentario" class="btn-enviar-chat">
                    <i class="fas fa-paper-plane"></i>
                </button>

                <div id="gaveta-opcoes" class="gaveta-opcoes" style="display: none;">
                    <div class="resposta-indicador" id="resposta-indicador" style="display: none;">
                        <i class="fas fa-reply"></i> <span id="texto-nome-resposta">Respondendo...</span>
                        <button type="button" onclick="cancelarResposta()" class="cancelar-resposta">✖</button>
                    </div>
                    <div class="customizacao-rapida">
                        <select name="pref_vibe_comentario" id="vibe-comentario" class="input-mini">
                            <option value="vibe-glass" <?php echo ($vibe_default == 'vibe-glass') ? 'selected' : ''; ?>>Glass</option>
                            <option value="vibe-neon" <?php echo ($vibe_default == 'vibe-neon') ? 'selected' : ''; ?>>Neon</option>
                            <option value="vibe-dark" <?php echo ($vibe_default == 'vibe-dark') ? 'selected' : ''; ?>>Dark</option>
                            <option value="vibe-light" <?php echo ($vibe_default == 'vibe-light') ? 'selected' : ''; ?>>Light</option>
                            <option value="vibe-ads" <?php echo ($vibe_default == 'vibe-ads') ? 'selected' : ''; ?>>ADS (Overclock)</option>
                        </select>
                        <input type="color" name="pref_cor_borda" id="cor-borda" value="<?php echo $cor_default; ?>" class="color-mini">
                    </div>
                    <input type="file" name="imagem_comentario" id="input-img-comentario" accept="image/*" style="display:none;">
                    <button type="button" id="btn-anexar-img" class="btn-attach-opcao"><i class="fas fa-image"></i> Imagem</button>
                    <button type="button" id="btn-gif" class="btn-attach-opcao" onclick="abrirGiphyModal()">
                        <i class="fas fa-grin-tongue-squint"></i> GIF/Sticker
                    </button>
                </div>

                <form action="enviar-comentario.php" method="POST" enctype="multipart/form-data" class="form-chat" id="form-comentario" style="display: none;">
                    <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
                    <input type="hidden" name="parent_id" id="input_parent_id" value="">
                    <input type="hidden" name="pref_vibe_comentario" id="hidden-vibe" value="">
                    <input type="hidden" name="pref_cor_borda" id="hidden-cor" value="">
                    <textarea name="comentario" id="hidden-textarea"></textarea>
                </form>

                <div class="chat-footer-info">
                    <span id="char-count" class="char-counter">500</span>
                    <span id="nome-arquivo"></span>
                    <div id="feedback-upload" style="color: #25ff25;"></div>
                </div>
            </section>
        </footer>
    </div>
</div>

<script>
    // ==================== ALTERNÂNCIA CORTINA/LINGOTE ====================
    document.addEventListener('DOMContentLoaded', function() {
        const preview = document.getElementById('previewOverlay');
        const lingote = document.getElementById('lingoteContainer');
        const btnRevelar = document.getElementById('btnRevelarConteudo');

        if (btnRevelar && preview && lingote) {
            btnRevelar.addEventListener('click', function() {
                preview.style.display = 'none';
                lingote.style.display = 'block';
                const textarea = document.querySelector('#lingoteContainer .textarea-chat');
                if (textarea) {
                    textarea.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    textarea.focus();
                }
            });
        }
    });
</script>

<script src="js/fenda-giphy.js"></script>

<?php include 'includes/footer.php'; ?>
<script src="js/fenda-mencoes.js"></script>

<script>
    // ==================== LÓGICA DE COMENTÁRIOS (ENVIO, ANEXOS, RESPOSTAS) ====================
    const barraFofoca = document.querySelector('.sessao-fofoca-focada');
    const campoTexto = document.querySelector('.textarea-chat');
    const contadorChar = document.getElementById('char-count');
    const form = document.getElementById('form-comentario');
    const btnEnviar = document.querySelector('.btn-enviar-chat');
    const btnGaveta = document.getElementById('btn-attach-gaveta');
    const gaveta = document.getElementById('gaveta-opcoes');
    const inputFile = document.getElementById('input-img-comentario');
    const nomeArquivoSpan = document.getElementById('nome-arquivo');
    const feedbackDiv = document.getElementById('feedback-upload');
    const hiddenVibe = document.getElementById('hidden-vibe');
    const hiddenCor = document.getElementById('hidden-cor');
    const hiddenTextarea = document.getElementById('hidden-textarea');
    const selectVibe = document.getElementById('vibe-comentario');
    const inputCor = document.getElementById('cor-borda');

    let gavetaAberta = false;
    let arquivoValido = true;
    const maxSizeMB = 2;
    const maxSizeBytes = maxSizeMB * 1024 * 1024;

    function atualizarHiddenPrefs() {
        if (hiddenVibe) hiddenVibe.value = selectVibe.value;
        if (hiddenCor) hiddenCor.value = inputCor.value;
        if (hiddenTextarea) hiddenTextarea.value = campoTexto.value;
    }

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

    window.prepararResposta = function(id, username) {
        const inputParent = document.getElementById('input_parent_id');
        const indicador = document.getElementById('resposta-indicador');
        const textoNome = document.getElementById('texto-nome-resposta');
        if (inputParent) inputParent.value = parseInt(id);
        if (indicador && textoNome) {
            textoNome.textContent = `Respondendo a ${username}...`;
            indicador.style.setProperty('display', 'flex', 'important');
        }
        if (campoTexto) {
            campoTexto.placeholder = "Escreva sua resposta...";
            campoTexto.focus();
        }
        if (gaveta && !gavetaAberta) {
            gaveta.style.display = 'flex';
            gavetaAberta = true;
        }
    };

    window.cancelarResposta = function() {
        const inputParent = document.getElementById('input_parent_id');
        const indicador = document.getElementById('resposta-indicador');
        if (inputParent) inputParent.value = '';
        if (indicador) indicador.style.setProperty('display', 'none', 'important');
        if (campoTexto) {
            campoTexto.value = '';
            campoTexto.placeholder = "Digite sua mensagem...";
        }
        if (contadorChar) contadorChar.textContent = '500';
        if (typeof esconderSugestoes === 'function') esconderSugestoes();
    };

    function mostrarFeedback(msg, tipo) {
        if (!feedbackDiv) return;
        feedbackDiv.textContent = msg;
        feedbackDiv.style.color = (tipo === 'erro') ? '#ff4444' : '#00ff00';
        setTimeout(() => {
            if (feedbackDiv) feedbackDiv.textContent = '';
        }, 3000);
    }

    function limparFeedback() {
        if (feedbackDiv) feedbackDiv.textContent = '';
    }

    function validarArquivo() {
        if (!inputFile || !inputFile.files.length) {
            if (nomeArquivoSpan) nomeArquivoSpan.textContent = '';
            limparFeedback();
            arquivoValido = true;
            return true;
        }
        const file = inputFile.files[0];
        const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        if (file.size > maxSizeBytes) {
            if (nomeArquivoSpan) {
                nomeArquivoSpan.textContent = file.name;
                nomeArquivoSpan.style.color = '#ff4444';
            }
            mostrarFeedback(`❌ Arquivo excede ${maxSizeMB}MB`, 'erro');
            arquivoValido = false;
            return false;
        }
        if (!tiposPermitidos.includes(file.type)) {
            if (nomeArquivoSpan) {
                nomeArquivoSpan.textContent = file.name;
                nomeArquivoSpan.style.color = '#ff4444';
            }
            mostrarFeedback('❌ Formato inválido (use JPG, PNG, WEBP ou GIF)', 'erro');
            arquivoValido = false;
            return false;
        }
        if (nomeArquivoSpan) {
            nomeArquivoSpan.textContent = file.name;
            nomeArquivoSpan.style.color = '#ccc';
        }
        mostrarFeedback('✅ Arquivo válido!', 'sucesso');
        arquivoValido = true;
        return true;
    }

    const btnAnexarImg = document.getElementById('btn-anexar-img');
    if (btnAnexarImg && inputFile) {
        btnAnexarImg.addEventListener('click', () => inputFile.click());
        inputFile.addEventListener('change', validarArquivo);
    }


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
            const temImagem = inputFile && inputFile.files && inputFile.files.length > 0;
            if (textoAtual === '' && !temImagem) {
                mostrarFeedback("⚠️ Escreva algo ou adicione uma imagem antes de enviar.", 'erro');
                return;
            }
            if (!arquivoValido) {
                mostrarFeedback("⚠️ Arquivo inválido. Verifique tamanho e formato.", 'erro');
                return;
            }
            atualizarHiddenPrefs();
            const formData = new FormData(form);
            if (inputFile.files[0]) formData.append('imagem_comentario', inputFile.files[0]);

            const btn = btnEnviar;
            const originalIcon = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            limparFeedback();

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
                        nomeArquivoSpan.textContent = '';
                        limparFeedback();
                        arquivoValido = true;
                        inputFile.value = '';
                        gaveta.style.display = 'none';
                        gavetaAberta = false;
                    } else {
                        mostrarFeedback("Erro: " + data.message, 'erro');
                    }
                })
                .catch(err => {
                    console.error(err);
                    mostrarFeedback("ERRO: " + err.message, 'erro');
                })
                .finally(() => {
                    btn.innerHTML = originalIcon;
                    btn.disabled = false;
                });
        });
    }

    if (campoTexto) {
        campoTexto.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            if (contadorChar) {
                const max = 500;
                const atual = this.value.length;
                contadorChar.textContent = (max - atual);
            }
        });
    }

    // ==================== LIGHTBOX PARA IMAGENS DOS COMENTÁRIOS ====================
    function initLightbox() {
        const imagens = document.querySelectorAll('.comentario-img');
        imagens.forEach(img => {
            img.removeEventListener('click', abrirLightbox);
            img.addEventListener('click', abrirLightbox);
        });
    }

    function abrirLightbox(e) {
        e.stopPropagation();
        const imgSrc = e.currentTarget.src;
        if (!imgSrc) return;
        const modalExistente = document.getElementById('modal-lightbox-fenda');
        if (modalExistente) modalExistente.remove();
        const modal = document.createElement('div');
        modal.id = 'modal-lightbox-fenda';
        modal.style.cssText = `position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.73); backdrop-filter:blur(6px); display:flex; justify-content:center; align-items:center; z-index:100000; cursor:pointer; opacity:0; transition:opacity 0.2s ease;`;
        const imgModal = document.createElement('img');
        imgModal.src = imgSrc;
        imgModal.style.cssText = `max-width:90%; max-height:90%; object-fit:contain; border-radius:12px; box-shadow:0 0 30px rgba(0,0,0,0.5);`;
        const fecharBtn = document.createElement('button');
        fecharBtn.innerHTML = '✖';
        fecharBtn.style.cssText = `position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:2rem; cursor:pointer; z-index:100001; font-weight:bold; text-shadow:0 0 5px black;`;
        fecharBtn.onclick = () => {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 200);
        };
        modal.appendChild(imgModal);
        modal.appendChild(fecharBtn);
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

    const observerLightbox = new MutationObserver(() => initLightbox());
    const listaComentarios = document.querySelector('.lista-comentarios-social');
    if (listaComentarios) observerLightbox.observe(listaComentarios, {
        childList: true,
        subtree: true
    });
    document.addEventListener('DOMContentLoaded', initLightbox);

    // ==================== CONTROLLER ÚNICO DE MINIMIZAÇÃO DO HEADER ====================
    function initLingoteController() {
        const container = document.getElementById('lingoteContainer');
        const btnToggle = document.getElementById('btn-toggle-collapse');
        const textarea = document.getElementById('comentario-textarea');

        if (!container || !btnToggle) return;

        btnToggle.addEventListener('click', () => {
            container.classList.toggle('minimizado');
            const icon = btnToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-chevron-up');
                icon.classList.toggle('fa-chevron-down');
            }
        });

        if (textarea) {
            textarea.addEventListener('focus', () => {
                container.classList.add('minimizado');
                const icon = btnToggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
        }
    }
</script>