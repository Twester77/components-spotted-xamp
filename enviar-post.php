<?php
include 'conexao.php';
require_once 'includes/upload_engine.php'; // 🚀 Motor oficial de upload

// 1. Verificação de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mensagem    = mysqli_real_escape_string($conn, $_POST['mensagem']);
    $categoria   = mysqli_real_escape_string($conn, $_POST['categoria']);
    $subcategoria = isset($_POST['subcategoria']) ? mysqli_real_escape_string($conn, $_POST['subcategoria']) : "";
    $usuario_id   = $_SESSION['usuario_id'];
    $imagem_nome  = null;

    // ============================================================
    // 🖼️ PROCESSAMENTO DA IMAGEM (prioridade: gif_url externa)
    // ============================================================
    $gif_url = isset($_POST['gif_url']) ? trim($_POST['gif_url']) : '';

    // 🔥 Se veio uma URL externa (GIPHY), valida e salva diretamente
    if (!empty($gif_url) && filter_var($gif_url, FILTER_VALIDATE_URL)) {
        if (strpos($gif_url, 'giphy.com') !== false || strpos($gif_url, 'media.giphy.com') !== false) {
            $imagem_nome = $gif_url;
        }
    } 
    // Se não veio URL externa, tenta upload de arquivo local
    else if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $imagem_nome = processarUploadSeguro(
            $_FILES['imagem'],
            'postagens',
            'post',
            2 * 1024 * 1024
        );
        if ($imagem_nome === false) $imagem_nome = null;
    }

    // ============================================================
    // 📝 INSERÇÃO NO BANCO
    // ============================================================
    $stmt = $conn->prepare("INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, data_post) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssis", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_nome);

    if ($stmt->execute()) {
        $post_id_recem_criado = $conn->insert_id;

        // --- 🧠 MENÇÕES ---
        if (preg_match_all('/@([a-zA-Z0-9\._]+)/', $mensagem, $matches)) {
            $mencoes = array_unique($matches[1]);
            foreach ($mencoes as $nome_usuario) {
                $nome_usuario_limpo = strtolower($nome_usuario);
                $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = ?");
                $stmt_busca->bind_param("s", $nome_usuario_limpo);
                $stmt_busca->execute();
                $res = $stmt_busca->get_result();
                if ($alvo = $res->fetch_assoc()) {
                    $id_dest = $alvo['id'];
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
        }

        header("Location: feed.php");
        exit();
    } else {
        die("ERRO AO SALVAR: " . $stmt->error);
    }
}
?>