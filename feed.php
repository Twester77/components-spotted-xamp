<?php

// 1. LÓGICA DE SEGURANÇA E CONFIGURAÇÃO
include 'conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}


// 2. FUNÇÃO AUXILIAR PARA MENÇÕES (@)
function formatarMencoes($texto)
{
    $texto_seguro = htmlspecialchars($texto);
    $padrao = '/@([^\\s]+)/';
    $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
    return preg_replace($padrao, $substituicao, $texto_seguro);
}


// 3. DADOS DO USUÁRIO LOGADO
$usuario_id = $_SESSION['usuario_id'];
$query_user = "SELECT nome, foto, username FROM usuarios WHERE id = '$usuario_id'";
$res_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($res_user);

$foto_perfil = !empty($user_data['foto']) ? "uploads/" . $user_data['foto'] : "imagensfoto/img_avatar_generico.jpg";
$nome_exibicao = !empty($user_data['username']) ? "@" . $user_data['username'] : $user_data['nome'];


// 4. LÓGICA DOS FILTROS (SQL DINÂMICO E SEGURO)
$categoria_selecionada = isset($_GET['categoria']) ? mysqli_real_escape_string($conn, $_GET['categoria']) : '';

// Primeiro definimos a base da query
$sql = "SELECT 
            m.id, m.mensagem, m.categoria, m.data_post, m.usuario_id, 
            u.username, u.foto 
        FROM mensagens m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id";

// Depois o filtro é adicionado, se ele existir
if (!empty($categoria_selecionada)) {
    $sql .= " WHERE m.categoria = '$categoria_selecionada'";
}

$sql .= " ORDER BY m.id DESC LIMIT 30"; // ele só traz os 30 primeiros 
$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    die("Erro no SQL: " . mysqli_error($conn));
}


// 5. INCLUDES DA PÁGINA (Ajustado para os seus paths)
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<div style="margin-top: 30px;">
    <?php include 'includes/filtros.php'; ?>
</div>

<main class="container-feed">
    <?php if (mysqli_num_rows($resultado) > 0): ?>
        <?php while ($linha = mysqli_fetch_assoc($resultado)):
            $post_id_atual = $linha['id'];
        ?>
            <article id="post-<?php echo $post_id_atual; ?>" class="spotted-card <?php echo $linha['categoria']; ?>" style="position: relative;">

                <div class="options-container" style="position: absolute; right: 15px; top: 15px; z-index: 10;">
                    <button class="btn-options" onclick="toggleMenu('menu-<?= $post_id_atual ?>')" style="background: none; border: none; color: #fff; cursor: pointer; opacity: 0.6;">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>

                    <div id="menu-<?= $post_id_atual ?>" class="options-menu-popup" style="display: none; position: absolute; right: 0; background: #222; border: 1px solid #444; border-radius: 8px; width: 150px; box-shadow: 0 5px 15px rgba(0,0,0,0.5);">
                        <?php if ($_SESSION['usuario_id'] == $linha['usuario_id']): ?>
                            <button onclick="confirmarExclusao(<?= $post_id_atual ?>)" class="opt-item opt-delete" style="width: 100%; padding: 10px; background: none; border: none; color: #ff4b2b; text-align: left; cursor: pointer;">
                                <i class="fas fa-trash-alt"></i> Excluir
                            </button>
                        <?php else: ?>
                            <button onclick="abrirDenuncia(<?= $post_id_atual ?>)" class="opt-item opt-report" style="width: 100%; padding: 10px; background: none; border: none; color: #ffbc00; text-align: left; cursor: pointer;">
                                <i class="fas fa-exclamation-triangle"></i> Denunciar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <span class="category-tag">#<?php echo strtoupper($linha['categoria']); ?></span>
                    <span class="post-time" style="opacity: 0.7; margin-right: 25px;">
                        <?php echo date('d/m H:i', strtotime($linha['data_post'])); ?>
                    </span>
                </div>

                <div class="user-info-post" style="text-align: left; display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                    <?php if ($linha['categoria'] === 'anonimo'): ?>
                        <img src="imagensfoto/anonimo-default.jpg" class="avatar-p" style="flex-shrink: 0;">
                        <span class="anonimo" style="font-weight: bold; opacity: 0.75; color: #a1a1a1da; white-space: nowrap;">
                            Habitante Anônimo
                        </span>
                    <?php else: ?>
                        <?php $foto_post = !empty($linha['foto']) ? 'uploads/' . $linha['foto'] : 'imagensfoto/default.jpg'; ?>
                        <img src="<?php echo $foto_post; ?>" class="avatar-p" style="flex-shrink: 0;">
                        <a href="ver-perfil.php?user=<?php echo $linha['username']; ?>" class="user-mention">
                            @<?php echo $linha['username']; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card-body" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;">
                    <p class="post-content"><?php echo formatarMencoes($linha['mensagem']); ?> </p>
                    <div class="reacoes-gravadas" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 15px;">
                        <?php
                        $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
                        $res_contas = mysqli_query($conn, $sql_contas);
                        $tradutor = ['amei' => '💖', 'perplecto' => '😲', 'haha' => '😂', 'ranco' => '🙄', 'forca' => '🫂', 'triste' => '😢', 'tendi-nada' => '🤔'];
                        while ($rc = mysqli_fetch_assoc($res_contas)) {
                            $emoji = $tradutor[$rc['tipo_reacao']] ?? '👍';
                            echo "<span class='reacao-item'>$emoji " . $rc['total'] . "</span>";
                        }
                        ?>
                    </div>
                </div>

                <div class="footer-links">
                    <div class="reacao-wrapper">
                        <span class="btn-reagir">👍 Reagir</span>
                        <?php $cat_atual = isset($_GET['categoria']) ? $_GET['categoria'] : ''; ?>
                        <div class="reacoes-popup">
                            <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=amei&categoria=<?php echo urlencode($_GET['categoria'] ?? ''); ?>&ref=post-<?php echo $post_id_atual; ?>" title="Amei">💖</a>
                            <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=perplecto&categoria=<?php echo urlencode($_GET['categoria'] ?? ''); ?>&ref=post-<?php echo $post_id_atual; ?>" title="Tô Perplecto">😲</a>
                            <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=haha&categoria=<?php echo urlencode($_GET['categoria'] ?? ''); ?>&ref=post-<?php echo $post_id_atual; ?>" title="Hahaha">😂</a>
                            <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=ranco&categoria=<?php echo urlencode($_GET['categoria'] ?? ''); ?>&ref=post-<?php echo $post_id_atual; ?>" title="Que ranço!">🙄</a>
                            <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=tendi-nada&categoria=<?php echo urlencode($_GET['categoria'] ?? ''); ?>&ref=post-<?php echo $post_id_atual; ?>" title="Entendi foi nada">🤔</a>
                            <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=forca&categoria=<?php echo urlencode($_GET['categoria'] ?? ''); ?>&ref=post-<?php echo $post_id_atual; ?>" title="Força Bro">🫂</a>
                        </div>
                    </div>

                    <a href="post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar">
                        <i class="fas fa-comments"></i> FOFOCAR
                    </a>
                </div>

            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 50px; opacity:0.7; font-size: 1.3rem;">Nenhum spotted encontrado nesta categoria ainda . </p>
    <?php endif; ?>

</main>
<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">
        CARREGAR MAIS FOFOCAS
    </button>
</div>

<script>
    function toggleMenu(menuId) {
        // Fecha todos os outros menus abertos primeiro (para não virar bagunça)
        document.querySelectorAll('.options-menu-popup').forEach(menu => {
            if (menu.id !== menuId) menu.style.display = 'none';
        });

        const menu = document.getElementById(menuId);
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    // Fecha o menu se clicar fora dele
    window.onclick = function(event) {
        if (!event.target.matches('.btn-options') && !event.target.matches('.fa-ellipsis-v')) {
            document.querySelectorAll('.options-menu-popup').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    }


    let offset = 30; // Começamos do 31º post, já que os primeiros 30 já estão lá
const btnLoad = document.getElementById('btn-load-more');
const feedContainer = document.querySelector('.container-feed');

if (btnLoad) {
    btnLoad.addEventListener('click', function() {
        btnLoad.innerText = "CARREGANDO...";
        
        // Pegamos a categoria da URL caso o usuário esteja filtrando
        const urlParams = new URLSearchParams(window.location.search);
        const categoria = urlParams.get('categoria') || '';

        // Faz a chamada para o motor que criamos
        fetch(`buscar_posts.php?offset=${offset}&categoria=${categoria}`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    btnLoad.innerText = "[ FIM DO ARQUIVO ]";
                    btnLoad.disabled = true;
                    btnLoad.style.opacity = "0.5";
                } else {
                    // Adiciona os novos cards ao final do feed
                    feedContainer.insertAdjacentHTML('beforeend', data);
                    
                    // Atualiza o offset para a próxima leva
                    offset += 30;
                    btnLoad.innerText = "CARREGAR MAIS";
                }
            })
            .catch(err => {
                console.error("Erro no sistema:", err);
                btnLoad.innerText = "[ ERRO_DE_CONEXÃO ]";
            });
    });
}

</script>


<?php include 'includes/footer.php'; ?>