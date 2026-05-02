<?php
// Tenta incluir a conexão verificando se está dentro de 'includes' ou na raiz
if (file_exists('conexao.php')) {
    include 'conexao.php';
} else {
    include '../conexao.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login necessário']);
    exit();
}

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? mysqli_real_escape_string($conn, $_GET['tipo']) : '';
$usuario_id = $_SESSION['usuario_id'];

if ($post_id > 0 && !empty($tipo)) {
    // 1. Verifica se já existe uma reação
    $check = $conn->prepare("SELECT tipo_reacao FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
    $check->bind_param("ii", $post_id, $usuario_id);
    $check->execute();
    $res_check = $check->get_result();
    $dados_reacao = $res_check->fetch_assoc();

    if ($res_check->num_rows == 0) {
        // Inserir nova
        $stmt = $conn->prepare("INSERT INTO curtidas (mensagem_id, usuario_id, tipo_reacao) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $usuario_id, $tipo);
        $stmt->execute();
    } else {
        if ($dados_reacao['tipo_reacao'] == $tipo) {
            // Se clicar no mesmo, remove (toggle)
            $stmt = $conn->prepare("DELETE FROM curtidas WHERE mensagem_id = ? AND usuario_id = ?");
            $stmt->bind_param("ii", $post_id, $usuario_id);
        } else {
            // Se clicar em um diferente, atualiza
            $stmt = $conn->prepare("UPDATE curtidas SET tipo_reacao = ? WHERE mensagem_id = ? AND usuario_id = ?");
            $stmt->bind_param("sii", $tipo, $post_id, $usuario_id);
        }
        $stmt->execute();
    }

    // 2. Busca contagens atualizadas
    $sql_count = "SELECT tipo_reacao, COUNT(*) as total FROM curtidas WHERE mensagem_id = ? GROUP BY tipo_reacao";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $post_id);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result();

    $contagens = [];
    while($row = $res_count->fetch_assoc()){
        $contagens[$row['tipo_reacao']] = (int)$row['total'];
    }

    // 3. Busca reações do usuário logado para esse post
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
        'contagens' => $contagens,
        'minhas_reacoes' => $minhas_reacoes
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
}