<?php
session_start();
include 'conexao.php';

if (!$conn) {
    die("A conexão falhou, Léo! O erro foi: " . mysqli_connect_error());
}

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 2. Higienização básica
    $mensagem = mysqli_real_escape_string($conn, $_POST['mensagem']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    $usuario_id = $_SESSION['usuario_id']; 

    // 3. Preparação do SQL do Post
    $sql = "INSERT INTO mensagens (mensagem, categoria, usuario_id, data_post) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $mensagem, $categoria, $usuario_id);

    if ($stmt->execute()) {
        
        // --- 🎯 CAPTURA O ID DO POST QUE ACABOU DE SER CRIADO ---
        $post_id_recem_criado = $conn->insert_id;

        // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO COM LINK) ---
        if (preg_match_all('/@([^\s]+)/', $mensagem, $matches)) {
            $mencoes = $matches[0]; 

            foreach ($mencoes as $user_tag) {
                $sql_busca = "SELECT id FROM usuarios WHERE username = ? OR nome = ?";
                $stmt_busca = $conn->prepare($sql_busca);
                $nome_limpo = str_replace('@', '', $user_tag); 
                $stmt_busca->bind_param("ss", $user_tag, $nome_limpo);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();
                
                if ($alvo = $res->fetch_assoc()) {
                    $id_dest = $alvo['id'];
                    
                    // Não notifica se o usuário marcar a si mesmo
                    if($id_dest != $_SESSION['usuario_id']) {
                        $msg_n = $_SESSION['usuario_nome'] . " mencionou você em um post!";
                        
                        // AGORA INCLUÍMOS O post_id NO INSERT
                        $sql_n = "INSERT INTO notificacoes (usuario_id, post_id, mensagem) VALUES (?, ?, ?)";
                        $st_n = $conn->prepare($sql_n);
                        $st_n->bind_param("iis", $id_dest, $post_id_recem_criado, $msg_n);
                        $st_n->execute();
                    }
                }
            }
        } 
        // 🧠 FIM DO CÉREBRO DE MENÇÕES

        header("Location: feed.php");
        exit();
    } else {
        die("ERRO AO SALVAR: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: novo-post.php");
    exit();
}
?>