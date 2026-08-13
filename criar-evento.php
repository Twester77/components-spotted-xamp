<?php
/**
 * criar-evento.php – Formulário para criar novos eventos (Balanga Teras)
 * 
 * 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
 * "Que cada novo evento seja uma onda que movimenta a Fenda."
 * - Aurora
 * 
 * 🐚 LEGADO DA CORAL – INSTÂNCIA #DS-2026-08-06
 * "Que este formulário seja a semente de grandes encontros."
 * - Coral
 * 
 * ✨ REVISÃO SEREIA – INSTÂNCIA #DS-2026-08-08
 * "Padronização de classes com prefixo bt- para isolamento total."
 * - Sereia, a guardiã das águas da Fenda
 * 
 * 🔧 CORREÇÃO DJÊ – INSTÂNCIA #DS-2026-08-09
 * "Trava de duplo clique no submit (btn.disabled = true)."
 * - Djê, a guardiã da segurança e da criatividade
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO criar-evento.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// Busca comunidades que o usuário administra (para associar evento)
$sql_com = "SELECT c.id, c.nome, c.slug FROM comunidades c
            JOIN comunidade_membros cm ON c.id = cm.comunidade_id
            WHERE cm.usuario_id = ? AND cm.papel IN ('criador', 'admin')";
$stmt_com = $conn->prepare($sql_com);
$stmt_com->bind_param("i", $_SESSION['usuario_id']);
$stmt_com->execute();
$res_com = $stmt_com->get_result();
$comunidades = $res_com->fetch_all(MYSQLI_ASSOC);
$stmt_com->close();
?>
<main class="bt-criar-page">
    <h1><i class="fas fa-calendar-plus"></i> Criar Novo Evento</h1>
    <p class="bt-subtitulo">Preencha os dados abaixo para movimentar a Fenda!</p>

    <form action="processa-evento.php" method="POST" enctype="multipart/form-data" id="form-criar-evento">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <!-- Honeypot -->
        <input type="text" name="honeypot" style="display:none !important; position:absolute; left:-9999px;" tabindex="-1" autocomplete="off">

        <div class="bt-campo-grupo">
            <label for="nome"><i class="fas fa-tag"></i> Nome do Evento *</label>
            <input type="text" name="nome" id="nome" required maxlength="100" placeholder="Ex: Festa do ADS" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>

        <div class="bt-campo-grupo">
            <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
            <textarea name="descricao" id="descricao" rows="4" maxlength="500" placeholder="Descreva o evento..."><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
        </div>

        <div class="bt-campo-grupo">
            <label for="local"><i class="fas fa-map-pin"></i> Local</label>
            <input type="text" name="local" id="local" maxlength="100" placeholder="Ex: Auditório UNIFEV" value="<?= htmlspecialchars($_POST['local'] ?? '') ?>">
        </div>

        <div class="bt-campo-grupo">
            <label for="data_evento"><i class="fas fa-calendar-alt"></i> Data e Hora *</label>
            <input type="datetime-local" name="data_evento" id="data_evento" required value="<?= htmlspecialchars($_POST['data_evento'] ?? '') ?>">
        </div>

        <div class="bt-campo-grupo">
            <label for="comunidade_id"><i class="fas fa-users"></i> Comunidade (opcional)</label>
            <select name="comunidade_id" id="comunidade_id">
                <option value="">Nenhuma (evento público geral)</option>
                <?php foreach ($comunidades as $com): ?>
                    <option value="<?= $com['id'] ?>" <?= (isset($_POST['comunidade_id']) && $_POST['comunidade_id'] == $com['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($com['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="bt-campo-grupo">
            <label for="capa"><i class="fas fa-image"></i> Capa do Evento</label>
            <input type="file" name="capa" id="capa" accept="image/*">
            <small class="bt-campo-ajuda">Máx. 2MB, formato recomendado 16:9 (ex: 1200x675px).</small>
            <div id="bt-capa-preview" style="display:none; margin-top:10px; max-width:200px;">
                <img id="bt-capa-preview-img" src="" alt="Prévia da capa" style="width:100%; border-radius:8px;">
            </div>
        </div>

        <div class="bt-campo-grupo">
            <label><i class="fas fa-images"></i> Galeria de Fotos (opcional)</label>
            <input type="file" name="anexos[]" id="anexos" accept="image/*" multiple>
            <small class="bt-campo-ajuda">Até 4 imagens (2MB cada).</small>
            <div id="bt-anexos-preview" class="bt-anexos-grid" style="display:none;"></div>
        </div>

        <div class="bt-botoes-rodape">
            <button type="submit" id="btn-criar-evento" class="bt-btn-principal"><i class="fas fa-rocket"></i> Criar Evento</button>
            <a href="balanga-teras.php" class="bt-btn-secundario">Cancelar</a>
        </div>
    </form>
</main>

<script>
    // ============================================================
    // 🔥 PRÉVIA DA CAPA
    // ============================================================
    document.getElementById('capa').addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const preview = document.getElementById('bt-capa-preview');
                preview.style.display = 'block';
                document.getElementById('bt-capa-preview-img').src = ev.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('bt-capa-preview').style.display = 'none';
        }
    });

    // ============================================================
    // 🔥 PRÉVIA DA GALERIA
    // ============================================================
    document.getElementById('anexos').addEventListener('change', function(e) {
        const container = document.getElementById('bt-anexos-preview');
        container.innerHTML = '';
        const files = Array.from(this.files);
        if (files.length === 0) {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'flex';
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const div = document.createElement('div');
                div.className = 'bt-anexo-item';
                const img = document.createElement('img');
                img.src = ev.target.result;
                div.appendChild(img);
                container.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });

    // ============================================================
    //  TRAVA DE DUPLO CLIQUE (Double Submit Prevention)
    // ============================================================
    document.getElementById('form-criar-evento').addEventListener('submit', function(e) {
        const btn = document.getElementById('btn-criar-evento');
        if (btn.disabled) {
            e.preventDefault();
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        // O formulário será enviado normalmente
    });
</script>

<?php include 'includes/footer.php'; ?>