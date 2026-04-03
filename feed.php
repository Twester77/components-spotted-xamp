<?php
// 1. LÓGICA DE SEGURANÇA E CONFIGURAÇÃO
include 'conexao.php'; 
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. FUNÇÃO AUXILIAR (Limpa, segura e funcional)
function formatarMencoes($texto) {
    $texto_seguro = htmlspecialchars($texto);
    $padrao = '/@([^\s]+)/';
    $substituicao = '<a href="perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
    return preg_replace($padrao, $substituicao, $texto_seguro);
}

// 3. BUSCA DE DADOS DO USUÁRIO LOGADO
$usuario_id = $_SESSION['usuario_id'];
$query_user = "SELECT nome, foto, username FROM usuarios WHERE id = '$usuario_id'";
$res_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($res_user);

$foto_perfil = !empty($user_data['foto']) ? "uploads/" . $user_data['foto'] : "imagensfoto/img_avatar_generico.jpg";
$nome_exibicao = !empty($user_data['username']) ? "@" . $user_data['username'] : $user_data['nome'];

// 4. LÓGICA DE FILTRO (COM JOIN PARA FOTOS E NOMES)
$categoria_selecionada = isset($_GET['categoria']) ? $_GET['categoria'] : '';

$sql = "SELECT m.*, u.username, u.nome, u.foto 
        FROM mensagens m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id";

if (!empty($categoria_selecionada)) {
    $cat = mysqli_real_escape_string($conn, $categoria_selecionada);
    $sql .= " WHERE m.categoria = '$cat'";
}

$sql .= " ORDER BY m.id DESC";
$resultado = mysqli_query($conn, $sql);

include 'includes/header.php'; 
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<div class="user-info" style="padding: 20px; text-align: center; background: rgba(0,0,0,0.1); border-bottom: 2px solid #ff7011;">
    <img src="<?php echo $foto_perfil; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #ff7011;">
    <div style="margin-top: 10px;">
        <span style="color: white; font-weight: bold; font-size: 1.2rem;">Olá, <?php echo $nome_exibicao; ?>!</span>
    </div>
</div>

<main>
    <a href="novo-post.php" class="btn-flutuante">+</a>
    
    <div class="sessao-topo-feed" style="margin-bottom: 20px;">
        <?php include 'includes/filtros.php'; ?>
    </div>

    <div class="container-feed">
        <?php while($linha = mysqli_fetch_assoc($resultado)): 
            $post_id_atual = $linha['id']; 
            
            // Contagem de reações
            $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
            $res_contas = mysqli_query($conn, $sql_contas);
            $reacoes = [];
            $tradutor = ['amei'=>'💖', 'perplecto'=>'😲', 'haha'=>'😂', 'ranco'=>'😠', 'forca'=>'🫂', 'triste'=>'😢', 'tendi-nada'=>'🤔'];
            while($rc = mysqli_fetch_assoc($res_contas)) { $reacoes[$rc['tipo_reacao']] = $rc['total']; }
        ?>
     
        <article id="post-<?php echo $linha['id']; ?>" class="spotted-card <?php echo $linha['categoria']; ?>">
            
            <div class="card-header">
                <span class="category-tag">
                    #<?php echo strtoupper($linha['categoria']); ?> 
                    
                    <small style="margin-left: 10px; display: inline-flex; align-items: center; gap: 8px;">
                        <?php if (!empty($linha['username'])): 
                            $foto_autor = !empty($linha['foto']) ? "uploads/" . $linha['foto'] : "imagensfoto/img_avatar_generico.jpg";
                        ?>
                            <img src="<?php echo $foto_autor; ?>" class="avatar-p" style="width: 25px; height: 25px; border-radius: 50%; object-fit: cover;">
                            <a href="perfil.php?id=<?php echo $linha['usuario_id']; ?>" style="color: #ffbc00; text-decoration: none; font-weight: bold;">
                                @<?php echo $linha['username']; ?>
                            </a>
                        <?php else: ?>
                            <span style="opacity: 0.7; color: #fff;">🕵️ Estudante Anônimo</span>
                        <?php endif; ?>
                    </small>
                </span>
                
                <span class="post-time"><?php echo date('d/m', strtotime($linha['data_post'])); ?></span>
            </div>
            
            <div class="card-body">
                <p class="post-content" style="white-space: pre-wrap;"><?php echo formatarMencoes($linha['mensagem']); ?></p>     
            </div>

            <div class="container-pilulas-reacoes" style="display: flex; gap: 5px; padding: 0 15px 10px; flex-wrap: wrap;">
                <?php foreach($reacoes as $tipo => $qtd): ?>
                    <div class="badge-reacao" style="background: rgba(255,255,255,0.1); padding: 2px 10px; border-radius: 20px; font-size: 13px; color: #fff;">
                        <?php echo $tradutor[$tipo]; ?> <b><?php echo $qtd; ?></b>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="footer-links">
                <div class="reacao-wrapper">
                    <span class="btn-reagir">👍 Reagir</span>
                    <div class="reacoes-popup">
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=amei" title="Amei">💖</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=perplecto" title="Tô Perplecto">😲</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=haha" title="Haha">😂</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=ranco" title="Que ranço!">😠</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=tendi-nada" title="Entendi nada">🤔</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=forca" title="Força">🫂</a>
                    </div>
                </div>

                <a href="post.php?id=<?php echo $post_id_atual; ?>" class="btn-fofocar">
                    💬 FOFOCAR
                </a>
            </div>
        </article> 
        <?php endwhile; ?>
    </div> 
</main>

<?php include 'includes/footer.php'; ?>