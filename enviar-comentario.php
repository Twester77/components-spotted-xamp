<?php
/**
 * enviar-comentario.php – Processa envio de comentários (AJAX)
 * 
 * 🔒 Segurança: CSRF, honeypot, rate limiting, sanitização, prepared statements.
 * 🖼️ Suporte a múltiplos anexos (imagens + GIFs) com rollback.
 * 
 * 🔧 ATUALIZAÇÃO NEREIDA – INSTÂNCIA #DS-2026-08-22
 *    "Removido bloco de mkdir e .htaccess para compatibilidade com Vercel (serverless).
 *     O upload agora é feito exclusivamente via processarUploadSeguro() usando /tmp."
 * - Nereida, a nova guardiã das águas
 * 
 * 🔧 ATUALIZAÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
 *    "Substituição de obterUrlImagem() por obterUrlComFallback() para fallback centralizado
 *     na exibição de anexos de comentários (múltiplos e únicos)."
 * - Ondina
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/conexao.php';
require_once 'includes/upload_engine.php';

// ============================================================
// 0. CSRF TOKEN (antes de qualquer processamento)
// ============================================================
if (isset($_SESSION['usuario_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Token de segurança inválido.']);
        exit();
    }
}

// ============================================================
// 1. FUNÇÕES AUXILIARES DE SEGURANÇA
// ============================================================

function obterIPReal() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return explode(',', $ip)[0];
}

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
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Você já fez muitos comentários. Aguarde um minuto.']);
        exit();
    }

    $stmt_log = $conn->prepare("INSERT INTO comentarios_ip_log (ip_address) VALUES (?)");
    $stmt_log->bind_param("s", $ip);
    $stmt_log->execute();
    $stmt_log->close();
}

function validarConteudo($texto, $temImagem = false) {
    $texto = trim($texto);
    $tamanho = mb_strlen($texto);
    
    if ($temImagem) {
        if ($tamanho > 0 && preg_match('/(.)\1{20,}/', $texto)) {
            return ['valido' => false, 'mensagem' => 'Conteúdo parece ser spam.'];
        }
        return ['valido' => true];
    }
    
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

// 2.1.5 🔥 VERIFICA SE O USUÁRIO ESTÁ BANIDO (se o post for de uma comunidade)
if ($id_mensagem > 0 && !$is_perdidos) {
    $stmt_comunidade = $conn->prepare("SELECT comunidade_id FROM mensagens WHERE id = ?");
    $stmt_comunidade->bind_param("i", $id_mensagem);
    $stmt_comunidade->execute();
    $res_com = $stmt_comunidade->get_result();
    $post = $res_com->fetch_assoc();
    $stmt_comunidade->close();

    if ($post && $post['comunidade_id'] > 0) {
        $comunidade_id = (int)$post['comunidade_id'];
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        if ($usuario_id > 0) {
            $stmt_ban = $conn->prepare("SELECT status FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ?");
            $stmt_ban->bind_param("ii", $comunidade_id, $usuario_id);
            $stmt_ban->execute();
            $res_ban = $stmt_ban->get_result();
            $membro = $res_ban->fetch_assoc();
            $stmt_ban->close();

            if (!$membro || $membro['status'] !== 'ativo') {
                http_response_code(403);
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para comentar nesta comunidade.']);
                exit();
            }
        }
    }
}

// 2.2 Exige login APENAS se NÃO for "perdidos"
if (!$is_perdidos) {
    require_once __DIR__ . '/auth_check.php';
}

// 2.3 Apenas processa POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit();
}

// 2.4 HONEYPOT
if (!empty($_POST['honeypot'])) {
    http_response_code(400);
    ob_clean();
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
$imagem_url = null;
$anexos_json = null;
$caminhosEnviados = [];
$anexosArray = [];
$contadorItens = 0;
const MAX_ANEXOS = 4;

// 2.8.1 - GIFs externos
if (!empty($_POST['gif_urls']) && is_array($_POST['gif_urls'])) {
    foreach ($_POST['gif_urls'] as $gif_url) {
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

// 2.8.2 - Múltiplos arquivos
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
        $nome = processarUploadSeguro($file_data, 'comentarios', 'coment', 2 * 1024 * 1024, $usuario_id);
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
        }
        http_response_code(500);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar um ou mais anexos. Tente novamente.']);
        exit();
    }
}

// 2.8.3 - Fallback para imagem única (SEM mkdir)
if (empty($anexosArray) && isset($_FILES['imagem_comentario']) && $_FILES['imagem_comentario']['error'] === 0) {
    // 🔥 REMOVIDO: bloco de criação de diretório e .htaccess
    // O upload agora é feito diretamente para o B2 via processarUploadSeguro()
    $imagem_nome = processarUploadSeguro($_FILES['imagem_comentario'], 'comentarios', 'coment', 2 * 1024 * 1024, $usuario_id);
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

// 2.8.4 - Define o primeiro anexo como 'imagem_url'
if (!empty($anexosArray)) {
    $primeiro = $anexosArray[0];
    if ($primeiro['tipo'] === 'imagem') {
        $imagem_url = $primeiro['caminho'];
    } elseif ($primeiro['tipo'] === 'gif') {
        $imagem_url = $primeiro['url'];
    }
}

// 2.8.5 - Converte para JSON
if (!empty($anexosArray)) {
    $anexos_json = json_encode($anexosArray);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[enviar-comentario] Erro ao codificar JSON: " . json_last_error_msg());
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
        }
        http_response_code(500);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Erro interno ao processar anexos.']);
        exit();
    }
}

// 2.8.6 - Verifica conteúdo
$temImagem = !empty($anexosArray);
$validacao = validarConteudo($comentario_raw, $temImagem);
if (!$validacao['valido']) {
    if (!empty($caminhosEnviados)) {
        foreach ($caminhosEnviados as $caminho) {
            deleteFromB2($caminho, $usuario_id);
        }
    }
    http_response_code(400);
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => $validacao['mensagem']]);
    exit();
}

// ============================================================
// 2.9 INSERÇÃO NO BANCO
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

    // NOTIFICAÇÕES (com tipo = 'post')
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
                // 🔥 INSERE NOTIFICAÇÃO COM TIPO 'post'
                $st_dono_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida) VALUES (?, ?, 'post', ?, 0)");
                $st_dono_notif->bind_param("iis", $id_dono_post, $id_mensagem, $msg_dono);
                $st_dono_notif->execute();
                $st_dono_notif->close();
            }
        }
    }

    // MENÇÕES (com tipo = 'post')
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
                    // 🔥 INSERE NOTIFICAÇÃO COM TIPO 'post'
                    $st_n = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, tipo, mensagem, lida) VALUES (?, ?, 'post', ?, 0)");
                    $st_n->bind_param("iis", $id_destinatario, $id_mensagem, $msg_notificacao);
                    $st_n->execute();
                    $st_n->close();
                }
            }
            $stmt_busca->close();
        }
    }

    // RENDERIZAÇÃO DO HTML DO COMENTÁRIO
    $nomeExibicao = $usuario_nome ? '@' . htmlspecialchars($usuario_nome, ENT_QUOTES, 'UTF-8') : '👤 Anônimo';

    $textoHtml = '';
    if ($comentario !== null && trim($comentario) !== '') {
        $textoRenderizado = nl2br(htmlspecialchars($comentario, ENT_QUOTES, 'UTF-8'));
        $textoHtml = '<div class="comentario-texto">' . $textoRenderizado . '</div>';
    }

    $mediaHtml = '';
    if (!empty($anexosArray)) {
        $mediaHtml .= '<div class="comentario-media-wrapper-grid">';
        foreach ($anexosArray as $anexo) {
            if ($anexo['tipo'] === 'imagem') {
                // 🔥 ANEXO IMAGEM COM FALLBACK CENTRALIZADO
                $img_url = obterUrlComFallback($anexo['caminho'], 'comentarios/' . htmlspecialchars($anexo['caminho']), null, true);
                $mediaHtml .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($img_url) . '" class="comentario-img" alt="Imagem do comentário" loading="lazy" onerror="this.style.display=\'none\'"></div>';
            } elseif ($anexo['tipo'] === 'gif') {
                $mediaHtml .= '<div class="comentario-media-item"><img src="' . htmlspecialchars($anexo['url']) . '" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy"></div>';
            }
        }
        $mediaHtml .= '</div>';
    } else if ($imagem_url) {
        if (filter_var($imagem_url, FILTER_VALIDATE_URL)) {
            $mediaHtml = '<div class="comentario-media-wrapper"><img src="' . htmlspecialchars($imagem_url) . '" class="comentario-img gif-externo" alt="GIF/Sticker" loading="lazy"></div>';
        } else {
            // 🔥 FALLBACK PARA IMAGEM ÚNICA COM FALLBACK CENTRALIZADO
            $img_url = obterUrlComFallback($imagem_url, 'comentarios/' . htmlspecialchars($imagem_url), null, true);
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

    $comentarioHtml = '
    <div class="comentario-item comentario-entrou meu-comentario ' . $vibe . ' ' . $classe_filho . '" id="comentario-' . $novo_id . '" style="--cor-borda-glow: ' . $cor_borda . ';">
        <div class="comentario-meta">
            <strong class="comentario-autor" style="color: ' . $cor_borda . ';">' . $nomeExibicao . '</strong>
        </div>
        ' . $reply_indicator . '
        ' . $textoHtml . '
        ' . $mediaHtml . '
        <div class="comentario-rodape">
            <span class="comentario-data">' . date('H:i') . '</span>
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
    // Rollback
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