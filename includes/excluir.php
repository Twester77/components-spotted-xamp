<?php
include '../conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: ../feed.php");
    exit();
}

$post_id = $_GET['id'];
$usuario_id = $_SESSION['usuario_id'];

// 1. Busca os dados do post e da imagem antes de alterar
$check = $conn->prepare("SELECT usuario_id, imagem_url FROM mensagens WHERE id = ? AND status = 'ativo'");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if ($dados_post && $dados_post['usuario_id'] == $usuario_id) {
    
    // 2. SOFT DELETE: Em vez de apagar a linha, marca como deletado para preservar o autor e os logs
    $soft_delete = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ?");
    $soft_delete->bind_param("i", $post_id);
    
    if ($soft_delete->execute()) {
        
        // 3. MOVER PARA A LIXEIRA: Se tinha imagem, tira da pasta pública e joga na privada
        if (!empty($dados_post['imagem_url'])) {
            $origem = "../postagens/" . $dados_post['imagem_url'];
            $destino = "../lixeira_postagens/" . $dados_post['imagem_url'];
            
            if (file_exists($origem)) {
                // rename() move o arquivo de pasta no servidor de forma instantânea
                rename($origem, $destino); 
            }
        }

        header("Location: ../feed.php?msg=deletado");
    } else {
        header("Location: ../feed.php?msg=erro");
    }
    $soft_delete->close();
} else {
    header("Location: ../feed.php");
}

$check->close();
$conn->close();
?>