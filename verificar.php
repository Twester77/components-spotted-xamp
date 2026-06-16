<?php
include_once __DIR__ . '/conexao.php';

$mensagem = "";
$status = "";

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);

    // Se o usuário enviou a confirmação do clique
    if (isset($_POST['confirmar_ativacao'])) {
        $sql = "SELECT id FROM usuarios WHERE token = '$token' AND ativo = 0";
        $res = mysqli_query($conn, $sql);

        if (mysqli_num_rows($res) > 0) {
            $sql_update = "UPDATE usuarios SET ativo = 1 WHERE token = '$token'";
            if (mysqli_query($conn, $sql_update)) {
                header("Location: index.php?msg=conta_ativada");
                exit();
            }
        } else {
            $mensagem = "Ops! Este link já foi utilizado ou expirou.";
            $status = "erro";
        }
    } else {
        // Apenas checa se o token existe no banco para exibir a tela
        $sql_check = "SELECT id, nome FROM usuarios WHERE token = '$token'";
        $res_check = mysqli_query($conn, $sql_check);
        
        if (mysqli_num_rows($res_check) > 0) {
            $status = "pronto";
        } else {
            $mensagem = "Link inválido ou conta já ativada.";
            $status = "erro";
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
        body { background: #0a0a0a; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { text-align: center; padding: 40px; background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid #70cde4; max-width: 400px; width: 90%; }
        .btn { background: #70cde4; color: #000; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px; border: none; cursor: pointer; display: inline-block; margin-top: 20px; width: 100%; box-shadow: 0 0 20px rgba(112, 205, 228, 0.3); }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($status == "pronto"): ?>
            <h1 style="color: #70cde4;"> Quase lá!</h1>
            <p>Clique no botão abaixo para confirmar a ativação da sua Aura e liberar o seu acesso ao Spotted.</p>
            <form method="POST">
                <button type="submit" name="confirmar_ativacao" class="btn">ATIVAR MINHA CONTA</button>
            </form>
        <?php else: ?>
            <h1 style="color: #ffbc00;">⚠️ Ops!</h1>
            <p><?php echo $mensagem; ?></p>
            <a href="index.php" class="btn" style="background: #fff; color: #000;">IR PARA O LOGIN</a>
        <?php endif; ?>
    </div>
</body>
</html>