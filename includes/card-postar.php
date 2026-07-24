<?php
/**
 * card-postar.php – Formulário de criação de posts (modal + inline)
 * 
 * Modo de uso:
 * - Modal: chamado normalmente via include (comportamento atual)
 * - Inline: passe ?modo=inline&comunidade_id=X via GET ou defina as variáveis antes do include
 * 
 * Variáveis suportadas:
 * - $modo_inline (bool) – se true, renderiza como inline em vez de modal
 * - $comunidade_id (int) – ID da comunidade (se estiver em modo inline)
 */

// Detecta modo inline via GET ou variável pré-definida
$modo_inline = isset($_GET['modo']) && $_GET['modo'] === 'inline';
$comunidade_id = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
$comunidade_nome = isset($_GET['comunidade_nome']) ? htmlspecialchars($_GET['comunidade_nome']) : 'Comunidade';

// Se a variável $modo_inline já foi definida (ex: via include no comunidade.php), usa ela
if (isset($modo_inline) && $modo_inline === true) {
    // Já está definido
} else {
    $modo_inline = false;
}

// Se a variável $comunidade_id já foi definida (ex: via include no comunidade.php), usa ela
if (isset($comunidade_id) && $comunidade_id > 0) {
    // Já está definido
} else {
    $comunidade_id = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
}

// Classes CSS diferentes para cada modo
$container_class = $modo_inline ? 'card-postar-inline' : 'form-container form-container-vivo';
$modo_atributo = $modo_inline ? 'inline' : 'modal';
?>
<?php if ($modo_inline): ?>
    <!-- ============================================================
    MODO INLINE – formulário fixo com toggle (para comunidades)
    ============================================================ -->
    <div class="card-postar-inline-wrapper">
        <button class="btn-toggle-postar" onclick="togglePostarInline()" type="button">
            <i class="fas fa-chevron-down" id="toggle-postar-icon"></i> 
            <span id="toggle-postar-texto">Novo post na comunidade</span>
        </button>
        <div class="postar-inline-conteudo" id="postar-inline-conteudo" style="display: none;">
<?php endif; ?>

<!-- ============================================================
    FORMULÁRIO PRINCIPAL (compartilhado entre modal e inline)
    ============================================================ -->
<section id="postar" class="main-novo-post <?php echo $modo_inline ? 'modo-inline' : ''; ?>">
    <div class="<?php echo $container_class; ?>">

        <form action="enviar-post.php" method="POST" enctype="multipart/form-data" id="form-postar-vivo">

            <!-- 🔥 CAMPO OCULTO: comunidade_id (se estiver em modo comunidade) -->
            <?php if ($comunidade_id > 0): ?>
                <input type="hidden" name="comunidade_id" value="<?php echo $comunidade_id; ?>">
                <input type="hidden" name="modo_origem" value="comunidade">
            <?php endif; ?>

            <!-- Categoria -->
            <div class="campo-categoria-vivo">
                <select name="categoria" id="categoria-vivo" aria-label="Selecione a categoria">
                    <?php if ($comunidade_id > 0): ?>
                        <option value="comunidade" selected>👥 Comunidade</option>
                    <?php else: ?>
                        <option value="anonimo">🕵️ Anônimo</option>
                        <option value="comunidade">👥 Comunidade</option>
                        <option value="academico">❓ Dúvidas Acadêmicas</option>
                        <option value="elogio">💖 Correio Elegante</option>
                        <option value="tenho-ranco">👌 Ranço</option>
                        <option value="acaba-pelo-amor-de-deus">😭 Eu não estou suportando mais</option>
                        <option value="caronas">🚗 Caronas</option>
                        <option value="esportes">🏀 Esportes</option>
                        <option value="games">🎮 Games</option>
                    <?php endif; ?>
                </select>
                <?php if ($comunidade_id > 0): ?>
                    <small style="color: #ffbc00; font-size: 0.8rem; display: block; margin-top: 4px;">
                        <i class="fas fa-users"></i> Postando em: <?php echo $comunidade_nome; ?>
                    </small>
                <?php endif; ?>
            </div>

            <!-- Área de texto -->
            <div class="area-texto-vivo area-post-vivo">
                <textarea name="mensagem" id="mensagem-vivo" placeholder="<?php echo $modo_inline ? 'Digite algo para a comunidade...' : 'O que tá rolando na UNIFEV?'; ?>" required maxlength="600"></textarea>
                
                <!-- Grid de anexos -->
                <div id="anexos-grid-post" class="anexos-grid" style="display: none;"></div>
            </div>

            <!-- Barra de ações -->
            <div class="barra-acoes-vivo">
                <div class="acoes-esquerda">
                    <label for="imagem-vivo" class="btn-acao btn-acao-vivo" title="Adicionar imagem">
                        <i class="fas fa-image"></i>
                    </label>
                    <input type="file" name="imagem" id="imagem-vivo" accept="image/*" style="display: none;" multiple>
                    
                    <button type="button" id="btn-gif-vivo" class="btn-acao btn-acao-vivo" title="Buscar GIF/Sticker" onclick="window.setGiphyTarget('gif-url-vivo'); abrirGiphyModal();">
                        <i class="fas fa-grin-tongue-squint"></i>
                    </button>
                    <!-- GIFs são adicionados via JavaScript -->
                </div>

                <div class="acoes-direita">
                    <span class="contador-caracteres" id="contador-vivo">0/600</span>
                    <?php if (!$modo_inline): ?>
                        <button type="button" class="btn-cancelar btn-cancelar-vivo" onclick="fecharModalPostLimpo()">Cancelar</button>
                    <?php endif; ?>
                    <button type="submit" class="btn-lancar btn-lancar-vivo">Publicar</button>
                </div>
            </div>

        </form>

        <?php if (!$modo_inline): ?>
            <div style="margin-top: 8px; text-align: center; font-size: 12px; opacity: 0.6;">
                <small>🔍 Perdeu algo? <a href="perdidos.php" style="color: var(--dourado);">Página Especializada</a></small>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($modo_inline): ?>
        </div><!-- .postar-inline-conteudo -->
    </div><!-- .card-postar-inline-wrapper -->

    <script>
    // ============================================================
    // TOGGLE DO FORMULÁRIO INLINE
    // ============================================================
    function togglePostarInline() {
        const conteudo = document.getElementById('postar-inline-conteudo');
        const icon = document.getElementById('toggle-postar-icon');
        const texto = document.getElementById('toggle-postar-texto');
        if (conteudo.style.display === 'none' || conteudo.style.display === '') {
            conteudo.style.display = 'block';
            icon.className = 'fas fa-chevron-up';
            texto.textContent = 'Cancelar post';
        } else {
            conteudo.style.display = 'none';
            icon.className = 'fas fa-chevron-down';
            texto.textContent = 'Novo post na comunidade';
        }
    }

    // Se houver erro no formulário, mantém aberto
    <?php if (isset($_SESSION['erro_post'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const conteudo = document.getElementById('postar-inline-conteudo');
            const icon = document.getElementById('toggle-postar-icon');
            const texto = document.getElementById('toggle-postar-texto');
            if (conteudo) {
                conteudo.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
                texto.textContent = 'Cancelar post';
            }
        });
    <?php endif; ?>
    </script>
<?php endif; ?>

<!-- ============================================================
    SCRIPTS (compartilhados)
    ============================================================ -->
<script src="js/fenda-mencoes.js"></script>
<script src="js/fenda-giphy.js"></script>

<script>
(function() {
    'use strict';

    const textarea = document.getElementById('mensagem-vivo');
    const inputFile = document.getElementById('imagem-vivo');
    const contador = document.getElementById('contador-vivo');
    const form = document.getElementById('form-postar-vivo');
    const gridElement = document.getElementById('anexos-grid-post');
    const isInline = <?php echo $modo_inline ? 'true' : 'false'; ?>;

    // ============================================================
    // 🖼️ GERENCIADOR DE ANEXOS (MODAL POST)
    // ============================================================
    const ModalAnexos = {
        anexos: [],
        maxItems: 3,
        gridElement: gridElement,

        adicionar(file, tipo = 'imagem', url = null) {
            if (tipo === 'imagem' && file) {
                const maxSize = 2 * 1024 * 1024;
                const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                if (file.size > maxSize) {
                    alert('❌ Arquivo excede o limite de 2MB.');
                    return false;
                }
                if (!tiposPermitidos.includes(file.type)) {
                    alert('❌ Formato não suportado. Use JPG, PNG, WEBP ou GIF.');
                    return false;
                }
            }

            if (tipo === 'gif' && url) {
                const existe = this.anexos.some(item => item.tipo === 'gif' && item.url === url);
                if (existe) {
                    alert('⚠️ Este GIF já foi adicionado.');
                    return false;
                }
            }

            if (this.anexos.length >= this.maxItems) {
                alert(`⚠️ Máximo de ${this.maxItems} anexos por post.`);
                return false;
            }

            const id = 'anexo-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
            const preview = tipo === 'gif' ? url : URL.createObjectURL(file);

            this.anexos.push({
                id,
                tipo,
                file: tipo === 'imagem' ? file : null,
                url: tipo === 'gif' ? url : null,
                preview,
                status: 'pending'
            });

            this.renderizar();
            return true;
        },

        remover(index) {
            if (index < 0 || index >= this.anexos.length) return;
            const item = this.anexos[index];
            if (item.preview && item.tipo === 'imagem') {
                URL.revokeObjectURL(item.preview);
            }
            this.anexos.splice(index, 1);
            this.renderizar();
        },

        limparTodos() {
            this.anexos.forEach(item => {
                if (item.preview && item.tipo === 'imagem') {
                    URL.revokeObjectURL(item.preview);
                }
            });
            this.anexos = [];
            this.renderizar();
        },

        renderizar() {
            if (!this.gridElement) return;
            if (this.anexos.length === 0) {
                this.gridElement.style.display = 'none';
                this.gridElement.innerHTML = '';
                return;
            }
            this.gridElement.style.display = 'flex';
            this.gridElement.innerHTML = '';

            this.anexos.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'anexo-item';
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

                this.gridElement.appendChild(div);
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
    // EVENTOS
    // ============================================================
    textarea.addEventListener('input', function() {
        const len = this.value.length;
        contador.textContent = len + '/600';
        contador.style.color = len >= 550 ? '#ff3c00d0' : '#888';
    });

    inputFile.addEventListener('change', function() {
        if (this.files.length > 0) {
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (ModalAnexos.anexos.length >= ModalAnexos.maxItems) {
                    alert(`⚠️ Máximo de ${ModalAnexos.maxItems} anexos por post.`);
                    break;
                }
                ModalAnexos.adicionar(file, 'imagem');
            }
            this.value = '';
        }
    });

    document.addEventListener('gifSelecionado', function(e) {
        if (e.detail && e.detail.url) {
            ModalAnexos.adicionar(null, 'gif', e.detail.url);
            document.getElementById('gif-url-vivo').value = '';
        }
    });

    // ============================================================
    // ENVIO DO FORMULÁRIO
    // ============================================================
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const texto = textarea.value.trim();
        const temAnexo = ModalAnexos.anexos.length > 0;

        if (texto === '' && !temAnexo) {
            alert('Escreva algo ou adicione uma imagem/GIF antes de publicar.');
            return;
        }

        const formData = new FormData(form);
        ModalAnexos.prepararFormData(formData);

        const btnSubmit = form.querySelector('button[type="submit"]');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = 'Publicando...';

        fetch('enviar-post.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            return response.text();
        })
        .then(data => {
            if (data) {
                console.error('Erro no servidor:', data);
                alert('Erro ao publicar. Tente novamente.');
            }
        })
        .catch(err => {
            console.error('Erro na requisição:', err);
            alert('Erro de conexão. Tente novamente.');
        })
        .finally(() => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
        });
    });

    // ============================================================
    // FECHAR MODAL (APENAS NO MODO MODAL)
    // ============================================================
    <?php if (!$modo_inline): ?>
    window.fecharModalPostLimpo = function() {
        ModalAnexos.limparTodos();
        if (typeof fecharModalPost === 'function') {
            fecharModalPost();
        } else {
            const modal = document.getElementById('modal-postar-fenda');
            if (modal) modal.style.display = 'none';
            document.body.classList.remove('modal-aberto');
            document.body.style.overflow = 'auto';
        }
    };
    <?php else: ?>
    // No modo inline, apenas limpa os anexos e fecha o toggle
    window.fecharModalPostLimpo = function() {
        ModalAnexos.limparTodos();
        const conteudo = document.getElementById('postar-inline-conteudo');
        const icon = document.getElementById('toggle-postar-icon');
        const texto = document.getElementById('toggle-postar-texto');
        if (conteudo) {
            conteudo.style.display = 'none';
            icon.className = 'fas fa-chevron-down';
            texto.textContent = 'Novo post na comunidade';
        }
    };
    <?php endif; ?>

    // Inicializa contador
    contador.textContent = '0/600';

})();
</script>