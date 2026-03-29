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
$sql_count = "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'";
$res_count = mysqli_query($conn, $sql_count);
$contagem = mysqli_fetch_assoc($res_count);
$total_seguidores = $contagem['total'];
?>

<style>
    /* SE FOR O ID 1 (A PRESENÇA), ATIVA O MODO LENDÁRIO */
    <?php if ($id_visto == 1): ?>
    body { background: #000 !important; }
    .card-perfil { border: 2px solid #ffbc00; box-shadow: 0 0 30px rgba(255, 188, 0, 0.3); }
    .nome-user { color: #ffbc00 !important; text-shadow: 0 0 10px #ffbc00; }
    <?php endif; ?>
</style>

<div class="container-perfil" style="max-width: 600px; margin: 20px auto; background: #1a1a1a; border-radius: 15px; overflow: hidden; color: white; font-family: sans-serif; padding-bottom: 20px;">
    
    <div class="capa" style="height: 180px; background: url('<?php echo $capa; ?>') center/cover;"></div>

    <div style="text-align: center; margin-top: -60px; padding: 20px;">
        <img src="<?php echo $foto; ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid #1a1a1a; object-fit: cover;">
        
        <h2 class="nome-user" style="margin-top: 10px;"><?php echo $dados['nome']; ?></h2>
        <p style="color: #ff7011; font-weight: bold;">@<?php echo $dados['username']; ?></p>

        <div style="display: flex; justify-content: center; gap: 20px; margin: 15px 0;">
            <div style="text-align: center;">
                <span style="display: block; font-size: 20px; font-weight: bold; color: #ff7011;">
                    <?php echo $total_seguidores; ?>
                </span>
                <span style="font-size: 11px; color: #aaa; text-transform: uppercase; letter-spacing: 1px;">Seguidores</span>
            </div>
        </div>
        
        <div style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; margin: 15px 0; border: 1px solid rgba(255,112,17,0.1);">
            <p style="font-size: 14px; line-height: 1.5; color: #ddd;"><?php echo $dados['bio']; ?></p>
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
                               color: white; font-weight: bold; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    <?php echo $ja_segue ? '✓ Seguindo' : '+ Seguir esta Lenda'; ?>
                </button>
            </a>
        <?php else: ?>
            <p style="color: #666; font-size: 13px; margin-top: 10px;">Este é você, @<?php echo $dados['username']; ?></p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>