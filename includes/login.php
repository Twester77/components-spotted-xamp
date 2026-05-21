<!-- Adicionado aria-label para identificar o propósito do formulário -->
<form action="confirma-login.php" method="post" aria-label="Formulário de Login">
    <div class="fenda-glass-container">

        <!-- Imagens puramente ilustrativas ocultadas com aria-hidden para não poluir o leitor de tela -->
        <div class="imgcontainer" aria-hidden="true">
            <img src="imagensfoto/img_avatar2.jpg" alt="" class="avatar de inicio">
            <img src="imagensfoto/img_avatar1.jpg" alt="" class="avatar de inicio">
        </div>

        <!-- Alertas ganham role="status" ou role="alert" para serem lidos assim que a página carregar -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'conta_ativada'): ?>
            <div role="status" aria-live="polite" style="background:linear-gradient(170deg, #27ce27bd 15%, #2e6ba5a2 100%); color: white; text-align: center; padding: 15px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 15px; border: 1px solid #00c3ff; box-shadow: 0 4px 15px rgba(0, 195, 255, 0.2);">
                <span style="display: block; font-size: 22px; margin-bottom: 5px;" aria-hidden="true">🫡</span>
                CONTA ATIVADA! <br> <span style="font-weight: normal; font-size: 13px;">Bem-vindo à Fenda, mergulhe com tudo!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'pendente'): ?>
            <div role="alert" aria-live="assertive" style="background: rgba(255, 165, 0, 0.2); color: #ffa500; text-align: center; padding: 15px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 14px; border: 1px solid #ffa500; box-shadow: 0 4px 15px rgba(255, 165, 0, 0.1);">
                <span style="display: block; font-size: 22px; margin-bottom: 5px;" aria-hidden="true">⏳</span>
                QUASE LÁ! <br>
                <span style="font-weight: normal; font-size: 13px;">Sua conta ainda não foi ativada. Dá uma olhadinha no seu e-mail para liberar o acesso!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'validar_email'): ?>
            <div role="status" aria-live="polite" style="background: rgba(255, 152, 0, 0.9); color: white; text-align: center; padding: 15px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 15px; border: 1px solid #ffcc80; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);">
                <span style="display: block; font-size: 22px; margin-bottom: 5px;" aria-hidden="true">📧</span>
                QUASE LÁ! <br> <span style="font-weight: normal; font-size: 13px;">Cheque seu e-mail institucional para liberar o acesso.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <!-- Erros críticos usam role="alert" para interromper leituras em andamento e avisar o usuário -->
            <div role="alert" aria-live="assertive" id="mensagem-erro-login" style="background: rgba(255, 77, 77, 0.15); color: #ff4d4d; text-align: center; padding: 12px; margin: 10px 0 20px 0; border-radius: 10px; font-weight: bold; font-size: 14px; border: 1px solid #ff4d4d;">
                <span aria-hidden="true">⚠️</span>
                <?php
                if ($_GET['erro'] == 'senha') echo "Senha incorreta, patrão! Tenta de novo.";
                if ($_GET['erro'] == 'usuario') echo "Usuário não encontrado.";
                if ($_GET['erro'] == 'pendente') echo "Eita.. Ativa esse e-mail primeiro!";
                ?>
            </div>
        <?php endif; ?>

        <div class="input-group">
            <label for="email"><b>E-mail</b></label>
            <!-- input do tipo email adicionado para validação nativa de formato de e-mail -->
            <input type="email" id="email" name="email" placeholder="Seu e-mail" required autocomplete="username" <?php echo isset($_GET['erro']) ? 'aria-invalid="true" aria-describedby="mensagem-erro-login"' : ''; ?>>
        </div>

        <div class="input-group">
            <label for="senha"><b>Senha</b></label>
            <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password" <?php echo (isset($_GET['erro']) && $_GET['erro'] == 'senha') ? 'aria-invalid="true" aria-describedby="mensagem-erro-login"' : ''; ?>>
        </div>

        <div style="margin: 15px 0; display: flex; align-items: center; gap: 8px; justify-content: flex-start;">
            <input type="checkbox" name="terms" id="terms" required style="width: auto; margin: 0; cursor: pointer;">
            <label for="terms" style="font-size: 15px; color: #ffffffa2; font-weight: bold; cursor: pointer; line-height: 1;">
                Eu aceito as <a href="diretrizes.php" style=" text-decoration: underline; display: inline;">Diretrizes de Segurança</a>
            </label>
        </div>

        <button type="submit" class="login-btn" style=" border: none; box-shadow: 0 4px 15px rgba(8, 216, 136, 0.3); width: 100%; padding: 12px; border-radius: 10px; color: white; font-weight: bold; cursor: pointer;">
            ACESSAR A FENDA
        </button>

        <p style="text-align: center; margin-top: 15px; margin-bottom: 5px; font-size: 13px;">
            Ainda não tem conta? <a href="cad-usuario.php" style="color: #08d888ab; font-weight: bold;">Cadastre-se aqui</a>
        </p>
    </div>
</form>
