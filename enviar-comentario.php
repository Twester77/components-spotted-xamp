
<?php
session_start(); // Sem isso o PHP não lê o nome de quem logou

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {   // Pega o nome da sessão se existir, do contrário ele fica vazio (para o banco de dados tratar como anônimo)

    // Pega o parent_id se vier do formulário, senão deixa NULL
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $id_mensagem = $_POST['id_mensagem'];
    $comentario = $_POST['comentario'];
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;
    $vibe = $_POST['pref_vibe_comentario'] ?? 'vibe-glass';
    $cor_borda = $_POST['pref_cor_borda'] ?? '#70cde4';

    // Adicionamos o parent_id no SQL
    $sql = "INSERT INTO comentarios (id_mensagem, comentario, usuario_nome, parent_id, pref_vibe_comentario, pref_cor_borda) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // i = int, s = string, s = string, i = int (ou null), s = string, s = string
    $stmt->bind_param("ississ", $id_mensagem, $comentario, $usuario_nome, $parent_id, $vibe, $cor_borda);

    if ($stmt->execute()) {

        // --- CÉREBRO DE MENÇÕES ---

if (preg_match_all('/@([a-zA-Z0-9_\-]+)/', $comentario, $matches)) {
    $mencoes = array_unique($matches[1]); 

    foreach ($mencoes as $nome_usuario) {
        // 1. Busca o ID do usuário (independente de maiúsculas/minúsculas)
        $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = LOWER(?)");
        $stmt_busca->bind_param("s", $nome_usuario);
        $stmt_busca->execute();
        $res = $stmt_busca->get_result();

        if ($alvo = $res->fetch_assoc()) {
            $id_destinatario = $alvo['id'];
            $meu_id = $_SESSION['usuario_id'] ?? 0;

            // 2. Só notifica se não for você mesmo
            if ($id_destinatario != $meu_id) {
                $quem = $_SESSION['usuario_username'] ?? "Alguém";
                $msg_notificacao = "@$quem mencionou você em um comentário!";

                // 3. O INSERT que faz o Radar dar o susto
                $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                $st_n->bind_param("iis", $id_destinatario, $id_mensagem, $msg_notificacao);
                $st_n->execute();
            }
        }
    }
}
        //  FIM DO CÉREBRO 

        header("Location: post.php?id=$id_mensagem&comentario=sucesso");
        exit();
    } else {
        echo "Erro ao comentar: " . $conn->error;
    }
}
?>




