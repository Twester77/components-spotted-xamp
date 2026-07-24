<?php
require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';
// 🔥 ADICIONADO: inclui o motor de upload (B2 e funções de imagem)
require_once __DIR__ . '/includes/upload_engine.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// Busca todas as comunidades
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as total_membros,
        u.username as criador_username
        FROM comunidades c
        LEFT JOIN usuarios u ON c.criador_id = u.id
        ORDER BY c.data_criacao DESC";
$result = mysqli_query($conn, $sql);
?>

<main class="main-comunidades">
    <div class="comunidades-header">
        <h1>🌐 Comunidades da Fenda</h1>
        <p class="subtitle">Encontre seu grupo, compartilhe ideias e faça parte de algo maior.</p>
        
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="create-community-wrapper">
                <a href="criar-comunidade.php" class="btn-criar-comunidade">
                    <i class="fas fa-plus"></i> Criar Nova Comunidade
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="comunidades-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($com = mysqli_fetch_assoc($result)): 
                $membros = $com['total_membros'] ?? 0;
                
                // 🔥 OBTÉM A URL DA CAPA VIA B2
                $capa_nome = !empty($com['capa']) ? $com['capa'] : 'default_comunidade.webp';
                try {
                    $b2 = B2Client::getInstance();
                    $capa_exibicao = obterUrlImagem($capa_nome, $b2, true) ?? 'uploads/ui/default_comunidade.webp';
                } catch (Exception $e) {
                    $capa_exibicao = 'uploads/ui/default_comunidade.webp';
                }
                
                // Verifica se o usuário é membro
                $is_membro = false;
                if (isset($_SESSION['usuario_id'])) {
                    $meu_id = $_SESSION['usuario_id'];
                    $check = mysqli_query($conn, "SELECT 1 FROM comunidade_membros WHERE comunidade_id = {$com['id']} AND usuario_id = $meu_id");
                    $is_membro = mysqli_num_rows($check) > 0;
                }
            ?>
                <div class="comunidade-card">
                    <a href="comunidade.php?id=<?php echo $com['id']; ?>" class="card-link">
                        <div class="capa-wrapper">
                            <img src="<?php echo htmlspecialchars($capa_exibicao); ?>" alt="<?php echo htmlspecialchars($com['nome']); ?>" onerror="this.src='uploads/ui/default_comunidade.webp'">
                            <span class="badge-membros">
                                <i class="fas fa-users"></i> <?php echo $membros; ?>
                            </span>
                        </div>
                        <div class="info-comunidade">
                            <h3><?php echo htmlspecialchars($com['nome']); ?></h3>
                            <p class="descricao"><?php echo htmlspecialchars($com['descricao'] ?? 'Sem descrição'); ?></p>
                            <div class="meta">
                                <span>Criada por @<?php echo htmlspecialchars($com['criador_username'] ?? 'Anônimo'); ?></span>
                                <span><?php echo date('d/m/Y', strtotime($com['data_criacao'])); ?></span>
                            </div>
                        </div>
                    </a>
                    
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <div class="card-actions">
                            <button class="btn-entrar-comunidade <?php echo $is_membro ? 'membro' : ''; ?>" 
                                    data-comunidade="<?php echo $com['id']; ?>">
                                <?php echo $is_membro ? '✅ Membro' : '➕ Entrar'; ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Nenhuma comunidade ainda.</p>
                <a href="criar-comunidade.php" class="btn-criar-comunidade">Seja o primeiro a criar uma!</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.querySelectorAll('.btn-entrar-comunidade').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const comunidadeId = this.dataset.comunidade;
        const isMembro = this.classList.contains('membro');
        const action = isMembro ? 'sair' : 'entrar';
        const url = `includes/comunidade-actions.php?comunidade_id=${comunidadeId}&acao=${action}`;
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('membro');
                    this.textContent = isMembro ? '➕ Entrar' : '✅ Membro';
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao processar solicitação.');
                }
            })
            .catch(err => {
                console.error('[COMUNIDADE] Erro:', err);
                alert('Erro de conexão. Tente novamente.');
            });
    });
});
</script>

<?php include 'includes/footer.php'; ?>