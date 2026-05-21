<section id="postar" class="main-novo-post" style="margin-bottom: 40px !important;">
    <div class="form-container">
        <h2>O que tá rolando na UNIFEV?</h2>

        <form action="enviar-post.php" method="POST" enctype="multipart/form-data">
            <textarea name="mensagem" placeholder="Sussurre algo para a Fenda..." required maxlength="600" aria-label="Sussurre algo para a Fenda"></textarea>

            <div style="margin-bottom: 15px;">
                <label id="label-imagem" for="imagem" style="color: var(--dourado); font-size: 14px; cursor: pointer;">
                    <i class="fas fa-camera" aria-hidden="true"></i> Adicionar Foto (Opcional)
                </label>
                <input type="file" name="imagem" id="imagem" accept="image/*" style="display: block; margin-top: 5px; color: #ccc; font-size: 0.8rem;" aria-labelledby="label-imagem">
            </div>

            <label for="categoria" style="color: var(--dourado); font-size: 14px;">Categoria:</label>
            <!-- Adicionado aria-label para o elemento select descrever o seu propósito de forma clara -->
            <select name="categoria" id="categoria" style="background: #1a1a1a; color: #fff; border: 1px solid var(--dourado); border-radius: 10px; padding: 10px; margin-bottom: 15px; display: block; width: 100%;" aria-label="Selecione a categoria da sua publicação">
                <!-- Adicionados aria-labels nas opções para traduzir o significado dos emojis para o leitor de tela -->
                <option value="anonimo" aria-label="Anônimo">🕵️ Anônimo </option>
                <option value="comunidade" aria-label="Comunidade">👥 Comunidade</option>
                <option value="academico" aria-label="Dúvidas Acadêmicas">❓ Dúvidas Acadêmicas</option>
                <option value="elogio" aria-label="Correio Elegante">💖 Correio Elegante</option>
                <option value="ranco" aria-label="Ranço">👌 Ranço</option>
                <option value="acaba-pelo-amor-de-deus" aria-label="Eu não estou suportando mais">😭 Eu não estou suportando mais</option>
                <option value="caronas" aria-label="Caronas">🚗 Caronas</option>
                <option value="esportes" aria-label="Esportes">🏀 Esportes</option>
                <option value="games" aria-label="Games">🎮 Games</option>
            </select>

            <button type="submit" class="btn-lancar" style="background: var(--dourado); color: #000; border: none; padding: 12px; border-radius: 10px; font-weight: bold; cursor: pointer; width: 100%;">
                Lançar ao Mar! 🌊
            </button>
        </form> 
        
        <!-- Corrigido: Fechamento correto e isolado do formulário -->
        <div style="margin-top: 10px; text-align: center; font-size: 12px; opacity: 0.8;">
            <small>🔍 Perdeu algo? <a href="perdidos.php" style="color: var(--dourado);" aria-label="Perdeu algo? Ir para a Página Especializada de achados e perdidos">Página Especializada</a></small>
        </div>
    </div>
</section>

<script src="js/fenda-mencoes.js"></script>
