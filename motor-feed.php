<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once __DIR__ . '/conexao.php';

/* MOTOR UNIVERSAL DA FENDA - OTIMIZADO (SEM N+1) */
$offset    = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$tipo_feed = isset($_GET['tipo']) ? $_GET['tipo'] : 'geral';
$user_alvo = isset($_GET['user']) ? $_GET['user'] : '';

// ==================================================
// 1. QUERY PRINCIPAL (COM SUBQUERIES PARA CONTAGENS)
// ==================================================
$sql = "SELECT m.*, u.username, u.foto, u.pref_vibe_padrao, u.pref_cor_padrao,
        (SELECT COUNT(c.id) FROM comentarios c WHERE c.id_mensagem = m.id) as total_comentarios,
        (SELECT COUNT(r.id) FROM curtidas r WHERE r.mensagem_id = m.id) as total_reacoes
        FROM mensagens m 
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.status = 'ativo'";

$filtros = [];
$tipos = "";
$params = [];

if (!empty($categoria)) {
    $filtros[] = "m.categoria = ?";
    $tipos .= "s";
    $params[] = $categoria;
}
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
if (count($filtros) > 0) {
    $sql .= " AND " . implode(' AND ', $filtros);
}
$sql .= " ORDER BY m.id DESC LIMIT 10 OFFSET ?";
$tipos .= "i";
$params[] = $offset;

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $tipos, ...$params);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) == 0) {
    echo "FIM_DADOS";
    exit();
}

// ==================================================
// 2. BUSCAR TODAS AS REAÇÕES DETALHADAS (UMA ÚNICA VEZ)
// ==================================================
$posts_ids = [];
while ($linha = mysqli_fetch_assoc($resultado)) {
    $posts_ids[] = $linha['id'];
}
mysqli_data_seek($resultado, 0);

$reacoes_por_post = [];
if (!empty($posts_ids)) {
    $placeholders = implode(',', array_fill(0, count($posts_ids), '?'));
    $sql_react = "SELECT mensagem_id, tipo_reacao, COUNT(*) as total 
                  FROM curtidas 
                  WHERE mensagem_id IN ($placeholders) 
                  GROUP BY mensagem_id, tipo_reacao";
    $stmt_react = mysqli_prepare($conn, $sql_react);
    $types = str_repeat('i', count($posts_ids));
    mysqli_stmt_bind_param($stmt_react, $types, ...$posts_ids);
    mysqli_stmt_execute($stmt_react);
    $res_react = mysqli_stmt_get_result($stmt_react);
    while ($row = mysqli_fetch_assoc($res_react)) {
        $reacoes_por_post[$row['mensagem_id']][$row['tipo_reacao']] = $row['total'];
    }
    mysqli_stmt_close($stmt_react);
}

// Buscar reações do usuário logado
$minhas_reacoes_por_post = [];
if (isset($_SESSION['usuario_id'])) {
    $meu_id = $_SESSION['usuario_id'];
    $sql_my = "SELECT mensagem_id, tipo_reacao 
               FROM curtidas 
               WHERE usuario_id = ? AND mensagem_id IN ($placeholders)";
    $stmt_my = mysqli_prepare($conn, $sql_my);
    mysqli_stmt_bind_param($stmt_my, 'i' . $types, $meu_id, ...$posts_ids);
    mysqli_stmt_execute($stmt_my);
    $res_my = mysqli_stmt_get_result($stmt_my);
    while ($row = mysqli_fetch_assoc($res_my)) {
        $minhas_reacoes_por_post[$row['mensagem_id']][] = $row['tipo_reacao'];
    }
    mysqli_stmt_close($stmt_my);
}

// ==================================================
// 3. LOOP DE EXIBIÇÃO
// ==================================================
$tradutor = ['amei' => '💖', 'perplecto' => '😲', 'haha' => '😂', 'ranco' => '🙄', 'forca' => '🫂', 'triste' => '😢', 'tendi-nada' => '🤔'];

while ($linha = mysqli_fetch_assoc($resultado)) {
    $post_id_atual = $linha['id'];
    $categoria_atual = $linha['categoria'];
    $total_comentarios = $linha['total_comentarios'] ?? 0;
    $total_reacoes = $linha['total_reacoes'] ?? 0;
    
    // 🔥 Sessão segura
    $usuario_logado = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
    $sou_eu = ($linha['usuario_id'] == $usuario_logado);
    
    $cor_post = $sou_eu ? '#FFD700' : ($linha['pref_cor_padrao'] ?? '#70cde4');
    $vibe_post = $linha['pref_vibe_padrao'] ?? 'vibe-glass';
    $classe_admin = $sou_eu ? 'post-admin-gold' : '';

    // Dados do autor
    if ($categoria_atual === 'anonimo') {
        $avatar = 'imagensfoto/anonimo-default.webp';
        $nome_autor = 'Habitante Anônimo';
        $link_autor = '#';
    } else {
        $avatar = !empty($linha['foto']) ? 'uploads/' . $linha['foto'] : 'imagensfoto/default.webp';
        $nome_autor = '@' . htmlspecialchars($linha['username']);
        $link_autor = 'ver-perfil.php?user=' . urlencode($linha['username']);
    }
    $mensagem_corpo = nl2br(formatarMencoes($linha['mensagem']));
    
    $imagem_post = '';
    if (!empty($linha['imagem_url'])) {
        if (filter_var($linha['imagem_url'], FILTER_VALIDATE_URL)) {
            $imagem_post = htmlspecialchars($linha['imagem_url']);
        } else {
            $imagem_post = 'postagens/' . htmlspecialchars($linha['imagem_url']);
        }
    }
    
    $data_post = date('d/m H:i', strtotime($linha['data_post']));
?>
<article class="spotted-card <?php echo $categoria_atual; ?> <?php echo $vibe_post; ?> <?php echo $classe_admin; ?>" 
         data-id="<?php echo $post_id_atual; ?>"
         style="border: 2px solid <?php echo $cor_post; ?> !important;">
    <div class="card-inner-content">
        <div class="card-header">
            <span class="category-tag">#<?php echo strtoupper($categoria_atual); ?></span>
            <span class="post-time"><?php echo $data_post; ?></span>
        </div>
        <div class="user-info-post">
            <img src="<?php echo $avatar; ?>" class="avatar-p">
            <?php if ($categoria_atual !== 'anonimo'): ?>
                <a href="<?php echo $link_autor; ?>" class="user-mention"><?php echo $nome_autor; ?></a>
            <?php else: ?>
                <span class="user-anonimo"><?php echo $nome_autor; ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p class="post-content"><?php echo $mensagem_corpo; ?></p>
            <?php if ($imagem_post): ?>
                <div class="container-img-post">
                    <img src="<?php echo $imagem_post; ?>" class="spotted-card-img" loading="lazy" alt="Imagem do Post">
                </div>
            <?php endif; ?>
            <div id="reacoes-post-<?php echo $post_id_atual; ?>" class="reacoes-gravadas">
                <?php
                $detalhes = $reacoes_por_post[$post_id_atual] ?? [];
                $minhas = $minhas_reacoes_por_post[$post_id_atual] ?? [];
                foreach ($detalhes as $tipo => $total): ?>
                    <span class="reacao-item <?php echo in_array($tipo, $minhas) ? 'voted' : ''; ?>">
                        <?php echo $tradutor[$tipo] ?? '👍'; ?> <?php echo $total; ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="footer-links">
                <div class="reacao-wrapper">
                    <span class="btn-reagir">👍 Reagir</span>
                    <div class="reacoes-popup">
                        <?php foreach ($tradutor as $tipo => $emoji): ?>
                            <span onclick="window.enviarReacao(<?php echo $post_id_atual; ?>, '<?php echo $tipo; ?>')"><?php echo $emoji; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a href="comentarios-post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar"><i class="fas fa-comments"></i> Fofocar</a>
            </div>
        </div>
    </div>
</article>
<?php
}
?>