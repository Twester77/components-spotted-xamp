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
<?php $foto_perfil = !empty($_SESSION['usuario_foto']) ? "uploads/" . $_SESSION['usuario_foto'] : "imagensfoto/img_avatar1.jpg";
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
    <?php while($linha = mysqli_fetch_assoc($resultado)) { 
        $post_id_atual = $linha['id'];

        // Lógica de contagem
        $sql_contas = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = '$post_id_atual' GROUP BY tipo_reacao";
        $res_contas = mysqli_query($conn, $sql_contas);
        $reacoes = [];
        $tradutor = ['amei'=>'💖', 'perplecto'=>'😲', 'haha'=>'😂', 'ranco'=>'😠', 'forca'=>'🫂', 'triste'=>'😢', 'tendi-nada'=>'🤔'];
        while($rc = mysqli_fetch_assoc($res_contas)) { $reacoes[$rc['tipo_reacao']] = $rc['total']; }
    ?>

        <article class="spotted-card <?php echo $linha['categoria']; ?>">
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

            <strong>
              <?php 
         // Se o post tiver um ID de usuário, e esse ID for igual ao seu, mostra seu nome, do contrario, mostra 'Anônimo' (padrão do Spotted)
               if (!empty($linha['usuario_id'])) {
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
                <p class="post-content"><?php echo $linha['mensagem']; ?></p>
            </div>

            <div class="container-pilulas-reacoes" style="display: flex; gap: 5px; padding: 0 15px 10px;">
                <?php foreach($reacoes as $tipo => $qtd): ?>
                    <div class="badge-reacao" style="background: rgba(255,255,255,0.1); padding: 2px 10px; border-radius: 20px; font-size: 13px;">
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
        </article> <?php } // FIM DO WHILE ?>
</div> 
</main>

