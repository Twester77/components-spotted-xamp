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

if (!$post) {
    die("<main> <style = font-size:2.8rem; <p> Ops...Spotted não encontrado!</p> </style> </main>");
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

                <textarea name="comentario" class="textarea-fenda" placeholder="Conte a fofoca aqui... use @ para marcar alguém!" required></textarea>

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
            $stmt_c = $conn->prepare("SELECT * FROM comentarios WHERE id_mensagem = ? ORDER BY id DESC");
            $stmt_c->bind_param("i", $id);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result();

            if ($res_c->num_rows > 0):
                while ($c = $res_c->fetch_assoc()): ?>
                    <div class="comentario-item">
                        <div class="comentario-meta">
                            <strong class="comentario-autor">
                                <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                            </strong>
                            <span class="comentario-data">🕒 <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?></span>
                        </div>
                        <p class="comentario-texto"><?php echo formatarMencoesGeral($c['comentario']); ?></p>
                    </div>
                <?php endwhile;
            else: ?>
                <p class="sem-comentarios">Ninguém fofocou nada ainda... Seja o primeiro!</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    // Se a URL tiver um parâmetro de sucesso ou se acabamos de postar
    // Vamos fazer a página deslizar suavemente até a lista de comentários
    window.onload = function() {
        if (window.location.search.includes('comentario=sucesso') || document.referrer.includes('enviar-comentario')) {
            document.querySelector('.lista-comentarios-social').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }
    };
</script>

<?php include 'includes/footer.php'; ?>