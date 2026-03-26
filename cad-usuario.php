
<?php include 'components/header.php'; ?>
<?php include 'components/navbar.php'; ?>

<main style="max-width: 500px; margin: 40px auto; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 15px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);">
    
    <h2 style="text-align: center; color: #f8c946ec; margin-bottom: 20px;"> Criar sua conta na Fenda</h2>
    
    <form action="processa-cadastro.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        
        <div class="input-group">
            <label style="color: #eee;"> Nome ou apelido</label>
            <input type="text" name="nome" placeholder="Ex: Fulano de Tal, Furlas" required style="width: 100%; padding: 10px; border-radius: 5px; border: none; margin-top: 5px;">
        </div>

        <div class="input-group">
    <label style="color: #eee;"> E-mail Institucional (RA)</label>
    <input type="email" 
           name="email" 
           placeholder="ex: 1234@unifev.edu.br" 
           pattern=".+@unifev\.edu\.br" 
           title="Por favor, use seu e-mail da UNIFEV (@unifev.edu.br)"
           required 
           style="width: 100%; padding: 10px; border-radius: 5px; border: none; margin-top: 5px;">
</div>


        <div class="input-group">
            <label style="color: #eee;"> Crie uma Senha</label>
            <input type="password" name="senha" placeholder="Mínimo 8 caracteres" required minlength="8" style="width: 100%; padding: 10px; border-radius: 5px; border: none; margin-top: 5px;">
        </div>

        <div style="margin: 10px 0; display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="termos" id="termos" required>
            <label for="termos" style="font-size: 13px; color: #ccc;">
                Concordo com as <a href="diretrizes.php" style="color: #ffbc00;">Diretrizes da Comunidade</a>.
            </label>
        </div>

        <button type="submit" style="background: #cc420c; color: white; border: none; padding: 12px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s;">
            Finalizar Cadastro 
        </button>

        <p style="text-align: center; font-size: 14px; color: #eee;">
            Já tem conta? <a href="index.php" style="color: #08d888ab; font-weight: bold;">Faça Login</a>
        </p>
    </form>
</main>

<?php include 'components/footer.php'; ?>