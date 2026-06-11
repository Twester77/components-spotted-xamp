<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

include 'conexao.php';
require_once 'includes/upload_engine.php';

// Apenas processa POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

// 👤 Captura dados do usuário
$usuario_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : null;
$usuario_nome = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : null;

$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$id_mensagem = intval($_POST['id_mensagem']);
$comentario_raw = $_POST['comentario'] ?? '';

// Trata o texto: se vazio após trim, vira NULL (para o banco)
$comentario = trim($comentario_raw) === '' ? null : $comentario_raw;

// 🎨 Preferências visuais
$vibe = $_POST['pref_vibe_comentario'] ?? 'vibe-glass';
$cor_borda = $_POST['pref_cor_borda'] ?? '#70cde4';

// 🖼️ PROCESSAMENTO DA IMAGEM (se enviada)
$imagem_url = null;

if ($usuario_id && isset($_FILES['imagem_comentario']) && $_FILES['imagem_comentario']['error'] === 0) {
    $pasta = 'comentarios';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
        file_put_contents($pasta . '/.htaccess', "Options -Indexes\n<FilesMatch \"\.(php|phtml|php3|php4|php5|phar|shtml|cgi|pl|py|jsp|asp|htm|html|js|css)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>");
    }
    
    $imagem_nome = processarUploadSeguro(
        $_FILES['imagem_comentario'],
        $pasta,
        'coment',
        2 * 1024 * 1024
    );
    
    if ($imagem_nome !== false) {
        $imagem_url = $imagem_nome;
    }
}

// 📝 INSERÇÃO NO BANCO (texto pode ser NULL)
$sql = "INSERT INTO comentarios (id_mensagem, comentario, usuario_nome, usuario_id, parent_id, pref_vibe_comentario, pref_cor_borda, imagem_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issiisss", $id_mensagem, $comentario, $usuario_nome, $usuario_id, $parent_id, $vibe, $cor_borda, $imagem_url);

if ($stmt->execute()) {
    // 🔔 NOTIFICAÇÕES (dono do post) - código mantido
    $meu_id = $usuario_id ?? 0;
    $quem_comentou = $usuario_nome ?? "Visitante";
    
    $stmt_dono = $conn->prepare("SELECT usuario_id FROM mensagens WHERE id = ?");
    $stmt_dono->bind_param("i", $id_mensagem);
    $stmt_dono->execute();
    $res_dono = $stmt_dono->get_result()->fetch_assoc();
    if ($res_dono) {
        $id_dono_post = $res_dono['usuario_id'];
        if ($id_dono_post != $meu_id || $meu_id == 0) {
            $msg_dono = "@$quem_comentou comentou no seu post!";
            $st_dono_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, post_id, mensagem, lida) VALUES (?, ?, ?, 0)");
            $st_dono_notif->bind_param("iis", $id_dono_post, $id_mensagem, $msg_dono);
            $st_dono_notif->execute();
            $st_dono_notif->close();
        }
    }
    
    // 🧠 MENÇÕES (se houver texto)
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
    
    // 🚀 RENDERIZA O HTML DO COMENTÁRIO (Source of Truth)
    $nomeExibicao = $usuario_nome ? '@' . htmlspecialchars($usuario_nome, ENT_QUOTES, 'UTF-8') : '👤 Anônimo';
    
    // Só adiciona a div de texto se houver conteúdo
    $textoHtml = '';
    if ($comentario !== null && trim($comentario) !== '') {
        $textoRenderizado = nl2br(htmlspecialchars($comentario, ENT_QUOTES, 'UTF-8'));
        $textoHtml = '<div class="comentario-texto">' . $textoRenderizado . '</div>';
    }
    
    $mediaHtml = '';
    if ($imagem_url) {
        $mediaHtml = '<div class="comentario-media-wrapper"><img src="comentarios/' . htmlspecialchars($imagem_url) . '" class="comentario-img" alt="Imagem do comentário"></div>';
    }
    
    $comentarioHtml = '
        <div class="comentario-item comentario-entrou meu-comentario ' . $vibe . '" style="--cor-borda-glow: ' . $cor_borda . ';">
            <div class="comentario-meta">
                <strong class="comentario-autor" style="color: ' . $cor_borda . ';">' . $nomeExibicao . '</strong>
                <span class="comentario-data">Agora</span>
            </div>
            ' . $textoHtml . '
            ' . $mediaHtml . '
        </div>
    ';
    
    $response = [
        'status' => 'success',
        'message' => 'Comentário enviado!',
        'html' => $comentarioHtml,
        'imagem_url' => $imagem_url ? 'comentarios/' . $imagem_url : null
    ];
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar comentário: ' . $conn->error]);
    exit();
}
?>