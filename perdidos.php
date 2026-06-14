<?php 
include 'conexao.php'; // Aqui já roda o ob_start()
// 1. INICIALIZAÇÃO E SEGURANÇA

// 2. BUSCA DE DADOS (Lógica Independente)
$filtro = isset($_GET['filtro']) ? mysqli_real_escape_string($conn, $_GET['filtro']) : 'todos';

// Começamos a frase (SEM o ORDER BY aqui)
$sql_perdidos = "SELECT m.*, u.username 
                 FROM mensagens m 
                 LEFT JOIN usuarios u ON m.usuario_id = u.id 
                 WHERE m.categoria = 'perdidos' AND m.status = 'ativo'"; // <-- Blindagem aqui

if ($filtro === 'achei' || $filtro === 'perdi') {
    $sql_perdidos .= " AND m.subcategoria = '$filtro'";
}

// No final de tudo, ordena
$sql_perdidos .= " ORDER BY m.id DESC";
$resultado_perdidos = mysqli_query($conn, $sql_perdidos);
$usuario_logado = isset($_SESSION['usuario_id']);

// 3. INCLUDES DE INTERFACE
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<main class="main-perdidos" style=" padding: 20px 0; overflow-x: hidden;" id="conteudo-principal"> 
    <?php if (!$usuario_logado): ?>
        <div class="sessao-login-top" style="margin-bottom: 40px;">
            <?php include 'includes/login.php'; ?>
        </div>
    <?php else: ?>
        <div class="painel-sessao" role="region" aria-label="Informações da Sessão">
            <p>Logado como: <strong><?php echo $_SESSION['usuario_nome']; ?></strong> <span aria-hidden="true">🎓</span></p>
            <button type="button" onclick="deslogar()" class="btn-sair-fenda">Sair da Conta</button>
        </div>
    <?php endif; ?>

    <!-- Adicionado aria-labelledby vinculando ao título h2 -->
    <article class="conteudo-principal" aria-labelledby="titulo-achados-perdidos">
        <h2 id="titulo-achados-perdidos" style="font-family: 'Bebas Neue', sans-serif; font-size: 2rem; text-align: center; color: #fc900c; letter-spacing: 2px; margin-bottom: 10px; margin-top: 30px;">
            Achados & Perdidos
        </h2>

        <div style="display: flex; flex-direction: column; gap: 10px; align-items: center; width: 100%;">
            <picture style="width: 100%;">
                <source srcset="imagensfoto/capa-achados-e-perdidos.avif" type="image/avif">
                <source srcset="imagensfoto/capa-achados-e-perdidos.webp" type="image/webp">
                <img src="imagensfoto/capa-achados-e-perdidos.jpg"
                     alt="Ilustração de um mural de achados e perdidos com chaves, óculos e objetos esquecidos"
                     style="width: 100%; height: auto; border-radius: 15px; opacity: 0.7; margin: 15px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"
                     loading="lazy">
            </picture>

            <div style="font-size: clamp(14px, 2vw, 18px); font-style: italic; line-height: 1.5; text-align: center; color: #ccc; max-width: 800px; margin-bottom: 30px;">
                <!-- Corrigido o erro de digitação de <blackquote> para <blockquote> -->
                <blockquote> Perdeu o juízo? Disso a gente não cuida. Mas se perdeu a chave ou a garrafinha, você está no lugar certo!</blockquote>
            </div>
        </div>
    </article>

    <section class="sessao-publicar" style="width: 100% !important; margin: 20px 0 !important;" aria-labelledby="titulo-form-publicar">
        <h3 id="titulo-form-publicar" class="titulo-publicar">Perdeu ou Achou algo?</h3>

        <div class="nota-seguranca" role="note" aria-label="Aviso de Segurança">
            <strong><span aria-hidden="true">⚠️</span> NOTA DE SEGURANÇA:</strong> Ao postar fotos, por favor, cubra dados sensíveis.
        </div>

        <?php if ($usuario_logado): ?>
            <form action="enviar-post.php" method="POST" enctype="multipart/form-data" class="form-publicar" aria-label="Formulário para reportar item achado ou perdido">
                <input type="hidden" name="categoria" value="perdidos">

                <div class="input-group">
                    <!-- aria-label adicionada para o select possuir uma descrição direta -->
                    <select name="subcategoria" class="fenda-input" required aria-label="Selecione se você perdeu ou achou um objeto">
                        <option value="perdi">❌ Eu perdi algo...</option>
                        <option value="achei">✅ Eu achei algo...</option>
                    </select>
                </div>

                <div class="input-group">
                    <textarea name="mensagem" class="fenda-input fenda-textarea" placeholder="Descreva o objeto..." required aria-label="Descrição detalhada do objeto"></textarea>
                </div>
                <div class="input-group">
                    <label for="imagem" style="color: #fc900c; font-size: 0.9rem; cursor: pointer;">
                        <i class="fas fa-camera" aria-hidden="true"></i> Postar foto do objeto (Opcional)
                    </label>
                    <input type="file" name="imagem" id="imagem" accept="image/*" class="fenda-input" style="padding: 5px;">
                </div>

                <button type="submit" class="btn-lancar">Publicar na Fenda</button>
            </form>
        <?php else: ?>
            <p style="text-align: center; opacity: 0.7;">Faça login acima para publicar seu achado/perdido!</p>
        <?php endif; ?>
    </section>

    <!-- Menu de abas/filtros ganha grupo de navegação para facilitar comandos via teclado -->
    <nav class="filtros-perdidos" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 30px;" aria-label="Filtros do feed">
        <a href="perdidos.php?filtro=todos" class="btn-filtro <?php echo ($filtro == 'todos') ? 'ativo' : ''; ?>" <?php echo ($filtro == 'todos') ? 'aria-current="page"' : ''; ?>>Todos</a>
        <a href="perdidos.php?filtro=perdi" class="btn-filtro <?php echo ($filtro == 'perdi') ? 'ativo' : ''; ?>" <?php echo ($filtro == 'perdi') ? 'aria-current="page"' : ''; ?>>❌ Só Perdidos</a>
        <a href="perdidos.php?filtro=achei" class="btn-filtro <?php echo ($filtro == 'achei') ? 'ativo' : ''; ?>" <?php echo ($filtro == 'achei') ? 'aria-current="page"' : ''; ?>>✅ Só Achados</a>
    </nav>

    <section class="feed-filtrado" style="margin-top: 30px;" aria-label="Feed de publicações">
        <div class="container-feed">
            <?php
            if (mysqli_num_rows($resultado_perdidos) > 0):
                while ($linha = mysqli_fetch_assoc($resultado_perdidos)):
            ?>
                    <article class="spotted-card perdidos-item <?php echo ($linha['subcategoria'] == 'achei') ? 'card-achado' : 'card-perdido'; ?>">
                        <div class="card-header">
                            <span class="category-tag">
                                <?php if ($linha['subcategoria'] == 'achei'): ?>
                                    <span class="badge-achado"><i class="fas fa-check-circle" aria-hidden="true"></i> #ACHADO</span>
                                <?php else: ?>
                                    <span class="badge-perdido"><i class="fas fa-search" aria-hidden="true"></i> #PERDIDO</span>
                                <?php endif; ?>

                                <small>@<?php echo !empty($linha['username']) ? $linha['username'] : "Anônimo"; ?></small>
                            </span>

                            <!-- Tag time adicionada dinamicamente para semântica cronológica correta -->
                            <span class="data-post">
                                <time datetime="<?php echo date('Y-m-d', strtotime($linha['data_post'])); ?>"><?php echo date('d/m', strtotime($linha['data_post'])); ?></time>
                            </span>

                            <div style="clear: both;"></div>
                        </div>

                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($linha['mensagem'])); ?></p>
                            <?php if (!empty($linha['imagem_url'])): ?>
                                <div class="container-img-post">
                                    <!-- Alt dinâmico puxando a subcategoria para contextualizar a foto ao deficiente visual -->
                                    <img src="postagens/<?php echo htmlspecialchars($linha['imagem_url']); ?>"
                                         class="spotted-card-img"
                                         loading="lazy"
                                         alt="Foto do objeto <?php echo ($linha['subcategoria'] == 'achei') ? 'achado' : 'perdido'; ?>">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer" style=" border-top: 1px solid rgba(255,255,255,0.1);">
                            <!-- aria-label detalha qual post está sendo clicado para evitar links repetidos cegos -->
                            <a href="comentarios-post.php?id=<?php echo $linha['id']; ?>" class="link-fofoca" aria-label="Ver detalhes / ajudar a encontrar o objeto de @<?php echo !empty($linha['username']) ? $linha['username'] : 'Anônimo'; ?>">
                                <i class="fas fa-comment-dots" aria-hidden="true"></i> Ver detalhes / Ajudar a encontrar →
                            </a>
                        </div>
                    </article>
                <?php endwhile;
            else: ?>
                <p style="text-align:center; color:#aaa; padding:20px;">Nenhum item por enquanto!</p>
            <?php endif; ?>
        </div>
    </section>
    
</main>

<?php include 'includes/footer.php'; ?>
