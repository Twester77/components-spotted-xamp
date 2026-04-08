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
    
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <span class="category-tag">#<?php echo strtoupper($linha['categoria']); ?></span>
        <span class="post-time" style="opacity: 0.7;">
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
        <?php 
            $foto_post = !empty($linha['foto']) ? 'uploads/'.$linha['foto'] : 'imagensfoto/default.jpg'; 
        ?>
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

        <a href="post.php?id=<?php echo $post_id_atual; ?>#fofocar" class="btn-fofocar">
            <i class="fas fa-comments"></i> FOFOCAR
        </a>
    </div>
</article> 
<?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 50px; opacity:0.7;">Nenhum spotted encontrado nesta categoria . </p>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>