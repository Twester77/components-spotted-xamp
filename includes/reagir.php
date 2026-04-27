<?php
include '../conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Login necessário']);
    exit();
}

$post_id = $_GET['id'] ?? 0;
$tipo = $_GET['tipo'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

$check = $conn->prepare("SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
$check->bind_param("ii", $post_id, $usuario_id);
$check->execute();
$res_check = $check->get_result();
$dados_reacao = $res_check->fetch_assoc();

if ($res_check->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO curtidas (mensagem_id, usuario_id, tipo_reacao) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $usuario_id, $tipo);
    $stmt->execute();
} else {
    if ($dados_reacao['tipo_reacao'] == $tipo) {
        $stmt = $conn->prepare("DELETE FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $post_id, $usuario_id);
    } else {
        $stmt = $conn->prepare("UPDATE curtidas SET tipo_reacao = ? WHERE mensagem_id = ? AND usuario_id = ?");
        $stmt->bind_param("sii", $tipo, $post_id, $usuario_id);
    }
    $stmt->execute();
}

header('Content-Type: application/json');

$sql_count = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = ? GROUP BY tipo_reacao";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $post_id);
$stmt_count->execute();
$res_count = $stmt_count->get_result();

$contagens = [];
while($row = $res_count->fetch_assoc()){
    $contagens[$row['tipo_reacao']] = $row['total'];
}

$minhas_reacoes = [];
$stmt_meu = $conn->prepare("SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
$stmt_meu->bind_param("ii", $post_id, $usuario_id);
$stmt_meu->execute();
$res_meu = $stmt_meu->get_result();

while($m = $res_meu->fetch_assoc()){
    $minhas_reacoes[] = $m['tipo_reacao'];
}

echo json_encode([
    'status' => 'success',
    'post_id' => $post_id,
    'contagens' => $contagens,
    'minhas' => $minhas_reacoes // Aqui estava o conflito!
]);
exit();