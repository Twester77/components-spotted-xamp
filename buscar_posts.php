<?php
include 'conexao.php';
session_start();

// Pegamos o deslocamento (quantos posts já foram mostrados)
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$categoria = isset($_GET['categoria']) ? mysqli_real_escape_string($conn, $_GET['categoria']) : '';

$sql = "SELECT m.*, u.username, u.foto 
        FROM mensagens m 
        LEFT JOIN usuarios u ON m.usuario_id = u.id";

if (!empty($categoria)) {
    $sql .= " WHERE m.categoria = '$categoria'";
}

$sql .= " ORDER BY m.id DESC LIMIT 30 OFFSET $offset";
$resultado = mysqli_query($conn, $sql);

if (mysqli_num_rows($resultado) > 0) {
    while ($linha = mysqli_fetch_assoc($resultado)) {
        // Aqui você vai copiar EXATAMENTE a estrutura HTML do seu <article class="spotted-card">
        // que está no seu feed.php. O PHP vai "cuspir" esse HTML pronto para o JavaScript.
        echo '<article class="spotted-card ' . $linha['categoria'] . '">';
        // ... (todo o resto do seu HTML do card que você já tem)
        echo '</article>';
    }
} else {
    echo "FIM_DADOS"; // Aviso para o JS que não tem mais nada
}
?>