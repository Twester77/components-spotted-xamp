<?php
// 1. CONEXÃO E ESTRUTURA (Lógica)
include_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php'; 

// 2. INSTANCIA O B2 (necessário para o fallback centralizado)
// 🐚 CORRENTE – 2026-10-13 (Sprint 2, Bloco 4.6): antes, o arquivo não instanciava
//    o B2Client e montava o caminho da foto na mão ("uploads/" . $foto). Isso
//    gerava URL quebrada quando o `foto` já vinha com prefixo próprio (ex:
//    "postagens/xyz.webp" → "uploads/postagens/xyz.webp"). Agora usa o
//    fallback centralizado como o resto da base.
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
    error_log('[ATLETICAS] Falha ao instanciar B2: ' . $e->getMessage());
}

// 3. VALIDAÇÃO E SEGURANÇA (PREPARED STATEMENT)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: feed.php");
    exit();
}

$atletica_id = trim($_GET['id']);

// 🔥 PREPARED STATEMENT para evitar SQL Injection
$sql = "SELECT id, nome, username, foto, bio FROM usuarios WHERE atletica_id = ? ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("[ATLETICAS] Erro ao preparar SELECT: " . $conn->error);
    header("Location: feed.php?erro=db");
    exit();
}
$stmt->bind_param("s", $atletica_id);
$stmt->execute();
$res = $stmt->get_result();
$total_habitantes = $res->num_rows;
?>

<main>
    <section class="comunidade-topo">
        <img src="badges/<?php echo htmlspecialchars($atletica_id); ?>.webp" 
             class="img-comunidade-grande" 
             alt="Imagem da Atlética da Comunidade"  
             onerror="this.src='badges/default.webp'">
        <h1 style="color: var(--dourado); text-transform: uppercase;">
            Comunidade <?php echo htmlspecialchars(str_replace('-', ' ', $atletica_id)); ?>
        </h1>
        <p style="color: #ccc; display:flex; align-self: flex-end;">Há <?php echo $total_habitantes; ?> habitantes nesta área da Fenda</p>
    </section>

    <div class="grid-habitantes">
        <?php if ($total_habitantes > 0): ?>
            <?php while ($h = $res->fetch_assoc()): 
                // 🐚 CORRENTE – 2026-10-13 (Bloco 4.6): fallback centralizado.
                //    Antes: "uploads/" . htmlspecialchars($h['foto']) montava o
                //    caminho na mão e quebrava quando o foto já tinha prefixo
                //    próprio. Agora obterUrlComFallback() resolve via B2 e cai
                //    em default.webp se o arquivo não existir.
                $foto_h = obterUrlComFallback($h['foto'] ?? null, 'uploads/ui/default.webp', $b2, true);
            ?>
                <a href="ver-perfil.php?user=<?php echo urlencode($h['username']); ?>" 
                   class="card-habitante">
                    <img src="<?php echo htmlspecialchars($foto_h); ?>" class="avatar-lista" alt="Avatar de <?php echo htmlspecialchars($h['nome']); ?>">
                    <div class="info-h">
                        <h3><?php echo htmlspecialchars($h['nome']); ?></h3>
                        <p>@<?php echo htmlspecialchars($h['username']); ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #7c7c7c; grid-column: 1/-1;">Ninguém apareceu por aqui ainda... 🌊</p>
        <?php endif; ?>
    </div>
</main>

<?php
$stmt->close();
include 'includes/footer.php';
?>