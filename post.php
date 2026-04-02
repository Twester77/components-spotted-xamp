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
$query = "SELECT * FROM mensagens WHERE id = ?"; // Exemplo de Prepared Statement - 1. O esqueleto
$stmt = $conn->prepare($query); // 2. A preparação
$stmt->bind_param("i", $id); // 3. O bind, "blindagem do dado" 
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
    <h3 style="margin-bottom: 15px; color: #ffbc00;">Opino ou prefiro não opinar?</h3>
    
    <form action="enviar-comentario.php" method="POST" class="form-fofoca">
        <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">

        <textarea name="comentario" placeholder="Sabe de algo? Conta aí..." required 
          style="width: 100%; min-height: 80px; padding: 10px; border-radius: 8px; margin-bottom: 10px; background: #222; color: #fff; border: 1px solid #444;"></textarea>

        <?php if (isset($_SESSION['usuario_id'])): 
            // Buscamos o nome atualizado da sessão ou do banco se preferir
            $exibir_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : $_SESSION['nome'];
        ?>
            <button type="submit" class="btn-lancar" style="background: #ffbc00; color: #000; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%;">
                Lançar Fofoca como @<?php echo htmlspecialchars($exibir_nome); ?> 🚀
            </button>
        <?php else: ?>
            <div style="background: rgba(255, 188, 0, 0.1); padding: 15px; border-radius: 8px; border: 1px dashed #ffbc00; margin-bottom: 10px;">
                <p style="color: #ffbc00; font-size: 14px; margin-bottom: 10px; text-align: center;">
                    ⚠️ Você não está logado. Sua fofoca será enviada como <strong>Anônimo</strong>.
                </p>
                <button type="submit" class="btn-lancar" style="background: #555; color: #fff; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%;">
                    Lançar Fofoca Anônima 
                </button>
            </div>
        <?php endif; ?>
    </form>

    <hr style="margin: 25px 0; opacity: 0.1;">
    </section>

        <div class="lista-comentarios">
            <?php
            // Buscamos os comentários do banco
            $query_c = "SELECT * FROM comentarios WHERE id_mensagem = ? ORDER BY id DESC";
            $stmt_c = $conn->prepare($query_c);
            $stmt_c->bind_param("i", $id);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result();

            // Verificamos SE existem comentários
            if ($res_c->num_rows > 0): 
                while ($c = $res_c->fetch_assoc()): ?>
                    <div class="comentario-item" style="background: rgba(255,255,255,0.03); padding: 25px; border-radius: 10px; margin-bottom: 12px; border-left: 4px solid #1c85dcc4;">
                        <p style="margin-bottom: 10px;">
                            <strong style="color: #ffbc00; font-size: 14px;">
                                <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>:
                            </strong> 
                            <span style="color: #eee;"><?php echo htmlspecialchars($c['comentario']); ?></span>
                        </p>
                        <small style="opacity: 0.5; font-size: 12px; display: block;">
                            🕒 Postado em: <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?>
                        </small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?> 
                <p class="sem-fofoca" style="text-align: center; opacity: 0.6;">Ninguém fofocou nada ainda... </p> 
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>