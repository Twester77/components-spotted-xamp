<?php
include 'conexao.php';
include 'includes/header.php'; 
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<main class="main-novo-post">
    <div class="form-container">
        <h2>Enviar Novo Post</h2>
        <form action="enviar-post.php" method="POST">
            <textarea name="mensagem" placeholder="O que tá rolando na UNIFEV?" required maxlength="800"></textarea>
            
            <label for="categoria">Categoria:</label>
            <select name="categoria" id="categoria">
                <option value="anonimo">🕵️ Anônimo </option>
                <option value="comunidade">👥 Comunidade / Evento </option>
                <option value="pergunta">❓ Pergunta </option>
                <option value="elogio">💖 Correio Elegante </option>
                <option value="ranco"> 👌 Ranço </option>
                <option value="desabafo"> 😩 Acaba pelo amor de Deus </option>
            </select>

            <button type="submit" class="btn-lancar">Lançar ao Mar!</button>
            <a href="feed.php" style="display:block; text-align:center; color:#ffbc00; text-decoration:none; margin-top: 15px;">Voltar ao Feed</a>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>