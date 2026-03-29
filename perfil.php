<?php
include 'conexao.php'; 
include 'includes/header.php'; 

if (!$usuario_logado) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// BUSCANDO TUDO: Nome, Foto, Bio e Capa
$query = "SELECT nome, foto, bio, capa FROM usuarios WHERE id = '$usuario_id'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

$nome_atual = $dados['nome'] ?? "Estudante da Fenda";
$foto_atual = !empty($dados['foto']) ? $dados['foto'] : "default.jpg"; 
$bio_atual  = $dados['bio'] ?? "";
$capa_atual = !empty($dados['capa']) ? $dados['capa'] : "default_capa.jpg";

include 'includes/navbar.php'; 
include 'includes/bolhas.php';
?>

<main class="main-perfil" style="max-width: 700px; margin: 20px auto; background: #111; border-radius: 20px; overflow: hidden; border: 1px solid #333;">
    
    <?php if ($usuario_id == 1): ?>
        <div class="capa-preview" style="width: 100%; height: 250px; background: url('uploads/<?php echo $capa_atual; ?>') center/cover; position: relative; border-bottom: 3px solid #ffbc00;">
            <div style="position: absolute; bottom: -50px; left: 30px;">
                <img src="uploads/<?php echo $foto_atual; ?>" style="width: 130px; height: 130px; border-radius: 50%; border: 4px solid #111; object-fit: cover; background: #222; box-shadow: 0 0 20px rgba(255, 188, 0, 0.4);">
            </div>
        </div>
        <p style="color: #ffbc00; text-align: center; font-weight: bold; margin-top: 65px; letter-spacing: 2px;">
            ⚠️ VOCÊ ESTÁ DIANTE DA PRESENÇA.
        </p>

    <?php else: ?>
        <div class="capa-comum" style="width: 100%; height: 150px; background: url('uploads/<?php echo $capa_atual; ?>') center/cover; border-bottom: 1px solid #333;">
        </div>
        <div style="text-align: center; margin-top: -50px;">
            <img src="uploads/<?php echo $foto_atual; ?>" style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #ffbc00; object-fit: cover; background: #111;">
            <h2 style="color: #fff; margin-top: 10px;"><?php echo $nome_atual; ?></h2>
            <span style="color: #666; font-size: 12px; text-transform: uppercase;">Membro da Fenda</span>
        </div>
    <?php endif; ?>
    <div class="form-container" style="padding: 30px;">
        <h3 style="color: #ffbc00; margin-bottom: 20px; font-size: 18px;">Configurações de Perfil</h3>
        
        <form action="processa-perfil.php" method="POST" enctype="multipart/form-data">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="color: #ccc; font-size: 13px;">Avatar:</label>
                    <input type="file" name="foto" accept="image/*" style="color: #fff; font-size: 12px; display: block; margin-top: 5px;">
                </div>
                <div>
                    <label style="color: #ccc; font-size: 13px;">Foto de Capa:</label>
                    <input type="file" name="capa" accept="image/*" style="color: #fff; font-size: 12px; display: block; margin-top: 5px;">
                </div>
            </div>

            <div style="margin-top: 20px;">
                <label style="color: #ccc; font-size: 13px;">Nome de Exibição:</label>
                <input type="text" name="nome" value="<?php echo $nome_atual; ?>" style="width: 100%; padding: 12px; background: #222; border: 1px solid #444; color: #fff; border-radius: 8px; outline: none;">
            </div>
            <div style="margin-top: 20px;">
             <label style="color: #ffbc00; font-size: 13px; font-weight: bold;">Seu @ de usuário (Único):</label>
             <div style="display: flex; align-items: center; background: #222; border: 1px solid #444; border-radius: 8px; padding-left: 10px;">
            <span style="color: #ffbc00;">@</span>
                  <input type="text" name="username" value="<?php echo $dados['username'] ?? ''; ?>" placeholder="ex: apresenca" style="width: 100%; padding: 12px; background: transparent; border: none; color: #fff; outline: none;">
              </div>
           </div>

            <div style="margin-top: 20px;">
                <label style="color: #ccc; font-size: 13px;">Bio (Sua frase de impacto):</label>
                <textarea name="bio" rows="3" placeholder="Conte algo sobre você..." style="width: 100%; padding: 12px; background: #222; border: 1px solid #444; color: #fff; border-radius: 8px; resize: none; outline: none;"><?php echo $bio_atual; ?></textarea>
            </div>

            <button type="submit" style="background: #ffbc00; color: #000; border: none; padding: 15px; border-radius: 10px; font-weight: bold; width: 100%; margin-top: 25px; cursor: pointer; transition: 0.3s;">
                Salvar Alterações Épicas
            </button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>