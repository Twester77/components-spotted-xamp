<!-- Adicionado aria-label para identificar o propósito do formulário -->
<form action="confirma-login.php" method="post" aria-label="Formulário de Login">
    <div class="fenda-glass-container">

        <!-- Imagens puramente ilustrativas ocultadas com aria-hidden para não poluir o leitor de tela -->
        <div class="avatar-pequeno" aria-hidden="true">
            <img src="uploads/ui/img_avatar2.webp" alt="" class="avatar de inicio">
            <img src="uploads/ui/img_avatar1.webp" alt="" class="avatar de inicio">
        </div>

        <!-- Alertas ganham role="status" ou role="alert" para serem lidos assim que a página carregar -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'conta_ativada'): ?>
            <div role="status" aria-live="polite" class="toast-login ativado">
                <span aria-hidden="true">🫡</span>
                CONTA ATIVADA! <br> <span>Bem-vindo à Fenda, mergulhe com tudo!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'pendente'): ?>
            <div role="alert" aria-live="assertive" class="toast-login pendente">
                <span aria-hidden="true">⏳</span>
                QUASE LÁ! <br>
                <span>Sua conta ainda não foi ativada. Dá uma olhadinha no seu e-mail para liberar o acesso. Não se esqueça de conferir a caixa de spam/lixo eletrônico!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'validar_email'): ?>
            <div role="status" aria-live="polite" class="toast-login validar">
                <span aria-hidden="true">📧</span>
                QUASE LÁ! <br> <span>Cheque seu e-mail institucional para liberar o acesso.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <!-- Erros críticos usam role="alert" para interromper leituras em andamento e avisar o usuário -->
            <div role="alert" aria-live="assertive" id="mensagem-erro-login" class="toast-login erro">
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
            <input type="email" id="email" name="email" placeholder="Seu e-mail" required autocomplete="username" <?php echo isset($_GET['erro']) ? 'aria-invalid="true" aria-describedby="mensagem-erro-login"' : ''; ?>>
        </div>

        <div class="input-group input-senha-wrapper">
            <label for="senha"><b>Senha</b></label>
            <div class="senha-container">
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password" <?php echo (isset($_GET['erro']) && $_GET['erro'] == 'senha') ? 'aria-invalid="true" aria-describedby="mensagem-erro-login"' : ''; ?>>
                <button type="button" id="toggle-senha" class="btn-toggle-senha" aria-label="Exibir senha">
                    <i class="fas fa-eye" id="icone-senha" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- 🔥 CHECKBOX: Aceite das Diretrizes (já existente) -->
        <div class="checkbox-wrapper">
            <input type="checkbox" name="terms" id="terms" required>
            <label for="terms">
                Eu aceito as <a href="diretrizes.php">Diretrizes de Segurança</a>
            </label>
        </div>

        <!-- 🔥 CHECKBOX: Manter-me conectado -->
        <div class="checkbox-wrapper">
            <input type="checkbox" name="manter_conectado" id="manter_conectado" value="1">
            <label for="manter_conectado">Manter-me conectado por 30 dias</label>
        </div>

        <button type="submit" class="login-btn">
            ACESSAR A FENDA
        </button>

        <p class="cadastro-link">
            Ainda não tem conta? <a href="cad-usuario.php">Cadastre-se aqui</a>
        </p>
    </div>
</form>

<script>
    // ============================================================
    // TOGGLE DE EXIBIÇÃO DE SENHA (Olhinho)
    // ============================================================
    (function() {
        const btnToggle = document.getElementById('toggle-senha');
        const inputSenha = document.getElementById('senha');
        const icone = document.getElementById('icone-senha');

        if (!btnToggle || !inputSenha || !icone) return;

        btnToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const tipoAtual = inputSenha.getAttribute('type');
            const novoTipo = tipoAtual === 'password' ? 'text' : 'password';
            inputSenha.setAttribute('type', novoTipo);
            
            // Troca o ícone
            icone.classList.toggle('fa-eye');
            icone.classList.toggle('fa-eye-slash');
            
            // Atualiza o aria-label
            const label = novoTipo === 'password' ? 'Exibir senha' : 'Ocultar senha';
            btnToggle.setAttribute('aria-label', label);
        });

        // Acessibilidade: permitir ativação com Enter/Space
        btnToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    })();
</script>