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

// --- AQUI É O LUGAR CERTO DA CONTAGEM! ---
// --- CONTAGEM DE SEGUIDORES (Quem segue esse perfil) ---
$sql_seguidores = "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'";
$res_seguidores = mysqli_query($conn, $sql_seguidores);
$dados_seguidores = mysqli_fetch_assoc($res_seguidores);
$total_seguidores = $dados_seguidores['total'];

// --- CONTAGEM DE SEGUINDO (Quem esse perfil segue) ---
$sql_seguindo = "SELECT COUNT(*) as total FROM seguidores WHERE id_seguidor = '$id_visto'";
$res_seguindo = mysqli_query($conn, $sql_seguindo);
$dados_seguindo = mysqli_fetch_assoc($res_seguindo);
$total_seguindo = $dados_seguindo['total']; 
?>

<style>
    /* SE FOR O ID 1 (A PRESENÇA), ATIVA O MODO LENDÁRIO */
    <?php if ($id_visto == 1): ?>
    body { background: #000 !important; }
    .card-perfil { border: 2px solid #ffbc00; box-shadow: 0 0 30px rgba(255, 188, 0, 0.3); }
    .nome-user { color: #ffbc00 !important; text-shadow: 0 0 10px #ffbc00; }
    <?php endif; ?>
</style>

<div class="container-perfil" style="max-width: 500px; width: 95%; margin: 40px auto; background: #1a1a1a; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.8); border: 1px solid #333;">  

    <div class="capa" style="height: 180px; background: url('<?php echo $capa; ?>') center/cover;"></div>

    <div style="text-align: center; margin-top: -60px; padding: 20px;">
        
        <img src="<?php echo $foto; ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid #1a1a1a; object-fit: cover;">
        
        <h2 class="nome-user" style="margin-top: 10px;"><?php echo $dados['nome']; ?></h2>
        <p style="color: #ff7011; font-weight: bold;">@<?php echo $dados['username']; ?></p>

        <div style="display: flex; justify-content: center; gap: 40px; margin: 20px 0; padding: 10px 0; border-top: 1px solid #333; border-bottom: 1px solid #333;">
            <div style="text-align: center;">
                <span style="display: block; font-size: 18px; font-weight: bold; color: #ffbc00;"><?php echo $total_seguidores; ?></span>
                <span style="font-size: 10px; color: #888; text-transform: uppercase;">Seguidores</span>
            </div>
            <div style="text-align: center; border-left: 1px solid #333; padding-left: 40px;">
                <span style="display: block; font-size: 18px; font-weight: bold; color: #ffbc00;"><?php echo $total_seguindo; ?></span>
                <span style="font-size: 10px; color: #888; text-transform: uppercase;">Seguindo</span>
            </div>
        </div>
        
        <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 15px 0; border: 1px solid rgba(255,112,17,0.1);">
            <p style="font-size: 14px; line-height: 1.5; color: #ddd;"><?php echo $dados['bio']; ?></p>
        </div>
        <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 15px 0; border: 1px solid rgba(255,112,17,0.1);">
          <p style="font-size: 14px; line-height: 1.5; color: #ddd;">
             <?php echo !empty($dados['bio']) ? $dados['bio'] : "<i>Este habitante da Fenda ainda não escreveu uma bio...</i>"; ?>
          </p>
       </div>

        <?php
        $meu_id = $_SESSION['usuario_id'];
        $id_da_pagina = $dados['id'];
        $check_follow = mysqli_query($conn, "SELECT * FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_da_pagina'");
        $ja_segue = mysqli_num_rows($check_follow) > 0;
        ?>

        <?php if ($meu_id != $id_da_pagina): ?>
            <a href="seguir.php?id=<?php echo $id_da_pagina; ?>&user=<?php echo $user_get; ?>" style="text-decoration: none;">
                <button style="width: 100%; padding: 12px; border-radius: 25px; border: none; background: <?php echo $ja_segue ? '#444' : '#ff7011'; ?>; color: white; font-weight: bold; cursor: pointer;">
                    <?php echo $ja_segue ? '✓ Seguindo' : '+ Seguir esta Lenda'; ?>
                </button>
            </a>
        <?php else: ?>
            <p style="color: #666; font-size: 13px;">Este é você, @<?php echo $dados['username']; ?></p>
        <?php endif; ?>

    </div> </div> <?php include 'includes/footer.php'; ?>