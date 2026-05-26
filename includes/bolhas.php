<?php
include 'conexao.php';
// 1. Garante que temos acesso à conexão e à sessão

$u_id_bolhas = $_SESSION['usuario_id'] ?? 0;

// 2. Se o usuário estiver logado, vamos buscar a preferência real dele
if ($u_id_bolhas > 0) {
    // Buscamos apenas o campo necessário
    $sql_bolhas = "SELECT pref_bolhas FROM usuarios WHERE id = '$u_id_bolhas'";
    $res_bolhas = mysqli_query($conn, $sql_bolhas);
    $dados_bolhas = mysqli_fetch_assoc($res_bolhas);
    
    $exibir = $dados_bolhas['pref_bolhas'] ?? 1;
} else {
    // Se não estiver logado (visitante) ele mostra automaticamente
    $exibir = 1; 
}

// 3. A TRAVA: Se a preferência for 0, o script para aqui e não carrega nada
if ($exibir == 0) {
    return; 
}
?>

<div class="bubbles-container">
    <?php
    for ($i = 0; $i < 10; $i++) {
        $left = rand(0, 100);
        $delay = rand(0, 8);
        $size = rand(20, 60);
        echo "<div class='bolha' style='left: {$left}%; animation-delay: {$delay}s; width: {$size}px; height: {$size}px;'></div>";
    }
    ?>
</div>

