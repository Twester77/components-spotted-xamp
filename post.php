<?php
include_once 'conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    die("<main> <style> body { font-size:2.1rem; color: white; text-align: center; padding-top: 50px; } </style> <p> Ops...Spotted não encontrado!</p> </main>");
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
                <p class="post-content-focado"><?php echo htmlspecialchars($post['mensagem']); ?></p>
            </div>
        </article>

        <section class="sessao-fofoca-focada" id="fofocar">
            <h3 class="titulo-fofoca">Opino ou prefiro não opinar?</h3>

            <form action="enviar-comentario.php" method="POST" class="form-fenda">
                <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
                <input type="hidden" name="parent_id" id="input_parent_id" value="">

                <div class="customizacao-post" style="display: flex; flex-direction: column;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="font-size: 0.85rem; color: #ccc;">Vibe do Card:</span>
                        <select name="pref_vibe_comentario" class="input-fenda" style="padding: 5px; font-size: 0.8rem;">
                            <option value="vibe-glass" <?php echo ($vibe_default == 'vibe-glass') ? 'selected' : ''; ?>>Padrão (Vidro)</option>
                            <option value="vibe-neon" <?php echo ($vibe_default == 'vibe-neon') ? 'selected' : ''; ?>>Neon (Preto Profundo)</option>
                            <option value="vibe-dark" <?php echo ($vibe_default == 'vibe-dark') ? 'selected' : ''; ?>>Dark (Eigengrau)</option>
                            <option value="vibe-light" <?php echo ($vibe_default == 'vibe-light') ? 'selected' : ''; ?>>Light (Solar)</option>
                        </select>

                        <span style="font-size: 0.8rem; color: #ccc; margin-left: 10px;">Cor da Borda:</span>
                        <input type="color" name="pref_cor_borda" value="<?php echo $cor_default; ?>" style="border: none; width: 30px; height: 30px; cursor: pointer; background: none;">
                    </div>
                </div>

                <textarea name="comentario" class="textarea-fenda" placeholder="Conte a fofoca aqui..." maxlength="600" required></textarea>
                <small id="char-count" style="color: var(--dourado); opacity: 0.7; float: right;">600 caracteres restantes</small>

                <?php
                $exibir_nome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? null;
                if ($exibir_nome): ?>
                    <button type="submit" class="btn-enviar-fenda">Mandar mensagem como @<?php echo htmlspecialchars($exibir_nome); ?> </button>
                <?php else: ?>
                    <button type="submit" class="btn-enviar-fenda anonimo">Responder como Visitante</button>
                <?php endif; ?>
            </form>
        </section>

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
            ?>
                    <div class="comentario-item <?php echo $vibe . ' ' . $classe_filho; ?>"
                        style="--cor-borda-glow: <?php echo $cor_borda; ?>; border-left-color: <?php echo $cor_borda; ?> !important;">
                        <div class="comentario-meta">
                            <strong class="comentario-autor" style="color: <?php echo $cor_borda; ?>;">
                                <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                            </strong>
                            <span class="comentario-data"> <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?></span>
                        </div>

                        <p class="comentario-texto"><?php echo formatarMencoes($c['comentario']); ?></p>

                        <?php if (empty($c['parent_id'])): ?>
                            <div style="text-align: right; width: 100%;">
                                <button onclick="prepararResposta('<?php echo $c['id']; ?>', '<?php echo htmlspecialchars($c['usuario_nome'] ?? 'Anonimo'); ?>')" class="btn-responder-fenda">
                                    <i class="fas fa-reply"></i> Responder
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
            <?php
                endwhile;
            else: ?>
                <p class="sem-comentarios">Ninguém fofocou nada ainda...</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function prepararResposta(id, autor) {
        const inputParent = document.getElementById('input_parent_id');
        if(inputParent) inputParent.value = id;
        const campo = document.querySelector('.textarea-fenda');
        if (campo) {
            const nomeLimpo = autor ? autor.replace(/\s+/g, '') : "Anonimo";
            campo.value = "@" + nomeLimpo + " " + campo.value;
            campo.placeholder = "Respondendo a @" + nomeLimpo + "...";
            campo.focus();
            document.getElementById('fofocar').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    const textarea = document.querySelector('.textarea-fenda');
    const count = document.getElementById('char-count');
    if(textarea && count) {
        textarea.addEventListener('input', function() {
            const restantes = 600 - this.value.length;
            count.textContent = restantes + " caracteres restantes";
            count.style.color = (restantes < 50) ? "#ff4444" : "var(--dourado)";
        });
    }

   const swipeAtivado = <?php echo $swipeAtivado; ?>;
   if (swipeAtivado == 1) {
        let touchstartX = 0;
        let touchX = 0;
        document.querySelectorAll('.comentario-item').forEach(item => {
            item.addEventListener('touchstart', e => {
                touchstartX = e.touches[0].clientX;
                item.style.transition = "none";
            }, {passive: true});
            item.addEventListener('touchmove', e => {
                touchX = e.touches[0].clientX;
                let deslocamento = touchX - touchstartX;
                if (deslocamento > 0 && deslocamento < 100) {
                    item.style.setProperty('transform', `translateX(${deslocamento}px)`, 'important');
                }
            }, {passive: true});
            item.addEventListener('touchend', e => {
                let deslocamentoFinal = touchX - touchstartX;
                item.style.transition = "transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)";
                if (deslocamentoFinal > 70) {
                    const btn = item.querySelector('.btn-responder-fenda');
                    if (btn) btn.click();
                }
                item.style.setProperty('transform', 'translateX(0)', 'important');
            });
        });
   }

document.querySelector('.form-fenda').addEventListener('submit', function(e) {
    e.preventDefault(); 

    const formData = new FormData(this);
    const btn = this.querySelector('.btn-enviar-fenda');
    const originalText = btn.innerText;

    btn.innerText = "Enviando...";
    btn.disabled = true;

    fetch('enviar-comentario.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Erro na rede');
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            const container = document.querySelector('.lista-comentarios-social');
            const novoComentario = document.createElement('div');
            
            const vibe = formData.get('pref_vibe_comentario');
            const cor = formData.get('pref_cor_borda');
            const texto = formData.get('comentario');
            
            const nomeSessao = "<?php echo $_SESSION['usuario_nome'] ?? ''; ?>";
            const autor = nomeSessao ? `@${nomeSessao}` : "👤 Visitante";

            novoComentario.className = `comentario-item ${vibe}`;
            novoComentario.style.cssText = `--cor-borda-glow: ${cor}; border-left-color: ${cor} !important; opacity: 0; transform: translateY(-20px); transition: all 0.5s ease;`;
            
            novoComentario.innerHTML = `
                <div class="comentario-meta">
                    <strong class="comentario-autor" style="color: ${cor};">${autor}</strong>
                    <span class="comentario-data">Agora mesmo</span>
                </div>
                <p class="comentario-texto">${texto}</p>
            `;

            const semComentarios = container.querySelector('.sem-comentarios');
            if (semComentarios) semComentarios.remove();
            
            container.prepend(novoComentario);
            
            setTimeout(() => {
                novoComentario.style.opacity = '1';
                novoComentario.style.transform = 'translateY(0)';
            }, 10);

            this.reset();
            document.getElementById('input_parent_id').value = "";
            document.getElementById('char-count').textContent = "600 caracteres restantes";
            btn.innerText = originalText;
            btn.disabled = false;
            
        } else {
            alert("Erro: " + data.message);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error("Erro no AJAX:", err);
        alert("Erro ao conectar com o servidor.");
        btn.innerText = originalText;
        btn.disabled = false;
    });
});
</script>

<?php include 'includes/footer.php'; ?>