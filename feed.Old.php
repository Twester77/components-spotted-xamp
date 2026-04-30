<?php
// 1. LÓGICA DE SEGURANÇA E CONFIGURAÇÃO
include_once 'conexao.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. DADOS DO USUÁRIO LOGADO (Mantendo seus nomes originais)
$usuario_id = $_SESSION['usuario_id'];
$query_user = "SELECT nome, foto, username FROM usuarios WHERE id = '$usuario_id'";
$res_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($res_user);

$foto_perfil = !empty($user_data['foto']) ? "uploads/" . $user_data['foto'] : "imagensfoto/img_avatar_generico.jpg";
$nome_exibicao = !empty($user_data['username']) ? "@" . $user_data['username'] : $user_data['nome'];

// 3. LÓGICA DOS FILTROS
$categoria_selecionada = isset($_GET['categoria']) ? mysqli_real_escape_string($conn, $_GET['categoria']) : '';
$sql = "SELECT m.*, u.username, u.foto FROM mensagens m LEFT JOIN usuarios u ON m.usuario_id = u.id";

if (!empty($categoria_selecionada)) {
    $sql .= " WHERE m.categoria = '$categoria_selecionada'";
}

$sql .= " ORDER BY m.id DESC LIMIT 30";

// AQUI ESTÁ O TRUQUE: Use um nome único para o resultado do feed
$resultado_feed = mysqli_query($conn, $sql); 

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<div style="margin-top: 30px;">
    <?php include 'includes/filtros.php'; // O filtros.php usa $res_cats, não vai mais bater com $resultado_feed ?>
</div>

<main class="main-fenda-total">
    <div class="container-feed">
        <?php 
        // Use a variável nova aqui também para evitar o erro de array
        if ($resultado_feed && mysqli_num_rows($resultado_feed) > 0): 
            while ($linha = mysqli_fetch_assoc($resultado_feed)):
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

                    <div class="card-header">
                        <span class="category-tag">#<?php echo strtoupper($linha['categoria']); ?></span>
                        <span class="post-time"><?php echo date('d/m H:i', strtotime($linha['data_post'])); ?></span>
                    </div>

                    <div class="user-info-post">
                        <?php if ($linha['categoria'] === 'anonimo'): ?>
                            <img src="imagensfoto/anonimo-default.jpg" class="avatar-p">
                            <span class="anonimo">Habitante Anônimo</span>
                        <?php else: ?>
                            <?php $foto_post = !empty($linha['foto']) ? 'uploads/' . $linha['foto'] : 'imagensfoto/default.jpg'; ?>
                            <img src="<?php echo $foto_post; ?>" class="avatar-p">
                            <a href="ver-perfil.php?user=<?php echo $linha['username']; ?>" class="user-mention">@<?php echo $linha['username']; ?></a>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <p class="post-content">
                            <?php 
                                if (function_exists('formatarMencoes')) {
                                    echo formatarMencoes($linha['mensagem']);
                                } else {
                                    echo htmlspecialchars($linha['mensagem']);
                                }
                            ?> 
                        </p>
                        
                        <div id="reacoes-post-<?= $post_id_atual ?>" class="reacoes-gravadas" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 15px;">
                            <?php
                            $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
                            $res_contas = mysqli_query($conn, $sql_contas);

                            $minhas_reacoes = [];
                            $meu_id = $_SESSION['usuario_id'];
                            $sql_meu = "SELECT tipo_reacao FROM curtidas WHERE mensagem_id = '$post_id_atual' AND usuario_id = '$meu_id'";
                            $res_meu = mysqli_query($conn, $sql_meu);
                            while ($m = mysqli_fetch_assoc($res_meu)) {
                                $minhas_reacoes[] = $m['tipo_reacao'];
                            }

                            $tradutor = ['amei' => '💖', 'perplecto' => '😲', 'haha' => '😂', 'ranco' => '🙄', 'forca' => '🫂', 'triste' => '😢', 'tendi-nada' => '🤔'];

                            while ($rc = mysqli_fetch_assoc($res_contas)) {
                                $tipo = $rc['tipo_reacao'];
                                $emoji = $tradutor[$tipo] ?? '👍';
                                $classe_voted = in_array($tipo, $minhas_reacoes) ? 'voted' : '';
                                echo "<span class='reacao-item $classe_voted'>$emoji " . $rc['total'] . "</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="footer-links">
                        <div class="reacao-wrapper">
                            <span class="btn-reagir">👍 Reagir</span>
                            <div class="reacoes-popup">
                                <?php foreach ($tradutor as $tipo => $emoji): ?>
                                    <a href="javascript:void(0)" onclick="enviarReacao(<?= $post_id_atual ?>, '<?= $tipo ?>')"><?= $emoji ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <a href="post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar"><i class="fas fa-comments"></i> FOFOCAR</a>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #ccc;">Nenhum post encontrado na Fenda.</p>
        <?php endif; ?>
    </div>
</main>

<div class="container-load-more">
    <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais Resultados</button>
</div>

<script>
    let offset = 30;
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    if (btnLoad) {
        btnLoad.addEventListener('click', function() {
            btnLoad.innerText = "CARREGANDO...";
            const urlParams = new URLSearchParams(window.location.search);
            const categoria = urlParams.get('categoria') || '';

            fetch(`motor-feed.php?offset=${offset}&categoria=${categoria}&tipo=geral`)
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "FIM_DADOS") {
                        btnLoad.innerText = "FIM DO FEED";
                        btnLoad.disabled = true;
                    } else {
                        feedContainer.insertAdjacentHTML('beforeend', data);
                        offset += 30;
                        btnLoad.innerText = "EXIBIR MAIS RESULTADOS";

                        if (typeof window.configurarPosts === 'function') {
                            window.configurarPosts();
                        }
                    }
                });
        });
    }
</script>

<?php include 'includes/footer.php'; ?>