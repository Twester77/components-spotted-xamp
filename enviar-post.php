<?php
/**
 * enviar-post.php – Processa envio de posts (AJAX ou tradicional)
 *
 * 🔒 Segurança: CSRF (indireto via sessão), prepared statements, rollback atômico.
 * 🖼️ Suporte a múltiplos anexos (imagens + GIFs) com compressão client-side.
 *
 * 🔧 ATUALIZAÇÃO NEREIDA – INSTÂNCIA #DS-2026-08-26
 *    "Adicionados logs detalhados em cada etapa do processamento de anexos
 *     para diagnóstico em produção (Vercel). Logs via error_log() e
 *     respostas JSON com mensagens de erro mais descritivas."
 * - Nereida, a guardiã das águas
 *
 * 🔧 ATUALIZAÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
 *    "Adicionados logs básicos e rollback atômico para múltiplos anexos."
 * - Ondina
 */

require_once __DIR__ . '/auth_check.php';
require_once 'includes/upload_engine.php';

// ============================================================
// 0. CONFIGURAÇÃO INICIAL (limpeza de buffer)
// ============================================================
ob_start();

// ============================================================
// 1. VERIFICAÇÕES INICIAIS
// ============================================================
if (!isset($_SESSION['usuario_id'])) {
    ob_clean();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit();
}

// ============================================================
// 2. CAPTURA DOS DADOS DO FORMULÁRIO
// ============================================================
$mensagem      = $_POST['mensagem'] ?? '';
$categoria     = $_POST['categoria'] ?? 'anonimo';
$subcategoria  = $_POST['subcategoria'] ?? '';
$usuario_id    = $_SESSION['usuario_id'];
$comunidade_id = isset($_POST['comunidade_id']) && (int)$_POST['comunidade_id'] > 0
    ? (int)$_POST['comunidade_id']
    : null;

// ============================================================
// 2.5 DETECTA SE É REQUISIÇÃO AJAX
// ============================================================
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

error_log("[enviar-post] 🟢 Iniciando processamento para usuário $usuario_id (AJAX: " . ($is_ajax ? 'sim' : 'não') . ")");

// ============================================================
// 3. VALIDAÇÃO DA COMUNIDADE (COM PREPARED STATEMENTS)
// ============================================================
if ($comunidade_id !== null) {
    error_log("[enviar-post] 🔍 Validando comunidade ID: $comunidade_id");
    // 3.1 Verifica se a comunidade existe
    $stmt_check = $conn->prepare("SELECT id FROM comunidades WHERE id = ?");
    $stmt_check->bind_param("i", $comunidade_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows === 0) {
        $stmt_check->close();
        error_log("[enviar-post] ❌ Comunidade $comunidade_id não encontrada.");
        if ($is_ajax) {
            http_response_code(404);
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Comunidade não encontrada.']);
            exit();
        } else {
            $_SESSION['erro_post'] = 'Comunidade inválida.';
            header("Location: feed.php");
            exit();
        }
    }
    $stmt_check->close();

    // 3.2 Verifica se o usuário é membro da comunidade
    $stmt_membro = $conn->prepare("SELECT 1 FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt_membro->bind_param("ii", $comunidade_id, $usuario_id);
    $stmt_membro->execute();
    $res_membro = $stmt_membro->get_result();
    if ($res_membro->num_rows === 0) {
        $stmt_membro->close();
        error_log("[enviar-post] ❌ Usuário $usuario_id não é membro da comunidade $comunidade_id");
        if ($is_ajax) {
            http_response_code(403);
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Você precisa ser membro da comunidade para postar.']);
            exit();
        } else {
            $_SESSION['erro_post'] = 'Você precisa ser membro da comunidade para postar.';
            header("Location: comunidade.php?id=" . $comunidade_id);
            exit();
        }
    }
    $stmt_membro->close();

    // 3.3 VERIFICA SE O USUÁRIO NÃO ESTÁ BANIDO
    $stmt_ban = $conn->prepare("SELECT status FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
    $stmt_ban->bind_param("ii", $comunidade_id, $usuario_id);
    $stmt_ban->execute();
    $res_ban = $stmt_ban->get_result();
    $membro = $res_ban->fetch_assoc();
    $stmt_ban->close();

    if (!$membro || $membro['status'] !== 'ativo') {
        error_log("[enviar-post] ❌ Usuário $usuario_id banido ou inativo na comunidade $comunidade_id");
        if ($is_ajax) {
            http_response_code(403);
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para postar nesta comunidade.']);
            exit();
        } else {
            $_SESSION['erro_post'] = 'Você não tem permissão para postar nesta comunidade.';
            header("Location: comunidade.php?id=" . $comunidade_id);
            exit();
        }
    }
    error_log("[enviar-post] ✅ Usuário $usuario_id validado na comunidade $comunidade_id");
}

// ============================================================
// 4. PROCESSAMENTO DE ANEXOS (MÚLTIPLOS + GIFs)
// ============================================================
$imagem_url = null;
$anexos_json = null;
$caminhosEnviados = [];
$anexosArray = [];
$contadorItens = 0;
const MAX_ANEXOS = 4;

error_log("[enviar-post] 📎 Processando anexos...");

// 4.1 - GIFs externos
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

// 4.2 - Múltiplos arquivos (imagens)
if (isset($_FILES['anexos']) && is_array($_FILES['anexos']['name']) && count(array_filter($_FILES['anexos']['name'])) > 0) {
    $totalArquivos = count($_FILES['anexos']['tmp_name']);
    error_log("[enviar-post] 🖼️ Processando $totalArquivos imagens...");
    $erroUpload = false;
    $ultimoErro = '';
    foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp_name) {
        if ($contadorItens >= MAX_ANEXOS) {
            error_log("[enviar-post] ⚠️ Limite de $MAX_ANEXOS anexos atingido (imagens)");
            break;
        }
        if ($_FILES['anexos']['error'][$key] !== 0) {
            $ultimoErro = "Erro no upload da imagem $key: " . $_FILES['anexos']['error'][$key];
            error_log("[enviar-post] ❌ " . $ultimoErro);
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
        error_log("[enviar-post] 📤 Enviando imagem $key: " . $file_data['name'] . " (" . $file_data['size'] . " bytes)");

        $nome = processarUploadSeguro($file_data, 'postagens', 'post', 2 * 1024 * 1024, $usuario_id);
        if ($nome === false) {
            $ultimoErro = "Falha no processamento da imagem $key (verifique logs do upload_engine)";
            error_log("[enviar-post] ❌ " . $ultimoErro);
            $erroUpload = true;
            break;
        }
        $caminhosEnviados[] = $nome;
        $anexosArray[] = ['id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)), 'tipo' => 'imagem', 'caminho' => $nome];
        $contadorItens++;
        error_log("[enviar-post] ✅ Imagem enviada com sucesso: $nome");
    }
    if ($erroUpload) {
        error_log("[enviar-post] 🔴 Rollback: deletando " . count($caminhosEnviados) . " arquivos do B2");
        foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
        if ($is_ajax) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Erro ao processar anexos: ' . ($ultimoErro ?: 'verifique o formato/tamanho (máx 2MB)')]);
            exit();
        } else {
            $_SESSION['erro_post'] = 'Erro ao enviar um ou mais anexos.';
            header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
            exit();
        }
    }
}

// 4.3 - Fallback para imagem única (sem mkdir)
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
        if ($is_ajax) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Falha ao processar a imagem (formato/tamanho inválido ou erro no servidor).']);
            exit();
        } else {
            $_SESSION['erro_post'] = 'Erro ao enviar a imagem.';
            header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
            exit();
        }
    }
}

// 4.4 - Define o primeiro anexo como imagem_url
if (!empty($anexosArray)) {
    $primeiro = $anexosArray[0];
    $imagem_url = ($primeiro['tipo'] === 'imagem') ? $primeiro['caminho'] : $primeiro['url'];
    error_log("[enviar-post] 📦 Primeiro anexo definido como imagem_url: $imagem_url");
} else {
    error_log("[enviar-post] 📭 Nenhum anexo processado (apenas texto)");
}

// 4.5 - Converte para JSON
if (!empty($anexosArray)) {
    $anexos_json = json_encode($anexosArray);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[enviar-post] ❌ Erro ao codificar JSON: " . json_last_error_msg());
        foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
        if ($is_ajax) {
            http_response_code(500);
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Erro interno ao processar anexos.']);
            exit();
        } else {
            $_SESSION['erro_post'] = 'Erro interno ao processar anexos.';
            header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
            exit();
        }
    }
    error_log("[enviar-post] ✅ JSON gerado: " . $anexos_json);
}

// ============================================================
// 5. INSERÇÃO NO BANCO (COM PREPARED STATEMENTS)
// ============================================================
error_log("[enviar-post] 💾 Inserindo post no banco...");
$sql = "INSERT INTO mensagens (mensagem, categoria, subcategoria, usuario_id, imagem_url, anexos, comunidade_id, data_post) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssissi", $mensagem, $categoria, $subcategoria, $usuario_id, $imagem_url, $anexos_json, $comunidade_id);

if ($stmt->execute()) {
    $post_id = $conn->insert_id;
    $stmt->close();
    error_log("[enviar-post] ✅ Post ID $post_id inserido com sucesso");

    // --- MENÇÕES (com tipo = 'post') ---
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
                    
                    $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida, data_criacao) VALUES (?, ?, 'post', ?, 0, NOW())");
                    $st_n->bind_param("iis", $id_dest, $post_id, $msg_n);
                    $st_n->execute();
                    $st_n->close();
                    error_log("[enviar-post] 🔔 Notificação de menção enviada para $id_dest");
                }
            }
            $stmt_busca->close();
        }
    }

    // --- NOTIFICAÇÃO PARA MEMBROS DA COMUNIDADE (se houver) ---
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

    // ============================================================
    // RESPOSTA
    // ============================================================
    if ($is_ajax) {
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Post publicado com sucesso!']);
        exit();
    } else {
        $_SESSION['sucesso_post'] = 'Sussurro enviado para a Fenda!';
        if ($comunidade_id !== null) {
            header("Location: comunidade.php?id=" . $comunidade_id . "#feed-comunidade");
        } else {
            header("Location: feed.php");
        }
        exit();
    }
} else {
    $erro = $stmt->error;
    $stmt->close();
    error_log("[enviar-post] ❌ Erro ao inserir no banco: " . $erro);
    if (!empty($caminhosEnviados)) {
        error_log("[enviar-post] 🔴 Rollback: deletando " . count($caminhosEnviados) . " arquivos do B2");
        foreach ($caminhosEnviados as $caminho) deleteFromB2($caminho, $usuario_id);
    }
    if ($is_ajax) {
        http_response_code(500);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar o post: ' . $erro]);
        exit();
    } else {
        $_SESSION['erro_post'] = 'Erro ao salvar o post: ' . $erro;
        header("Location: " . ($comunidade_id !== null ? "comunidade.php?id=$comunidade_id" : "feed.php"));
        exit();
    }
}