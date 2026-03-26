
<?php
include 'conexao.php';

$sql = "SELECT * FROM mensagens ORDER BY id DESC";
$resultado = mysqli_query($conn, $sql);

include 'components/header.php'; 
include 'components/navbar.php'; 
?>

<main>
    <?php if(!isset($_SESSION['usuario_id'])): ?>
        <div class="aviso-login">
            <p>Você está vendo o mural público. <a href="index.php">  Realize Login  </a> para poder postar o seu Spotted!</p>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['usuario_id'])): ?>
        <a href="novo-post.php" class="btn-flutuante">+</a>
    <?php endif; ?>

<div class="container-feed">
    <?php 
    while($linha = mysqli_fetch_assoc($resultado)) { 
    ?>
        <article class="spotted-card <?php echo $linha['categoria']; ?>">
            
            <div class="card-header">
                <span class="category-tag">
                    #<?php echo (is_null($linha['usuario_id'])) ? "Anônimo" : "Estudante"; ?>
                </span>
                <span class="post-time">
                    <?php echo date('d/m', strtotime($linha['data_post'])); ?>
                </span>
            </div>
            
            <div class="card-body">
                <p class="post-content"><?php echo $linha['mensagem']; ?></p>
            </div>

            <div class="card-footer">
                <button class="btn-like"><i class="fa-solid fa-heart"></i></button>
            </div>
        </article>

    <?php } ?>
</div>
</main>

<?php include 'components/footer.php' ; ?>

