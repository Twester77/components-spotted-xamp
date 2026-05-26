<?php
error_reporting(0); 
ini_set('display_errors', 0);
// buscar-mencoes.php
include_once 'conexao.php';

$termo = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$resultados = []; // Inicializamos vazio

// Só faz a query se tiver algo digitado
if (strlen($termo) >= 1) {
    $sql = "SELECT username FROM usuarios WHERE username LIKE ? LIMIT 5";
    $stmt = $conn->prepare($sql);
    $busca = $termo . '%';
    $stmt->bind_param("s", $busca);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $resultados[] = $row['username'];
    }
    $stmt->close();
}

// Sempre retorna um JSON (ou [], ou ["usuario1", ...])
header('Content-Type: application/json');
echo json_encode($resultados);
?>
