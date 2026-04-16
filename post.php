<?php
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// 1. Pegamos o ID da URL de forma segura
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    header("Location: feed.php");
    exit();
}

// 2. Buscamos o post específico
$stmt = $conn->prepare("SELECT * FROM mensagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
// Variáveis para as Vibes
$vibe_default = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
$cor_default = $dados['pref_cor_padrao'] ?? '#70cde4';

if (!$post) {
    die("<main> <style> body { font-size:2.3rem; color: white; text-align: center; padding-top: 50px; } </style> <p> Ops...Spotted não encontrado!</p> </main>");
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

                <div class="customizacao-post" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="font-size: 0.8rem; color: #ccc;">Vibe do Card:</span>
                        <select name="pref_vibe_comentario" class="input-fenda" style="padding: 5px; font-size: 0.8rem;">
                            <option value="vibe-glass">Padrão (Vidro)</option>
                            <option value="vibe-neon">Neon (Preto Profundo)</option>
                            <option value="vibe-dark">Dark (Eigengrau)</option>
                            <option value="vibe-light">Light (Solar)</option>
                        </select>

                        <span style="font-size: 0.8rem; color: #ccc; margin-left: 10px;">Cor da Borda:</span>
                        <input type="color" name="pref_cor_borda" value="#70cde4" style="border: none; width: 30px; height: 30px; cursor: pointer; background: none;">
                    </div>
                </div>

                <textarea name="comentario"
                    class="textarea-fenda"
                    placeholder="Conte a fofoca aqui..."
                    maxlength="600"
                    required> </textarea>
                <small id="char-count" style="color: var(--dourado); opacity: 0.6; float: right;">600 caracteres restantes</small>

                <?php
                $exibir_nome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? null;
                if ($exibir_nome): ?>
                    <button type="submit" class="btn-enviar-fenda">Mandar mensagem como @<?php echo htmlspecialchars($exibir_nome); ?> </button>
                <?php else: ?>
                    <button type="submit" class="btn-enviar-fenda anonimo">Mensagem Anônima</button>
                <?php endif; ?>
            </form>
        </section>

        <div class="lista-comentarios-social">
            <?php

            if (isset($_SESSION['usuario'])) {
                $sql_u = "SELECT pref_vibe_padrao, pref_cor_padrao FROM usuarios WHERE username = ?";
                $stmt_u = $conn->prepare($sql_u);
                $stmt_u->bind_param("s", $_SESSION['usuario']);
                $stmt_u->execute();
                $user_prefs = $stmt_u->get_result()->fetch_assoc();

                if ($user_prefs) {
                    $vibe_default = $user_prefs['pref_vibe_padrao'] ?? 'vibe-glass';
                    $cor_default = $user_prefs['pref_cor_padrao'] ?? '#70cde4';
                }
            }

            //  LISTAR OS COMENTÁRIOS DA MENSAGEM 
            $sql_c = "SELECT c.* FROM comentarios c WHERE c.id_mensagem = ? ORDER BY c.id ASC";
            $stmt_c = $conn->prepare($sql_c);
            $stmt_c->bind_param("i", $id);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result();

            if ($res_c->num_rows > 0):
                while ($c = $res_c->fetch_assoc()):
                    // Pega a vibe e a cor salvas no COMENTÁRIO (Vibe do Momento)
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
                            <span class="comentario-data">🕒 <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?></span>
                        </div>

                        <p class="comentario-texto"><?php echo formatarMencoesGeral($c['comentario']); ?></p>

                        <?php if (empty($c['parent_id'])): ?>
                            <div style="text-align: right; width: 100%;">
                                <button onclick="prepararResposta(<?php echo $c['id']; ?>, '<?php echo $c['usuario_nome']; ?>')"
                                    class="btn-responder-fenda">
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
    // Função para preencher o ID do pai e focar no campo de texto
    function prepararResposta(id, autor) {
        document.getElementById('input_parent_id').value = id;
        const campo = document.querySelector('.textarea-fenda');
        const nomeAutor = autor ? "@" + autor : "Anônimo";
        campo.placeholder = "Respondendo a " + nomeAutor + "...";
        campo.focus();

        // Scroll suave até o formulário para facilitar a vida no celular
        document.getElementById('fofocar').scrollIntoView({
            behavior: 'smooth'
        });
    }

    window.onload = function() {
        if (window.location.search.includes('comentario=sucesso') || document.referrer.includes('enviar-comentario')) {
            document.querySelector('.lista-comentarios-social').scrollIntoView({
                behavior: 'smooth'
            });
        }
    };

    const textarea = document.querySelector('.textarea-fenda');
    const count = document.getElementById('char-count');

    textarea.addEventListener('input', function() {
        const restantes = 600 - this.value.length;
        count.textContent = restantes + " caracteres restantes";

        if (restantes < 50) {
            count.style.color = "#ff4444"; // Fica vermelho quando tá acabando
        } else {
            count.style.color = "var(--dourado)";
        }
    });
</script>

<?php include 'includes/footer.php'; ?>