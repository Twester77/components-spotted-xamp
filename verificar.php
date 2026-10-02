<?php
include_once __DIR__ . '/conexao.php';

// Garante CSRF token na sessão (proteção do form)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = "";
$status = "";

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    if (empty($token)) {
        $mensagem = "Token não fornecido.";
        $status = "erro";
    } else {
        // O botão é desabilitado no submit para evitar duplo clique e, por isso,
        // seu name pode não ser enviado pelo navegador. O método identifica a confirmação.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';

            if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
                // 🐚 MARESIA – Sprint 1: CSRF obrigatório
                $mensagem = "Token de segurança inválido. Tente novamente.";
                $status = "erro";
            } else {
                // 🐚 MARESIA – Sprint 1: UPDATE condicional (race-safe).
                // Só ativa se AINDA estiver inativo. Se dois cliques
                // chegarem juntos, o segundo afeta 0 linhas e cai no else.
                $stmt = $conn->prepare("UPDATE usuarios SET ativo = 1 WHERE token = ? AND ativo = 0");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $afetadas = $stmt->affected_rows;
                $stmt->close();

                if ($afetadas === 1) {
                    // Regenera CSRF (defesa em profundidade contra reuso)
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: index.php?msg=conta_ativada");
                    exit();
                } else {
                    $mensagem = "Ops! Este link já foi utilizado ou expirou.";
                    $status = "erro";
                }
            }
        } else {
            // GET = checa se o token ainda é válido pra mostrar a tela
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE token = ? AND ativo = 0");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $res = $stmt->get_result();
            $existe = $res->num_rows > 0;
            $stmt->close();

            if ($existe) {
                $status = "pronto";
            } else {
                $mensagem = "Link inválido ou conta já ativada.";
                $status = "erro";
            }
        }
    }
} else {
    $mensagem = "Token não fornecido.";
    $status = "erro";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativação de Conta - A Fenda</title>
    <style>
        body { background: #0a0a0ad0; background:oklch(14.479% 0.00002 271.152 / 0.816); color: #fff; color:oklch(100% 0.00011 271.152); font-family: -apple-system, BlinkMacSystemFont, system-ui, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 0 10px; margin: 20px auto; }
        .card { text-align: center; padding: 40px; background: rgba(255, 255, 255, 0.05); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); border-radius: 20px; border: 2px solid #70cde4; background-clip: padding-box; max-width: 800px; width: 90%; height: auto; }
        .btn { background: #70cde4; background: oklch(79.934% 0.09416 215.83); color: #000; color: oklch(0% 0 0); padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px; border: none; cursor: pointer; display: inline-block; margin-top: 20px; max-width: 100%; box-shadow: 0 0 15px rgba(112, 205, 228, 0.3); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($status == "pronto"): ?>
            <h1 style="color: #70cde4;"> Quase lá!</h1>
            <p>Clique no botão abaixo para confirmar a ativação da sua Aura e liberar o seu acesso ao Spotted.</p>
            <form method="POST" id="form-ativar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="confirmar_ativacao" id="btn-ativar" class="btn">ATIVAR MINHA CONTA</button>
            </form>
            <script>
                // 🐚 MARESIA – Sprint 1: desabilita o botão no primeiro clique
                // pra evitar duplo POST. Não substitui o UPDATE condicional
                // do servidor (que é a defesa real), mas melhora a UX.
                document.getElementById('form-ativar').addEventListener('submit', function () {
                    const btn = document.getElementById('btn-ativar');
                    btn.disabled = true;
                    btn.textContent = 'Ativando...';
                });
            </script>
        <?php else: ?>
            <h1 style="color: #ffbc00;">⚠️ Ops!</h1>
            <p><?php echo $mensagem; ?></p>
            <a href="index.php" class="btn" style="background: #fff; background:oklch(100% 0.00011 271.152); color: #000; color: oklch(0% 0 0);">IR PARA O LOGIN</a>
        <?php endif; ?>
    </div>
</body>
</html>