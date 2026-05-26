<?php
include 'conexao.php';

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Higienização básica e processamento do formulário
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
        
        // Mapeamento seguro para validação prévia do formato
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

        // 3. NOVO MOTOR: Conversão e Compactação para WebP Nativo
        $novo_nome = bin2hex(random_bytes(16)) . ".webp"; // Extensão agora é sempre .webp
        $destino = "postagens/" . $novo_nome;

        // Cria a imagem na memória do PHP dependendo do formato original enviado
        switch ($mime_type) {
            case 'image/jpeg':
                $imagem_original = imagecreatefromjpeg($arquivo_tmp);
                break;
            case 'image/png':
                $imagem_original = imagecreatefrompng($arquivo_tmp);
                // Preserva a transparência do PNG caso usem fundos alphas
                imagealphablending($imagem_original, false);
                imagesavealpha($imagem_original, true);
                break;
            case 'image/webp':
                $imagem_original = imagecreatefromwebp($arquivo_tmp);
                break;
            case 'image/gif':
                $imagem_original = imagecreatefromgif($arquivo_tmp);
                break;
            default:
                die("ERRO: Formato não suportado pelo motor GD.");
        }

        if ($imagem_original) {
            // Salva como WebP na pasta postagens com qualidade 75 (Lighthouse vai amar!)
            if (imagewebp($imagem_original, $destino, 75)) {
                $imagem_url = $novo_nome;
            } else {
                die("ERRO: Falha ao processar e compactar a imagem.");
            }

            // Faxina obrigatória: Deleta a imagem da memória RAM do servidor
            imagedestroy($imagem_original);
        } else {
            die("ERRO: Arquivo inválido ou corrompido.");
        }
    } // FIM DO UPLOAD DE IMAGEM

    // 3. Preparação do SQL do Post
    $stmt = $conn->prepare("INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, data_post) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssis", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_url);

    if ($stmt->execute()) {

        // --- CAPTURA O ID DO POST QUE ACABOU DE SER CRIADO ---
        $post_id_recem_criado = $conn->insert_id;

        // --- 🧠 CÉREBRO DE MENÇÕES (VERSÃO ANTIBUG E MINÚSCULAS) ---
        if (preg_match_all('/@([a-zA-Z0-9\._]+)/', $mensagem, $matches)) {
            $mencoes = $matches[1]; 

            // Remove usernames duplicados no mesmo post
            $mencoes = array_unique($mencoes);

            foreach ($mencoes as $nome_usuario) {
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
} // FIM DO IF METHOD == POST
?>