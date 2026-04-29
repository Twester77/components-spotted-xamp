<?php
session_start();

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $id_mensagem = $_POST['id_mensagem'];
    $comentario = $_POST['comentario'];
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;
    $vibe = $_POST['pref_vibe_comentario'] ?? 'vibe-glass';
    $cor_borda = $_POST['pref_cor_borda'] ?? '#70cde4';

    $sql = "INSERT INTO comentarios (id_mensagem, comentario, usuario_nome, parent_id, pref_vibe_comentario, pref_cor_borda) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ississ", $id_mensagem, $comentario, $usuario_nome, $parent_id, $vibe, $cor_borda);

    if ($stmt->execute()) {
        // --- TESTE DO VILÃO: SE CHEGAR AQUI, O BANCO ESTÁ OK ---
        // die("DEBUG: O PHP salvou no banco com sucesso!"); 

        $meu_id = $_SESSION['usuario_id'] ?? 0;
        $quem_comentou = $_SESSION['usuario_username'] ?? "Alguém";

        // Notificar dono do post
        $stmt_dono = $conn->prepare("SELECT usuario_id FROM mensagens WHERE id = ?");
        $stmt_dono->bind_param("i", $id_mensagem);
        $stmt_dono->execute();
        $res_dono = $stmt_dono->get_result()->fetch_assoc();

        if ($res_dono) {
            $id_dono_post = $res_dono['usuario_id'];
            if ($id_dono_post != $meu_id) {
                $msg_dono = "@$quem_comentou comentou no seu post!";
                $st_dono_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                $st_dono_notif->bind_param("iis", $id_dono_post, $id_mensagem, $msg_dono);
                $st_dono_notif->execute();
            }
        }

        // Cérebro de menções
        if (preg_match_all('/@([a-zA-Z0-9_\-]+)/', $comentario, $matches)) {
            $mencoes = array_unique($matches[1]);
            foreach ($mencoes as $nome_usuario) {
                $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = LOWER(?)");
                $stmt_busca->bind_param("s", $nome_usuario);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();

                if ($alvo = $res->fetch_assoc()) {
                    $id_destinatario = $alvo['id'];
                    if ($id_destinatario != $meu_id && (!isset($id_dono_post) || $id_destinatario != $id_dono_post)) {
                        $msg_notificacao = "@$quem_comentou mencionou você em um comentário!";
                        $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                        $st_n->bind_param("iis", $id_destinatario, $id_mensagem, $msg_notificacao);
                        $st_n->execute();
                    }
                }
            }
        }

        // Resposta para o AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            ob_clean(); 
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Comentário enviado!']);
            exit(); 
        } else {
            header("Location: post.php?id=$id_mensagem&comentario=sucesso");
            exit();
        }
        
    } else {
        echo "Erro ao comentar: " . $conn->error;
    }
}
?>