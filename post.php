<?php
include_once 'conexao.php';

// --- LÓGICA DE EXCEÇÃO PARA PERDIDOS ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscamos a categoria antes de validar a sessão
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

// Só redireciona se NÃO estiver logado E NÃO for categoria perdidos
if (!isset($_SESSION['usuario_id']) && !$is_perdidos) {
    header("Location: index.php");
    exit();
}

// Puxar dados do usuário logado (apenas se houver um)
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

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

if ($id == 0) {
    header("Location: feed.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM mensagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    die("<main> <style> body { font-size:2.1rem; color: white; text-align: center; padding-top: 50px; } </style> <p>Ops... Spotted não encontrado!</p> </main>");
}
?>

<main class="container-post-foco">
    <div class="box-post-central">
        
        <a href="feed.php" class="btn-voltar-fenda">
            <i class="fas fa-arrow-left"></i> Voltar para o Feed
        </a>

        <article class="spotted-card <?php echo $post['categoria']; ?> card-focado">
            <div class="card-header">
                <span class="category-tag">#<?php echo strtoupper($post['categoria']); ?></span>
                <span class="post-time"><?php echo date('d/m', strtotime($post['data_post'])); ?></span>
            </div>
            <div class="card-body">
                <p class="post-content-focado"><?php echo nl2br(htmlspecialchars($post['mensagem'])); ?></p>
            </div>
        </article>

        <div class="lista-comentarios-social">
            <?php
            $sql_c = "SELECT c.* FROM comentarios c WHERE c.id_mensagem = ? ORDER BY c.id ASC";
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

                    $user_alvo_limpo = !empty($c['username']) ? $c['username'] : (!empty($c['usuario_nome']) ? $c['usuario_nome'] : 'Habitante');
            ?>
                    <div class="comentario-item <?php echo $vibe . ' ' . $classe_filho . ' ' . $sou_eu; ?>"
                        id="comentario-<?php echo $c['id']; ?>"
                        style="--cor-borda-glow: <?php echo $cor_borda; ?>;">

                        <div class="comentario-meta">
                            <strong class="comentario-autor" style="color: var(--cor-borda-glow);">
                                <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                            </strong>
                            <span class="comentario-data"><?php echo date('H:i', strtotime($c['data_comentario'])); ?></span>
                        </div>

                        <?php if ($classe_filho): ?>
                            <div class="reply-indicator">
                                <i class="fas fa-reply"></i> Respondendo a <?php echo htmlspecialchars($user_alvo_limpo); ?>
                            </div>
                        <?php endif; ?>

                        <p class="comentario-texto"><?php echo nl2br(formatarMencoes($c['comentario'])); ?></p>
                        
                        <div class="acoes-bolha" style="text-align: right;">
                            <button onclick="prepararResposta('<?php echo $id_vincular; ?>', '<?php echo htmlspecialchars($user_alvo_limpo); ?>')" class="btn-responder-bolha">
                                RESPONDER
                            </button>
                        </div>
                    </div> <?php
                endwhile; // Fim do loop original
            else: ?>
                <p class="sem-comentarios">Ninguém fofocou nada ainda... Seja o primeiro!</p>
            <?php endif; ?>
        </div> </div> </main>

<!-- FORMULÁRIO FIXO ( porém retrátil ) ESTILO WHATSAPP (NO RODAPÉ)       -->

<section class="sessao-fofoca-focada" id="fofocar">
    <button type="button" id="toggle-chat-barra" onclick="toggleBarraFofoca()">
        <i class="fas fa-comment-dots"></i>
    </button>
    <div class="chat-input-container">
        <div class="customizacao-rapida">
            <select name="pref_vibe_comentario" id="vibe-comentario" class="input-mini">
                <option value="vibe-glass" <?php echo ($vibe_default == 'vibe-glass') ? 'selected' : ''; ?>> Glass</option>
                <option value="vibe-neon" <?php echo ($vibe_default == 'vibe-neon') ? 'selected' : ''; ?>> Neon</option>
                <option value="vibe-dark" <?php echo ($vibe_default == 'vibe-dark') ? 'selected' : ''; ?>>Dark</option>
                <option value="vibe-light" <?php echo ($vibe_default == 'vibe-light') ? 'selected' : ''; ?>>Light</option>
            </select>
            <input type="color" name="pref_cor_borda" id="cor-borda" value="<?php echo $cor_default; ?>" class="color-mini">
        </div>

        <form action="enviar-comentario.php" method="POST" class="form-chat" id="form-comentario">
            <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
            <input type="hidden" name="parent_id" id="input_parent_id" value="">

            <div class="chat-input-wrapper">
                <textarea name="comentario" class="textarea-chat" placeholder="Digite sua mensagem..." maxlength="500" required></textarea>
                <button type="submit" class="btn-enviar-chat">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="chat-footer-info">
                <span id="char-count" class="char-counter">500</span>
                <span class="resposta-indicador" id="resposta-indicador" style="display: none;">
                    <i class="fas fa-reply"></i> Respondendo...
                    <button type="button" onclick="cancelarResposta()" class="cancelar-resposta">✖</button>
                </span>
            </div>
        </form>
    </div>
</section>

<script>
    // --- ENGINE CENTRAL DE INTERAÇÃO DA FENDA ---

    // Seletores Únicos (Evita erro de undefined)
    const campoTexto = document.querySelector('.textarea-chat');
    const contadorChar = document.getElementById('char-count');
    const formFofoca = document.getElementById('form-comentario');
    const barraFofoca = document.querySelector('.sessao-fofoca-focada');

    // 1. CONTADOR DE CARACTERES
    if (campoTexto && contadorChar) {
        campoTexto.addEventListener('input', function() {
            const restantes = 500 - this.value.length;
            contadorChar.textContent = restantes;
            contadorChar.style.color = (restantes < 50) ? "#ff4444" : "#888";
        });
    }

    // 2. CONTROLE DA BARRA RETRÁTIL
    window.toggleBarraFofoca = function() {
        const icone = document.querySelector('#toggle-chat-barra i');
        if (!barraFofoca) return;

        barraFofoca.classList.toggle('encolhida');
        if (icone) {
            icone.className = barraFofoca.classList.contains('encolhida') ?
                'fas fa-comment-dots' : 'fas fa-times';
        }
    };

    // 3. SISTEMA DE RESPOSTA E CANCELAMENTO (Unificado)
    window.prepararResposta = function(id, username) {
        const inputParent = document.getElementById('input_parent_id');
        const indicador = document.getElementById('resposta-indicador');

        if (barraFofoca && barraFofoca.classList.contains('encolhida')) toggleBarraFofoca();

        if (inputParent) inputParent.value = id;
        if (indicador) {
            indicador.style.display = 'flex';
            // Procura o span interno ou usa o próprio indicador
            const info = indicador.querySelector('span') || indicador;
            info.innerHTML = `<i class="fas fa-reply"></i> Respondendo a ${username}...`;
        }

        if (campoTexto) {
            campoTexto.placeholder = "Escreva sua resposta...";
            campoTexto.focus();
        }
    };

    window.cancelarResposta = function() {
        const inputParent = document.getElementById('input_parent_id');
        const indicador = document.getElementById('resposta-indicador');

        if (inputParent) inputParent.value = '';
        if (indicador) indicador.style.display = 'none';
        if (campoTexto) {
            campoTexto.placeholder = "Digite sua mensagem...";
            campoTexto.focus();
        }
    };

    // 4. ENVIO AJAX (Blindado)
    if (formFofoca) {
        formFofoca.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('.btn-enviar-chat');
            const originalIcon = btn.innerHTML;
            const formData = new FormData(this);

            btn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
            btn.disabled = true;

            fetch('enviar-comentario.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const container = document.querySelector('.lista-comentarios-social');

                        // 1. Pegamos o texto que o usuário digitou
                        const textoOriginal = formData.get('comentario');

                        // 2. A MARRETA: Troca o Enter (\n) por <br> na hora
                        const textoFormatado = textoOriginal.replace(/\n/g, '<br>');

                        // 3. Resetamos a interface
                        cancelarResposta();
                        this.reset();
                        if (contadorChar) contadorChar.textContent = '600';

                        // 4. Se você NÃO quiser dar reload, você criaria o elemento aqui:
                        // Mas como você está usando reload, o nl2br do PHP vai brilhar.
                        // Se preferir manter o reload por segurança:
                        location.reload();

                    } else {
                        alert("Erro: " + data.message);
                    }
                })
                .finally(() => {
                    btn.innerHTML = originalIcon;
                    btn.disabled = false;
                });
        });
    }
</script>

<?php include 'includes/footer.php'; ?>