<?php
// 1. INICIALIZAÇÃO E SEGURANÇA
include 'conexao.php'; // Aqui já roda o ob_start()

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. BUSCA DE DADOS (Lógica Independente)
$sql_perdidos = "SELECT m.*, u.username 
                 FROM mensagens m 
                 LEFT JOIN usuarios u ON m.usuario_id = u.id 
                 WHERE m.categoria = 'perdidos' 
                 ORDER BY m.id DESC";
$resultado_perdidos = mysqli_query($conn, $sql_perdidos);

$usuario_logado = isset($_SESSION['usuario_id']);

// 3. INCLUDES DE INTERFACE
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<main class="main-perdidos" style="max-width: 1000px; margin: 90px auto 40px auto; padding: 20px;">
    <?php if (!$usuario_logado): ?>
        <div class="sessao-login-top" style="margin-bottom: 40px;">
            <?php include 'includes/login.php'; ?>
        </div>
    <?php else: ?>
        <?php if ($usuario_logado): ?>
            <div class="painel-sessao">
                <p>Logado como: <strong><?php echo $_SESSION['usuario_nome']; ?></strong> 🎓</p>
                <button onclick="deslogar()" class="btn-sair-fenda">Sair</button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <article class="conteudo-principal">
        <h2 style="font-size: 2.0rem; text-align: center; margin-bottom: 15px; margin-top: 30px; color: #fff;">
            Achados & Perdidos
        </h2>

        <div style="display: flex; flex-direction: column; gap: 10px; align-items: center;">
            <img src="imagensfoto/capa-achados-e-perdidos.jpg"
                alt="Capa do Achados e Perdidos"
                style="width: 100%; height: auto; border-radius: 15px; opacity: 0.82; margin: 15px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">

            <div style="font-size: 16px; font-style: italic; line-height: 1.5; text-align: center; color: #ccc; max-width: 800px; margin-bottom: 30px;">
                <blackquote> Perdeu o juízo? Disso a gente não cuida. Mas se perdeu a chave ou a garrafinha, está no lugar certo!</blackquote>
            </div>
        </div>
    </article>

    <section class="sessao-publicar">
    <h3 class="titulo-publicar">Perdeu ou Achou algo?</h3>

    <div class="nota-seguranca">
        <strong>⚠️ NOTA DE SEGURANÇA:</strong> Ao postar fotos, cubra dados sensíveis.
    </div>

    <?php if ($usuario_logado): ?>
        <form action="enviar-post.php" method="POST" enctype="multipart/form-data" class="form-publicar">
            <input type="hidden" name="categoria" value="perdidos">
            
            <div class="input-group">
                <select name="subcategoria" class="fenda-input" required>
                    <option value="perdi">❌ Eu perdi algo...</option>
                    <option value="achei">✅ Eu achei algo...</option>
                </select>
            </div>

            <div class="input-group">
                <textarea name="mensagem" class="fenda-input fenda-textarea" placeholder="Descreva o objeto..." required></textarea>
            </div>

            <button type="submit" class="btn-lancar">Publicar na Fenda</button>
        </form>
    <?php else: ?>
        <p style="text-align: center; opacity: 0.7;">Faça login acima para publicar seu achado/perdido!</p>
    <?php endif; ?>
</section>

    <section class="feed-filtrado" style="margin-top: 40px;">
        <div class="container-feed">
            <?php
            if (mysqli_num_rows($resultado_perdidos) > 0):
                while ($linha = mysqli_fetch_assoc($resultado_perdidos)):
            ?>
                    <article class="spotted-card perdidos <?php echo $linha['subcategoria']; ?>">
                        <div class="card-header">
                            <span class="category-tag">
                                #PERDIDOS
                                <?php if ($linha['subcategoria'] == 'achei'): ?>
                                    <span class="badge-achado"><i class="fas fa-check-circle"></i> ACHADO</span>
                                <?php else: ?>
                                    <span class="badge-perdido"><i class="fas fa-search"></i> PERDIDO</span>
                                <?php endif; ?>
                                <small>@<?php echo !empty($linha['username']) ? $linha['username'] : "Anônimo"; ?></small>
                            </span>
                            <span class="data-post"><?php echo date('d/m', strtotime($linha['data_post'])); ?></span>
                        </div>

                        <div class="card-body">
                            <p><?php echo $linha['mensagem']; ?></p>
                        </div>

                        <div class="card-footer" style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
                            <a href="post.php?id=<?php echo $linha['id']; ?>" class="link-fofoca">
                                <i class="fas fa-comment-dots"></i> Ver detalhes / Ajudar a encontrar →
                            </a>
                        </div>
                    </article>
                <?php endwhile;
            else: ?>
                <p style="text-align:center; color:#aaa; padding:20px;">Nenhum item por enquanto!</p>
            <?php endif; ?>
        </div>
    </section>
    </article>
</main>

<?php include 'includes/footer.php'; ?>