

<form action="confirma-login.php" method="post">
    <div class="imgcontainer"> 
      <img src="imagensfoto/img_avatar2.jpg" alt="Avatar" class="avatar" >
      <img src="imagensfoto/img_avatar1.jpg" alt="Avatar" class="avatar" >
    </div>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso'): ?>
    <div style="background-color: #4CAF50; color: white; text-align: center; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; font-size: 14px;">
        ✅ Cadastro realizado! Agora é só logar.
    </div>
<?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
    <div style="color: #ff4d4d; text-align: center; margin-bottom: 15px; font-weight: bold; font-size: 14px;">
        <?php 
            if ($_GET['erro'] == 'senha') echo "Senha incorreta!";
            if ($_GET['erro'] == 'usuario') echo "Usuário não encontrado!";
        ?>
    </div>
<?php endif; ?>
    <div class="container">
        <div class="input-group">
            <label for="email"><b>E-mail</b></label>
            <input type="email" placeholder="Digite seu e-mail" name="email" required>
        </div>

        <div class="input-group">
            <label for="senha"><b>Senha</b></label>
            <input type="password" placeholder="Digite sua senha" name="senha" required>
        </div>

        <div style="margin: 15px 0; display: flex; align-items: center; gap: 8px; justify-content: flex-start;">
         <input type="checkbox" name="terms" id="terms" required style="width: auto; margin: 0; cursor: pointer;">
           <label for="terms" style="font-size: 13px; color: #ddc80e; font-weight: bold; cursor: pointer; line-height: 1;">
        Eu aceito as <a href="diretrizes.php" style="color: #ddc80e; text-decoration: underline; display: inline;">Diretrizes de Segurança</a>
          </label>
        </div>

        <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px; width: 100%; cursor: pointer; border: none; border-radius: 4px;">
            Login
        </button>
        
        <p style="text-align: center; margin-top: 15px; margin-bottom: 15px; font-size: 13px;">
            Ainda não tem conta? <a href="cad-usuario.php" style="color: #08d888ab; font-weight: bold;">Cadastre-se aqui</a>
        </p>
    </div> 
</form>