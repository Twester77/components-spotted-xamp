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
$query = "SELECT * FROM mensagens WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    echo "<main><p>Spotted não encontrado!</p></main>";
    include 'includes/footer.php';
    exit();
}
?>

<main class="container-feed">
    <article class="spotted-card <?php echo $post['categoria']; ?>">
        <div class="card-header">
            <span class="category-tag">#<?php echo strtoupper($post['categoria']); ?></span>
            <span class="post-time"><?php echo date('d/m', strtotime($post['data_post'])); ?></span>
        </div>
        
        <div class="card-body">
            <p class="post-content"><?php echo htmlspecialchars($post['mensagem']); ?></p>
        </div>
    </article>

    <section class="sessao-publicar">
        <h3 style="margin-bottom: 15px; color: #ffbc00;"> Opino ou prefiro não opinar?</h3>
        
        <form action="enviar-comentario.php" method="POST" class="form-fofoca">
            <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
            <textarea name="comentario" placeholder="" required 
                      style="width: 100%; min-height: 80px; padding: 10px; border-radius: 8px; margin-bottom: 10px;"></textarea>
            <button type="submit" class="btn-lancar">Lançar Fofoca</button>
        </form>

        <hr style="margin: 25px 0; opacity: 0.1;">

        <div class="lista-comentarios">
            <?php
            $query_c = "SELECT * FROM comentarios WHERE id_mensagem = ? ORDER BY id DESC";
            $stmt_c = $conn->prepare($query_c);
            $stmt_c->bind_param("i", $id);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result();

            if ($res_c->num_rows > 0):
                while ($c = $res_c->fetch_assoc()): ?>
                    <div class="comentario-item">
                        <p><?php echo htmlspecialchars($c['comentario']); ?></p>
                    <small>Postado em: <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?></small>
                    </div>
                <?php endwhile;
            else: ?>
                <p class="sem-fofoca" style="text-align: center; opacity: 0.5;">Ninguém fofocou nada ainda... 🤐</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>