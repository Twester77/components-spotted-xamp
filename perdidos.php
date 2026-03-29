<?php 
// 1. CONFIGURAÇÃO DO FILTRO 
$_GET['cat'] = 'perdidos'; 

include 'includes/header.php';  
include 'includes/bolhas.php'; 


$usuario_logado = isset($_SESSION['usuario_id']);
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

        <section class="feed-filtrado" style="margin-top: 40px;">
            <?php include 'feed.php'; ?>
        </section>

        <section class="sessao-publicar" style="margin-top: 50px; background: rgba(255,255,255,0.02); padding: 25px; border-radius: 15px; border: 1px dashed #555;">
            <h3 style="color: #ffbc00; margin-bottom: 15px;">Perdeu ou Achou algo?</h3>
            
            <div class="nota-seguranca" style="background: rgba(255, 193, 7, 0.1); color: #ffc107; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 20px;">
                <strong>⚠️ NOTA DE SEGURANÇA:</strong> Ao postar fotos, cubra dados sensíveis (CPF, números de cartão). O Spotted não se responsabiliza.
            </div>

            <?php if ($usuario_logado): ?>
                <form action="enviar-post.php" method="POST" enctype="multipart/form-data" class="form-publicar">
                    <input type="hidden" name="categoria" value="perdidos">

                    <div class="input-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px;">O que aconteceu?</label>
                        <select name="subcategoria" required style="width: 100%; padding: 10px; border-radius: 5px; background: #222; color: #fff; border: 1px solid #444;">
                            <option value="perdi">❌ Eu perdi algo...</option>
                            <option value="achei">✅ Eu achei algo...</option>
                        </select>
                    </div>

                    <div class="input-group" style="margin-bottom: 15px;">
                        <textarea name="mensagem" placeholder="Descreva o objeto e o local onde foi visto por último..." required style="width: 100%; height: 100px; padding: 10px; border-radius: 5px; background: #222; color: #fff; border: 1px solid #444; resize: vertical;"></textarea>
                    </div>

                    <div class="input-group" style="margin-bottom: 15px;">
                        <label for="foto" class="label-file">📸 Foto do objeto (opcional):</label>
                        <input type="file" id="foto" name="foto" class="input-file" style="display: block; margin-top: 5px;">
                    </div>

                    <button type="submit" class="btn-lancar" style="background: #ffbc00; color: #000; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 16px;">Publicar na Fenda</button>
                </form>
            <?php else: ?>
                <div class="alerta-login" style="text-align: center; padding: 20px;">
                    <p style="font-size: 18px;"><strong>Opa!</strong> Você precisa estar logado para publicar.</p>
                    <p class="sub-alerta" style="opacity: 0.7;">Use o formulário de login no topo da página 👆</p>
                </div>
            <?php endif; ?>
        </section>
    </article>
</main>

<?php include 'includes/footer.php'; ?>