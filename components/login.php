

<form action="logar.php" method="post">
    <div class="imgcontainer"> 
      <img src="imagensfoto/img_avatar2.jpg" alt="Avatar" class="avatar" >
      <img src="imagensfoto/img_avatar1.jpg" alt="Avatar" class="avatar" >
    </div>

    <div class="container">
        <div class="input-group">
            <label for="email"><b>E-mail</b></label>
            <input type="email" placeholder="Digite seu e-mail" name="email" required>
        </div>

        <div class="input-group">
            <label for="senha"><b>Senha</b></label>
            <input type="password" placeholder="Digite sua senha" name="senha" required>
        </div>

        <div style="margin: 15px 0; display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="terms" id="terms" required>
            <label for="terms" style="font-size: 13px; color: #ddc80e; font-weight: bold;">
                Eu aceito as <a href="diretrizes.php" style="color: #ddc80e; text-decoration: underline;">Diretrizes de Segurança</a>
            </label>
        </div>

        <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px; width: 100%; cursor: pointer; border: none; border-radius: 4px;">
            Login
        </button>
        
        <p style="text-align: center; margin-top: 15px; font-size: 14px;">
            Ainda não tem conta? <a href="cadastro.php" style="color: #004a8d; font-weight: bold;">Cadastre-se aqui</a>
        </p>
    </div> 
</form>