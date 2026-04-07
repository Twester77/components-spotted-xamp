<?php
include 'conexao.php';
include 'includes/header.php'; 
include 'includes/navbar.php';
include 'includes/bolhas.php'; 
?>

<main class="main-novo-post">
    <div class="form-container">
        <h2>Enviar Novo Post</h2>

        <div style="background: rgba(255, 188, 0, 0.1); border: 1px solid #ffbc00; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
         <p style="color: #fff; margin: 0; font-size: 14px;">
        🔍 <strong>Perdeu ou achou algum objeto?</strong> <br>
        Para itens perdidos, use nossa <a href="perdidos.php" style="color: #ffbc00; font-weight: bold; text-decoration: underline;">Página Especializada</a> para ter mais visibilidade!
         </p>
         </div>
        <form action="enviar-post.php" method="POST">
            <textarea name="mensagem" placeholder="O que tá rolando na UNIFEV?" required maxlength="800"></textarea>
            
            <label for="categoria">Categoria:</label>
            <select name="categoria" id="categoria">
                <option value="anonimo">🕵️ Anônimo </option>
                <option value="comunidade">👥 Comunidade / Evento </option>
                <option value="academico">❓ Dúvidas Acadêmicas </option>
                <option value="elogio">💖 Correio Elegante </option>
                <option value="ranco"> 👌 Ranço </option>
                <option value="acaba-pelo-amor-de-deus"> 😭 Eu não estou suportando mais  </option>
                <option value="caronas"> 🚗 Caronas </option>
                <option value="esportes"> 🏀 Esportes </option>
                <option value="games"> 🎮 Games </option>

            </select>

            <button type="submit" class="btn-lancar">Lançar ao Mar!</button>
            <a href="feed.php" style="display:block; text-align:center; color:#ffbc00; text-decoration:none; margin-top: 15px;">Voltar ao Feed</a>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>