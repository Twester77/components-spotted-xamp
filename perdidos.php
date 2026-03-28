<?php 
// 1. CONFIGURAÇÃO DO FILTRO (O segredo do sucesso)
$_GET['cat'] = 'perdidos'; 

include 'includes/header.php'; 
include 'includes/navbar.php'; 
include 'includes/bolhas.php'; 
?>

<main class="main-perdidos">
    <?php include 'includes/login.php'; ?>
    
    <article class="conteudo-principal">
        <header class="achados-perdidos-header">
            <h2>Achados e Perdidos</h2>
            <div class="capa-container">
                <img src="imagensfoto/capa-achados-e-perdidos.jpg" alt="Capa" class="img-capa" style="max-width: 100%; height: auto; border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            </div>
            <p class="frase-efeito">
                Perdeu o juízo? Disso a gente não cuida. Mas se perdeu a chave ou a garrafinha, está no lugar certo!
            </p>
        </header>

        <section class="feed-filtrado">
            <?php include 'feed.php'; // Aqui a mágica acontece! ?>
        </section>

        <section class="sessao-publicar">
            <h3>Perdeu ou Achou algo?</h3>
            
            <div class="nota-seguranca">
                <strong>⚠️ NOTA DE SEGURANÇA:</strong> Ao postar fotos, cubra dados sensíveis (CPF, números de cartão). O Spotted não se responsabiliza.
            </div>

            <?php if ($usuario_logado): ?>
                <form action="processa_post.php" method="POST" enctype="multipart/form-data" class="form-publicar">
                    <input type="hidden" name="categoria" value="perdidos">

                    <div class="input-group">
                        <label>O que aconteceu?</label>
                        <select name="subcategoria" required>
                            <option value="perdi">❌ Eu perdi algo...</option>
                            <option value="achei">✅ Eu achei algo...</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <textarea name="mensagem" placeholder="Descreva o objeto e o local onde foi visto por último..." required></textarea>
                    </div>

                    <div class="input-group">
                        <label for="foto" class="label-file">📸 Foto do objeto (opcional):</label>
                        <input type="file" id="foto" name="foto" class="input-file">
                    </div>

                    <button type="submit" class="btn-lancar">Publicar na Fenda</button>
                </form>
            <?php else: ?>
                <div class="alerta-login">
                    <p><strong>Opa!</strong> Você precisa estar logado para publicar.</p>
                    <p class="sub-alerta">Faça login na <a href="index.php">Página Inicial</a>.</p>
                </div>
            <?php endif; ?>
        </section>
    </article>
</main>

<?php include 'includes/footer.php'; ?>