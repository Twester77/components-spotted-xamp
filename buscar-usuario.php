<?php
require_once __DIR__ . '/auth_check.php';

// ============================================================
// 1. VALIDAÇÃO DA BUSCA
// ============================================================
$busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultados = [];

if (!empty($busca)) {
    // 🔥 PREPARED STATEMENT para evitar SQL Injection
    $sql = "SELECT id, nome, username, foto FROM usuarios 
            WHERE username LIKE ? OR nome LIKE ? 
            LIMIT 20";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $termo = '%' . $busca . '%';
        $stmt->bind_param("ss", $termo, $termo);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $resultados[] = $row;
        }
        $stmt->close();
    } else {
        error_log("[BUSCAR_USUARIO] Erro ao preparar SELECT: " . $conn->error);
    }
}

// ============================================================
// 2. INCLUSÃO DOS HEADERS (JÁ COM A SESSÃO ATIVA)
// ============================================================
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container-busca container-fenda-flex" style="height: 100dvh;">
    <h2>🔍 Buscar Estudantes</h2>
    
    <form action="buscar-usuario.php" method="GET" class="form-busca-fenda">
        <div class="container-autocomplete">
            <input type="text" name="q" id="input-busca" 
                   value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>" 
                   placeholder="Digite o nome ou @username..." 
                   autocomplete="off">
            <div id="dropdown-busca" class="dropdown-busca"></div>
        </div>
        <button type="submit">IR</button>
    </form>

    <div class="lista-resultados">
        <?php if (!empty($busca)): ?>
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $user): 
                    $foto = !empty($user['foto']) 
                        ? "uploads/" . htmlspecialchars($user['foto'], ENT_QUOTES, 'UTF-8') 
                        : "uploads/ui/default_masculino.jpg";
                ?>
                    <a href="ver-perfil.php?user=<?php echo urlencode($user['username']); ?>" 
                       style="text-decoration: none; color: inherit;">
                        <div class="user-card">
                            <img src="<?php echo $foto; ?>" 
                                 class="avatar-busca" 
                                 alt="Avatar de <?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div>
                                <strong class="nome-user"><?php echo htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="username-user">@<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #aaa; padding: 20px;">
                    Nenhum estudante encontrado com "<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>".
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
const inputBusca = document.getElementById('input-busca');
const dropdown = document.getElementById('dropdown-busca');

inputBusca.addEventListener('input', function() {
    const termo = this.value.trim();
    
    if (termo.length < 2) {
        dropdown.style.display = 'none';
        return;
    }

    fetch('buscar-mencoes.php?q=' + encodeURIComponent(termo))
        .then(res => res.json())
        .then(data => {
            dropdown.innerHTML = '';
            if (data.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            data.forEach(user => {
                const div = document.createElement('div');
                div.className = 'item-sugestao';
                div.textContent = '@' + user;
                div.onclick = () => {
                    window.location.href = 'ver-perfil.php?user=' + encodeURIComponent(user);
                };
                dropdown.appendChild(div);
            });
            
            dropdown.style.display = 'block';
        })
        .catch(err => {
            console.warn('[BUSCAR] Erro no autocomplete:', err);
            dropdown.style.display = 'none';
        });
});

document.addEventListener('click', (e) => {
    if (!inputBusca.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>