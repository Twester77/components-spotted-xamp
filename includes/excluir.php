<?php
include '../conexao.php';


// 1. Bloqueio de acesso direto / Não logado
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: ../feed.php");
    exit();
}

$post_id = $_GET['id'];
$usuario_id = $_SESSION['usuario_id'];

// 2. PREPARED STATEMENT: Busca o post para conferir se o dono é o mesmo que está logado
// Isso impede que alguém mude o ID na URL para apagar posts de terceiros
$check = $conn->prepare("SELECT usuario_id FROM mensagens WHERE id = ?");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if ($dados_post && $dados_post['usuario_id'] == $usuario_id) {
    
    // 3. SEGUNDO PREPARED STATEMENT: Se ele é o dono, apaga o post
    $delete = $conn->prepare("DELETE FROM mensagens WHERE id = ?");
    $delete->bind_param("i", $post_id);
    
    if ($delete->execute()) {
        // Sucesso: Volta para o feed com uma mensagem de confirmação
        header("Location: ../feed.php?msg=deletado");
    } else {
        // Erro 
        header("Location: ../feed.php?erro=db");
    }

} else {
    // Tentativa de excluir post alheio ou post não existe
    header("Location: ../feed.php?erro=permissao");
}

exit();