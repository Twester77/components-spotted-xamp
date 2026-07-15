<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/conexao.php';
require_once 'includes/upload_engine.php';

// ============================================================
// 1. FUNÇÕES AUXILIARES DE SEGURANÇA
// ============================================================

// 1.1 Obtém o IP real do usuário (considerando proxies)
function obterIPReal() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return explode(',', $ip)[0];
}

// 1.2 Rate Limiting (máximo 5 comentários por minuto por IP)
function verificarRateLimiting($conn, $ip) {
    $sql = "SELECT COUNT(*) as total FROM comentarios_ip_log WHERE ip_address = ? AND tentativa > NOW() - INTERVAL 1 MINUTE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row['total'] >= 5) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Você já fez muitos comentários. Aguarde um minuto.']);
        exit();
    }

    $stmt_log = $conn->prepare("INSERT INTO comentarios_ip_log (ip_address) VALUES (?)");
    $stmt_log->bind_param("s", $ip);
    $stmt_log->execute();
    $stmt_log->close();
}

// 1.3 Validação de conteúdo (mínimo 1 caractere, sem imagem pode ser vazio)
function validarConteudo($texto, $temImagem = false) {
    $texto = trim($texto);
    $tamanho = mb_strlen($texto);
    
    // Se tiver imagem/GIF, o texto pode ser vazio
    if ($temImagem) {
        if ($tamanho > 0 && preg_match('/(.)\1{20,}/', $texto)) {
            return ['valido' => false, 'mensagem' => 'Conteúdo parece ser spam.'];
        }
        return ['valido' => true];
    }
    
    // Sem imagem: exige pelo menos 1 caractere
    if ($tamanho < 1) {
        return ['valido' => false, 'mensagem' => 'Digite algo ou adicione uma imagem/GIF.'];
    }
    if ($tamanho > 500) {
        return ['valido' => false, 'mensagem' => 'O comentário excede o limite de 500 caracteres.'];
    }
    if (preg_match('/(.)\1{20,}/', $texto)) {
        return ['valido' => false, 'mensagem' => 'Conteúdo parece ser spam.'];
    }
    return ['valido' => true];
}

// ============================================================
// 2. EXECUÇÃO DO FLUXO PRINCIPAL
// ============================================================

$ip_origem = obterIPReal();

// 2.1 Verifica se o post é da categoria "perdidos" (público)
$id_mensagem = isset($_POST['id_mensagem']) ? intval($_POST['id_mensagem']) : 0;
$is_perdidos = false;
if ($id_mensagem > 0) {
    $stmt_check = $conn->prepare("SELECT categoria FROM mensagens WHERE id = ?");
    $stmt_check->bind_param("i", $id_mensagem);
    $stmt_check->execute();
    $check_post = $stmt_check->get_result()->fetch_assoc();
    if ($check_post && $check_post['categoria'] === 'perdidos') {
        $is_perdidos = true;
    }
}

// 2.2 Exige login APENAS se NÃO for "perdidos"
if (!$is_perdidos) {
    require_once __DIR__ . '/auth_check.php';
}

// 2.3 Apenas processa POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

// 2.4 HONEYPOT: campo oculto não deve ser preenchido
if (!empty($_POST['honeypot'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar comentário.']);
    exit();
}

// 2.5 Rate Limiting (apenas para visitantes anônimos)
$usuario_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : null;
if (!$usuario_id) {
    verificarRateLimiting($conn, $ip_origem);
}

// 2.6 Captura dados do usuário
$usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;
if ($is_perdidos && !$usuario_nome) {
    $usuario_nome = 'Visitante Anônimo';
}

$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$comentario_raw = $_POST['comentario'] ?? '';
$comentario = trim($comentario_raw) === '' ? null : $comentario_raw;

// 2.7 Preferências visuais
$vibe = $_POST['pref_vibe_comentario'] ?? 'vibe-glass';
$cor_borda = $_POST['pref_cor_borda'] ?? '#70cde4';

// ============================================================
// 🔥 2.8 PROCESSAMENTO DE ANEXOS (MÚLTIPLOS + GIFs)
// ============================================================
$imagem_url = null;      // Mantido para compatibilidade (primeiro anexo)
$anexos_json = null;     // JSON com todos os anexos (novo formato)
$caminhosEnviados = [];  // Para rollback atômico
$anexosArray = [];       // Array que será convertido para JSON
$contadorItens = 0;      // Contador para limitar a 3 itens no total
const MAX_ANEXOS = 3;    // Limite máximo de anexos por comentário

// ============================================================
// 🔥 2.8.1 - Processa GIFs externos (GIPHY) – AGORA ACEITA MÚLTIPLOS
// ============================================================
if (!empty($_POST['gif_urls']) && is_array($_POST['gif_urls'])) {
    foreach ($_POST['gif_urls'] as $gif_url) {
        $gif_url = trim($gif_url);
        
        // 🔥 LIMITE DE 3 ITENS (MESMO QUE O FRONT)
        if ($contadorItens >= MAX_ANEXOS) {
            break;
        }
        
        if (filter_var($gif_url, FILTER_VALIDATE_URL)) {
            if (strpos($gif_url, 'giphy.com') !== false || strpos($gif_url, 'media.giphy.com') !== false) {
                // Se for o primeiro, define como imagem_url (compatibilidade)
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
}

// ============================================================
// 🔥 2.8.2 - Processa múltiplos arquivos (campo 'anexos[]')
// ============================================================
if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
    $erroUpload = false;
    
    foreach ($_FILES['anexos']['tmp_name'] as $key => $tmp_name) {
        // 🔥 LIMITE DE 3 ITENS (MESMO QUE O FRONT)
        if ($contadorItens >= MAX_ANEXOS) {
            break;
        }
        
        // Verifica se o arquivo foi enviado sem erros
        if ($_FILES['anexos']['error'][$key] !== 0) {
            $erroUpload = true;
            break;
        }

        // Monta o array compatível com processarUploadSeguro()
        $file_data = [
            'name'     => $_FILES['anexos']['name'][$key],
            'type'     => $_FILES['anexos']['type'][$key],
            'tmp_name' => $tmp_name,
            'error'    => $_FILES['anexos']['error'][$key],
            'size'     => $_FILES['anexos']['size'][$key]
        ];

        // Upload para B2 com validação (tamanho, tipo, polyglot)
        $nome = processarUploadSeguro($file_data, 'comentarios', 'coment', 2 * 1024 * 1024, $usuario_id);
        
        if ($nome === false) {
            $erroUpload = true;
            break;
        }

        // Guarda o caminho para rollback
        $caminhosEnviados[] = $nome;
        
        // Adiciona ao array de anexos
        $anexosArray[] = [
            'id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)),
            'tipo' => 'imagem',
            'caminho' => $nome
        ];
        $contadorItens++;
    }

    // Se houve erro em algum upload, faz rollback total
    if ($erroUpload) {
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
            error_log("[enviar-comentario] Rollback: arquivo deletado do B2: $caminho");
        }
        
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar um ou mais anexos. Tente novamente.']);
        exit();
    }
}

// ============================================================
// 🔥 2.8.3 - Fallback para campo único (imagem_comentario) – compatibilidade
// ============================================================
if (empty($anexosArray) && isset($_FILES['imagem_comentario']) && $_FILES['imagem_comentario']['error'] === 0) {
    $pasta = 'comentarios';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
        file_put_contents($pasta . '/.htaccess', "Options -Indexes\n<FilesMatch \"\.(php|phtml|php3|php4|php5|phar|shtml|cgi|pl|py|jsp|asp|htm|html|js|css)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>");
    }
    $imagem_nome = processarUploadSeguro($_FILES['imagem_comentario'], $pasta, 'coment', 2 * 1024 * 1024, $usuario_id);
    if ($imagem_nome !== false) {
        $imagem_url = $imagem_nome;
        $anexosArray[] = [
            'id' => 'anexo-' . uniqid() . '-' . bin2hex(random_bytes(4)),
            'tipo' => 'imagem',
            'caminho' => $imagem_nome
        ];
        $caminhosEnviados[] = $imagem_nome;
        $contadorItens++;
    }
}

// ============================================================
// 🔥 2.8.4 - Define o primeiro anexo como 'imagem_url' (compatibilidade)
// ============================================================
if (!empty($anexosArray)) {
    $primeiro = $anexosArray[0];
    if ($primeiro['tipo'] === 'imagem') {
        $imagem_url = $primeiro['caminho'];
    } elseif ($primeiro['tipo'] === 'gif') {
        $imagem_url = $primeiro['url'];
    }
}

// ============================================================
// 🔥 2.8.5 - Converte para JSON com validação defensiva
// ============================================================
if (!empty($anexosArray)) {
    $anexos_json = json_encode($anexosArray);
    
    // Valida se o JSON foi gerado corretamente
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Erro na codificação JSON: faz rollback
        error_log("[enviar-comentario] Erro ao codificar JSON: " . json_last_error_msg());
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
        }
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro interno ao processar anexos.']);
        exit();
    }
}

// ============================================================
// 🔥 2.8.6 - Verifica se há conteúdo (texto ou anexos)
// ============================================================
$temImagem = !empty($anexosArray);
$validacao = validarConteudo($comentario_raw, $temImagem);
if (!$validacao['valido']) {
    // Se a validação falhou e houve uploads, faz rollback
    if (!empty($caminhosEnviados)) {
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
        }
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $validacao['mensagem']]);
    exit();
}

// ============================================================
// 2.9 INSERÇÃO NO BANCO (COM CAMPO 'anexos')
// ============================================================
$sql = "INSERT INTO comentarios (
            id_mensagem, 
            comentario, 
            usuario_nome, 
            usuario_id, 
            parent_id, 
            pref_vibe_comentario, 
            pref_cor_borda, 
            imagem_url,
            anexos,
            ip_origem
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "issiisssss",
    $id_mensagem,
    $comentario,
    $usuario_nome,
    $usuario_id,
    $parent_id,
    $vibe,
    $cor_borda,
    $imagem_url,
    $anexos_json,
    $ip_origem
);

if ($stmt->execute()) {
    $novo_id = $stmt->insert_id;
    error_log("[enviar-comentario] ✅ Comentário ID $novo_id criado com " . count($anexosArray) . " anexos.");

    // ============================================================
    // 🔔 NOTIFICAÇÕES (mantidas)
    // ============================================================
    if ($usuario_id) {
        $meu_id = $usuario_id;
        $quem_comentou = $usuario_nome ?? "Visitante";
        $stmt_dono = $conn->prepare("SELECT usuario_id FROM mensagens WHERE id = ?");
        $stmt_dono->bind_param("i", $id_mensagem);
        $stmt_dono->execute();
        $res_dono = $stmt_dono->get_result()->fetch_assoc();
        if ($res_dono) {
            $id_dono_post = $res_dono['usuario_id'];
            if ($id_dono_post != $meu_id) {
                $msg_dono = "@$quem_comentou comentou no seu post!";
                $st_dono_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                $st_dono_notif->bind_param("iis", $id_dono_post, $id_mensagem, $msg_dono);
                $st_dono_notif->execute();
                $st_dono_notif->close();
            }
        }
    }

    // ============================================================
    // 🧠 MENÇÕES (mantidas)
    // ============================================================
    if ($comentario !== null && preg_match_all('/@([a-zA-Z0-9\._]+)/', $comentario, $matches)) {
        $mencoes = array_unique($matches[1]);
        foreach ($mencoes as $nome_usuario) {
            $nome_usuario_limpo = strtolower($nome_usuario);
            $stmt_busca = $conn->prepare("SELECT id FROM usuarios WHERE LOWER(username) = ?");
            $stmt_busca->bind_param("s", $nome_usuario_limpo);
            $stmt_busca->execute();
            $res = $stmt_busca->get_result();
            if ($alvo = $res->fetch_assoc()) {
                $id_destinatario = $alvo['id'];
                if ($id_destinatario != $meu_id) {
                    $msg_notificacao = "@$quem_comentou mencionou você em um comentário!";
                    $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
                    $st_n->bind_param("iis", $id_destinatario, $id_mensagem, $msg_notificacao);
                    $st_n->execute();
                    $st_n->close();
                }
            }
            $stmt_busca->close();
        }
    }

    // ============================================================
    // 🚀 RENDERIZAÇÃO DO HTML DO COMENTÁRIO
    // ============================================================
    $nomeExibicao = $usuario_nome ? '@' . htmlspecialchars($usuario_nome, ENT_QUOTES, 'UTF-8') : '👤 Anônimo';

    $textoHtml = '';
    if ($comentario !== null && trim($comentario) !== '') {
        $textoRenderizado = nl2br(htmlspecialchars($comentario, ENT_QUOTES, 'UTF-8'));
        $textoHtml = '<div class="comentario-texto">' . $textoRenderizado . '</div>';
    }

    // ============================================================
    // 🔥 RENDERIZAÇÃO DOS ANEXOS (USANDO JSON)
    // ============================================================
    $mediaHtml = '';
    if (!empty($anexosArray)) {
        // Se houver múltiplos anexos, renderiza em grid (usando o JSON)
        $mediaHtml .= '<div class="comentario-media-wrapper-grid">';
        foreach ($anexosArray as $anexo) {
            if ($anexo['tipo'] === 'imagem') {
                try {
                    $b2 = B2Client::getInstance();
                    $img_url = obterUrlImagem($anexo['caminho'], $b2, true) ?? 'comentarios/' . htmlspecialchars($anexo['caminho']);
                } catch (Exception $e) {
                    $img_url = 'comentarios/' . htmlspecialchars($anexo['caminho']);
                }
                $mediaHtml .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($img_url) . '" class="comentario-img" alt="Imagem do comentário" loading="lazy" onerror="this.style.display=\'none\'"></div>';
            } elseif ($anexo['tipo'] === 'gif') {
                $mediaHtml .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($anexo['url']) . '" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy"></div>';
            }
        }
        $mediaHtml .= '</div>';
    } else if ($imagem_url) {
        // Fallback: se não tiver JSON mas tiver imagem_url (compatibilidade)
        if (filter_var($imagem_url, FILTER_VALIDATE_URL)) {
            $mediaHtml = '<div class="comentario-media-wrapper"><img src="' . htmlspecialchars($imagem_url) . '" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy"></div>';
        } else {
            try {
                $b2 = B2Client::getInstance();
                $img_url = obterUrlImagem($imagem_url, $b2, true) ?? 'comentarios/' . htmlspecialchars($imagem_url);
            } catch (Exception $e) {
                $img_url = 'comentarios/' . htmlspecialchars($imagem_url);
            }
            $mediaHtml = '<div class="comentario-media-wrapper"><img src="' . htmlspecialchars($img_url) . '" class="comentario-img" alt="Imagem do comentário" loading="lazy" onerror="this.style.display=\'none\'"></div>';
        }
    }

    $classe_filho = ($parent_id > 0) ? 'comentario-filho' : '';

    $reply_indicator = '';
    if ($parent_id > 0) {
        $stmt_parent = $conn->prepare("SELECT comentario, usuario_nome FROM comentarios WHERE id = ?");
        $stmt_parent->bind_param("i", $parent_id);
        $stmt_parent->execute();
        $parent_data = $stmt_parent->get_result()->fetch_assoc();
        $trecho = '';
        if ($parent_data) {
            $texto_puro = strip_tags($parent_data['comentario']);
            $texto_cortado = mb_substr($texto_puro, 0, 50);
            $trecho = mb_strlen($texto_puro) > 50 ? $texto_cortado . '...' : $texto_cortado;
        }
        $reply_indicator = '<div class="indicador-resposta" onclick="irParaMensagem(' . $parent_id . ')">
                                <i class="fas fa-reply"></i> <small>' . htmlspecialchars($trecho) . '</small>
                            </div>';
    }

    $ellipsisHtml = '';
    if ($usuario_id) {
        $ellipsisHtml = '<button class="btn-excluir-comentario" data-id="' . $novo_id . '" title="Excluir comentário">
                            <i class="fas fa-ellipsis-v"></i>
                         </button>';
    }

    $comentarioHtml = '
    <div class="comentario-item comentario-entrou meu-comentario ' . $vibe . ' ' . $classe_filho . '" id="comentario-' . $novo_id . '" style="--cor-borda-glow: ' . $cor_borda . ';">
        ' . $ellipsisHtml . '
        <div class="comentario-meta">
            <strong class="comentario-autor" style="color: ' . $cor_borda . ';">' . $nomeExibicao . '</strong>
        </div>
        ' . $reply_indicator . '
        ' . $textoHtml . '
        ' . $mediaHtml . '
        <div class="comentario-rodape">
            <span class="comentario-data">' . date('H:i') . '</span>
        </div>
        <div class="acoes-bolha">
            <button onclick="prepararResposta(' . $novo_id . ', \'' . addslashes($usuario_nome) . '\')" class="btn-responder-bolha">RESPONDER</button>
        </div>
    </div>
';

    $response = [
        'status' => 'success',
        'message' => 'Comentário enviado!',
        'html' => $comentarioHtml,
        'imagem_url' => $imagem_url,
        'anexos' => $anexosArray
    ];
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    // Se houve erro na inserção, faz rollback dos uploads já feitos
    if (!empty($caminhosEnviados)) {
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
            error_log("[enviar-comentario] Rollback por falha no banco: $caminho");
        }
    }
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar comentário: ' . $conn->error]);
    exit();
}
?>