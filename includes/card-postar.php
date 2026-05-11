<!-- Card de Postagem Integrado -->
<section id="postar" class="main-novo-post" style="margin-bottom: 40px !important;">
    <section id="postar" class="main-novo-post"></section>
    <div class="form-container">
        <h2>O que tá rolando na UNIFEV?</h2>

        <form action="enviar-post.php" method="POST" enctype="multipart/form-data">
            <textarea name="mensagem" placeholder="Sussurre algo para a Fenda..." required maxlength="800"></textarea>

            <div style="margin-bottom: 15px;">
                <label for="imagem" style="color: var(--dourado); font-size: 14px; cursor: pointer;">
                    <i class="fas fa-camera"></i> Adicionar Foto (Opcional)
                </label>
                <input type="file" name="imagem" id="imagem" accept="image/*" style="display: block; margin-top: 5px; color: #ccc; font-size: 0.8rem;">
            </div>

        </form>

        <label for="categoria" style="color: var(--dourado); font-size: 14px;">Categoria:</label>
        <select name="categoria" id="categoria" style="background: #1a1a1a; color: #fff; border: 1px solid var(--dourado); border-radius: 10px; padding: 10px; margin-bottom: 15px;">
            <option value="anonimo">🕵️ Anônimo </option>
            <option value="comunidade">👥 Comunidade</option>
            <option value="academico">❓ Dúvidas Acadêmicas</option>
            <option value="elogio">💖 Correio Elegante</option>
            <option value="ranco">👌 Ranço</option>
            <option value="acaba-pelo-amor-de-deus">😭 Eu não estou suportando mais</option>
            <option value="caronas">🚗 Caronas</option>
            <option value="esportes">🏀 Esportes</option>
            <option value="games">🎮 Games</option>
        </select>

        <button type="submit" class="btn-lancar" style="background: var(--dourado); color: #000; border: none; padding: 12px; border-radius: 10px; font-weight: bold; cursor: pointer;">
            Lançar ao Mar! 🌊
        </button>

        <div style="margin-top: 10px; text-align: center; font-size: 12px; opacity: 0.8;">
            <small>🔍 Perdeu algo? <a href="perdidos.php" style="color: var(--dourado);">Página Especializada</a></small>
        </div>
        </form>
    </div>
</section>