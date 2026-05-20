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

    $usuario_id = $_SESSION['usuario_id'];
    $imagem_url = null;

    // Lógica de Upload de Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $arquivo_tmp = $_FILES['imagem']['tmp_name'];

        // 1. Camada de Segurança: Verificar se é REALMENTE uma imagem (Bytes Mágicos)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($arquivo_tmp);
        
        // Mapeamento seguro: NÓS definimos a extensão correta a partir do MIME real
        $mapeamento_extensoes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mime_type, $mapeamento_extensoes)) {
            die("ERRO: O arquivo enviado não é uma imagem válida.");
        }

        // 2. Camada de Segurança: Limitar Tamanho (5MB)
        if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
            die("ERRO: Imagem muito pesada. Limite de 5MB.");
        }

        // 3. Camada de Segurança e Nova Rota: 
        // Pegamos a extensão limpa do NOSSO mapeamento e jogamos o arquivo na pasta 'postagens/' na raiz
        $extensao_segura = $mapeamento_extensoes[$mime_type];
        $novo_nome = bin2hex(random_bytes(16)) . "." . $extensao_segura;
        $destino = "postagens/" . $novo_nome;

        if (move_uploaded_file($arquivo_tmp, $destino)) {
            $imagem_url = $novo_nome;

            // Validação secundária estrutural
            if (!getimagesize($destino)) {
                unlink($destino); // Deleta imediatamente se for um script disfarçado
                die("ERRO: Arquivo corrompido ou malicioso.");
            }
        }
    }

    // 3. Preparação do SQL do Post
    $stmt = $conn->prepare("INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, data_post) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssis", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_url);

    if ($stmt->execute()) {

        // --- CAPTURA O ID DO POST QUE ACABOU DE SER CRIADO ---
        $post_id_recem_criado = $conn->insert_id;

        // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO ANTIBUG E MINÚSCULAS) ---
        // A Regex agora pega apenas letras, números, underlines e pontos: [a-z0-9\._]
        if (preg_match_all('/@([a-zA-Z0-9\._]+)/', $mensagem, $matches)) {
            $mencoes = $matches[1]; // Pega só o texto capturado

            // Remove usernames duplicados no mesmo post (caso marque o mesmo user duas vezes)
            $mencoes = array_unique($mencoes);

            foreach ($mencoes as $nome_usuario) {
                // Força o username capturado para minúsculo para bater com o padrão do banco
                $nome_usuario_limpo = strtolower($nome_usuario);

                // Busca EXATAMENTE pelo username que está no banco
                $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = ?");
                $stmt_busca->bind_param("s", $nome_usuario_limpo);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();

                if ($alvo = $res->fetch_assoc()) {
                    $id_dest = $alvo['id'];

                    // Verifica se não é o próprio usuário se marcando
                    if ($id_dest != $_SESSION['usuario_id']) {
                        // Se não tiver username na sessão, define um fallback seguro
                        $quem_username = $_SESSION['usuario_username'] ?? "alguem";
                        $msg_n = "@" . $quem_username . " mencionou você em um post!";

                        $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida, data_criacao) VALUES (?, ?, ?, 0, NOW())");
                        $st_n->bind_param("iis", $id_dest, $post_id_recem_criado, $msg_n);
                        $st_n->execute();
                        $st_n->close();
                    }
                }
                $stmt_busca->close();
            }
        } // 🧠 FIM DO CÉREBRO DE MENÇÕES

        header("Location: feed.php");
        exit();
    } else {
        die("ERRO AO SALVAR: " . $stmt->error);
    }
}
?>