<?php
session_start();
include 'conexao.php';

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Higienização básica
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mensagem = mysqli_real_escape_string($conn, $_POST['mensagem']);
    $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
    // --- ADICIONE ESTA LINHA AQUI ---
    $subcategoria = mysqli_real_escape_string($conn, $_POST['subcategoria']);
    $usuario_id = $_SESSION['usuario_id'];

    // 3. Preparação do SQL do Post

    $stmt = $conn->prepare("INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, data_post) VALUES (?, ?, ?, ?, NOW())");
    // Agora sim: 4 parâmetros (s, s, s, i) para 4 interrogações
    $stmt->bind_param("sssi", $mensagem, $categoria, $subcategoria, $usuario_id);

    if ($stmt->execute()) {

        // ---  CAPTURA O ID DO POST QUE ACABOU DE SER CRIADO ---

        $post_id_recem_criado = $conn->insert_id;

        // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO À PROVA DE ERROS) ---

        if (preg_match_all('/@([^\s]+)/', $mensagem, $matches)) {
            $mencoes = $matches[1]; // Pega só o que vem DEPOIS do @ (ex: 'apresença_fev')

            foreach ($mencoes as $nome_usuario) {
                // Busca EXATAMENTE pelo username que está no banco

                $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
                $stmt_busca->bind_param("s", $nome_usuario);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();

                if ($alvo = $res->fetch_assoc()) {
                    $id_dest = $alvo['id'];

                    // Verifica se não é o próprio usuário se marcando
                    if ($id_dest != $_SESSION['usuario_id']) {
                        // CORREÇÃO: Pegando o nome da sessão de forma segura
                        $quem = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? "Alguém";
                        $msg_n = $quem . " mencionou você em um post!";

                        // Insere a notificação com o ID do post
                        $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida, data_criacao) VALUES (?, ?, ?, 0, NOW())");
                        $st_n->bind_param("iis", $id_dest, $post_id_recem_criado, $msg_n);
                        $st_n->execute();
                    }
                }
            }
        }  // 🧠 FIM DO CÉREBRO DE MENÇÕES

        header("Location: feed.php");
        exit();
    } else {
        die("ERRO AO SALVAR: " . $stmt->error);
    }
}
