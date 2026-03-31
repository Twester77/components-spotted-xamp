<?php
// 1. LÓGICA DE SEGURANÇA E CONFIGURAÇÃO
include 'conexao.php'; 
session_start();

// TRAVA DE SEGURANÇA
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. FUNÇÕES AUXILIARES
function formatarMencoes($texto) {
    $padrao = '/@([a-zA-Z0-9._]+)/';
    $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ff7011; font-weight: bold; text-decoration: none;">@$1</a>';
    return preg_replace($padrao, $substituicao, $texto);
}

// 3. BUSCA DE DADOS DO USUÁRIO LOGADO
$usuario_id = $_SESSION['usuario_id'];
$query_user = "SELECT nome, foto, username FROM usuarios WHERE id = '$usuario_id'";
$res_user = mysqli_query($conn, $query_user);
$user_data = mysqli_fetch_assoc($res_user);

$foto_perfil = !empty($user_data['foto']) ? "uploads/" . $user_data['foto'] : "imagensfoto/img_avatar1.jpg";
$nome_exibicao = !empty($user_data['username']) ? "@" . $user_data['username'] : $user_data['nome'];

// 4. LÓGICA DE FILTRO DOS POSTS (CONSERTADA AQUI)
$categoria_selecionada = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// ADICIONEI A VÍRGULA QUE FALTAVA ENTRE username E nome
$sql = "SELECT m.*, u.username, u.nome 
        FROM mensagens m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id";

if (!empty($categoria_selecionada)) {
    $cat = mysqli_real_escape_string($conn, $categoria_selecionada);
    $sql .= " WHERE m.categoria = '$cat'";
}
$sql .= " ORDER BY m.id DESC";
$resultado = mysqli_query($conn, $sql);

// Se a query falhar, mostra o erro do banco em vez de tela branca
if (!$resultado) {
    die("Erro no Banco de Dados: " . mysqli_error($conn));
}

// 5. INCLUDES DE INTERFACE
include 'includes/header.php'; 
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<div class="user-info" style="padding: 20px; text-align: center; background: rgba(0,0,0,0.1); border-bottom: 2px solid #ff7011;">
    <img src="<?php echo $foto_perfil; ?>" 
         style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #ff7011; box-shadow: 0 4px 15px rgba(255, 112, 17, 0.4);" 
         alt="Meu Perfil">
    <div style="margin-top: 10px;">
        <span style="color: white; font-weight: bold; font-size: 18px;">Olá, <?php echo $nome_exibicao; ?>!</span>
    </div>
</div>

<main>
    <a href="novo-post.php" class="btn-flutuante">+</a>

    <?php include 'includes/filtros.php'; ?>

    <div class="container-feed">
        <?php while($linha = mysqli_fetch_assoc($resultado)) { 
            $post_id_atual = $linha['id'];

            // Lógica de contagem de reações
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
                    <?php if ($linha['categoria'] == 'perdidos'): ?>
                        <?php if ($linha['subcategoria'] == 'achei'): ?>
                            <span class="badge-achado">✨ ACHADO</span>
                        <?php else: ?>
                            <span class="badge-perdido">🔍 PERDIDO</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <strong style="margin-left: 10px;">
                        <?php 
                        if (!empty($linha['username'])) {
                            echo "@" . $linha['username']; 
                        } elseif (!empty($linha['usuario_id'])) {
                            echo "@Estudante_" . $linha['usuario_id']; 
                        } else {
                            echo "@Anônimo";
                        }
                        ?>
                    </strong>
                </span>
                <span class="post-time"><?php echo date('d/m', strtotime($linha['data_post'])); ?></span>
            </div>
            
            <div class="card-body">
                <p class="post-content"><?php echo formatarMencoes($linha['mensagem']); ?></p>
            </div>

            <div class="container-pilulas-reacoes" style="display: flex; gap: 5px; padding: 0 15px 10px;">
                <?php foreach($reacoes as $tipo => $qtd): ?>
                    <div class="badge-reacao" style="background: rgba(255,255,255,0.1); padding: 2px 10px; border-radius: 20px; font-size: 13px; color: #fff;">
                        <?php echo $tradutor[$tipo]; ?> <b><?php echo $qtd; ?></b>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="footer-links" style="position: relative;">
                <div class="reacao-wrapper">
                    <a href="#" class="btn-reagir">Reagir</a>
                    <div class="reacoes-popup">
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=amei">💖</a>
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=perplecto">😲</a>
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=haha">😂</a>
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=ranco">😠</a>
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=forca">🫂</a>
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=triste">😢</a>
                        <a href="includes/reagir.php?id=<?php echo $linha['id']; ?>&tipo=tendi-nada">🤔</a>
                    </div>
                </div>
                <a href="post.php?id=<?php echo $linha['id']; ?>" class="btn-fofocar">Fofocar</a>
            </div>
        </article> 
        <?php } ?>
    </div> 
</main>

<?php include 'includes/footer.php'; ?>