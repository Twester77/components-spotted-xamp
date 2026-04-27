<?php
include_once 'conexao.php';
session_start();

// 1. REMOVIDA A FUNÇÃO formatarMencoes DAQUI (Ela já vem do conexao.php ou feed.php)

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$categoria = isset($_GET['categoria']) ? mysqli_real_escape_string($conn, $_GET['categoria']) : '';

// 2. Query de busca (Mantenha o filtro de categoria para a busca funcionar!)
$sql = "SELECT m.*, u.username, u.foto 
        FROM mensagens m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id";

if (!empty($categoria)) {
    $sql .= " WHERE m.categoria = '$categoria'";
}

$sql .= " ORDER BY m.id DESC LIMIT 30 OFFSET $offset";
$resultado = mysqli_query($conn, $sql);

if (mysqli_num_rows($resultado) > 0) {
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $post_id_atual = $linha['id'];
        $categoria_atual = $linha['categoria'];
?>

        <article id="post-<?php echo $post_id_atual; ?>" class="spotted-card <?php echo $categoria_atual; ?>" style="position: relative;">

            <div class="options-container" style="position: absolute; right: 15px; top: 15px; z-index: 10;">
                <button class="btn-options" onclick="toggleMenu('menu-<?= $post_id_atual ?>')" style="background: none; border: none; color: #fff; cursor: pointer; opacity: 0.6;">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div id="menu-<?= $post_id_atual ?>" class="options-menu-popup" style="display: none; position: absolute; right: 0; background: #222; border: 1px solid #444; border-radius: 8px; width: 150px; box-shadow: 0 5px 15px rgba(0,0,0,0.5);">
                    <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $linha['usuario_id']): ?>
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
                <span class="category-tag">#<?php echo strtoupper($categoria_atual); ?></span>
                <span class="post-time"><?php echo date('d/m H:i', strtotime($linha['data_post'])); ?></span>
            </div>

            <div class="user-info-post">
                <?php if ($categoria_atual === 'anonimo'): ?>
                    <img src="imagensfoto/anonimo-default.jpg" class="avatar-p">
                    <span class="anonimo">Habitante Anônimo</span>
                <?php else: ?>
                    <?php $foto_post = !empty($linha['foto']) ? 'uploads/' . $linha['foto'] : 'imagensfoto/default.jpg'; ?>
                    <img src="<?php echo $foto_post; ?>" class="avatar-p">
                    <a href="ver-perfil.php?user=<?php echo $linha['username']; ?>" class="user-mention">@<?php echo $linha['username']; ?></a>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <p class="post-content"><?php echo formatarMencoes($linha['mensagem']); ?></p>

                <div id="reacoes-post-<?= $post_id_atual ?>" class="reacoes-gravadas">
                    <?php
                    $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
                    $res_contas = mysqli_query($conn, $sql_contas);

                    $minhas_reacoes = [];
                    if (isset($_SESSION['usuario_id'])) {
                        $meu_id = $_SESSION['usuario_id'];
                        $sql_meu = "SELECT tipo_reacao FROM curtidas WHERE mensagem_id = '$post_id_atual' AND usuario_id = '$meu_id'";
                        $res_meu = mysqli_query($conn, $sql_meu);
                        while ($m = mysqli_fetch_assoc($res_meu)) { $minhas_reacoes[] = $m['tipo_reacao']; }
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

                <div class="footer-links">
                    <div class="reacao-wrapper">
                        <span class="btn-reagir">👍 Reagir</span>
                        <div class="reacoes-popup">
                            <?php foreach($tradutor as $tipo => $emoji): ?>
                                <span onclick="window.enviarReacao(<?= $post_id_atual ?>, '<?= $tipo ?>')"><?= $emoji ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar"><i class="fas fa-comments"></i> FOFOCAR</a>
                </div>
            </div>
        </article>

<?php
    }
} else {
    echo "FIM_DADOS";
}
?>