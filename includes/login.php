<form action="confirma-login.php" method="post">
    <div class="imgcontainer"> 
      <img src="imagensfoto/img_avatar2.jpg" alt="Avatar" class="avatar" >
      <img src="imagensfoto/img_avatar1.jpg" alt="Avatar" class="avatar" >
    </div>

   <?php if (isset($_GET['msg']) && $_GET['msg'] == 'conta_ativada'): ?>
    <div style="background: rgba(0, 74, 141, 0.9); color: white; text-align: center; padding: 15px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 15px; border: 1px solid #00c3ff; box-shadow: 0 4px 15px rgba(0, 195, 255, 0.2);">
        <span style="display: block; font-size: 22px; margin-bottom: 5px;">🚀</span>
        CONTA ATIVADA! <br> <span style="font-weight: normal; font-size: 13px;">Bem-vindo à Fenda, mergulhe com tudo!</span>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'validar_email'): ?>
    <div style="background: rgba(255, 152, 0, 0.9); color: white; text-align: center; padding: 15px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 15px; border: 1px solid #ffcc80; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);">
        <span style="display: block; font-size: 22px; margin-bottom: 5px;">📧</span>
        QUASE LÁ! <br> <span style="font-weight: normal; font-size: 13px;">Checa seu e-mail institucional para liberar o acesso.</span>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['erro'])): ?>
    <div style="background: rgba(255, 77, 77, 0.15); color: #ff4d4d; text-align: center; padding: 12px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 14px; border: 1px solid #ff4d4d;">
        ⚠️ 
        <?php 
            if ($_GET['erro'] == 'senha') echo "Senha incorreta, patrão! Tenta de novo.";
            if ($_GET['erro'] == 'usuario') echo "Usuário não encontrado no radar.";
            if ($_GET['erro'] == 'pendente') echo "Opa! Ativa esse e-mail primeiro.";
        ?>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="input-group">
            <label for="email"><b>E-mail</b></label>
           <input type="text" name="email" placeholder="Seu e-mail" required autocomplete="username"> 
        </div>     
        <div class="input-group">
            <label for="senha"><b>Senha</b></label>
           <input type="password" name="senha" placeholder="Digite sua senha" required autocomplete="current-password">
        </div>

        <div style="margin: 15px 0; display: flex; align-items: center; gap: 8px; justify-content: flex-start;">
            <input type="checkbox" name="terms" id="terms" required style="width: auto; margin: 0; cursor: pointer;">
            <label for="terms" style="font-size: 14px; color: #ddc80e; font-weight: bold; cursor: pointer; line-height: 1;">
                Eu aceito as <a href="diretrizes.php" style="color: #ddc80e; text-decoration: underline; display: inline;">Diretrizes de Segurança</a>
            </label>
        </div>

        <button type="submit" class="login-btn" style="background: linear-gradient(135px, #08d888, #05a870); border: none; box-shadow: 0 4px 15px rgba(8, 216, 136, 0.3);">
           ACESSAR A FENDA 🌊
        </button>
        
        <p style="text-align: center; margin-top: 15px; margin-bottom: 15px; font-size: 13px;">
            Ainda não tem conta? <a href="cad-usuario.php" style="color: #08d888ab; font-weight: bold;">Cadastre-se aqui</a>
        </p>
    </div> 
</form>