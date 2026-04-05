<?php

// 1. LÓGICA DE SEGURANÇA E CONFIGURAÇÃO
include 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}


// 2. FUNÇÃO AUXILIAR PARA MENÇÕES (@)
function formatarMencoes($texto)
{
    $texto_seguro = htmlspecialchars($texto);
    $padrao = '/@([^\\s]+)/';
    $substituicao = '<a href="perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
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

// Depois adicionamos o filtro, se ele existir
if (!empty($categoria_selecionada)) {
    $sql .= " WHERE m.categoria = '$categoria_selecionada'";
}

$sql .= " ORDER BY m.id DESC";
$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    die("Erro no SQL: " . mysqli_error($conn));
}


// 5. INCLUDES DA PÁGINA (Ajustado para os seus paths)
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<div style="margin-top: 100px;">
    <?php include 'includes/filtros.php'; ?>
</div>

<main class="container-feed">
    <?php if (mysqli_num_rows($resultado) > 0): ?>
        <?php while($linha = mysqli_fetch_assoc($resultado)): 
    $post_id_atual = $linha['id']; 
?>
     
<article id="post-<?php echo $post_id_atual; ?>" class="spotted-card <?php echo $linha['categoria']; ?>">
    <div class="card-header">
        <span class="category-tag">#<?php echo strtoupper($linha['categoria']); ?></span>
    
        <div class="user-info-post" style="text-align: right;">
            <span class="post-time" style="display: block; font-size: 13px; opacity: 0.6;">
                🕒 <?php echo date('d/m H:i', strtotime($linha['data_post'])); ?>
            </span>
        
            <?php if (!empty($linha['username'])): ?>
                <img src="<?php echo !empty($linha['foto']) ? 'uploads/'.$linha['foto'] : 'imagensfoto/default.jpg'; ?>" class="avatar-p">
                <a href="perfil.php?id=<?php echo $linha['usuario_id']; ?>" class="user-mention">
                    @<?php echo $linha['username']; ?>
                </a>
            <?php else: ?>
                <img src="imagensfoto/default.jpg" class="avatar-p">
                <span class="anonimo">🕵️ Anônimo</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body">
        <p class="post-content"><?php echo formatarMencoes($linha['mensagem']); ?></p>   
    </div>

    <div class="footer-links">
        <div class="reacao-wrapper">
            <span class="btn-reagir">👍 Reagir</span>
            
            <div class="reacoes-popup">
                <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=amei&ref=post-<?php echo $post_id_atual; ?>" title="Amei">💖</a>
                <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=perplecto&ref=post-<?php echo $post_id_atual; ?>" title="Tô Perplecto">😲</a>
                <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=haha&ref=post-<?php echo $post_id_atual; ?>" title="Haha">😂</a>
                <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=ranco&ref=post-<?php echo $post_id_atual; ?>" title="Que ranço!">😠</a>
                <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=tendi-nada&ref=post-<?php echo $post_id_atual; ?>" title="Entendi nada">🤔</a>
                <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=forca&ref=post-<?php echo $post_id_atual; ?>" title="Força">🫂</a>
            </div>
        </div>

        <div class="reacoes-gravadas">
            <?php
            $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
            $res_contas = mysqli_query($conn, $sql_contas);
            
            $tradutor = [
                'amei'=>'💖', 'perplecto'=>'😲', 'haha'=>'😂', 
                'ranco'=>'😠', 'forca'=>'🫂', 'triste'=>'😢', 'tendi-nada'=>'🤔'
            ];

            while($rc = mysqli_fetch_assoc($res_contas)) {
                $emoji = $tradutor[$rc['tipo_reacao']] ?? '👍';
                echo "<span class='reacao-item'>$emoji " . $rc['total'] . "</span>";
            }
            ?>
        </div>

        <a href="post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar">
            <i class="fas fa-comments"></i> 💬 FOFOCAR
        </a>
    </div>
</article> 
<?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 50px;">Nenhum spotted encontrado nesta categoria. 🌊</p>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>