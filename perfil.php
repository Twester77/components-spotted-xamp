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
    
<?php 
$borda_perfil = ($id_visitado == 1) ? 'border: 2px solid #ffbc00; box-shadow: 0 0 20px rgba(255, 188, 0, 0.2);' : 'border: 1px solid #333;';
?>
<main class="main-perfil" style="max-width: 700px; margin: 15px auto; background: #111; border-radius: 20px; overflow: hidden; <?php echo $borda_perfil; ?>">    <?php if ($id_visitado == 1): ?>
        <div class="capa-preview" style="width: 100%; height: 300px; background: url('<?php echo ($capa_atual == 'default_capa.jpg') ? 'imagensfoto/capa_padrao.jpg' : 'uploads/'.$capa_atual; ?>') center/cover; position: relative; border-bottom: 3px solid #ffbc00;">
            <div style="position: absolute; bottom: -50px; left: 30px;"> 
                <img src="<?php echo ($foto_atual == 'default.jpg') ? 'imagensfoto/default.jpg' : 'uploads/'.$foto_atual; ?>" 
                     style="width: 130px; height: 130px; border-radius: 50%; border: 4px solid #111; object-fit: cover; background: #222; box-shadow: 0 0 20px rgba(255, 188, 0, 0.6);">
            </div>
        </div>
        <p style="color: #ffbc00; text-align: center; font-weight: bold; margin-top: 65px; margin-bottom:30px; letter-spacing: 2px; text-shadow: 0 0 10px rgba(255,188,0,0.5);">
            ⚠️ VOCÊ ESTÁ DIANTE DA PRESENÇA.
        </p>

    <?php else: ?>
        <div class="capa-comum" style="width: 100%; height: 400px; background: url('<?php echo ($capa_atual == 'default_capa.jpg') ? 'imagensfoto/capa_padrao.jpg' : 'uploads/'.$capa_atual; ?>') center/cover; border-bottom: 1px solid #333;">
        </div>
        <div style="text-align: center; margin-top: -50px; padding-bottom: 20px;">
            <img src="<?php echo ($foto_atual == 'default.jpg') ? 'imagensfoto/default.jpg' : 'uploads/'.$foto_atual; ?>" 
                 style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #ffbc00; object-fit: cover; background: #111; box-shadow: 0 0 15px rgba(255, 188, 0, 0.3);">
            <h2 style="color: #fff; margin-top: 15px;"><?php echo $nome_atual; ?></h2>
            <span style="color: #ffbc00; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Membro da Fenda</span>
        </div>
    <?php endif; ?>

    <?php if ($id_visitado == $id_meu): ?>
        <div class="form-container" style="padding: 30px; border-top: 1px solid #222;">
            <h3 style="color: #ffbc00; margin-bottom: 20px; font-size: 18px;">Configurações de Perfil</h3>
            
            <form action="processa-perfil.php" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; border: 1px solid #444;">
                        <label style="color: #ffbc00; font-size: 14px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 8px;">Avatar:</label>
                        <input type="file" name="foto" accept="image/*" style="color: #fff; font-size: 13px; width: 100%;">
                    </div>
                    <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; border: 1px solid #444;">
                        <label style="color: #ffbc00; font-size: 14px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 8px;">Capa:</label>
                        <input type="file" name="capa" accept="image/*" style="color: #fff; font-size: 13px; width: 100%;">
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <label style="color: #ccc;">Nome de Exibição:</label>
                    <input type="text" name="nome" value="<?php echo $nome_atual; ?>" style="width: 100%; padding: 12px; background: #222; border: 1px solid #444; color: #fff; border-radius: 8px; outline: none;">
                </div>

                <div style="margin-top: 20px;">
                    <label style="color: #ffbc00; font-weight: bold;">Seu @ de usuário:</label>
                    <div style="display: flex; align-items: center; background: #222; border: 1px solid #444; border-radius: 8px; padding-left: 10px;">
                        <span style="color: #ffbc00;">@</span>
                        <input type="text" name="username" value="<?php echo $dados['username'] ?? ''; ?>" style="width: 100%; padding: 12px; background: transparent; border: none; color: #fff; outline: none;">
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <label style="color: #ccc;">Bio:</label>
                    <textarea name="bio" rows="3" style="width: 100%; padding: 12px; background: #222; border: 1px solid #444; color: #fff; border-radius: 8px; resize: none; outline: none;"><?php echo $bio_atual; ?></textarea>
                </div>

                <button type="submit" style="background: #ffbc00; color: #000; border: none; padding: 15px; border-radius: 10px; font-weight: bold; width: 100%; margin-top: 25px; cursor: pointer;">
                    Salvar Alterações 
                </button>
            </form>
        </div>
    <?php else: ?>
        <div style="padding: 40px; text-align: center; color: white; background: rgba(255,255,255,0.02);">
            <p style="font-style: italic; color: #ccc; font-size: 1.1rem;"> <?php echo $bio_atual; ?></p>
            <hr style="border: 0; border-top: 1px solid #333; margin: 30px 0;">
            <p style="color: #666; font-size: 0.9rem;">Em breve: Veja os últimos spotteds de <?php echo $nome_atual; ?>!</p>
        </div>
    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>