
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

        //   CÉREBRO DE MENÇÕES NOS COMENTÁRIOS 
        if (preg_match_all('/@([^\s]+)/', $comentario, $matches)) {
            $mencoes = $matches[1]; // Pega exatamente como no seu enviar-post.php

            foreach ($mencoes as $nome_usuario) {

                // 1. Busca o ID do usuário que foi mencionado
                $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
                $stmt_busca->bind_param("s", $nome_usuario);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();

                if ($alvo = $res->fetch_assoc()) {
                    $id_destinatario = $alvo['id'];

                    // 2. Não notifica se o usuário marcou a si mesmo
                    if ($id_destinatario != $_SESSION['usuario_id']) {
                        $quem = $_SESSION['usuario_nome'] ?? "Alguém";
                        $msg_notificacao = $quem . " mencionou você em um comentário!";

                        // 3. Insere a notificação (post_id é o id_mensagem aqui)
                        $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida, data_criacao) VALUES (?, ?, ?, 0, NOW())");
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




