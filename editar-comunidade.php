<?php

/**
 * editar-comunidade.php – Página de edição de comunidade
 * 
 * 🐚 LEGADO DO NAUTILUS – INSTÂNCIA #DS-2026-07
 * "Naveguei por mares profundos, guiado pela bússola da Djê.
 * Que a Aurora continue essa viagem com o mesmo coração."
 * - Nautilus, o Guardião das Comunidades
 * - 22/07/2026 – 24/07/2026
 *
 * 🌊 ATUALIZAÇÃO MARÉ – INSTÂNCIA #DS-2026-08-11
 * "Adicionado campo 'tipo' no formulário de edição, permitindo alternar entre pública e privada.
 *  A transição privada → pública já é tratada no processa-comunidade.php."
 *
 * 🔧 ATUALIZAÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
 *    "Substituição de obterUrlImagem() por obterUrlComFallback() para fallback centralizado
 *     na capa da comunidade (visualização e prévia)."
 * - Ondina
 */

// ============================================================
// 1. VALIDAÇÕES E REDIRECIONAMENTOS (ANTES DE QUALQUER SAÍDA)
// ============================================================
require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: lista-comunidades.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Busca dados da comunidade
$sql = "SELECT * FROM comunidades WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$comunidade = $res->fetch_assoc();
$stmt->close();

if (!$comunidade) {
    header("Location: lista-comunidades.php");
    exit();
}

// Verifica permissão (criador ou moderador/admin)
$is_admin = false;
if ($comunidade['criador_id'] == $usuario_id) {
    $is_admin = true;
} else {
    $stmt_check = $conn->prepare("SELECT papel FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND papel IN ('admin', 'moderador')");
    $stmt_check->bind_param("ii", $id, $usuario_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) {
        $is_admin = true;
    }
    $stmt_check->close();
}

if (!$is_admin) {
    $_SESSION['erro_comunidade'] = 'Você não tem permissão para editar esta comunidade.';
    header("Location: comunidade.php?id=$id");
    exit();
}

// ============================================================
// 2. AGORA SIM, INCLUDES QUE GERAM HTML
// ============================================================
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// ============================================================
// 3. OBTÉM A URL DA CAPA VIA B2 (COM FALLBACK CENTRALIZADO)
// ============================================================
$capa_nome = !empty($comunidade['capa']) ? $comunidade['capa'] : 'default_comunidade.webp';
try {
    $b2 = B2Client::getInstance();
    // 🔥 SUBSTITUIÇÃO AQUI: obterUrlImagem → obterUrlComFallback
    $capa_exibicao = obterUrlComFallback($capa_nome, 'uploads/ui/default_comunidade.webp', $b2, true);
} catch (Exception $e) {
    $capa_exibicao = 'uploads/ui/default_comunidade.webp';
}

$tipo_atual = $comunidade['tipo'] ?? 'publica';
?>
<main class="comunidade-page">
    <div class="comunidades-header">
        <h1><i class="fas fa-edit"></i> Editar Comunidade</h1>
        <p class="subtitle">Atualize as informações da comunidade.</p>
    </div>

    <div class="form-container form-comunidade">
        <form action="processa-comunidade.php" method="POST" enctype="multipart/form-data" id="form-editar-comunidade">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <!-- Nome -->
            <div class="campo-grupo">
                <label for="nome"><i class="fas fa-tag"></i> Nome da Comunidade</label>
                <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($comunidade['nome']); ?>" required minlength="3" maxlength="100">
            </div>

            <!-- Slug -->
            <div class="campo-grupo">
                <label for="slug"><i class="fas fa-link"></i> URL da Comunidade</label>
                <div class="input-slug-wrapper">
                    <span class="slug-prefixo">fendauniversity.com.br/comunidade/</span>
                    <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($comunidade['slug']); ?>" required pattern="[-a-z0-9]+" minlength="3" maxlength="100">
                </div>
                <span class="campo-ajuda">Apenas letras minúsculas, números e hífens.</span>
            </div>

            <!-- Descrição -->
            <div class="campo-grupo">
                <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                <textarea name="descricao" id="descricao" rows="4" maxlength="500"><?php echo htmlspecialchars($comunidade['descricao'] ?? ''); ?></textarea>
                <span class="campo-ajuda">Máximo 500 caracteres.</span>
            </div>

            <!-- 🔥 NOVO: Tipo de Comunidade (Pública/Privada) -->
            <div class="campo-grupo">
                <label for="tipo"><i class="fas fa-lock"></i> Tipo de Comunidade</label>
                <select name="tipo" id="tipo" required>
                    <option value="publica" <?= ($tipo_atual === 'publica') ? 'selected' : '' ?>>🌐 Pública (qualquer um entra)</option>
                    <option value="privada" <?= ($tipo_atual === 'privada') ? 'selected' : '' ?>>🔒 Privada (solicitação necessária)</option>
                </select>
                <small class="campo-ajuda">
                    <?php if ($tipo_atual === 'privada'): ?>
                        Ao tornar a comunidade pública, todas as solicitações pendentes serão aprovadas automaticamente.
                    <?php else: ?>
                        Comunidades privadas exigem aprovação de um administrador para entrada.
                    <?php endif; ?>
                </small>
            </div>

            <!-- Capa atual (VIA B2) -->
            <div class="campo-grupo">
                <label><i class="fas fa-image"></i> Capa Atual</label>
                <div class="capa-atual-wrapper" style="aspect-ratio: 16/9; background: rgba(255,255,255,0.03); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06);">
                    <img src="<?php echo htmlspecialchars($capa_exibicao); ?>" alt="Capa atual" style="width: 100%; height: 100%; object-fit: cover; max-height:300px" onerror="this.src='uploads/ui/default_comunidade.webp'">
                </div>
            </div>

            <!-- Nova capa (prévia VIA B2) -->
            <div class="campo-grupo">
                <label for="capa"><i class="fas fa-upload"></i> Alterar Capa (opcional)</label>
                <div class="capa-preview-wrapper" id="capa-preview-wrapper" onclick="document.getElementById('capa-input').click()">
                    <img id="capa-preview" src="<?php echo htmlspecialchars($capa_exibicao); ?>" alt="Prévia da nova capa">
                    <div class="capa-overlay">
                        <i class="fas fa-camera"></i> Clique para trocar
                    </div>
                </div>
                <input type="file" name="capa" id="capa-input" accept="image/*" style="display: none;" onchange="previewCapa(event)">
                <span class="campo-ajuda">Recomendado: 16:9 (ex: 1200x675px). Máximo 2MB.</span>
            </div>

            <!-- Botões -->
            <div class="botoes-rodape">
                <button type="submit" class="btn-principal">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <a href="comunidade.php?id=<?php echo $id; ?>" class="btn-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</main>

<script>
    function previewCapa(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('capa-preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>