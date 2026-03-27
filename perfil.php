 <?php if (!$usuario_logado) {
    header("Location:login.php");
    exit();
} ?>
<?php
include 'includes/navbar.php'; 
include 'includes/bolhas.php';
?>
<main class="main-novo-post">
    <div class="form-container">
        <h2>Configurações de Perfil</h2>
        <form action="processa_perfil.php" method="POST" enctype="multipart/form-data">
            <div class="imgcontainer">
                <img src="uploads/perfil/<?php echo $foto_atual; ?>" class="avatar" alt="Sua foto">
            </div>
            
            <label for="foto">Trocar Foto (Máx. 15MB):</label>
            <input type="file" name="foto" id="foto" accept="image/jpeg, image/png">
            
            <label for="nome">Nome de Exibição:</label>
            <input type="text" name="nome" value="<?php echo $nome_atual; ?>" placeholder="Como quer ser chamado?">

            <button type="submit" class="btn-lancar">Salvar Alterações</button>
        </form>
    </div>
</main>

<?php 
include 'includes/footer.php'; 
?>