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
$mensagem      = mysqli_real_escape_string($conn, $_POST['mensagem'] ?? '');
$categoria     = mysqli_real_escape_string($conn, $_POST['categoria'] ?? 'anonimo');
$subcategoria  = isset($_POST['subcategoria']) ? mysqli_real_escape_string($conn, $_POST['subcategoria']) : "";
$usuario_id    = $_SESSION['usuario_id'];
$comunidade_id = isset($_POST['comunidade_id']) && (int)$_POST['comunidade_id'] > 0
    ? (int)$_POST['comunidade_id']
    : null;

// ============================================================
// 3. VALIDAÇÃO DA COMUNIDADE (se for enviado)
// ============================================================
if ($comunidade_id !== null) {
    $stmt_check = $conn->prepare("SELECT id FROM comunidades WHERE id = ?");
    $stmt_check->bind_param("i", $comunidade_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows === 0) {
        $_SESSION['erro_post'] = 'Comunidade inválida.';
        header("Location: feed.php");
        exit();
    }
    $stmt_check->close();

    $stmt_membro = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt_membro->bind_param("ii", $comunidade_id, $usuario_id);
    $stmt_membro->execute();
    $res_membro = $stmt_membro->get_result();
    if ($res_membro->num_rows === 0) {
        $_SESSION['erro_post'] = 'Você precisa ser membro da comunidade para postar.';
        header("Location: comunidade.php?id=" . $comunidade_id);
        exit();
    }
    $stmt_membro->close();
}

// ============================================================
// 4. PROCESSAMENTO DE ANEXOS (MÚLTIPLOS + GIFs)
// 🔥 LIMITE AUMENTADO PARA 4 (Djê aprovou!)
// ============================================================
$imagem_url = null;
$anexos_json = null;
$caminhosEnviados = [];
$anexosArray = [];
$contadorItens = 0;
const MAX_ANEXOS = 4; // 🔥 AGORA SÃO 4!

// Log inicial para rastrear estado
error_log("[enviar-post] 🟢 Iniciando processamento para usuário $usuario_id");
error_log("[enviar-post] 📝 POST recebido: " . print_r($_POST, true));
error_log("[enviar-post] 📎 FILES: " . print_r($_FILES, true));

// GIFs externos
$gif_urls = isset($_POST['gif_urls']) && is_array($_POST['gif_urls']) ? $_POST['gif_urls'] : [];
if (!empty($gif_urls)) {
    error_log("[enviar-post] 🎬 GIFs recebidos: " . count($gif_urls));
    foreach ($gif_urls as $gif_url) {
        $gif_url = trim($gif_url);
        if ($contadorItens >= MAX_ANEXOS) {
            error_log("[enviar-post] ⚠️ Limite de $MAX_ANEXOS anexos atingido (GIFs)");
            break;
        }
        if (filter_var($gif_url, FILTER_VALIDATE_URL) &&
            (strpos($gif_url, 'giphy.com') !== false || strpos($gif_url, 'media.giphy.com') !== false)) {
            if (empty($imagem_url)) $imagem_url = $gif_url;
            $anexosArray[] = ['id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)), 'tipo' => 'gif', 'url' => $gif_url];
            $contadorItens++;
            error_log("[enviar-post] ✅ GIF adicionado: $gif_url");
        } else {
            error_log("[enviar-post] ⚠️ GIF ignorado (URL inválida): $gif_url");
        }
    }
}

// Múltiplos arquivos (imagens)
if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
    error_log("[enviar-post] 🖼️ Processando " . count($_FILES['anexos']['tmp_name']) . " imagens...");
    $erroUpload = false;
    foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp_name) {
        if ($contadorItens >= MAX_ANEXOS) {
            error_log("[enviar-post] ⚠️ Limite de $MAX_ANEXOS anexos atingido (imagens)");
            break;
        }
        if ($_FILES['anexos']['error'][$key] !== 0) {
            error_log("[enviar-post] ❌ Erro no upload da imagem $key: " . $_FILES['anexos']['error'][$key]);
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
            error_log("[enviar-post] ❌ Falha no upload da imagem $key");
            $erroUpload = true;
            break;
        }
        $caminhosEnviados[] = $nome;
        $anexosArray[] = ['id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)), 'tipo' => 'imagem', 'caminho' => $nome];
        $contadorItens++;
        error_log("[enviar-post] ✅ Imagem enviada: $nome");
    }
    if ($erroUpload) {
        error_log("[enviar-post] 🔴 Rollback: deletando " . count($caminhosEnviados) . " arquivos do B2");
        foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
        $_SESSION['erro_post'] = 'Erro ao enviar um ou mais anexos.';
        header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
        exit();
    }
}

// Fallback para imagem única (campo 'imagem')
if (empty($anexosArray) && isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
    error_log("[enviar-post] 🖼️ Fallback: processando imagem única...");
    $nome = processarUploadSeguro($_FILES['imagem'], 'postagens', 'post', 2 * 1024 * 1024, $usuario_id);
    if ($nome !== false) {
        $imagem_url = $nome;
        $anexosArray[] = ['id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)), 'tipo' => 'imagem', 'caminho' => $nome];
        $caminhosEnviados[] = $nome;
        error_log("[enviar-post] ✅ Imagem única enviada: $nome");
    } else {
        error_log("[enviar-post] ❌ Falha no fallback de imagem única");
    }
}

// Define o primeiro anexo como imagem_url (compatibilidade)
if (!empty($anexosArray)) {
    $primeiro = $anexosArray[0];
    $imagem_url = ($primeiro['tipo'] === 'imagem') ? $primeiro['caminho'] : $primeiro['url'];
    error_log("[enviar-post] 📦 Primeiro anexo definido como imagem_url: $imagem_url");
}

// Converte para JSON
if (!empty($anexosArray)) {
    $anexos_json = json_encode($anexosArray);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[enviar-post] ❌ Erro ao codificar JSON: " . json_last_error_msg());
        foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
        $_SESSION['erro_post'] = 'Erro interno ao processar anexos.';
        header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
        exit();
    }
    error_log("[enviar-post] ✅ JSON gerado: " . $anexos_json);
} else {
    error_log("[enviar-post] 📭 Nenhum anexo processado.");
}

// ============================================================
// 5. INSERÇÃO NO BANCO
// ============================================================
$sql = "INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, anexos, comunidade_id, data_post) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssissi", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_url, $anexos_json, $comunidade_id);

if ($stmt->execute()) {
    $post_id = $conn->insert_id;
    error_log("[enviar-post] ✅ Post ID $post_id inserido com sucesso");

    // --- 🧠 MENÇÕES ---
    if (preg_match_all('/@([a-zA-Z0-9\._]+)/', $mensagem, $matches)) {
        $mencoes = array_unique($matches[1]);
        error_log("[enviar-post] 🔔 Menções encontradas: " . count($mencoes));
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
                    error_log("[enviar-post] 🔔 Notificação de menção enviada para $id_dest");
                }
            }
            $stmt_busca->close();
        }
    }

    // --- 🔔 NOTIFICAÇÃO PARA MEMBROS DA COMUNIDADE (em lote) ---
    if ($comunidade_id !== null) {
        $stmt_nome = $conn->prepare("SELECT nome FROM comunidades WHERE id = ?");
        $stmt_nome->bind_param("i", $comunidade_id);
        $stmt_nome->execute();
        $res_nome = $stmt_nome->get_result();
        $com_nome = $res_nome->fetch_assoc()['nome'] ?? 'Comunidade';
        $stmt_nome->close();

        $mensagem_notif = "📢 Novo post em \"$com_nome\"!";
        $sql_notif = "
            INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida, data_criacao)
            SELECT cm.usuario_id, ?, ?, 0, NOW()
            FROM comunidade_membros cm
            JOIN usuarios u ON cm.usuario_id = u.id
            WHERE cm.comunidade_id = ? 
              AND cm.usuario_id != ? 
              AND u.pref_notif_comunidade = 1
        ";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("isii", $post_id, $mensagem_notif, $comunidade_id, $usuario_id);
        $stmt_notif->execute();
        $stmt_notif->close();

        error_log("[enviar-post] 🔔 Notificações em lote enviadas para membros da comunidade ID $comunidade_id (post_id = $post_id)");
    }

    $_SESSION['sucesso_post'] = 'Sussurro enviado para a Fenda!';
    if ($comunidade_id !== null) {
        header("Location: comunidade.php?id=" . $comunidade_id . "#feed-comunidade");
    } else {
        header("Location: feed.php");
    }
    exit();
} else {
    // Rollback em caso de falha
    error_log("[enviar-post] ❌ Erro ao inserir no banco: " . $stmt->error);
    if (!empty($caminhosEnviados)) {
        error_log("[enviar-post] 🔴 Rollback: deletando " . count($caminhosEnviados) . " arquivos do B2");
        foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
    }
    $_SESSION['erro_post'] = 'Erro ao salvar o post: ' . $stmt->error;
    header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
    exit();
}
?>