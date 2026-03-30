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

<main class="main-perdidos" style="max-width: 900px; margin: auto; padding: 20px;">
    
    <?php if (!$usuario_logado): ?>
        <div class="sessao-login-top" style="margin-bottom: 40px;">
            <?php include 'includes/login.php'; ?>
        </div>
    <?php else: ?>
        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; text-align: center; margin-bottom: 30px; border: 1px solid #ffbc00;">
            <p style="color: #fff; margin-bottom: 10px;">
                Conectado como <strong><?php echo $_SESSION['usuario_nome']; ?></strong> 🎓
            </p>
            <button onclick="deslogar()" style="background: #cc420c; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">Sair da Conta</button>
        </div>
    <?php endif; ?>
    
    <article class="conteudo-principal">
        <header class="achados-perdidos-header" style="text-align: center;">
            <h2>Achados e Perdidos</h2>
            <div class="capa-container">
                <img src="imagensfoto/capa-achados-e-perdidos.jpg" alt="Capa" class="img-capa" style="max-width: 100%; height: auto; border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            </div>
            <p class="frase-efeito" style="font-style: italic; color: #ccc;">
                Perdeu o juízo? Disso a gente não cuida. Mas se perdeu a chave ou a garrafinha, está no lugar certo!
            </p>
        </header>

        <section class="sessao-publicar" style="margin-top: 40px; background: rgba(255,255,255,0.02); padding: 25px; border-radius: 15px; border: 1px dashed #555;">
            <h3 style="color: #ffbc00; margin-bottom: 15px;">Perdeu ou Achou algo?</h3>
            
            <div class="nota-seguranca" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
                <strong>⚠️ NOTA DE SEGURANÇA:</strong> Ao postar fotos, cubra dados sensíveis.
            </div>

            <?php if ($usuario_logado): ?>
                <form action="enviar-post.php" method="POST" enctype="multipart/form-data" class="form-publicar">
                    <input type="hidden" name="categoria" value="perdidos">
                    <div class="input-group" style="margin-bottom: 15px;">
                        <select name="subcategoria" required style="width: 100%; padding: 10px; border-radius: 5px; background: #222; color: #fff; border: 1px solid #444;">
                            <option value="perdi">❌ Eu perdi algo...</option>
                            <option value="achei">✅ Eu achei algo...</option>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 15px;">
                        <textarea name="mensagem" placeholder="Descreva o objeto..." required style="width: 100%; height: 100px; padding: 10px; border-radius: 5px; background: #222; color: #fff; border: 1px solid #444; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn-lancar" style="background: #ffbc00; color: #000; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%;">Publicar na Fenda</button>
                </form>
            <?php else: ?>
                <p style="text-align: center; opacity: 0.7;">Faça login acima para publicar seu achado/perdido!</p>
            <?php endif; ?>
        </section>

        <section class="feed-filtrado" style="margin-top: 40px;">
            <div class="container-feed">
                <?php 
                if (mysqli_num_rows($resultado_perdidos) > 0):
                    while($linha = mysqli_fetch_assoc($resultado_perdidos)): 
                ?>
                <article class="spotted-card perdidos" style="margin-bottom: 20px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; border-left: 5px solid #ffbc00;">
                    <div class="card-header" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span class="category-tag" style="font-weight: bold; color: #ffbc00;">
                            #PERDIDOS 
                            <?php echo ($linha['subcategoria'] == 'achei') ? '<span style="color:#4caf50;">✨ ACHADO</span>' : '<span style="color:#f44336;">🔍 PERDIDO</span>'; ?>
                            <small style="color: #fff; margin-left: 10px;">@<?php echo !empty($linha['username']) ? $linha['username'] : "Estudante"; ?></small>
                        </span>
                        <span style="font-size: 12px; opacity: 0.6;"><?php echo date('d/m', strtotime($linha['data_post'])); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <p style="color: #eee;"><?php echo $linha['mensagem']; ?></p>
                    </div>

                    <div class="footer-links" style="margin-top: 10px; text-align: right;">
                        <a href="post.php?id=<?php echo $linha['id']; ?>" style="color: #ffbc00; text-decoration: none; font-size: 14px;">Ver detalhes / Fofocar →</a>
                    </div>
                </article>
                <?php endwhile; else: ?>
                    <p style="text-align:center; color:#aaa; padding:20px;">Nenhum item por enquanto!</p>
                <?php endif; ?>
            </div>
        </section>
    </article>
</main>

<?php include 'includes/footer.php'; ?>