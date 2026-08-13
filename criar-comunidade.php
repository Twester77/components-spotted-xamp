<?php
require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

$usuario_id = $_SESSION['usuario_id'];

// Busca atléticas para sugerir como slug (opcional)
$sql_atleticas = "SELECT DISTINCT atletica_id FROM usuarios WHERE atletica_id IS NOT NULL AND atletica_id != ''";
$res_atleticas = mysqli_query($conn, $sql_atleticas);
$atleticas = [];
while ($row = mysqli_fetch_assoc($res_atleticas)) {
    $atleticas[] = $row['atletica_id'];
}
?>
<main class="comunidade-page">
    <div class="comunidades-header">
        <h1>
            <i class="fas fa-plus-circle"></i> Criar Nova Comunidade
        </h1>
        <p class="comunidades-subtitulo">
            Dê vida a um novo espaço na Fenda. Reúna pessoas com interesses em comum (ou não).
        </p>
    </div>

    <div class="form-container">
        <form action="processa-comunidade.php" method="POST" enctype="multipart/form-data" class="form-comunidade" id="form-criar-comunidade">

            <!-- Nome -->
            <div class="campo-grupo">
                <label for="nome"><i class="fas fa-tag"></i> Nome da Comunidade</label>
                <input type="text" name="nome" id="nome" placeholder="Ex: ADS Overclock" required minlength="3" maxlength="100"
                    oninput="gerarSlug(this.value)">
            </div>

            <!-- Slug (gerado automaticamente) -->
            <div class="campo-grupo">
                <label for="slug"><i class="fas fa-link"></i> URL da Comunidade</label>
                <div class="input-slug-wrapper">
                    <span class="slug-prefixo">fendauniversity.com.br/comunidade/</span>
                    <input type="text" name="slug" id="slug" placeholder="ads-overclock" required pattern="[-a-z0-9]+" minlength="3" maxlength="100">
                </div>
                <small class="campo-ajuda">Apenas letras minúsculas, números e hífens. Ex: ads-overclock</small>
            </div>

            <!-- Descrição -->
            <div class="campo-grupo">
                <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                <textarea name="descricao" id="descricao" placeholder="O que essa comunidade representa?" rows="4" maxlength="500"></textarea>
                <small class="campo-ajuda">Máximo 500 caracteres.</small>
            </div>

            <!-- 🔥 NOVO: Tipo de Comunidade (Pública/Privada) -->
            <div class="campo-grupo">
                <label for="tipo"><i class="fas fa-lock"></i> Tipo de Comunidade</label>
                <select name="tipo" id="tipo" required>
                    <option value="publica" selected>🌐 Pública (qualquer um entra)</option>
                    <option value="privada">🔒 Privada (solicitação necessária)</option>
                </select>
                <small class="campo-ajuda">Comunidades privadas exigem aprovação de um administrador para entrada.</small>
            </div>

            <!-- Capa -->
            <div class="campo-grupo">
                <label for="capa"><i class="fas fa-image"></i> Capa da Comunidade</label>
                <div class="capa-preview-wrapper" id="capa-preview-wrapper" onclick="document.getElementById('capa-input').click()">
                    <img id="capa-preview" src="uploads/ui/default_comunidade.webp" alt="Prévia da capa">
                    <div class="capa-overlay">
                        <i class="fas fa-camera"></i> Clique para adicionar capa
                    </div>
                </div>
                <input type="file" name="capa" id="capa-input" accept="image/*" style="display: none;" onchange="previewCapa(event)">
                <small class="campo-ajuda">Recomendado: 16:9 (ex: 1200x675px). Máximo 2MB.</small>
            </div>

            <!-- Sugestão de Slug baseado em atléticas -->
            <?php if (!empty($atleticas)): ?>
                <div class="campo-grupo sugestao-atleticas">
                    <label><i class="fas fa-lightbulb"></i> Sugestões baseadas em atléticas:</label>
                    <div class="sugestoes-lista">
                        <?php foreach ($atleticas as $atletica):
                            $nome_atletica = ucfirst(str_replace('-', ' ', $atletica));
                        ?>
                            <button type="button" class="btn-sugestao" onclick="preencherSugestao('<?php echo $atletica; ?>', '<?php echo $nome_atletica; ?>')">
                                <?php echo $nome_atletica; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <small class="campo-ajuda">Clique para preencher nome e slug automaticamente.</small>
                </div>
            <?php endif; ?>

            <!-- Botões -->
            <div class="botoes-rodape">
                <button type="submit" class="btn-principal">
                    <i class="fas fa-rocket"></i> Criar Comunidade
                </button>
                <a href="lista-comunidades.php" class="btn-secundario">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<script>
    // ============================================================
    // GERAR SLUG AUTOMATICAMENTE A PARTIR DO NOME
    // ============================================================
    function gerarSlug(nome) {
        const slug = nome.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove acentos
            .replace(/[^a-z0-9]+/g, '-') // Substitui caracteres especiais por hífen
            .replace(/^-+|-+$/g, ''); // Remove hífens no início/fim
        document.getElementById('slug').value = slug;
    }

    // ============================================================
    // PRÉVIA DA CAPA
    // ============================================================
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

    // ============================================================
    // PREENCHER SUGESTÃO DE ATLÉTICA
    // ============================================================
    function preencherSugestao(slug, nome) {
        document.getElementById('nome').value = nome;
        document.getElementById('slug').value = slug;
        document.querySelector('.form-container').scrollIntoView({
            behavior: 'smooth'
        });
    }

    // ============================================================
    // VALIDAÇÃO DO FORMULÁRIO (antes de enviar)
    // ============================================================
    document.getElementById('form-criar-comunidade').addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        const slug = document.getElementById('slug').value.trim();

        if (nome.length < 3) {
            e.preventDefault();
            alert('O nome deve ter pelo menos 3 caracteres.');
            document.getElementById('nome').focus();
            return;
        }

        if (!/^[a-z0-9-]+$/.test(slug)) {
            e.preventDefault();
            alert('O slug deve conter apenas letras minúsculas, números e hífens.');
            document.getElementById('slug').focus();
            return;
        }

        if (slug.length < 3) {
            e.preventDefault();
            alert('O slug deve ter pelo menos 3 caracteres.');
            document.getElementById('slug').focus();
            return;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>