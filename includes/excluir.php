<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth_check.php';


$post_id = (int)$_GET['id'];
$usuario_id = (int)$_SESSION['usuario_id'];

// 1. Busca os dados do post e da imagem antes de alterar (apenas posts ativos)
$check = $conn->prepare("SELECT usuario_id, imagem_url FROM mensagens WHERE id = ? AND status = 'ativo'");
$check->bind_param("i", $post_id);
$check->execute();
$resultado = $check->get_result();
$dados_post = $resultado->fetch_assoc();

if ($dados_post && $dados_post['usuario_id'] == $usuario_id) {
    
    // 2. SOFT DELETE: marca como deletado
    $soft_delete = $conn->prepare("UPDATE mensagens SET status = 'deletado' WHERE id = ?");
    $soft_delete->bind_param("i", $post_id);
    
    if ($soft_delete->execute()) {
        
        // 3. Move a imagem (se existir) para a pasta de lixeira
        if (!empty($dados_post['imagem_url'])) {
            $origem = "../postagens/" . $dados_post['imagem_url'];
            $destino = "../lixeira_postagens/" . $dados_post['imagem_url'];
            
            if (file_exists($origem)) {
                rename($origem, $destino);
            }
        }

        header("Location: ../feed.php?msg=deletado");
        exit();
    } else {
        // Em caso de erro, exibe a mensagem do MySQL
        die("Erro no soft delete: " . $soft_delete->error);
    }
    $soft_delete->close();
} else {
    // Post não encontrado, já deletado ou usuário não é o dono
    header("Location: ../feed.php");
    exit();
}

$check->close();
$conn->close();
?>