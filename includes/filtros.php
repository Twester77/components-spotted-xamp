<section class="filtros-container">
    <a href="feed.php" class="filtro-btn <?php echo !isset($_GET['categoria']) || $_GET['categoria'] == '' ? 'ativo' : ''; ?>">
        #TODOS
    </a>

    <?php
    // Busca as categorias existentes
    $sql_categorias = "SELECT DISTINCT categoria FROM mensagens WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC";
    $res_cats = mysqli_query($conn, $sql_categorias);

    while($cat = mysqli_fetch_assoc($res_cats)) {
        $nome_cat = $cat['categoria'];
        $selected_class = (isset($_GET['categoria']) && $_GET['categoria'] == $nome_cat) ? 'ativo' : '';
        
        // O urlencode protege contra espaços ou caracteres especiais
        echo "<a href='feed.php?categoria=" . urlencode($nome_cat) . "' class='filtro-btn $selected_class'>#" . strtoupper($nome_cat) . "</a>";
    }
    ?>
</section>
