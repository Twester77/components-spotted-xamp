<?php include 'conexao.php';
/* MOTOR UNIVERSAL DA FENDA 
    Funciona para: Feed Geral, Feed Pessoal e Ver Perfil
*/

// 1. CAPTURA DE PARÂMETROS VIA GET (Mantendo seus nomes originais)
$offset    = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$tipo_feed = isset($_GET['tipo']) ? $_GET['tipo'] : 'geral';
$user_alvo = isset($_GET['user']) ? $_GET['user'] : '';

// 2. CONSTRUÇÃO DA QUERY BASE
$sql = "SELECT m.*, u.username, u.foto, u.pref_vibe_padrao, u.pref_cor_padrao 
        FROM mensagens m 
        INNER JOIN usuarios u ON m.usuario_id = u.id";

$filtros = [];
$tipos = "";
$params = [];

// Filtro por Categoria
if (!empty($categoria)) {
    $filtros[] = "m.categoria = ?";
    $tipos .= "s";
    $params[] = $categoria;
}

// LÓGICA DE TIPO DE FEED (Mantendo suas variáveis)
if ($tipo_feed === 'perfil' && !empty($user_alvo)) {
    $filtros[] = "u.username = ?";
    $tipos .= "s";
    $params[] = $user_alvo;
} elseif ($tipo_feed === 'pessoal' && isset($_SESSION['usuario_id'])) {
    $meu_id = $_SESSION['usuario_id'];
    $filtros[] = "m.usuario_id = ?";
    $tipos .= "i";
    $params[] = $meu_id;
}

// Aplica os filtros na Query
if (count($filtros) > 0) {
    $sql .= " WHERE " . implode(' AND ', $filtros);
}

// Finaliza a Query com Order e Limit (Offset vira ?)
$sql .= " ORDER BY m.id DESC LIMIT 10 OFFSET ?";
$tipos .= "i";
$params[] = $offset;

// --- EXECUÇÃO COM PREPARED STATEMENT ---
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $tipos, ...$params);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

// 3. RENDERIZAÇÃO DO HTML (Absolutamente nada mudou aqui embaixo nas variáveis)
if (mysqli_num_rows($resultado) > 0) {
    while ($linha = mysqli_fetch_assoc($resultado)) {
        $post_id_atual = $linha['id'];
        $categoria_atual = $linha['categoria'];

        $sou_eu = ($linha['usuario_id'] == 1);

        $cor_post = $sou_eu ? '#FFD700' : ($linha['pref_cor_padrao'] ?? '#70cde4');
        $vibe_post = $linha['pref_vibe_padrao'] ?? 'vibe-glass';
        $classe_admin = $sou_eu ? 'post-admin-gold' : '';
?>
        <article id="post-<?php echo $post_id_atual; ?>"
            data-id="<?php echo $post_id_atual; ?>"
            class="spotted-card <?php echo $categoria_atual; ?> <?php echo $vibe_post; ?> <?php echo $classe_admin; ?>"
            style="position: relative; border: 2px solid <?php echo $cor_post; ?> !important;">

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

                <?php if (!empty($linha['imagem_url'])): ?>
                    <div class="container-img-post">
                        <img src="uploads/<?php echo $linha['imagem_url']; ?>"
                            class="spotted-card-img"
                            loading="lazy"
                            alt="Imagem do Spotted">
                    </div>
                <?php endif; ?>

                <div id="reacoes-post-<?= $post_id_atual ?>" class="reacoes-gravadas">

                    <?php
                    // Query de reações (Simplificada para não estender o código, mas mantendo a lógica)
                    $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = ? GROUP BY tipo_reacao";
                    $st_c = mysqli_prepare($conn, $sql_contas);
                    mysqli_stmt_bind_param($st_c, "i", $post_id_atual);
                    mysqli_stmt_execute($st_c);
                    $res_contas = mysqli_stmt_get_result($st_c);

                    $minhas_reacoes = [];
                    if (isset($_SESSION['usuario_id'])) {
                        $meu_id = $_SESSION['usuario_id'];
                        $sql_meu = "SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?";
                        $st_m = mysqli_prepare($conn, $sql_meu);
                        mysqli_stmt_bind_param($st_m, "ii", $post_id_atual, $meu_id);
                        mysqli_stmt_execute($st_m);
                        $res_meu = mysqli_stmt_get_result($st_m);
                        while ($m = mysqli_fetch_assoc($res_meu)) {
                            $minhas_reacoes[] = $m['tipo_reacao'];
                        }
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
                            <?php foreach ($tradutor as $tipo => $emoji): ?>
                                <span onclick="window.enviarReacao(<?= $post_id_atual ?>, '<?= $tipo ?>')"><?= $emoji ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar"><i class="fas fa-comments"></i> Fofocar </a>
                </div>
            </div>
        </article>
<?php
    }
} else {
    echo "FIM_DADOS";
}
?>