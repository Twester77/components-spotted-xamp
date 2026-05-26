<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $id_mensagem = $_POST['id_mensagem'];
    $comentario = $_POST['comentario'];

    // CAPTURA O ID DO USUÁRIO LOGADO (Se for visitante, vira null)
    $usuario_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : null;
    $usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;

    $vibe = $_POST['pref_vibe_comentario'] ?? 'vibe-glass';
    $cor_borda = $_POST['pref_cor_borda'] ?? '#70cde4';

    // ADICIONAMOS 'usuario_id' NO INSERTO DO BANCO 
    $sql = "INSERT INTO comentarios (id_mensagem, comentario, usuario_nome, usuario_id, parent_id, pref_vibe_comentario, pref_cor_borda) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    // Sua sequência: id_mensagem (i), comentario (s), usuario_nome (s), usuario_id (i), parent_id (i), vibe (s), cor (s)
    // Sequência correta: "ississi" (o 5º é integer, não string)

    $stmt->bind_param("ississi", $id_mensagem, $comentario, $usuario_nome, $usuario_id, $parent_id, $vibe, $cor_borda);

    if ($stmt->execute()) {
        // ... o resto do seu código de notificações e menções continua igualzinho aqui para baixo ...

        // Ajuste para não quebrar se for visitante (identifica como "Visitante" na notificação)
        $meu_id = $_SESSION['usuario_id'] ?? 0;
        $quem_comentou = $_SESSION['usuario_nome'] ?? "Visitante";

        // --- NOTIFICAR DONO DO POST ---
        $stmt_dono = $conn->prepare("SELECT usuario_id FROM mensagens WHERE id = ?");
        $stmt_dono->bind_param("i", $id_mensagem);
        $stmt_dono->execute();
        $res_dono = $stmt_dono->get_result()->fetch_assoc();

        if ($res_dono) {
            $id_dono_post = $res_dono['usuario_id'];
            // Só notifica se quem comentou não for o dono (visitantes sempre notificam o dono)
            if ($id_dono_post != $meu_id || $meu_id == 0) {
                $msg_dono = "@$quem_comentou comentou no seu post!";
                $st_dono_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                $st_dono_notif->bind_param("iis", $id_dono_post, $id_mensagem, $msg_dono);
                $st_dono_notif->execute();
            }
        }

        // --- 🧠 CÉREBRO DE MENÇÕES EM COMENTÁRIOS ---
        // Regex corrigida para ignorar vírgulas, pontos e símbolos colados ao @
        if (preg_match_all('/@([a-zA-Z0-9\._]+)/', $comentario, $matches)) {
            $mencoes = array_unique($matches[1]);

            foreach ($mencoes as $nome_usuario) {
                $nome_usuario_limpo = strtolower($nome_usuario);

                $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = ?");
                $stmt_busca->bind_param("s", $nome_usuario_limpo);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();

                if ($alvo = $res->fetch_assoc()) {
                    $id_destinatario = $alvo['id'];

                    // Notifica o mencionado se não for o próprio autor logado
                    if ($id_destinatario != $meu_id) {
                        $msg_notificacao = "@$quem_comentou mencionou você em um comentário!";
                        $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                        $st_n->bind_param("iis", $id_destinatario, $id_mensagem, $msg_notificacao);
                        $st_n->execute();
                        $st_n->close();
                    }
                }
                $stmt_busca->close();
            }
        }

        // --- RESPOSTA PARA O AJAX (SEM REFRESH) ---
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Comentário enviado!']);
            exit();
        } else {
            // Fallback caso o JS falhe
            header("Location: post.php?id=$id_mensagem&comentario=sucesso");
            exit();
        }
    } else {
        echo "Erro ao comentar: " . $conn->error;
    }
}
