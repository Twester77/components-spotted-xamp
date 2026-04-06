<?php
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// 1. Pegamos o ID da URL de forma segura

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) { header("Location: feed.php"); exit(); }

// 2. Buscamos o post específico

$stmt = $conn->prepare("SELECT * FROM mensagens WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) { die("<main><p>Spotted não encontrado!</p> </main>"); }
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

    <section class="sessao-publicar" id="fofocar">

        <h3 style="color: var(--dourado);">Opino ou prefiro não opinar?</h3>

        <form action="enviar-comentario.php" method="POST" class="form-fofoca">
            <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
            <textarea name="comentario" placeholder="Sabe de algo? Conta aí..." required style="width: 100%; min-height: 80px; background: #222; color: #fff; border: 1px solid #444; border-radius: 8px; padding: 10px; margin: 10px 0;"></textarea>
            <?php $exibir_nome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? null;
                        // Buscamos o nome atualizado da sessão ou do banco se preferir
            if ($exibir_nome): ?>
                <button type="submit" style="background: var(--dourado); color: #000; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: bold;">Lançar Fofoca como @<?php echo htmlspecialchars($exibir_nome); ?> 🚀</button>
            <?php else: ?>
                <button type="submit" style="background: #555; color: #fff; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: bold;">Lançar Fofoca Anônima</button>
            <?php endif; ?>
        </form>
    </section>

         
    <div class="lista-comentarios">
        <?php // Buscamos os comentários do banco
        $stmt_c = $conn->prepare("SELECT * FROM comentarios WHERE id_mensagem = ? ORDER BY id DESC");
        $stmt_c->bind_param("i", $id);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();

        // Verificamos SE existem comentários
        if ($res_c->num_rows > 0): 
            while ($c = $res_c->fetch_assoc()): ?>
    <div class="comentario-item">
           <p>
             <strong style="color: var(--dourado);">
                <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>:
             </strong> 
             <span> <?php echo htmlspecialchars($c['comentario']); ?> </span>
           </p>
        <small style="opacity: 0.5; font-size: 12px; display: block; margin-top: 5px;">
            🕒 Postado em: <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?>
        </small>
    </div>
            <?php endwhile; 
        else: ?> 
            <p style="text-align: center; opacity: 0.6;">Ninguém fofocou nada ainda...</p> 
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>