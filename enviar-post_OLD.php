<?php
session_start();
include 'conexao.php';

if (!$conn) {
    // Log interno do erro (opcional)
    error_log("Erro de conexão: " . mysqli_connect_error());
    // Mensagem para o usuário (estilo A Fenda)
    die("Ops! Parece que os servidores da Fenda caíram no limbo. Tente novamente mais tarde.");
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

        // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO À PROVA DE ERROS) ---
if (preg_match_all('/@([^\s]+)/', $mensagem, $matches)) {
    $mencoes = $matches[1]; // Pega só o que vem DEPOIS do @ (ex: 'apresença_fev')

    foreach ($mencoes as $nome_usuario) {
        // Busca EXATAMENTE pelo username que está no banco
        $sql_busca = "SELECT id FROM usuarios WHERE username = ?";
        $stmt_busca = $conn->prepare($sql_busca);
        $stmt_busca->bind_param("s", $nome_usuario);
        $stmt_busca->execute();
        $res = $stmt_busca->get_result();
        
        if ($alvo = $res->fetch_assoc()) {
            $id_dest = $alvo['id'];
            
            // Verifica se não é o próprio usuário se marcando
            if($id_dest != $_SESSION['usuario_id']) {
                $nome_quem_mencionou = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? "Alguém";
                $msg_n = $nome_quem_mencionou . " mencionou você em um post!";
                
                // Insere a notificação com o ID do post
                $sql_n = "INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida, data_criacao) VALUES (?, ?, ?, 0, NOW())";
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