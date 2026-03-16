<?php include 'components/header.php'; ?>
<?php include 'components/navbar.php'; ?>

<main style="max-width: 600px; margin: auto;">
   <article>
    <h2>Achados e Perdidos</h2>
    <div style="display: flex; gap: 10px; justify-content:center;">
        <img src="imagensfoto/Capa Achados e Perdidos.jpg" alt="Capa do Achados e Perdidos" width="80%">
    </div>
    <p style="text-align: center; font-style: italic;">
        Perdeu o juízo? Disso a gente não cuida. Agora se você perdeu a chave ou a garrafinha, você está no lugar certo!
    </p>
    <div class="post-item" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px; background-color: rgba(255,255,255,0.1);">
        <p><strong>@Anônimo:</strong> Galera, achei um <strong>estojo de óculos preto</strong> em cima da mesa na Biblioteca do Centro. Deixei na recepção!</p>
        <button type="button" style="font-size: 12px;">Comentar</button>
    </div>

    <div class="post-item" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 8px; background-color: rgba(255,255,255,0.1);">
        <p><strong>@FuturaDra:</strong> Perdi minha carteira com estampa florida no bloco de Direito, sala 12!</p>
        <div style="margin-left: 20px; border-left: 2px solid rgb(204, 66, 12); padding-left: 10px;">
            <p style="font-size: 14px;"><strong>@DireitoUnifev:</strong> "Vi uma carteira assim na cantina agora pouco!"</p>
            <p style="font-size: 14px; color: #00ff00;"><strong>@FuturaDra:</strong> "Era essa mesmo! Muito obrigada! 😭🙏"</p>
        </div>

    </div>
    <section style="background-color: #313092; padding: 20px; border-radius: 10px; margin-top: 30px;">
        <h3>Perdeu ou Achou algo? Publique aqui:</h3>
        <p style="background-color: #ffffcc; color: #000; padding: 10px; font-size: 0.85em; border-left: 5px solid #ffcc00; margin-bottom: 15px;">
            <strong>⚠️ NOTA DE SEGURANÇA:</strong> Ao postar fotos de documentos ou cartões, 
            <strong>cubra dados sensíveis</strong> (CPF, número do cartão, foto). O Spotted não se responsabiliza pela 
            exposição de dados pessoais.
        </p>
        <form>
            <label>O que aconteceu?</label><br>
            <select style="width: 100%; padding: 5px; margin: 10px 0;">
                <option>Eu perdi algo...</option>
                <option>Eu achei algo...</option>
            </select>
            <textarea placeholder="Descreva o objeto e o local..." style="width: 100%; height: 80px;"></textarea>
            <label for="foto">Anexar foto do objeto:</label><br>
            <input type="file" id="foto" name="foto" style="margin: 10px 0;"><br>
            <button type="submit" style="background-color: rgb(204, 66, 12); color: white; border: none; padding: 10px 20px; cursor: pointer; width: 100%;">
                Publicar no Feed
            </button>
        </form>
    </section>
</article>

<?php include 'components/footer.php'; ?>