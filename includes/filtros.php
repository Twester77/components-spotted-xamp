<section class="filtros-container">
    <form action="feed.php" method="GET">
        <select name="categoria">
            <option value="">Todas as Categorias</option>
            
            <?php
            // 1. Busca as categorias únicas que já existem no banco
            $sql_categorias = "SELECT DISTINCT categoria FROM mensagens WHERE categoria IS NOT NULL AND categoria != ''";
            $res_cats = mysqli_query($conn, $sql_categorias);

            // 2. Cria as opções conforme o que foi encontrado
            while($cat = mysqli_fetch_assoc($res_cats)) {
                $nome_cat = $cat['categoria'];
                // Mantém selecionado se o usuário já filtrou
                $selected = ($categoria_selecionada == $nome_cat) ? 'selected' : '';
                
                echo "<option value='$nome_cat' $selected>" . ucfirst($nome_cat) . "</option>";
            }
            ?>
        </select>
        <button type="submit" class="btn-filtrar">Filtrar</button>
    </form>
</section>