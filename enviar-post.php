<?php
require_once __DIR__ . '/auth_check.php';
require_once 'includes/upload_engine.php';

// ============================================================
// 1. VERIFICAÇÕES INICIAIS
// ============================================================
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

// ============================================================
// 2. CAPTURA DOS DADOS DO FORMULÁRIO
// ============================================================
$mensagem    = mysqli_real_escape_string($conn, $_POST['mensagem']);
$categoria   = mysqli_real_escape_string($conn, $_POST['categoria']);
$subcategoria = isset($_POST['subcategoria']) ? mysqli_real_escape_string($conn, $_POST['subcategoria']) : "";
$usuario_id   = $_SESSION['usuario_id'];

// ============================================================
// 3. PROCESSAMENTO DE ANEXOS (MÚLTIPLOS + GIFs)
// ============================================================
$imagem_url = null;      // Mantido para compatibilidade (primeiro anexo)
$anexos_json = null;     // JSON com todos os anexos
$caminhosEnviados = [];  // Para rollback atômico
$anexosArray = [];       // Array que será convertido para JSON
$contadorItens = 0;
const MAX_ANEXOS = 3;

// 🔥 3.1 - Processa GIFs externos (GIPHY) – suporte a múltiplos
$gif_urls = isset($_POST['gif_urls']) && is_array($_POST['gif_urls']) ? $_POST['gif_urls'] : [];
if (!empty($gif_urls)) {
    foreach ($gif_urls as $gif_url) {
        $gif_url = trim($gif_url);
        if ($contadorItens >= MAX_ANEXOS) break;
        if (filter_var($gif_url, FILTER_VALIDATE_URL) &&
            (strpos($gif_url, 'giphy.com') !== false || strpos($gif_url, 'media.giphy.com') !== false)) {
            if (empty($imagem_url)) {
                $imagem_url = $gif_url;
            }
            $anexosArray[] = [
                'id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)),
                'tipo' => 'gif',
                'url' => $gif_url
            ];
            $contadorItens++;
        }
    }
}

// 🔥 3.2 - Processa múltiplos arquivos (campo 'anexos[]')
if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
    $erroUpload = false;
    foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp_name) {
        if ($contadorItens >= MAX_ANEXOS) break;
        if ($_FILES['anexos']['error'][$key] !== 0) {
            $erroUpload = true;
            break;
        }

        $file_data = [
            'name'     => $_FILES['anexos']['name'][$key],
            'type'     => $_FILES['anexos']['type'][$key],
            'tmp_name' => $tmp_name,
            'error'    => $_FILES['anexos']['error'][$key],
            'size'     => $_FILES['anexos']['size'][$key]
        ];

        $nome = processarUploadSeguro($file_data, 'postagens', 'post', 2 * 1024 * 1024, $usuario_id);
        if ($nome === false) {
            $erroUpload = true;
            break;
        }

        $caminhosEnviados[] = $nome;
        $anexosArray[] = [
            'id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)),
            'tipo' => 'imagem',
            'caminho' => $nome
        ];
        $contadorItens++;
    }

    if ($erroUpload) {
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
            error_log("[ENVIAR_POST] Rollback: arquivo deletado do B2: $caminho");
        }
        $_SESSION['erro_post'] = 'Erro ao enviar um ou mais anexos. Tente novamente.';
        header("Location: feed.php");
        exit();
    }
}

// 🔥 3.3 - Fallback para campo único (imagem) – compatibilidade
if (empty($anexosArray) && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
    $nome = processarUploadSeguro($_FILES['imagem'], 'postagens', 'post', 2 * 1024 * 1024, $usuario_id);
    if ($nome !== false) {
        $imagem_url = $nome;
        $anexosArray[] = [
            'id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)),
            'tipo' => 'imagem',
            'caminho' => $nome
        ];
        $caminhosEnviados[] = $nome;
        $contadorItens++;
    }
}

// 🔥 3.4 - Define o primeiro anexo como 'imagem_url' (compatibilidade)
if (!empty($anexosArray)) {
    $primeiro = $anexosArray[0];
    $imagem_url = ($primeiro['tipo'] === 'imagem') ? $primeiro['caminho'] : $primeiro['url'];
}

// 🔥 3.5 - Converte para JSON com validação defensiva
if (!empty($anexosArray)) {
    $anexos_json = json_encode($anexosArray);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[ENVIAR_POST] Erro JSON: " . json_last_error_msg());
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
        }
        $_SESSION['erro_post'] = 'Erro interno ao processar anexos.';
        header("Location: feed.php");
        exit();
    }
}

// ============================================================
// 4. INSERÇÃO NO BANCO (COM CAMPO 'anexos')
// ============================================================
$stmt = $conn->prepare("INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, anexos, data_post) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssiss", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_url, $anexos_json);

if ($stmt->execute()) {
    $post_id = $conn->insert_id;

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
                    $st_n->bind_param("iis", $id_dest, $post_id, $msg_n);
                    $st_n->execute();
                    $st_n->close();
                }
            }
            $stmt_busca->close();
        }
    }

    $_SESSION['sucesso_post'] = 'Sussurro enviado para a Fenda!';
    header("Location: feed.php");
    exit();
} else {
    // Rollback em caso de falha no banco
    if (!empty($caminhosEnviados)) {
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
            error_log("[ENVIAR_POST] Rollback por falha no banco: $caminho");
        }
    }
    $_SESSION['erro_post'] = 'Erro ao salvar o post: ' . $stmt->error;
    header("Location: feed.php");
    exit();
}
?>