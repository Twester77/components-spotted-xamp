
<?php
session_start();
include 'conexao.php';
if (!$conn) {
    die("A conexão falhou, Léo! O erro foi: " . mysqli_connect_error());
} else {
    // echo "Conexão OK!"; // Descomente só pra testar e depois apague
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
        
       // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO FINAL) ---
// Captura tudo que começa com @ até encontrar um espaço
if (preg_match_all('/@([^\s]+)/', $mensagem, $matches)) {
    $mencoes = $matches[0]; // Pega o nome completo: @test ou @apresença_fevN#1

    foreach ($mencoes as $user_tag) {
        // Busca na coluna 'username' OU 'nome' para garantir
        $sql_busca = "SELECT id FROM usuarios WHERE username = ? OR nome = ?";
        $stmt_busca = $conn->prepare($sql_busca);
        $nome_limpo = str_replace('@', '', $user_tag); // 'test' sem o @
        $stmt_busca->bind_param("ss", $user_tag, $nome_limpo);
        $stmt_busca->execute();
        $res = $stmt_busca->get_result();
        
        if ($alvo = $res->fetch_assoc()) {
            $id_dest = $alvo['id'];
            if($id_dest != $_SESSION['usuario_id']) {
                $msg_n = $_SESSION['usuario_nome'] . " mencionou você!";
                $sql_n = "INSERT INTO notificacoes (usuario_id, mensagem) VALUES (?, ?)";
                $st_n = $conn->prepare($sql_n);
                $st_n->bind_param("is", $id_dest, $msg_n);
                $st_n->execute();
            }
        }
    }
}   // 🧠 FIM DO CÉREBRO DE MENÇÕES

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