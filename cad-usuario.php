<?php 
include 'includes/header.php'; 
include 'includes/navbar.php'; 
include 'includes/bolhas.php';
?>

<main class="main-form-cadastro">
    <div class="container-cadastro-fenda">
        
        <div class="cadastro-header">
            <h2>Criar sua conta na Fenda</h2>
            <p>Junte-se à comunidade oficial da Fenda</p>
        </div>
        
        <form action="processa-cadastro.php" method="POST" class="form-fenda-estilizado">
            <div class="campo-grupo-fenda">
                <label for="nome">Nome ou Apelido</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-user"></i>
                    <input type="text" id="nome" name="nome" placeholder="Ex: Fulano, Furlas..." maxlength="30" required>
                </div>
            </div>
            
            <div class="campo-grupo-fenda">
                <label for="email">E-mail Institucional (RA)</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           id="email"
                           name="email" 
                           placeholder="ex: 1234@unifev.edu.br" 
                           pattern=".+@unifev\.edu\.br" 
                           title="Use seu e-mail @unifev.edu.br"
                           required>
                </div>
            </div>

            <div class="campo-grupo-fenda">
                <label for="senha">Crie uma Senha</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="senha" name="senha" placeholder="8 a 20 caracteres" maxlength="20" minlength="8" required>
                </div>
            </div>

            <div class="termos-wrapper">
                <label class="checkbox-custom">
                    <input type="checkbox" name="termos" required>
                    <span class="checkmark"></span>
                    Reafirmo que eu li e concordo com as <a href="diretrizes.php"> Diretrizes da Comunidade</a>.
                </label>
            </div>

            <button type="submit" class="btn-finalizar-fenda">
                FINALIZAR CADASTRO <i class="fas fa-rocket"></i>
            </button>

            <div class="form-footer">
                Já tem conta? <a href="index.php">Faça Login</a>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>