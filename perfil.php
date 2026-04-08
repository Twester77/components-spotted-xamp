<?php
include 'conexao.php'; 
include 'includes/header.php'; 
include 'includes/navbar.php'; 
include 'includes/bolhas.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id_meu = $_SESSION['usuario_id'];

// BUSCA DE DADOS
$query = "SELECT id, nome, foto, bio, capa, username FROM usuarios WHERE id = '$id_meu'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

$foto_atual = !empty($dados['foto']) ? "uploads/".$dados['foto'] : "imagensfoto/default.jpg"; 
$capa_atual = !empty($dados['capa']) ? "uploads/".$dados['capa'] : "imagensfoto/capa_padrao.jpg";

// Verifica se é a "Presença" (ID 1) para aplicar classe especial
$classe_presenca = ($id_meu == 1) ? 'perfil-gold' : '';
?>

<main class="main-perfil-container <?php echo $classe_presenca; ?>">
    <form action="processa-perfil.php" method="POST" enctype="multipart/form-data">
        
        <div class="capa-wrapper">
            <img src="<?php echo $capa_atual; ?>" class="img-capa-preview">
            <label class="btn-mudar-capa">
                <i class="fas fa-camera"></i>
                <input type="file" name="capa" style="display:none;">
            </label>
            <?php if($id_meu == 1): ?>
                <div class="coroa-admin">👑</div>
            <?php endif; ?>
        </div>

        <div class="avatar-wrapper">
            <img src="<?php echo $foto_atual; ?>" class="img-avatar-perfil">
            <label class="btn-mudar-avatar">
                <i class="fas fa-pencil-alt"></i>
                <input type="file" name="foto" style="display:none ;">
            </label>
        </div>

        <div class="form-perfil-corpo">
            <h2 class="titulo-pagina">Configurações de Habitante</h2>

            <div class="campo-grupo">
                <label>Nome</label>
                <input type="text" name="nome" maxlength="20" value="<?php echo $dados['nome']; ?>" required>
            </div>

            <div class="campo-grupo">
                <label>Username</label>
                <div class="input-username-wrapper">
                    <span>@</span>
                    <input type="text" name="username" maxlength="30" value="<?php echo $dados['username']; ?>">
                </div>
            </div>

            <div class="campo-grupo">
                <label>Sua Bio</label>
                <textarea name="bio" maxlength="350" rows="3"><?php echo $dados['bio']; ?></textarea>
            </div>

            <button type="submit" class="btn-salvar-perfil">SALVAR ALTERAÇÕES</button>
            <a href="ver-perfil.php?user=<?php echo $dados['username']; ?>" class="btn-visualizar">Ver meu perfil público</a>
        </div>
    </form>
</main>

<?php include 'includes/footer.php'; ?>