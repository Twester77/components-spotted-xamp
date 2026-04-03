<?php
include 'conexao.php'; 
include 'includes/header.php'; 

if (!$usuario_logado) {
    header("Location: index.php");
    exit();
}

// 1. LÓGICA DE NAVEGAÇÃO: Quem estamos visitando?
$id_visitado = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['usuario_id'];
$id_meu = $_SESSION['usuario_id'];

// 2. BUSCA DE DADOS
$query = "SELECT id, nome, foto, bio, capa, username FROM usuarios WHERE id = '$id_visitado'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

if (!$dados) {
    header("Location: perfil.php");
    exit();
}

$nome_atual = $dados['nome'] ?? "Estudante da Fenda";
$foto_atual = !empty($dados['foto']) ? $dados['foto'] : "default.jpg"; 
$bio_atual  = $dados['bio'] ?? "";
$capa_atual = !empty($dados['capa']) ? $dados['capa'] : "default_capa.jpg";

include 'includes/navbar.php'; 
include 'includes/bolhas.php';
?>

<main class="main-perfil" style="max-width: 500px !important; align-items: center !important;">
    </main>    
    <?php if ($id_visitado == 1): ?>
        <div style="width: 100%; position: relative; border-radius: 20px 20px 0 0;"> 
            <div style="width: 100%; height: 180px; background: url('<?php echo ($capa_atual == 'default_capa.jpg') ? 'imagensfoto/capa_padrao.jpg' : 'uploads/'.$capa_atual; ?>') center/cover; position: relative; border-radius: 20px 20px 0 0; border: 3px solid #ffbc00; border-bottom: none; box-sizing: border-box; box-shadow: 0 0 25px #ffbc00;">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: #ffbc00; box-shadow: 0 0 15px #ffbc00;"></div>
            </div>
            <div style="position: absolute; bottom: -60px; left: 0; right: 0; display: flex; justify-content: center; z-index: 10;"> 
                <img src="<?php echo ($foto_atual == 'default.jpg') ? 'imagensfoto/default.jpg' : 'uploads/'.$foto_atual; ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid #0a0a0a; object-fit: cover; box-shadow: 0 0 20px #ffbc00;">
            </div>
        </div>
        <div style="height: 80px;"></div>
        <p style="color: #ffbc00; font-weight: bold; text-align: center; letter-spacing: 2px; text-shadow: 0 0 10px #ffbc00; margin-bottom: 20px;">⚠️ VOCÊ ESTÁ DIANTE DA PRESENÇA.</p>
    <?php endif; ?>

    <div style="padding: 20px; width: 100%; box-sizing: border-box; display: flex; flex-direction: column; align-items: center;">
        <?php if ($id_visitado == $id_meu): ?>
            <form action="processa-perfil.php" method="POST" enctype="multipart/form-data" style="width: 100%; max-width: 450px;">
                
                <p style="color: #ffbc00; font-size: 14px; font-weight: bold; margin-bottom: 20px; text-align: center;">Configurações de Perfil</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div style="background: #111; padding: 12px; border-radius: 10px; border: 1px solid #222; text-align: center;">
                        <label style="color: #ffbc00; font-size: 10px; font-weight: bold; display: block; margin-bottom: 5px;">AVATAR</label>
                        <input type="file" name="foto" style="color: #fff; font-size: 10px; width: 100%;">
                    </div>
                    <div style="background: #111; padding: 12px; border-radius: 10px; border: 1px solid #222; text-align: center;">
                        <label style="color: #ffbc00; font-size: 10px; font-weight: bold; display: block; margin-bottom: 5px;">CAPA</label>
                        <input type="file" name="capa" style="color: #fff; font-size: 10px; width: 100%;">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="color: #ccc; font-size: 12px; display: block; text-align: center; margin-bottom: 5px;">Nome de Exibição:</label>
                    <input type="text" name="nome" value="<?php echo $nome_atual; ?>" style="width: 100%; padding: 12px; background: #151515; border: 1px solid #333; color: #fff; border-radius: 8px; box-sizing: border-box; text-align: center;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="color: #ffbc00; font-size: 12px; font-weight: bold; display: block; text-align: center; margin-bottom: 5px;">Seu @ de usuário:</label>
                    <div style="display: flex; align-items: center; justify-content: center; background: #151515; border: 1px solid #333; border-radius: 8px; padding-left: 10px; box-sizing: border-box;">
                        <span style="color: #ffbc00; font-weight: bold;">@</span>
                        <input type="text" name="username" value="<?php echo $dados['username'] ?? ''; ?>" style="width: 100%; padding: 12px; background: transparent; border: none; color: #fff; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="color: #ccc; font-size: 12px; display: block; text-align: center; margin-bottom: 5px;">Bio:</label>
                    <textarea name="bio" rows="3" style="width: 100%; padding: 12px; background: #151515; border: 1px solid #333; color: #fff; border-radius: 8px; resize: none; box-sizing: border-box; text-align: center;"><?php echo $bio_atual; ?></textarea>
                </div>

                <button type="submit" style="width: 100%; background: #ffbc00; color: #000; border: none; padding: 15px; border-radius: 10px; font-weight: bold; cursor: pointer;">
                    Salvar Alterações
                </button>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>