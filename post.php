<?php
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// 1. Pegamos o ID da URL de forma segura (Sua lógica mantida)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    header("Location: feed.php");
    exit();
}

// 2. Buscamos o post específico (Seu Prepared Statement original)
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

<main class="container-feed" style="max-width: 700px; margin: 20px auto; padding: 0 15px;">
    
    <article class="spotted-card <?php echo $post['categoria']; ?>" style="transform: none !important;">
        <div class="card-header" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <span class="category-tag" style="font-weight: 800;">#<?php echo strtoupper($post['categoria']); ?></span>
            <span class="post-time" style="opacity: 0.5; font-size: 0.8rem;"><?php echo date('d/m', strtotime($post['data_post'])); ?></span>
        </div>
        
        <div class="card-body">
            <p class="post-content" style="font-size: 1.2rem; line-height: 1.6; color: #fff;">
                <?php echo htmlspecialchars($post['mensagem']); ?>
            </p>
        </div>
    </article>

    <section class="sessao-publicar" style="margin-top: 30px;">
        <h3 style="margin-bottom: 15px; color: #ffbc00; font-size: 1.1rem;">Opino ou prefiro não opinar?</h3>
        
        <form action="enviar-comentario.php" method="POST" class="form-fofoca" style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
            <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">

            <textarea name="comentario" placeholder="Sabe de algo? Conta aí..." required 
              style="width: 100%; min-height: 80px; background: transparent; border: none; color: #fff; outline: none; resize: none; margin-bottom: 15px;"></textarea>

            <?php if (isset($_SESSION['usuario_id'])): 
                $exibir_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : $_SESSION['nome'];
            ?>
                <button type="submit" class="btn-lancar" style="background: #ffbc00; color: #000; border: none; padding: 12px; border-radius: 25px; font-weight: 900; cursor: pointer; width: 100%;">
                    Lançar Fofoca como @<?php echo htmlspecialchars($exibir_nome); ?> 🚀
                </button>
            <?php else: ?>
                <div style="background: rgba(255, 188, 0, 0.05); padding: 15px; border-radius: 15px; border: 1px dashed rgba(255, 188, 0, 0.3); margin-bottom: 10px;">
                    <p style="color: #ffbc00; font-size: 13px; text-align: center; margin-bottom: 10px;">
                        ⚠️ Você não está logado. Sua fofoca será enviada como <strong>Anônimo</strong>.
                    </p>
                    <button type="submit" class="btn-lancar" style="background: rgba(255,255,255,0.1); color: #fff; border: none; padding: 12px; border-radius: 25px; font-weight: bold; cursor: pointer; width: 100%;">
                        Lançar Fofoca Anônima 
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <div class="lista-comentarios" style="margin-top: 30px;">
        <?php
        $query_c = "SELECT * FROM comentarios WHERE id_mensagem = ? ORDER BY id DESC";
        $stmt_c = $conn->prepare($query_c);
        $stmt_c->bind_param("i", $id);
        $stmt_c->execute();
        $res_c = $stmt_c->get_result();

        if ($res_c->num_rows > 0): 
            while ($c = $res_c->fetch_assoc()): ?>
                <div class="comentario-item" style="display: flex; gap: 12px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; margin-bottom: 12px; border-left: 4px solid #1c85dcc4; backdrop-filter: blur(5px);">
                    
                    <img src="imagensfoto/img_avatar_generico.jpg" class="avatar-fenda avatar-p">
                    
                    <div style="flex: 1;">
                        <strong style="color: #ffbc00; font-size: 0.9rem; display: block; margin-bottom: 4px;">
                            <?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?>
                        </strong> 
                        <p style="color: #eee; font-size: 0.95rem; line-height: 1.4;">
                            <?php echo htmlspecialchars($c['comentario']); ?>
                        </p>
                        <small style="opacity: 0.4; font-size: 10px; margin-top: 8px; display: block;">
                            🕒 <?php echo date('d/m H:i', strtotime($c['data_comentario'])); ?>
                        </small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?> 
            <p style="text-align: center; opacity: 0.5; margin-top: 20px;">Ninguém fofocou nada ainda... </p> 
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>