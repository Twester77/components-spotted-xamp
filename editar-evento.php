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
 *
 * 🔧 CORREÇÃO NEREIDA/DJÊ – INSTÂNCIA #DS-2026-08-24 (v2)
 *    "Adicionado suporte a GIFs na edição (exibição e remoção),
 *     compressão client-side, listener assíncrono para múltiplos arquivos,
 *     exposição global da função removerAnexoExistente,
 *     envio via fetch com JSON, e exibição de erros de sessão.
 *     Contagem de anexos com classe .removido para evitar duplicidade."
 * - Nereida & Djê, as guardiãs das águas
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

// 🔥 CAPA ATUAL COM FALLBACK CENTRALIZADO
$capa_atual = obterUrlComFallback($evento['imagem_url'] ?? null, 'uploads/ui/default_evento.webp', null, true);

// Anexos atuais (já decodificados)
$anexos_atuais = [];
if (!empty($evento['anexos'])) {
    $anexos_atuais = json_decode($evento['anexos'], true);
    if (!is_array($anexos_atuais)) $anexos_atuais = [];
}

// 🔥 EXIBE ERROS DE SESSÃO (se houver)
$erro_sessao = $_SESSION['erro_evento'] ?? '';
unset($_SESSION['erro_evento']);

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>
<main class="bt-criar-page">
    <h1><i class="fas fa-edit"></i> Editar Evento</h1>
    <p class="bt-subtitulo">Atualize os dados do evento abaixo.</p>

    <?php if (!empty($erro_sessao)): ?>
        <div class="bt-erro-sessao" style="background:rgba(255,0,0,0.1); border-left:4px solid #ff4757; padding:12px 16px; border-radius:8px; margin-bottom:20px; color:#ff6b6b;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erro_sessao) ?>
        </div>
    <?php endif; ?>

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
                <option value="">Nenhuma específica (evento público geral)</option>
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
            <label for="capa"><i class="fas fa-upload"></i> Substituir Capa Atual (opcional)</label>
            <input type="file" name="capa" id="capa" accept="image/*">
            <small class="bt-campo-ajuda">Máx. 2MB, recomendado 16:9. Deixe em branco para manter.</small>
            <div id="bt-capa-preview" style="display:none; margin-top:10px;">
                <img id="bt-capa-preview-img" src="" alt="Prévia da nova capa">
            </div>
        </div>

        <!-- Galeria -->
        <div class="bt-campo-grupo" id="galeria-container">
            <label><i class="fas fa-images"></i> Galeria de Fotos</label>
            <div class="bt-anexos-grid" id="galeria-grid">
                <?php if (!empty($anexos_atuais)): ?>
                    <?php foreach ($anexos_atuais as $item): ?>
                        <?php if ($item['tipo'] === 'imagem' && !empty($item['caminho'])): ?>
                            <?php $img_url = obterUrlComFallback($item['caminho'], 'uploads/ui/default_evento.webp', null, true); ?>
                            <div class="bt-anexo-item" data-id="<?= htmlspecialchars($item['id']) ?>">
                                <img src="<?= htmlspecialchars($img_url) ?>" alt="Foto do evento" onerror="this.onerror=null; this.style.display='none'">
                                <button type="button" class="btn-remover-anexo" onclick="removerAnexoExistente(this)" title="Remover esta foto">✕</button>
                            </div>
                        <?php elseif ($item['tipo'] === 'gif' && !empty($item['url'])): ?>
                            <!-- 🔥 EXIBE GIFS NA PRÉVIA DE EDIÇÃO -->
                            <div class="bt-anexo-item" data-id="<?= htmlspecialchars($item['id']) ?>">
                                <img src="<?= htmlspecialchars($item['url']) ?>" alt="GIF do evento" onerror="this.onerror=null; this.style.display='none'">
                                <button type="button" class="btn-remover-anexo" onclick="removerAnexoExistente(this)" title="Remover este GIF">✕</button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div id="novos-anexos-preview" class="bt-anexos-grid"></div>

            <!-- 🔥 BOTÕES DE ADIÇÃO DE ANEXOS (com GIF) -->
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin: 10px auto; justify-content:center;">
                <label for="anexos" class="bt-btn-secundario" style="cursor:pointer; pointer-events: auto;">
                    <i class="fas fa-upload"></i> Adicionar fotos
                </label>
                <input type="file" name="anexos[]" id="anexos" accept="image/*" multiple style="display:none;">

                <button type="button" class="bt-btn-secundario" onclick="window.setGiphyTarget('gif-url-editar-evento'); abrirGiphyModal();" style="display:inline-flex; align-items:center; gap:6px;">
                    <i class="fas fa-grin-tongue-squint"></i> Adicionar GIF/Sticker
                </button>
                <input type="hidden" name="gif_url" id="gif-url-editar-evento" value="">
            </div>
            <small class="bt-campo-ajuda">Até 4 imagens ou GIFs no total (2MB cada).</small>
        </div>

        <div class="bt-botoes-rodape">
            <button type="submit" id="btn-salvar-evento" class="bt-btn-principal"><i class="fas fa-save"></i> Salvar Alterações</button>
            <a href="evento.php?id=<?= $id ?>" class="bt-btn-secundario">Cancelar</a>
        </div>
    </form>
</main>

<script>
    // ============================================================
    // 🔥 EXPOSIÇÃO GLOBAL PARA ONCLICK INLINE
    // ============================================================
    window.removerAnexoExistente = function(btn) {
        if (typeof EditarEventoAnexos !== 'undefined' && EditarEventoAnexos.removerAnexoExistente) {
            EditarEventoAnexos.removerAnexoExistente(btn);
        } else {
            console.warn('[EDITAR-EVENTO] EditarEventoAnexos.removerAnexoExistente não encontrado.');
        }
    };

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
    // 3. GERENCIADOR DE ANEXOS DA EDIÇÃO (EditarEventoAnexos)
    // ============================================================
    const EditarEventoAnexos = {
        anexosRemover: [],
        arquivosSelecionados: [],
        maxItems: 4,

        // 🔥 ADICIONA UM NOVO ANEXO (com compressão e validação de limite)
        async adicionarNovo(file, tipo = 'imagem', url = null) {
            // 🔥 VALIDA LIMITE GLOBAL (ANTES DE QUALQUER COISA)
            const galeriaAtual = document.querySelectorAll('#galeria-grid .bt-anexo-item:not(.removido)').length;
            if (galeriaAtual + this.arquivosSelecionados.length >= this.maxItems) {
                exibirBalao(`Limite de ${this.maxItems} anexos atingido.`, 'erro', document.getElementById('btn-salvar-evento'));
                return false;
            }

            if (tipo === 'imagem' && file) {
                const maxSize = 2 * 1024 * 1024;
                const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                if (file.size > maxSize) {
                    exibirBalao(`❌ "${file.name}" excede 2MB.`, 'erro', document.getElementById('btn-salvar-evento'));
                    return false;
                }
                if (!tiposPermitidos.includes(file.type)) {
                    exibirBalao(`❌ "${file.name}" formato não suportado.`, 'erro', document.getElementById('btn-salvar-evento'));
                    return false;
                }

                // 🔥 COMPRESSÃO
                let fileToAdd = file;
                if (typeof window.comprimirImagemClientSide === 'function') {
                    try {
                        const blobComprimido = await window.comprimirImagemClientSide(file, 0.7, 1200, 1200);
                        const nomeBase = file.name.replace(/\.[^.]+$/, '') + '.webp';
                        const arquivoComprimido = new File([blobComprimido], nomeBase, { type: 'image/webp' });
                        fileToAdd = arquivoComprimido;
                        console.log(`[EDITAR-EVENTO] Imagem comprimida: ${(fileToAdd.size / 1024).toFixed(1)} KB`);
                    } catch (err) {
                        console.warn('[EDITAR-EVENTO] Falha na compressão, usando original:', err);
                    }
                }
                this.arquivosSelecionados.push(fileToAdd);
                this.renderizarNovos();
                return true;
            }

            if (tipo === 'gif' && url) {
                // 🔥 VERIFICA DUPLICATA DE GIF
                const existe = this.arquivosSelecionados.some(item => item.isGif && item.url === url);
                if (existe) {
                    exibirBalao('Este GIF já foi adicionado.', 'info', document.getElementById('btn-salvar-evento'));
                    return false;
                }
                this.arquivosSelecionados.push({ isGif: true, url: url });
                this.renderizarNovos();
                exibirBalao('GIF adicionado à galeria!', 'sucesso', document.getElementById('btn-salvar-evento'), 2000);
                return true;
            }
            return false;
        },

        // 🔥 REMOVE ANEXO EXISTENTE (com classe .removido – contagem correta)
        removerAnexoExistente(btn) {
            const item = btn.closest('.bt-anexo-item');
            if (!item) return;
            const id = item.dataset.id;
            if (!id) return;

            // Marca como removido imediatamente
            item.classList.add('removido');
            item.style.transition = 'opacity 0.3s, transform 0.3s';
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';

            if (!this.anexosRemover.includes(id)) {
                this.anexosRemover.push(id);
                document.getElementById('anexos_remover').value = JSON.stringify(this.anexosRemover);
            }

            setTimeout(() => {
                if (item.parentNode) item.remove();
            }, 300);

            exibirBalao('Anexo removido da galeria.', 'sucesso', btn, 2000);
        },

        // 🔥 REMOVE NOVO ANEXO (prévia)
        removerAnexoNovo(btn) {
            const item = btn.closest('.bt-anexo-item');
            if (!item) return;
            const index = parseInt(item.dataset.index);
            if (isNaN(index)) return;

            this.arquivosSelecionados.splice(index, 1);
            this.renderizarNovos();
            if (this.arquivosSelecionados.length === 0) {
                document.getElementById('novos-anexos-preview').style.display = 'none';
            }
            exibirBalao('Anexo removido da lista.', 'info', btn, 2000);
        },

        // 🔥 RENDERIZA PRÉVIAS DE NOVOS ANEXOS
        renderizarNovos() {
            const container = document.getElementById('novos-anexos-preview');
            container.innerHTML = '';
            if (this.arquivosSelecionados.length === 0) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'flex';
            this.arquivosSelecionados.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'bt-anexo-item';
                div.dataset.index = index;

                const img = document.createElement('img');
                if (item.isGif) {
                    img.src = item.url;
                    img.alt = 'GIF';
                } else {
                    img.src = URL.createObjectURL(item);
                    img.alt = 'Nova imagem';
                }
                img.loading = 'lazy';
                div.appendChild(img);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-remover-anexo';
                btn.innerHTML = '✕';
                btn.dataset.index = index;
                btn.onclick = () => this.removerAnexoNovo(btn);
                div.appendChild(btn);

                container.appendChild(div);
            });
        },

        // 🔥 PREPARA FORMDATA PARA ENVIO
        prepararFormData(formData) {
            // Arquivos de imagem (apenas os que não são GIFs)
            const arquivosImagem = this.arquivosSelecionados.filter(item => !item.isGif);
            arquivosImagem.forEach(file => formData.append('anexos[]', file));

            // GIFs (URLs)
            const gifs = this.arquivosSelecionados.filter(item => item.isGif);
            gifs.forEach(gif => formData.append('gif_urls[]', gif.url));
        }
    };

    // ============================================================
    // 🔥 LISTENER: input file de anexos (ASSÍNCRONO COM AWAIT)
    // ============================================================
    document.getElementById('anexos').addEventListener('change', async function() {
        if (this.files.length > 0) {
            const galeriaAtual = document.querySelectorAll('#galeria-grid .bt-anexo-item:not(.removido)').length;
            const totalAtual = galeriaAtual + EditarEventoAnexos.arquivosSelecionados.length;
            const limite = 4;
            let adicionados = 0;

            for (const file of this.files) {
                if (totalAtual + adicionados >= limite) {
                    exibirBalao(`Limite de ${limite} anexos atingido.`, 'erro', this);
                    break;
                }
                const adicionado = await EditarEventoAnexos.adicionarNovo(file, 'imagem');
                if (adicionado) adicionados++;
            }
            this.value = '';
            if (adicionados > 0) {
                exibirBalao(`${adicionados} foto(s) adicionada(s).`, 'sucesso', this, 2000);
            }
        }
    });

    // ============================================================
    // 🔥 EVENTO: GIF SELECIONADO (com verificação de target)
    // ============================================================
    document.addEventListener('gifSelecionado', function(e) {
        console.log('[editar-evento] gifSelecionado recebido:', e.detail);
        if (e.detail && e.detail.targetId && e.detail.targetId !== 'gif-url-editar-evento') {
            console.log('[editar-evento] GIF para outro alvo. Ignorando.');
            return;
        }
        if (e.detail && e.detail.url) {
            const hiddenGif = document.getElementById('gif-url-editar-evento');
            if (hiddenGif) hiddenGif.value = e.detail.url;
            EditarEventoAnexos.adicionarNovo(null, 'gif', e.detail.url);
        }
    });

    // ============================================================
    // 🔥 SUBMIT: ENVIO VIA FETCH COM JSON
    // ============================================================
    document.getElementById('form-editar-evento').addEventListener('submit', function(e) {
        const btn = document.getElementById('btn-salvar-evento');
        if (btn.disabled) {
            e.preventDefault();
            return;
        }

        e.preventDefault();

        const formData = new FormData(this);
        EditarEventoAnexos.prepararFormData(formData);

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        fetch('processa-editar-evento.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                exibirBalao('Evento atualizado com sucesso! 🎉', 'sucesso', btn);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                const msg = data.message || 'Falha ao atualizar.';
                exibirBalao('Erro: ' + msg, 'erro', btn);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
            }
        })
        .catch(err => {
            console.error('[EDITAR-EVENTO] Erro no fetch:', err);
            exibirBalao('❌ ' + err.message, 'erro', btn);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
        });
    });

    // ============================================================
    // 5. INICIALIZAÇÃO
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[EDITAR-EVENTO] Inicializado com sucesso.');
    });
</script>

<?php include 'includes/footer.php'; ?>