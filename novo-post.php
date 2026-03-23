<?php
include 'conexao.php';
session_start();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Postar na Fenda</title>
</head>
<body>

<div class="form-container">
    <h2>🚀 Lançar na Fenda</h2>
    <form action="enviar-post.php" method="POST">
        <textarea name="mensagem" placeholder="O que tá rolando na UNIFEV?" required maxlength="500"></textarea>
        
        <label for="categoria">Categoria:</label>
        <select name="categoria" id="categoria">
            <option value="anonimo">🕵️ Anônimo </option>
            <option value="comunidade">👥 Comunidade / Evento </option>
            <option value="pergunta">❓ Pergunta </option>
            <option value="elogio">💖 Elogio </option>
            <option value="ranço"> 👌 Ranço </option>
            <option value="desabafo"> 😩 Acaba pelo amor de Deus </option>

        

        </select>

        <button type="submit"> Lançar ao Mar!</button>
        <a href="feed.php" style="display:block; text-align:center; color:#666; text-decoration:none;">Voltar ao Feed</a>
    </form>
</div>

</body>
</html>