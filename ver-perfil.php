<?php
session_start();
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';

// 1. PEGA O USERNAME DA URL (ex: ver-perfil.php?user=apresenca.fev)
$user_get = mysqli_real_escape_string($conn, $_GET['user']);

// 2. BUSCA OS DADOS DA CRIATURA NO BANCO
$sql = "SELECT * FROM usuarios WHERE username = '$user_get'";
$res = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($res);

// Se o usuário não existir, volta pro feed
if (!$dados) { header("Location: feed.php"); exit(); }

$id_visto = $dados['id'];
$foto = !empty($dados['foto']) ? "uploads/".$dados['foto'] : "imagensfoto/img_avatar1.jpg";
$capa = !empty($dados['capa']) ? "uploads/".$dados['capa'] : "imagensfoto/capa_padrao.jpg";
?>

<style>
    /* SE FOR O ID 1 (A PRESENÇA), ATIVA O MODO LENDÁRIO */
    <?php if ($id_visto == 1): ?>
    body { background: #000 !important; }
    .card-perfil { border: 2px solid #ffbc00; box-shadow: 0 0 30px rgba(255, 188, 0, 0.3); }
    .nome-user { color: #ffbc00 !important; text-shadow: 0 0 10px #ffbc00; }
    <?php endif; ?>
</style>

<div class="container-perfil" style="max-width: 600px; margin: 20px auto; background: #1a1a1a; border-radius: 15px; overflow: hidden; color: white; font-family: sans-serif;">
    
    <div class="capa" style="height: 180px; background: url('<?php echo $capa; ?>') center/cover;"></div>

    <div style="text-align: center; margin-top: -60px; padding: 20px;">
        <img src="<?php echo $foto; ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid #1a1a1a; object-fit: cover;">
        
        <h2 class="nome-user" style="margin-top: 10px;"><?php echo $dados['nome']; ?></h2>
        <p style="color: #ff7011; font-weight: bold;">@<?php echo $dados['username']; ?></p>
        
        <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 15px 0;">
            <p><?php echo $dados['bio']; ?></p>
        </div>

        <?php
// LÓGICA PARA VERIFICAR O STATUS DO BOTÃO
$meu_id = $_SESSION['usuario_id'];
$id_da_pagina = $dados['id'];

$check_follow = mysqli_query($conn, "SELECT * FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_da_pagina'");
$ja_segue = mysqli_num_rows($check_follow) > 0;
?>

<?php if ($meu_id != $id_da_pagina): ?>
    <a href="seguir.php?id=<?php echo $id_da_pagina; ?>&user=<?php echo $user_get; ?>" style="text-decoration: none;">
        <button style="width: 100%; padding: 12px; border-radius: 25px; border: none; 
                       background: <?php echo $ja_segue ? '#444' : '#ff7011'; ?>; 
                       color: white; font-weight: bold; cursor: pointer; transition: 0.3s;">
            <?php echo $ja_segue ? '✓ Seguindo' : '+ Seguir esta Lenda'; ?>
        </button>
    </a>
<?php else: ?>
    <p style="color: #666; font-size: 13px;">Este é você, @<?php echo $dados['username']; ?></p>
<?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>