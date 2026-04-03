<?php
// 1. LÓGICA DE SEGURANÇA E CONFIGURAÇÃO (MANTIDA)
include 'conexao.php'; 
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. FUNÇÃO AUXILIAR (MANTIDA)
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

// 4. LÓGICA DE FILTRO (MANTIDA)
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

$body_class = "pg-fundo-azul"; 
include 'includes/header.php'; 
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<div class="user-info" style="padding: 40px 20px; text-align: center;">
    <img src="<?php echo $foto_perfil; ?>" class="avatar-fenda avatar-g" style="border-color: var(--amarelo-fenda);">
    <div style="margin-top: 15px;">
        <span style="color: white; font-weight: bold; font-size: 1.4rem; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
            Olá, <?php echo $nome_exibicao; ?>!
        </span>
    </div>
</div>

<main>
    <a href="novo-post.php" class="btn-flutuante">
        <i class="bi bi-plus-lg"></i>
    </a>

    <div class="sessao-topo-feed">
        <?php include 'includes/filtros.php'; ?>
    </div>

    <div class="container-feed" style="max-width: 650px; margin: 0 auto; padding: 0 15px;">
        <?php while($linha = mysqli_fetch_assoc($resultado)): 
            $post_id_atual = $linha['id']; 
            $reacoes = [];
            $tradutor = ['amei'=>'💖', 'perplecto'=>'😲', 'haha'=>'😂', 'ranco'=>'😠', 'forca'=>'🫂', 'triste'=>'😢', 'tendi-nada'=>'🤔'];
            
            $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
            $res_contas = mysqli_query($conn, $sql_contas);
            while($rc = mysqli_fetch_assoc($res_contas)) { $reacoes[$rc['tipo_reacao']] = $rc['total']; }
        ?>
     
        <article id="post-<?php echo $linha['id']; ?>" class="spotted-card <?php echo $linha['categoria']; ?>">
            
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <?php if (!empty($linha['username'])): 
                        $foto_autor = !empty($linha['foto']) ? "uploads/" . $linha['foto'] : "imagensfoto/img_avatar_generico.jpg";
                  ?>
                        <img src="<?php echo $foto_autor; ?>" class="avatar-fenda avatar-p">
                        <a href="perfil.php?id=<?php echo $linha['usuario_id']; ?>" style="color: #ffbc00; text-decoration: none; font-weight: bold;">
                           @<?php echo $linha['username']; ?>
                        </a>
                  <?php else: ?>
                        <span style="font-size: 1.2rem;">🕵️</span>
                        <span style="opacity: 0.7; font-size: 0.9rem;">Anônimo</span>
                  <?php endif; ?>
                </div>
                
                <span style="font-size: 0.8rem; opacity: 0.5;">
                    #<?php echo strtoupper($linha['categoria']); ?> • <?php echo date('d/m', strtotime($linha['data_post'])); ?>
                </span>
            </div>
            
            <div class="card-body">
                <p style="font-size: 1.1rem; line-height: 1.6;">
                    <?php echo formatarMencoes($linha['mensagem']); ?> 
                </p>     
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 15px;">
                <?php foreach($reacoes as $tipo => $qtd): ?>
                    <div style="background: rgba(255,255,255,0.05); padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; border: 1px solid rgba(255,255,255,0.1);">
                        <?php echo $tradutor[$tipo]; ?> <b><?php echo $qtd; ?></b>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="footer-links" style="display: flex; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <div class="reacao-wrapper" style="flex: 1;">
                    <span class="btn-reagir" style="display: block; text-align: center; padding: 15px; cursor: pointer;">👍 Reagir</span>
                    
                    <div class="reacoes-popup">
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=amei">💖</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=perplecto">😲</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=haha">😂</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=ranco">😠</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=tendi-nada">🤔</a>
                        <a href="includes/reagir.php?id=<?php echo $post_id_atual; ?>&tipo=forca">🫂</a>
                    </div>
                </div>

                <a href="post.php?id=<?php echo $post_id_atual; ?>" class="btn-fofocar" style="flex: 1; text-align: center; padding: 15px; text-decoration: none;">
                    💬 FOFOCAR
                </a>
            </div>
        </article> 
        <?php endwhile; ?>
    </div> 
</main>

<?php include 'includes/footer.php'; ?>