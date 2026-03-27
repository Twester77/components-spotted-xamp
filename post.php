<?php
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// Pegamos o ID da URL (ex: post.php?id=5)
$id = $_GET['id'];

// Buscamos o post específico
$query = "SELECT * FROM mensagens WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
?>

<main class="main-post-detalhes">
    <div class="spotted-card <?php echo $post['categoria']; ?>">
        <p><?php echo $post['mensagem']; ?></p>
        <small>Postado em: <?php echo date('d/m', strtotime($post['data_post'])); ?></small>
    </div>

    <div class="reacoes-bar">
        <button title="Amei">💖</button>
        <button title="Perplecto">😲</button>
        <button title="Haha">😂</button>
        <button title="Grr">😡</button>
        <button title="Força">🫂</button>
        <button title="Tendi foi nada">🤔</button>
    </div>

    <section class="comentarios-section">
        <h3>Fofocas sobre esse post:</h3>
        <form action="enviar-comentario.php" method="POST">
            <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
            <textarea name="comentario" placeholder="O que você sabe sobre isso?" required></textarea>
            <button type="submit">Comentar</button>
        </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>