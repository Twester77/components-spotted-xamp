<?php
include 'conexao.php';
$categoria_selecionada = isset($_GET['categoria']) ? $_GET['categoria'] : '';

$sql = "SELECT * FROM mensagens";

if (!empty($categoria_selecionada)) {
    $sql .= " WHERE categoria = '$categoria_selecionada'";
}
$sql .= " ORDER BY id DESC";

$resultado = mysqli_query($conn, $sql); 

include 'includes/header.php'; 
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<main>
    <?php if(!isset($_SESSION['usuario_id'])): ?>
        <div class="aviso-login">
            <p>Você está vendo o mural público. <a href="index.php"> Realize Login </a> para poder postar o seu Spotted!</p>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['usuario_id'])): ?>
        <a href="novo-post.php" class="btn-flutuante">+</a>
    <?php endif; ?>

    <?php include 'includes/filtros.php';?>

    <div class="container-feed">
        <?php while($linha = mysqli_fetch_assoc($resultado)) { ?>
            
            <article class="spotted-card <?php echo $linha['categoria']; ?>">
                
                <div class="card-header">
                    <span class="category-tag">
                        #<?php echo strtoupper($linha['categoria']); ?> 
                        <?php echo (is_null($linha['usuario_id'])) ? "Anônimo" : "Estudante"; ?>
                    </span>
                    <span class="post-time">
                        <?php echo date('d/m', strtotime($linha['data_post'])); ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <p class="post-content"><?php echo $linha['mensagem']; ?></p>
                </div>

                <div class="card-footer">
                    <div class="reacoes-container reacoes-popup">
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'amei')" title="Correio Elegante">💖</button>
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'perplecto')" title="Perplecto">😲</button>
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'haha')" title="Haha">😂</button>
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'ranco')" title="Ranço">👌</button>
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'forca')" title="Força">🫂</button>
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'triste')" title="Magoei">😢</button>
                        <button onclick="reagir(<?php echo $linha['id']; ?>, 'tendi-nada')" title="Tendi foi nada">🤔</button>
                    </div>

                    <div class="footer-links">
                        <span class="btn-trigger-reacoes">
                            <i class="fa-solid fa-face-smile"></i> Reagir
                        </span>

                        <a href="post.php?id=<?php echo $linha['id']; ?>" class="btn-comentar">
                            <i class="fa-solid fa-comment"></i> Fofocar
                        </a>
                    </div>
                </div>
            </article>

        <?php } // Fim do while ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>