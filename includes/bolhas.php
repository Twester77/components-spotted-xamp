<?php
include_once __DIR__ . '/../conexao.php';

$u_id_bolhas = $_SESSION['usuario_id'] ?? 0;

// 2. Se o usuário estiver logado, vamos buscar a preferência real dele
if ($u_id_bolhas > 0) {
    // 🔥 CORREÇÃO BLINDAGEM (Blue Team): Prepared Statement em vez de interpolação
    $sql_bolhas = "SELECT pref_bolhas FROM usuarios WHERE id = ?";
    $stmt_bolhas = $conn->prepare($sql_bolhas);
    $stmt_bolhas->bind_param("i", $u_id_bolhas);
    $stmt_bolhas->execute();
    $res_bolhas = $stmt_bolhas->get_result();
    $dados_bolhas = $res_bolhas->fetch_assoc();
    $stmt_bolhas->close();
    
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