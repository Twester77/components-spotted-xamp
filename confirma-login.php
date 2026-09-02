<?php
include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/fenda_debug.php';

fenda_log('🔵 INÍCIO confirma-login.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    
    fenda_log('🔵 POST recebido para login: ' . $_POST['email']);
    
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        fenda_log('🔴 Erro ao preparar SELECT: ' . mysqli_error($conn));
        header("Location: index.php?erro=usuario");
        exit();
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    if ($usuario) {
        fenda_log('🔵 Usuário encontrado: ID ' . $usuario['id']);
        
        if ($usuario['ativo'] == 0) {
            fenda_log('🔴 REDIRECIONANDO para index.php?erro=pendente');
            header("Location: index.php?erro=pendente");
            exit();
        }
        
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_username'] = $usuario['username'];

            // ============================================================
            // 🔥 NOVO: Gera token único para esta sessão
            // ============================================================
            $token_sessao = bin2hex(random_bytes(32)); // 64 caracteres hex
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            // Insere na tabela sessoes_ativas
            $stmt_insert = $conn->prepare("INSERT INTO sessoes_ativas (usuario_id, token, user_agent, ip) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("isss", $usuario['id'], $token_sessao, $user_agent, $ip);
            if (!$stmt_insert->execute()) {
                fenda_log('🔴 Erro ao inserir sessão ativa: ' . $stmt_insert->error);
                // Não impede o login, mas registra o erro
            }
            $stmt_insert->close();

            // ============================================================
            // 🔥 "Manter-me conectado" – lógica condicional
            // ============================================================
            $manter = isset($_POST['manter_conectado']) && $_POST['manter_conectado'] == 1;
            $expires_in = $manter ? time() + (86400 * 30) : 0;
            
            // 🔥 CORRIGIDO: campo 'token' → 'token_sessao' para alinhar com auth-bridge.php e conexao.php
            $cookie_payload = json_encode([
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'username' => $usuario['username'],
                'token_sessao' => $token_sessao, // 🔥 agora usa token_sessao
                'persistente' => $manter,
                'exp' => $expires_in
            ]);
            
            $encrypted_payload = fenda_encrypt_state($cookie_payload);
            
            $current_host = $_SERVER['HTTP_HOST'] ?? '';
            $is_real_production = ($is_production ?? false) || str_ends_with($current_host, 'fendauniversity.com.br');
            $cookieDomain = $is_real_production ? '.fendauniversity.com.br' : null;
            
            setcookie('fenda_state_token', $encrypted_payload, [
                'expires' => $expires_in ?: 0,
                'path' => '/',
                'domain' => $cookieDomain,
                'secure' => $is_real_production,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            fenda_log('🟢 Login bem-sucedido para ID ' . $usuario['id'] . ' (token: ' . substr($token_sessao, 0, 8) . '...)');
            
            header("Location: feed.php");
            exit();
        } else {
            fenda_log('🔴 REDIRECIONANDO para index.php?erro=senha');
            header("Location: index.php?erro=senha");
            exit();
        }
    } else {
        fenda_log('🔴 REDIRECIONANDO para index.php?erro=usuario');
        header("Location: index.php?erro=usuario");
        exit();
    }
} else {
    fenda_log('🔴 REDIRECIONANDO para index.php (acesso direto)');
    header("Location: index.php");
    exit();
}