<?php
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
    // Pega a subcategoria se ela existir (formulário de perdidos), senão deixa vazio (feed geral)
    $subcategoria = isset($_POST['subcategoria']) ? mysqli_real_escape_string($conn, $_POST['subcategoria']) : "";

    // Se for do feed geral, a imagem_url já está sendo tratada.
    $usuario_id = $_SESSION['usuario_id'];
    $imagem_url = null;

    // Lógica de Upload de Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $arquivo_tmp = $_FILES['imagem']['tmp_name'];

        // 1. Camada de Segurança: Verificar se é REALMENTE uma imagem
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($arquivo_tmp);
        $aceitos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mime_type, $aceitos)) {
            die("ERRO: O arquivo enviado não é uma imagem válida.");
        }

        // 2. Camada de Segurança: Limitar Tamanho (ex: 5MB)
        if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
            die("ERRO: Imagem muito pesada. Limite de 5MB.");
        }

        // 3. Camada de Segurança: Renomear com Hash (Você já faz, isso é ótimo!)
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $novo_nome = bin2hex(random_bytes(16)) . "." . $extensao; // random_bytes é mais seguro que uniqid
        $destino = "uploads/" . $novo_nome;

        if (move_uploaded_file($arquivo_tmp, $destino)) {
            $imagem_url = $novo_nome;

            // DICA DE OURO: Se quiser ser 100% seguro, use a função getimagesize() 
            // para validar que o arquivo tem dimensões reais.
            if (!getimagesize($destino)) {
                unlink($destino); // Deleta se for um script disfarçado
                die("ERRO: Arquivo corrompido ou malicioso.");
            }
        }
    }

    // 3. Preparação do SQL do Post
    $stmt = $conn->prepare("INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, data_post) VALUES (?, ?, ?, ?, ?, NOW())");

    // 4 parâmetros (s, s, s, i, s) para 5 interrogações
    $stmt->bind_param("sssis", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_url);
    // Mensagem(s), Categoria(s), Subcategoria(s), Usuario_id(i), Imagem_url(s)


    if ($stmt->execute()) {

        // --- CAPTURA O ID DO POST QUE ACABOU DE SER CRIADO ---
        $post_id_recem_criado = $conn->insert_id;

        // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO À PROVA DE ERROS) ---
        if (preg_match_all('/@([^\s]+)/', $mensagem, $matches)) {
            $mencoes = $matches[1]; // Pega só o que vem DEPOIS do @

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
                        // Usamos o username para a notificação ficar padrão rede social
                        $quem_username = $_SESSION['usuario_username'] ?? "alguem";
                        $msg_n = "@" . $quem_username . " mencionou você em um post!";

                        $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida, data_criacao) VALUES (?, ?, ?, 0, NOW())");
                        $st_n->bind_param("iis", $id_dest, $post_id_recem_criado, $msg_n);
                        $st_n->execute();
                    }
                }
            }
        } // 🧠 FIM DO CÉREBRO DE MENÇÕES

        header("Location: feed.php");
        exit();
    } else {
        die("ERRO AO SALVAR: " . $stmt->error);
    }
}
