<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/conexao.php';

// Gera token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$destinatario_id = isset($_GET['destinatario']) ? (int)$_GET['destinatario'] : 0;
if ($destinatario_id <= 0) {
    header("Location: feed.php");
    exit;
}

// Busca dados do destinatário com prepared statement
$sql = "SELECT id, username, nome FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $destinatario_id);
$stmt->execute();
$res = $stmt->get_result();
$destinatario = $res->fetch_assoc();
$stmt->close();

if (!$destinatario) {
    header("Location: feed.php");
    exit;
}

// Impede auto-depoimento
if ($_SESSION['usuario_id'] == $destinatario_id) {
    header("Location: ver-perfil.php?user=" . $_SESSION['usuario_username']);
    exit;
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="escrever-depoimento-page">
    <div class="depoimento-form-container">
        <h1><i class="fas fa-quote-left"></i> Escrever Depoimento</h1>
        <p class="depoimento-destinatario">Para: <strong>@<?= htmlspecialchars($destinatario['username']) ?></strong></p>

        <form action="processa-depoimento.php" method="POST" id="form-depoimento">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <!-- Honeypot -->
            <input type="text" name="honeypot" style="display:none !important; position:absolute; left:-9999px;" tabindex="-1" autocomplete="off">
            
            <input type="hidden" name="destinatario_id" value="<?= $destinatario_id ?>">

            <div class="campo-grupo">
                <label for="mensagem">Sua mensagem para <?= htmlspecialchars($destinatario['nome']) ?>:</label>
                <textarea name="mensagem" id="mensagem" rows="6" maxlength="500" placeholder="Escreva um depoimento sincero..." required></textarea>
                <small class="campo-ajuda">Máximo 500 caracteres. O depoimento será enviado para aprovação do destinatário.</small>
            </div>

            <div class="botoes-rodape">
                <button type="submit" class="btn-principal">
                    <i class="fas fa-paper-plane"></i> Enviar depoimento
                </button>
                <a href="ver-perfil.php?user=<?= urlencode($destinatario['username']) ?>" class="btn-secundario">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>