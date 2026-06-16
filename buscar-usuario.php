<?php
require_once __DIR__ . '/auth_check.php';

$busca = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$resultados = [];

if (!empty($busca)) {
    $sql = "SELECT id, nome, username, foto FROM usuarios 
            WHERE username LIKE '%$busca%' OR nome LIKE '%$busca%' 
            LIMIT 20";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $resultados[] = $row;
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container-busca container-fenda-flex">
    <h2>🔍 Buscar Estudantes</h2>
    
    <form action="buscar-usuario.php" method="GET" class="form-busca-fenda">
    <div class="container-autocomplete">
        <input type="text" name="q" id="input-busca" value="<?php echo htmlspecialchars($busca); ?>" 
               placeholder="Digite o nome ou @username..." autocomplete="off">
        
        <div id="dropdown-busca" class="dropdown-busca"></div>
    </div>
    
    <button type="submit">IR</button>
</form>

    <div class="lista-resultados"> <?php if (!empty($busca)): ?>
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $user): 
                    $foto = !empty($user['foto']) ? "uploads/" . $user['foto'] : "uploads/default_masculino.jpg";
                ?>
                    <a href="ver-perfil.php?user=<?php echo $user['username']; ?>" style="text-decoration: none; color: inherit;">
                        <div class="user-card">
                            <img src="<?php echo $foto; ?>" class="avatar-busca" alt="Avatar do Usuario">
                            <div>
                                <strong class="nome-user"><?php echo $user['nome']; ?></strong>
                                <span class="username-user">@<?php echo $user['username']; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #aaa; padding: 20px;">Nenhum estudante encontrado com "<?php echo htmlspecialchars($busca); ?>".</p>
            <?php endif; ?>
        <?php endif; ?>
    </div> </main>
    <script>
const inputBusca = document.getElementById('input-busca');
const dropdown = document.getElementById('dropdown-busca');

inputBusca.addEventListener('input', function() {
    const termo = this.value.trim();
    
    if (termo.length < 2) {
        dropdown.style.display = 'none';
        return;
    }

    // Chama o seu arquivo que já existe e funciona
    fetch('buscar-mencoes.php?q=' + encodeURIComponent(termo))
        .then(res => res.json())
        .then(data => {
            dropdown.innerHTML = '';
            if (data.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            // Cria a lista de resultados
            data.forEach(user => {
                const div = document.createElement('div');
                div.className = 'item-sugestao';
                div.textContent = '@' + user;
                div.onclick = () => {
                    window.location.href = 'ver-perfil.php?user=' + user;
                };
                dropdown.appendChild(div);
            });
            
            dropdown.style.display = 'block';
        });
});

// Fecha se clicar fora
document.addEventListener('click', (e) => {
    if (!inputBusca.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>