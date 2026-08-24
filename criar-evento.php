<?php
/**
 * criar-evento.php – Formulário para criar novos eventos (Balanga Teras)
 * 
 * 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
 * "Que cada novo evento seja uma onda que movimenta a Fenda."
 * - Aurora
 * 
 * 🔧 CORREÇÃO NEREIDA/DJÊ – INSTÂNCIA #DS-2026-08-24
 *    "Refatorado para usar a mesma estrutura do editar-evento.php,
 *     garantindo envio de GIFs e consistência de limites."
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO criar-evento.php');

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

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

$erro_sessao = $_SESSION['erro_evento'] ?? '';
unset($_SESSION['erro_evento']);
?>
<main class="bt-criar-page">
    <h1><i class="fas fa-calendar-plus"></i> Criar Novo Evento</h1>
    <p class="bt-subtitulo">Preencha os dados abaixo para movimentar a Fenda!</p>

    <?php if (!empty($erro_sessao)): ?>
        <div class="bt-erro-sessao" style="background:rgba(255,0,0,0.1); border-left:4px solid #ff4757; padding:12px 16px; border-radius:8px; margin-bottom:20px; color:#ff6b6b;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erro_sessao) ?>
        </div>
    <?php endif; ?>

    <form action="processa-evento.php" method="POST" enctype="multipart/form-data" id="form-criar-evento">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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

        <!-- Capa -->
        <div class="bt-campo-grupo">
            <label for="capa"><i class="fas fa-image"></i> Capa do Evento</label>
            <input type="file" name="capa" id="capa" accept="image/*">
            <small class="bt-campo-ajuda">Máx. 2MB, recomendado 16:9.</small>
            <div id="bt-capa-preview" style="display:none; margin-top:10px;">
                <img id="bt-capa-preview-img" src="" alt="Prévia da capa">
            </div>
        </div>

        <!-- Galeria -->
        <div class="bt-campo-grupo">
            <label><i class="fas fa-images"></i> Galeria de Fotos (opcional)</label>
            <div id="anexos-grid-evento" class="bt-anexos-grid" style="display: none;"></div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px; justify-content:center;">
                <label for="anexos" class="bt-btn-secundario" style="cursor:pointer; pointer-events:auto;">
                    <i class="fas fa-upload"></i> Escolher imagens
                </label>
                <input type="file" name="anexos[]" id="anexos" accept="image/*" multiple style="display:none;">

                <button type="button" class="bt-btn-secundario" onclick="window.setGiphyTarget('gif-url-evento'); abrirGiphyModal();" style="display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-grin-tongue-squint"></i> Adicionar GIF/Sticker
                </button>
                <input type="hidden" name="gif_url" id="gif-url-evento" value="">
            </div>
            <small class="bt-campo-ajuda">Até 4 imagens ou GIFs (2MB cada).</small>
        </div>

        <div class="bt-botoes-rodape">
            <button type="submit" id="btn-criar-evento" class="bt-btn-principal"><i class="fas fa-rocket"></i> Criar Evento</button>
            <a href="balanga-teras.php" class="bt-btn-secundario">Cancelar</a>
        </div>
    </form>
</main>

<script>
    // ============================================================
    // 🔥 BALÃO DE FALA
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
    // 🔥 GERENCIADOR DE ANEXOS (igual ao editar-evento.php)
    // ============================================================
    const EventoAnexos = {
        anexos: [],
        maxItems: 4,
        gridElement: document.getElementById('anexos-grid-evento'),

        async adicionar(file, tipo = 'imagem', url = null) {
            // 🔥 VALIDA LIMITE GLOBAL
            if (this.anexos.length >= this.maxItems) {
                exibirBalao(`Limite de ${this.maxItems} anexos atingido.`, 'erro', document.getElementById('btn-criar-evento'));
                return false;
            }

            if (tipo === 'imagem' && file) {
                const maxSize = 2 * 1024 * 1024;
                const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                if (file.size > maxSize) {
                    exibirBalao(`Arquivo excede 2MB (${(file.size / 1024 / 1024).toFixed(1)}MB)`, 'erro', document.getElementById('btn-criar-evento'));
                    return false;
                }
                if (!tiposPermitidos.includes(file.type)) {
                    exibirBalao('Formato não suportado. Use JPG, PNG, WEBP ou GIF.', 'erro', document.getElementById('btn-criar-evento'));
                    return false;
                }
                const tamanhoKB = Math.round(file.size / 1024);
                exibirBalao(`Arquivo aceito (${tamanhoKB} KB)`, 'sucesso', document.getElementById('btn-criar-evento'));

                // 🔥 COMPRESSÃO
                let fileToAdd = file;
                if (typeof window.comprimirImagemClientSide === 'function') {
                    try {
                        const blobComprimido = await window.comprimirImagemClientSide(file, 0.7, 1200, 1200);
                        const nomeBase = file.name.replace(/\.[^.]+$/, '') + '.webp';
                        const arquivoComprimido = new File([blobComprimido], nomeBase, { type: 'image/webp' });
                        fileToAdd = arquivoComprimido;
                        console.log(`[EventoAnexos] Imagem comprimida: ${(fileToAdd.size / 1024).toFixed(1)} KB`);
                    } catch (err) {
                        console.warn('[EventoAnexos] Falha na compressão, usando original:', err);
                    }
                }
                this.anexos.push({
                    id: 'anexo-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6),
                    tipo: 'imagem',
                    file: fileToAdd,
                    url: null,
                    preview: URL.createObjectURL(fileToAdd),
                    status: 'pending'
                });
                this.renderizar();
                return true;
            }

            if (tipo === 'gif' && url) {
                const existe = this.anexos.some(item => item.tipo === 'gif' && item.url === url);
                if (existe) {
                    exibirBalao('Este GIF já foi adicionado.', 'info', document.getElementById('btn-criar-evento'));
                    return false;
                }
                this.anexos.push({
                    id: 'anexo-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6),
                    tipo: 'gif',
                    file: null,
                    url: url,
                    preview: url,
                    status: 'pending'
                });
                this.renderizar();
                exibirBalao('GIF adicionado!', 'sucesso', document.getElementById('btn-criar-evento'));
                document.getElementById('anexos').value = '';
                return true;
            }
            return false;
        },

        remover(index) {
            if (index < 0 || index >= this.anexos.length) return;
            const item = this.anexos[index];
            if (item.preview && item.tipo === 'imagem') URL.revokeObjectURL(item.preview);
            this.anexos.splice(index, 1);
            this.renderizar();
        },

        limparTodos() {
            this.anexos.forEach(item => {
                if (item.preview && item.tipo === 'imagem') URL.revokeObjectURL(item.preview);
            });
            this.anexos = [];
            this.renderizar();
        },

        renderizar() {
            const grid = this.gridElement;
            if (!grid) return;
            if (this.anexos.length === 0) {
                grid.style.display = 'none';
                grid.innerHTML = '';
                return;
            }
            grid.style.display = 'flex';
            grid.innerHTML = '';
            this.anexos.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'bt-anexo-item';
                div.dataset.index = index;
                const img = document.createElement('img');
                img.src = item.preview;
                img.alt = 'Anexo ' + (index + 1);
                img.loading = 'lazy';
                div.appendChild(img);
                const btn = document.createElement('button');
                btn.className = 'btn-remover-anexo';
                btn.innerHTML = '✕';
                btn.title = 'Remover anexo';
                btn.dataset.index = index;
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.remover(index);
                });
                div.appendChild(btn);
                img.addEventListener('click', () => {
                    if (typeof window.abrirLightboxManual === 'function') {
                        window.abrirLightboxManual(item.preview);
                    } else {
                        window.open(item.preview, '_blank');
                    }
                });
                grid.appendChild(div);
            });
        },

        prepararFormData(formData) {
            this.anexos.forEach((item) => {
                if (item.file) {
                    formData.append('anexos[]', item.file);
                } else if (item.tipo === 'gif' && item.url) {
                    formData.append('gif_urls[]', item.url);
                }
            });
        }
    };

    // ============================================================
    // 🔥 EVENTO: input file (assíncrono)
    // ============================================================
    document.getElementById('anexos').addEventListener('change', async function() {
        if (this.files.length > 0) {
            for (const file of this.files) {
                const adicionado = await EventoAnexos.adicionar(file, 'imagem');
                if (!adicionado) break;
            }
            this.value = '';
        }
    });

    // ============================================================
    // 🔥 EVENTO: GIF SELECIONADO
    // ============================================================
    document.addEventListener('gifSelecionado', function(e) {
        console.log('[criar-evento] gifSelecionado recebido:', e.detail);
        if (e.detail && e.detail.targetId && e.detail.targetId !== 'gif-url-evento') {
            console.log('[criar-evento] GIF para outro alvo. Ignorando.');
            return;
        }
        if (e.detail && e.detail.url) {
            const hiddenGif = document.getElementById('gif-url-evento');
            if (hiddenGif) hiddenGif.value = e.detail.url;
            EventoAnexos.adicionar(null, 'gif', e.detail.url);
        }
    });

    // ============================================================
    // 🔥 ENVIO DO FORMULÁRIO
    // ============================================================
    document.getElementById('form-criar-evento').addEventListener('submit', function(e) {
        const btn = document.getElementById('btn-criar-evento');
        if (btn.disabled) {
            e.preventDefault();
            return;
        }

        const formData = new FormData(this);
        EventoAnexos.prepararFormData(formData);

        e.preventDefault();

        const nome = document.getElementById('nome').value.trim();
        if (nome.length < 3) {
            exibirBalao('O nome do evento deve ter pelo menos 3 caracteres.', 'erro', btn);
            return;
        }
        const dataEvento = document.getElementById('data_evento').value;
        if (!dataEvento || new Date(dataEvento) < new Date()) {
            exibirBalao('A data do evento deve ser futura.', 'erro', btn);
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';

        fetch('processa-evento.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                exibirBalao('Evento criado com sucesso! 🎉', 'sucesso', btn);
                EventoAnexos.limparTodos();
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                const msg = data.message || 'Falha ao criar evento.';
                exibirBalao('Erro: ' + msg, 'erro', btn);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket"></i> Criar Evento';
            }
        })
        .catch(err => {
            console.error('[criar-evento] Erro no fetch:', err);
            exibirBalao('❌ ' + err.message, 'erro', btn);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket"></i> Criar Evento';
        });
    });

    console.log('[criar-evento] Inicializado com sucesso.');
</script>

<?php include 'includes/footer.php'; ?>