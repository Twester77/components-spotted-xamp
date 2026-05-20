<?php
// buscar-mencoes.php
include_once 'conexao.php';

// Pega o termo digitado após o @
$termo = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

if (strlen($termo) >= 1) {
    // Busca os usuários que começam com o que o habitante digitou
    $sql = "SELECT username FROM usuarios WHERE username LIKE ? LIMIT 5";
    $stmt = $conn->prepare($sql);
    $busca = $termo . '%';
    $stmt->bind_param("s", $busca);
    $stmt->execute();
    $res = $stmt->get_result();

    $resultados = [];
    while ($row = $res->fetch_assoc()) {
        $resultados[] = $row['username'];
    }

    // Retorna em JSON para o JavaScript ler
    header('Content-Type: application/json');
    echo json_encode($resultados);
}
?>