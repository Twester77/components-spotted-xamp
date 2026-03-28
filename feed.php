<?php
// 1. INCLUDES DE CONFIGURAÇÃO E LÓGICA
include 'conexao.php';
include 'includes/header.php'; // O session_start() já acontece aqui dentro!

// 2. TRAVA DE SEGURANÇA: Se não tiver logado, tchau!
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 3. LOGICA DE FILTRO
$categoria_selecionada = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$sql = "SELECT * FROM mensagens";
if (!empty($categoria_selecionada)) {
    $cat = mysqli_real_escape_string($conn, $categoria_selecionada);
    $sql .= " WHERE categoria = '$cat'";
}
$sql .= " ORDER BY id DESC";
$resultado = mysqli_query($conn, $sql);

// 4. OUTROS INCLUDES DE INTERFACE
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<?php 
  $foto_perfil = !empty($_SESSION['usuario_foto']) ? "uploads/" . $_SESSION['usuario_foto'] : "imagensfoto/img_avatar1.jpg";
?>
<div class="user-info" style="padding: 20px; text-align: center; background: rgba(0,0,0,0.1); border-bottom: 2px solid #ff7011;">
    <img src="<?php echo $foto_perfil; ?>" 
         style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #ff7011; box-shadow: 0 4px 10px rgba(0,0,0,0.3);" 
         alt="Meu Perfil">
    <div style="margin-top: 10px;">
        <span style="color: white; font-weight: bold; font-size: 18px;">Olá, <?php echo $_SESSION['usuario_nome']; ?>!</span>
    </div>
</div>

<main>
    <a href="novo-post.php" class="btn-flutuante">+</a>

    <?php include 'includes/filtros.php'; ?>

    <div class="container-feed">
        <?php while($linha = mysqli_fetch_assoc($resultado)) { ?>
            <article class="spotted-card <?php echo $linha['categoria']; ?>">
                <div class="card-header">
                    <span class="category-tag">
                        #<?php echo strtoupper($linha['categoria']); ?> 
                        <?php echo (empty($linha['usuario_id'])) ? "Anônimo" : "Estudante"; ?>
                    </span>
                    <span class="post-time">
                        <?php echo date('d/m', strtotime($linha['data_post'])); ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <p class="post-content"><?php echo $linha['mensagem']; ?></p>
                </div>

                <div class="footer-links">
                    <div class="container-reacoes-wrapper">
                        <span class="btn-trigger-reacoes">
                            <i class="fa-solid fa-face-smile"></i> Reagir
                        </span>
                
                        <div class="reacoes-popup">
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=amei" title="Amei">💖</a>
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=perplecto" title="Perplecto">😲</a>
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=haha" title="Haha">😂</a>
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=ranco" title="Ranço">😠</a>
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=forca" title="Força">🫂</a>
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=triste" title="Magoei">😢</a>
                            <a href="/includes/reagir.php echo $linha['id']; ?>&tipo=tendi-nada" title="Tendi foi nada">🤔</a>
                        </div>
                    </div>
            
                    <a href="post.php?id=<?php echo $linha['id']; ?>" class="btn-comentar">
                        <i class="fa-solid fa-comment"></i> Fofocar
                    </a>
                </div>
            </article>
        <?php } ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>