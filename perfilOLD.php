<?php
include 'conexao.php'; 
include 'includes/header.php'; 

if (!$usuario_logado) {
    header("Location: index.php");
    exit();
}

// 1. LÓGICA DE NAVEGAÇÃO
$id_visitado = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['usuario_id'];
$id_meu = $_SESSION['usuario_id'];

// 2. BUSCA DE DADOS
$query = "SELECT id, nome, foto, bio, capa, username FROM usuarios WHERE id = '$id_visitado'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

if (!$dados) {
    header("Location: feed.php");
    exit();
}

$nome_atual = $dados['nome'] ?? "Estudante da Fenda";
$foto_atual = !empty($dados['foto']) ? "uploads/".$dados['foto'] : "imagensfoto/default.jpg"; 
$bio_atual  = $dados['bio'] ?? "";
$capa_atual = !empty($dados['capa']) ? "uploads/".$dados['capa'] : "imagensfoto/capa_padrao.jpg";

include 'includes/navbar.php'; 
include 'includes/bolhas.php';
?>

<main class="main-perfil" style="max-width: 450px; margin: 80px auto 50px auto; padding: 0 10px;">
    
    <div style="width: 100%; position: relative; margin-bottom: 70px;"> 
        <div style="width: 100%; height: 180px; background: url('<?php echo $capa_atual; ?>') center/cover; position: relative; border-radius: 20px; border: 3px solid #ffbc00; box-shadow: 0 0 15px rgba(255, 188, 0, 0.2);">
            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: #ffbc00;"></div>
        </div>
        
        <div style="position: absolute; bottom: -60px; left: 50%; transform: translateX(-50%); z-index: 10;"> 
            <img src="<?php echo $foto_atual; ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid #0a0a0a; object-fit: cover; box-shadow: 0 0 20px rgba(255, 188, 0, 0.4);">
        </div>
    </div>

    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: #fff; margin: 0;"><?php echo $nome_atual; ?></h2>
        <p style="color: #ffbc00; font-weight: bold; margin: 5px 0;">@<?php echo $dados['username'] ?? 'usuario'; ?></p>
        
        <?php if ($id_visitado == 1): ?>
            <p style="color: #ffbc00; font-size: 11px; font-weight: bold; letter-spacing: 2px; text-shadow: 0 0 10px #ffbc00; background: rgba(255,188,0,0.1); display: inline-block; padding: 5px 15px; border-radius: 20px;">
                ⚠️ VOCÊ ESTÁ DIANTE DA PRESENÇA.
            </p>
        <?php endif; ?>

        <p style="color: #ccc; font-size: 14px; margin-top: 15px; padding: 0 20px; line-height: 1.5;">
            <?php echo nl2br(htmlspecialchars($bio_atual)); ?>
        </p>
    </div>

    <hr style="border: 0; border-top: 1px solid #222; margin: 30px 0;">

    <div style="width: 100%;">
        <?php if ($id_visitado == $id_meu): ?>
            <form action="processa-perfil.php" method="POST" enctype="multipart/form-data">
                
                <p style="color: #ffbc00; font-size: 13px; font-weight: bold; margin-bottom: 20px; text-align: center; text-transform: uppercase;">
                    Configurações
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                    <div style="background: #111; padding: 15px; border-radius: 12px; border: 1px solid #222; text-align: center;">
                        <label style="color: #ffbc00; font-size: 9px; font-weight: bold; display: block; margin-bottom: 8px;">FOTO PERFIL</label>
                        <input type="file" name="foto" style="color: #fff; font-size: 10px; width: 100%;">
                    </div>
                    <div style="background: #111; padding: 15px; border-radius: 12px; border: 1px solid #222; text-align: center;">
                        <label style="color: #ffbc00; font-size: 9px; font-weight: bold; display: block; margin-bottom: 8px;">FOTO CAPA</label>
                        <input type="file" name="capa" style="color: #fff; font-size: 10px; width: 100%;">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="color: #777; font-size: 13px; display: block; margin-bottom: 5px; margin-left: 5px;">Nome de Exibição</label>
                    <input type="text" name="nome" value="<?php echo $nome_atual; ?>" style="width: 100%; padding: 12px; background: #151515; border: 1px solid #333; color: #fff; border-radius: 10px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="color: #777; font-size: 13px; display: block; margin-bottom: 5px; margin-left: 5px;">Username (@)</label>
                    <div style="display: flex; align-items: center; background: #151515; border: 1px solid #333; border-radius: 10px; padding-left: 15px;">
                        <span style="color: #ffbc00; font-weight: bold;">@</span>
                        <input type="text" name="username" value="<?php echo $dados['username'] ?? ''; ?>" style="width: 100%; padding: 12px; background: transparent; border: none; color: #fff; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="color: #777; font-size: 13px; display: block; margin-bottom: 5px; margin-left: 5px;">Bio</label>
                    <textarea name="bio" rows="3" style="width: 100%; padding: 12px; background: #151515; border: 1px solid #333; color: #fff; border-radius: 10px; resize: none; box-sizing: border-box;"><?php echo $bio_atual; ?></textarea>
                </div>

                <button type="submit" style="width: 100%; background: #ffbc00; color: #000; border: none; padding: 16px; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 14px;">
                    Atualizar Perfil
                </button>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: #444; font-size: 12px; margin-top: 50px;">O mural de fofocas está privado.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>