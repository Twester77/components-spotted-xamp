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