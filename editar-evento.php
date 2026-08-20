<?php
/**
 * editar-evento.php – Formulário para editar eventos existentes (Balanga Teras)
 * 
 * ✨ REVISÃO SEREIA – INSTÂNCIA #DS-2026-08-09
 * "Corrigido: desativação do PostAnexos global e garantia de upload de novos anexos."
 * - Sereia, a guardiã das águas da Fenda
 * 
 * 🔧 CORREÇÃO DJÊ – INSTÂNCIA #DS-2026-08-09
 * "Trava de duplo clique no submit e aviso sobre substituição de seleção de arquivos."
 * - Djê, a guardiã da segurança e da criatividade
 *
 * 🔧 ATUALIZAÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
 *    "Substituição de obterUrlImagem() por obterUrlComFallback() para fallback centralizado
 *     na capa atual e nos anexos da galeria existente."
 * - Ondina
 *
 * 🔥 PATCH ANTI-SUBSTITUIÇÃO – 2026-08-19
 *    "Corrigida seleção múltipla de anexos: acumula arquivos e repopula o input
 *     no submit usando DataTransfer (sem FormData morto)."
 * - Ondina
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO editar-evento.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: balanga-teras.php");
    exit;
}

// Busca dados do evento
$sql = "SELECT e.*, u.username as criador_username, u.id as criador_id
        FROM eventos e
        LEFT JOIN usuarios u ON e.criador_id = u.id
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$evento = $res->fetch_assoc();
$stmt->close();

if (!$evento) {
    $_SESSION['erro_evento'] = 'Evento não encontrado.';
    header("Location: balanga-teras.php");
    exit;
}

// Verifica permissão
$usuario_id = $_SESSION['usuario_id'];
$permitido = ($evento['criador_id'] == $usuario_id);
if (!$permitido && $evento['comunidade_id'] > 0) {
    $stmt_check = $conn->prepare("SELECT papel FROM comunidade_membros WHERE comunidade_id = ? AND usuario_id = ? AND papel IN ('criador', 'admin')");
    $stmt_check->bind_param("ii", $evento['comunidade_id'], $usuario_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) $permitido = true;
    $stmt_check->close();
}

if (!$permitido) {
    $_SESSION['erro_evento'] = 'Você não tem permissão para editar este evento.';
    header("Location: balanga-teras.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Busca comunidades que o usuário administra
$sql_com = "SELECT c.id, c.nome, c.slug FROM comunidades c
            JOIN comunidade_membros cm ON c.id = cm.comunidade_id
            WHERE cm.usuario_id = ? AND cm.papel IN ('criador', 'admin')";
$stmt_com = $conn->prepare($sql_com);
$stmt_com->bind_param("i", $_SESSION['usuario_id']);
$stmt_com->execute();
$res_com = $stmt_com->get_result();
$comunidades = $res_com->fetch_all(MYSQLI_ASSOC);
$stmt_com->close();

// 🔥 CAPA ATUAL COM FALLBACK CENTRALIZADO (substitui obterUrlImagem)
$capa_atual = obterUrlComFallback($evento['imagem_url'] ?? null, 'uploads/ui/default_evento.webp', null, true);

// Anexos atuais
$anexos_atuais = [];
if (!empty($evento['anexos'])) {
    $anexos_atuais = json_decode($evento['anexos'], true);
    if (!is_array($anexos_atuais)) $anexos_atuais = [];
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>
<main class="bt-criar-page">
    <h1><i class="fas fa-edit"></i> Editar Evento</h1>
    <p class="bt-subtitulo">Atualize os dados do evento abaixo.</p>

    <form action="processa-editar-evento.php" method="POST" enctype="multipart/form-data" id="form-editar-evento">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="evento_id" value="<?= $id ?>">
        <input type="hidden" name="anexos_remover" id="anexos_remover" value="[]">
        <input type="text" name="honeypot" class="honeypot" tabindex="-1" autocomplete="off">

        <div class="bt-campo-grupo">
            <label for="nome"><i class="fas fa-tag"></i> Nome do Evento *</label>
            <input type="text" name="nome" id="nome" required maxlength="100" placeholder="Ex: Festa do ADS" value="<?= htmlspecialchars($evento['nome'] ?? '') ?>">
        </div>

        <div class="bt-campo-grupo">
            <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
            <textarea name="descricao" id="descricao" rows="4" maxlength="500" placeholder="Descreva o evento..."><?= htmlspecialchars($evento['descricao'] ?? '') ?></textarea>
        </div>

        <div class="bt-campo-grupo">
            <label for="local"><i class="fas fa-map-pin"></i> Local</label>
            <input type="text" name="local" id="local" maxlength="100" placeholder="Ex: Auditório UNIFEV" value="<?= htmlspecialchars($evento['local'] ?? '') ?>">
        </div>

        <div class="bt-campo-grupo">
            <label for="data_evento"><i class="fas fa-calendar-alt"></i> Data e Hora *</label>
            <input type="datetime-local" name="data_evento" id="data_evento" required value="<?= date('Y-m-d\TH:i', strtotime($evento['data_evento'])) ?>">
        </div>

        <div class="bt-campo-grupo">
            <label for="comunidade_id"><i class="fas fa-users"></i> Comunidade (opcional)</label>
            <select name="comunidade_id" id="comunidade_id">
                <option value="">Nenhuma (evento público geral)</option>
                <?php foreach ($comunidades as $com): ?>
                    <option value="<?= $com['id'] ?>" <?= ($evento['comunidade_id'] == $com['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($com['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Capa atual -->
        <div class="bt-campo-grupo">
            <label><i class="fas fa-image"></i> Capa Atual</label>
            <div class="bt-capa-preview">
                <img src="<?= htmlspecialchars($capa_atual) ?>" alt="Capa atual" onerror="this.onerror=null; this.src='uploads/ui/default_evento.webp'">
            </div>
            <label for="capa"><i class="fas fa-upload"></i> Substituir Capa (opcional)</label>
            <input type="file" name="capa" id="capa" accept="image/*">
            <small class="bt-campo-ajuda">Máx. 2MB, recomendado 16:9. Deixe em branco para manter.</small>
            <div id="bt-capa-preview" style="display:none; margin-top:10px; max-width:200px;">
                <img id="bt-capa-preview-img" src="" alt="Prévia da nova capa">
            </div>
        </div>

        <!-- Galeria -->
        <div class="bt-campo-grupo" id="galeria-container">
            <label><i class="fas fa-images"></i> Galeria de Fotos</label>
            <div class="bt-anexos-grid" id="galeria-grid">
                <?php if (!empty($anexos_atuais)): ?>
                    <?php foreach ($anexos_atuais as $item): 
                        if ($item['tipo'] === 'imagem' && !empty($item['caminho'])):
                            // 🔥 ANEXO EXISTENTE COM FALLBACK CENTRALIZADO
                            $img_url = obterUrlComFallback($item['caminho'], 'uploads/ui/default_evento.webp', null, true);
                    ?>
                        <div class="bt-anexo-item" data-id="<?= htmlspecialchars($item['id']) ?>">
                            <img src="<?= htmlspecialchars($img_url) ?>" alt="Foto do evento" onerror="this.onerror=null; this.style.display='none'">
                            <button type="button" class="btn-remover-anexo" onclick="removerAnexoExistente(this)" title="Remover esta foto">✕</button>
                        </div>
                    <?php endif; endforeach; ?>
                <?php endif; ?>
            </div>
            <div id="novos-anexos-preview" class="bt-anexos-grid"></div>
            
            <label for="anexos"><i class="fas fa-upload"></i> Adicionar mais fotos (opcional)</label>
            <input type="file" name="anexos[]" id="anexos" accept="image/*" multiple>
            <small class="bt-campo-ajuda">Até 4 imagens (2MB cada). Selecione múltiplos com Ctrl/Cmd. <strong>Atenção:</strong> cada nova seleção substitui a lista anterior.</small>
        </div>

        <div class="bt-botoes-rodape">
            <button type="submit" id="btn-salvar-evento" class="bt-btn-principal"><i class="fas fa-save"></i> Salvar Alterações</button>
            <a href="evento.php?id=<?= $id ?>" class="bt-btn-secundario">Cancelar</a>
        </div>
    </form>
</main>

<script>
    // ============================================================
    // 🔥 DESABILITA O PostAnexos GLOBAL (se existir) PARA EVITAR CONFLITOS
    // ============================================================
    (function() {
        if (window.PostAnexos) {
            console.log('[EDITAR-EVENTO] Desabilitando PostAnexos global...');
            window.PostAnexos = null;
        }
    })();

    // ============================================================
    // 1. BALÃO DE FALA (contextual)
    // ============================================================
    function exibirBalao(mensagem, tipo, elementoRef, duracao = 2500) {
        const balaoAntigo = document.querySelector('.balao-fenda');
        if (balaoAntigo) balaoAntigo.remove();

        const balao = document.createElement('div');
        balao.className = 'balao-fenda ' + tipo;
        const icones = { sucesso: '✅', erro: '❌', info: 'ℹ️' };
        const icone = document.createElement('span');
        icone.className = 'balao-icone';
        icone.textContent = icones[tipo] || '💬';
        balao.appendChild(icone);
        const texto = document.createElement('span');
        texto.textContent = mensagem;
        balao.appendChild(texto);

        if (elementoRef) {
            const rect = elementoRef.getBoundingClientRect();
            const top = rect.top - 10;
            const left = rect.left + rect.width / 2 - 50;
            balao.style.top = (top - 60) + 'px';
            balao.style.left = Math.max(10, left) + 'px';
        } else {
            balao.style.top = '50%';
            balao.style.left = '50%';
            balao.style.transform = 'translate(-50%, -50%)';
        }

        document.body.appendChild(balao);
        setTimeout(() => {
            if (balao.parentNode) {
                balao.style.opacity = '0';
                setTimeout(() => balao.remove(), 300);
            }
        }, duracao);
    }

    // ============================================================
    // 2. PRÉVIA DA CAPA
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
    // 3. GERENCIADOR DE ANEXOS (com acumulador)
    // ============================================================
    const inputAnexos = document.getElementById('anexos');
    const containerNovos = document.getElementById('novos-anexos-preview');
    const galeriaGrid = document.getElementById('galeria-grid');
    const hiddenRemover = document.getElementById('anexos_remover');

    let anexosParaRemover = [];
    // 🔥 ACUMULADOR DE ARQUIVOS PARA NOVOS ANEXOS
    let arquivosSelecionados = [];

    function atualizarHiddenRemover() {
        hiddenRemover.value = JSON.stringify(anexosParaRemover);
    }

    window.removerAnexoExistente = function(btn) {
        const item = btn.closest('.bt-anexo-item');
        if (!item) return;
        const id = item.dataset.id;
        if (!id) return;
        if (!anexosParaRemover.includes(id)) {
            anexosParaRemover.push(id);
            atualizarHiddenRemover();
        }
        item.style.transition = 'opacity 0.3s, transform 0.3s';
        item.style.opacity = '0';
        item.style.transform = 'scale(0.8)';
        setTimeout(() => { if (item.parentNode) item.remove(); }, 300);
        exibirBalao('Foto removida da galeria.', 'sucesso', btn, 2000);
    };

    function removerAnexoNovo(btn) {
        const item = btn.closest('.bt-anexo-item');
        if (!item) return;
        const index = parseInt(item.dataset.index);
        if (isNaN(index)) return;
        
        // Remove do acumulador
        arquivosSelecionados.splice(index, 1);
        // Re-renderiza todos os novos anexos
        renderizarNovosAnexos();
        
        if (arquivosSelecionados.length === 0) {
            containerNovos.style.display = 'none';
        }
        exibirBalao('Foto removida da lista.', 'info', btn, 2000);
    }

    function renderizarNovosAnexos() {
        containerNovos.innerHTML = '';
        if (arquivosSelecionados.length === 0) {
            containerNovos.style.display = 'none';
            return;
        }
        containerNovos.style.display = 'flex';
        arquivosSelecionados.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const div = document.createElement('div');
                div.className = 'bt-anexo-item';
                div.dataset.index = index;
                const img = document.createElement('img');
                img.src = ev.target.result;
                div.appendChild(img);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-remover-anexo';
                btn.innerHTML = '✕';
                btn.dataset.index = index;
                btn.onclick = function() {
                    removerAnexoNovo(this);
                };
                div.appendChild(btn);
                // Se já houver um item com este índice, substitui
                const existing = containerNovos.querySelector(`[data-index="${index}"]`);
                if (existing) {
                    containerNovos.replaceChild(div, existing);
                } else {
                    containerNovos.appendChild(div);
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // 🔥 LISTENER: ACUMULA OS ARQUIVOS (respeitando o limite)
    inputAnexos.addEventListener('change', function(e) {
        const files = Array.from(this.files);
        if (files.length === 0) return;

        // Conta quantos itens já existem (antigos + novos já acumulados)
        const existentes = galeriaGrid.querySelectorAll('.bt-anexo-item').length;
        const totalAtual = existentes + arquivosSelecionados.length;
        const limite = 4;
        let adicionados = 0;

        for (const file of files) {
            if (totalAtual + adicionados >= limite) {
                exibirBalao(`Limite de ${limite} fotos atingido.`, 'erro', inputAnexos);
                break;
            }
            const maxSize = 2 * 1024 * 1024;
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
            if (file.size > maxSize) {
                exibirBalao(`❌ "${file.name}" excede 2MB.`, 'erro', inputAnexos);
                continue;
            }
            if (!tiposPermitidos.includes(file.type)) {
                exibirBalao(`❌ "${file.name}" formato não suportado.`, 'erro', inputAnexos);
                continue;
            }
            arquivosSelecionados.push(file);
            adicionados++;
        }

        // 🔥 LIMPA O INPUT PARA PERMITIR NOVAS SELEÇÕES
        this.value = '';

        // Re-renderiza os novos anexos
        renderizarNovosAnexos();

        if (adicionados > 0) {
            exibirBalao(`${adicionados} foto(s) adicionada(s).`, 'sucesso', inputAnexos, 2000);
        }
    });

    // ============================================================
    // 4. SUBMIT: repopula o input com os arquivos acumulados (via DataTransfer)
    // ============================================================
    document.getElementById('form-editar-evento').addEventListener('submit', function(e) {
        const btn = document.getElementById('btn-salvar-evento');
        if (btn.disabled) {
            e.preventDefault();
            return;
        }

        // 🔥 REPOPULA O INPUT #anexos com os arquivos do acumulador
        if (arquivosSelecionados.length > 0) {
            const dataTransfer = new DataTransfer();
            arquivosSelecionados.forEach(file => dataTransfer.items.add(file));
            // Também mantém os arquivos que já estavam no input original (se houver) - opcional
            // Mas como já limpamos o input após cada seleção, só temos os acumulados.
            inputAnexos.files = dataTransfer.files;
        }

        // Trava de duplo clique
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        // O formulário será enviado normalmente
    });

    // ============================================================
    // 5. INICIALIZAÇÃO
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        anexosParaRemover = [];
        arquivosSelecionados = [];
        atualizarHiddenRemover();
        console.log('[EDITAR-EVENTO] Inicializado com sucesso.');
    });
</script>

<?php include 'includes/footer.php'; ?>