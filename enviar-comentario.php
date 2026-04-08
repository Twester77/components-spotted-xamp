
<?php
session_start(); // OBRIGATÓRIO: Sem isso o PHP não lê o nome de quem logou!

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {   // Pegamos o nome da sessão se existir, senão ele fica vazio (para o banco tratar como anônimo)

    $id_mensagem = $_POST['id_mensagem'];
    $comentario = $_POST['comentario'];
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;
        
    // IMPORTANTE: A tabela 'comentarios' tem a coluna 'usuario_nome'

    $sql = "INSERT INTO comentarios (id_mensagem, comentario, usuario_nome) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $id_mensagem, $comentario, $usuario_nome);

             // "iss" -> i (inteiro), s (string comentário), s (string nome)

    if ($stmt->execute()) {
        
        // --- 🧠 CÉREBRO DE MENÇÕES NOS COMENTÁRIOS ---
        if (preg_match_all('/@([^\s]+)/', $comentario, $matches)) {
            $mencoes = $matches[1]; // Pega exatamente como no seu enviar-post.php

            foreach ($mencoes as $nome_usuario) {
                // 1. Busca o ID do usuário mencionado
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
        // --- FIM DO CÉREBRO ---

        header("Location: post.php?id=" . $id_mensagem);
        exit();
    } else {
        echo "Erro ao comentar: " . $conn->error;
    }
}
?>




