<?php include 'includes/header.php'; 
      include 'includes/navbar.php'; 
      include 'includes/bolhas.php'; ?>

<main class="main-perdidos">
    <?php include 'includes/login.php'; ?>
    
    <article class="conteudo-principal">
        <header class="achados-perdidos-header">
            <h2>Achados e Perdidos</h2>
            <div class="capa-container">
                <img src="imagensfoto/Capa Achados e Perdidos.jpg" alt="Capa do Achados e Perdidos" class="img-capa">
            </div>
            <p class="frase-efeito">
                Perdeu o juízo? Disso a gente não cuida. Agora se você perdeu a chave ou a garrafinha, você está no lugar certo!
            </p>
        </header>

        <div class="container-feed">
            
            <div class="spotted-card perdidos">
                <div class="card-header">
                    <span class="category-tag">Achado</span>
                    <strong>@Anônimo</strong>
                </div>
                <div class="post-content">
                    <p>Galera, achei um <strong>estojo de óculos preto</strong> em cima da mesa na Biblioteca do Centro. Deixei na recepção!</p>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn-comentar">Comentar</button>
                </div>
            </div>

            <div class="spotted-card perdidos">
                <div class="card-header">
                    <span class="category-tag">Perdido</span>
                    <strong>@FuturaDra</strong>
                </div>
                <div class="post-content">
                    <p>Perdi minha carteira com estampa florida no bloco 1, Lab 2!</p>
                </div>
                
                <div class="reply-container">
                    <div class="reply-box">
                        <p><strong>@DireitoUnifev:</strong> "Vi uma carteira assim na cantina agora pouco!"</p>
                    </div>
                    <div class="reply-box status-resolvido">
                        <p><strong>@FuturaDra:</strong> "Era essa mesmo! Muito obrigada! 😭🙏"</p>
                    </div>
                </div>
            </div>

        </div>

        <section class="sessao-publicar">
            <h3>Perdeu ou Achou algo? Publique aqui:</h3>
            
            <div class="nota-seguranca">
                <strong>⚠️ NOTA DE SEGURANÇA:</strong> Ao postar fotos de documentos ou cartões, cubra dados sensíveis. O Spotted não se responsabiliza.
            </div>

            <?php if ($usuario_logado): ?>
                <form class="form-publicar">
                    <div class="input-group">
                        <label>O que aconteceu?</label>
                        <select name="tipo_post">
                            <option>Eu perdi algo...</option>
                            <option>Eu achei algo...</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <textarea placeholder="Descreva o objeto e o local..."></textarea>
                    </div>

                    <div class="input-group">
                        <label for="foto" class="label-file">📸 Anexar foto do objeto:</label>
                        <input type="file" id="foto" name="foto" class="input-file">
                    </div>

                    <button type="submit" class="btn-lancar">
                        Publicar no Feed
                    </button>
                </form>
            <?php else: ?>
                <div class="alerta-login">
                    <p><strong>Opa!</strong> Você precisa estar logado para poder publicar.</p>
                    <p class="sub-alerta">Faça login na <a href="index.php">Página Inicial</a> para continuar.</p>
                </div>
            <?php endif; ?>
        </section>
    </article>
</main>

<script>
    document.querySelector('.form-publicar').addEventListener('submit', function(e) {
        e.preventDefault(); // Impede a página de recarregar por enquanto
        
        const texto = this.querySelector('textarea').value;
        
        if(texto.trim() === "") {
            alert("Ei, descreva o que você achou ou perdeu antes de publicar!");
        } else {
            alert("Boa! Seu post foi enviado para a moderação da Fenda e aparecerá no feed em breve.");
            this.reset(); // Limpa o formulário
        }
    });
</script>
<?php include 'includes/footer.php'; ?>