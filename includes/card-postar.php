<?php

/**
 * card-postar.php – Formulário de criação de posts (modal + inline)
 * 
 * 🔥 VERSÃO COM SINGLETON – evita duplicidade de inicialização
 * 
 * Modo de uso:
 * - Modal: chamado normalmente via include
 * - Inline: passe ?modo=inline&comunidade_id=X via GET
 */

// Detecta modo inline via GET ou variável pré-definida
$modo_inline = isset($_GET['modo']) && $_GET['modo'] === 'inline';
$comunidade_id = isset($_GET['comunidade_id']) ? (int)$_GET['comunidade_id'] : 0;
$comunidade_nome = isset($_GET['comunidade_nome']) ? htmlspecialchars($_GET['comunidade_nome']) : 'Comunidade';

// Se a variável $modo_inline já foi definida (ex: via include no comunidade.php)
if (isset($modo_inline) && $modo_inline === true) {
    // Já está definido
} else {
    $modo_inline = false;
}

// Se a variável $comunidade_id já foi definida (ex: via include no comunidade.php)
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
                            <input type="hidden" name="gif_url" id="gif-url-vivo" value="">
                        </div>

                        <div class="acoes-direita">
                            <span class="contador-caracteres" id="contador-vivo">0/600</span>
                            <?php if (!$modo_inline): ?>
                                <button type="button" class="btn-cancelar btn-cancelar-vivo" onclick="fecharModalPostLimpo()">Cancelar</button>
                            <?php endif; ?>
                            <button type="submit" class="btn-lancar btn-lancar-vivo" id="btn-publicar-vivo">Publicar</button>
                        </div>
                    </div>

                </form>

                <?php if (!$modo_inline): ?>
                    <div class="link-pagina-especializada">
                        <small>🔍 Perdeu algo? <a href="perdidos.php">Página Especializada</a></small>
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

        // ============================================================
        // 🔥 SINGLETON: verifica se já foi inicializado
        // ============================================================
        if (window._PostAnexosInicializado) {
            console.log('[card-postar] Já inicializado. Reutilizando instância existente.');
            return; // Sai da função, não cria nada novo
        }
        window._PostAnexosInicializado = true;
        console.log('[card-postar] Primeira inicialização. Criando instância...');

        // ============================================================
        // 🔥 FUNÇÃO DO BALÃO DE FALA (contextual)
        // ============================================================
        function exibirBalao(mensagem, tipo, elementoRef, duracao = 2500) {
            const balaoAntigo = document.querySelector('.balao-fenda');
            if (balaoAntigo) balaoAntigo.remove();

            const balao = document.createElement('div');
            balao.className = 'balao-fenda ' + tipo;

            const icones = {
                sucesso: '✅',
                erro: '❌',
                info: 'ℹ️'
            };
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
        // ELEMENTOS (com verificação de existência)
        // ============================================================
        const textarea = document.getElementById('mensagem-vivo');
        const inputFile = document.getElementById('imagem-vivo');
        const contador = document.getElementById('contador-vivo');
        const form = document.getElementById('form-postar-vivo');
        const gridElement = document.getElementById('anexos-grid-post');
        const btnPublicar = document.getElementById('btn-publicar-vivo');
        const gifHiddenInput = document.getElementById('gif-url-vivo');
        const isInline = <?php echo $modo_inline ? 'true' : 'false'; ?>;

        console.log('[card-postar] Modo:', isInline ? 'inline' : 'modal');
        console.log('[card-postar] Elementos encontrados:', {
            textarea: !!textarea,
            inputFile: !!inputFile,
            contador: !!contador,
            form: !!form,
            gridElement: !!gridElement,
            btnPublicar: !!btnPublicar,
            gifHiddenInput: !!gifHiddenInput
        });

        if (!textarea || !form || !btnPublicar) {
            console.error('[card-postar] Elementos essenciais não encontrados. Abortando.');
            return;
        }

        // ============================================================
        // 🔥 GERENCIADOR DE ANEXOS (PostAnexos)
        // ============================================================
        const PostAnexos = {
            anexos: [],
            maxItems: 4,
            gridElement: gridElement,

            adicionar(file, tipo = 'imagem', url = null) {
                console.log('[PostAnexos] adicionar() chamado com:', {
                    tipo,
                    url: url || 'N/A',
                    file: file ? file.name : 'N/A'
                });
                console.log('[PostAnexos] Estado atual:', this.anexos.map(a => a.tipo + (a.url ? ' (GIF)' : ' (IMG)')));

                if (tipo === 'imagem' && file) {
                    const maxSize = 2 * 1024 * 1024;
                    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                    if (file.size > maxSize) {
                        exibirBalao(`Arquivo excede 2MB (${(file.size / 1024 / 1024).toFixed(1)}MB)`, 'erro', btnPublicar);
                        return false;
                    }
                    if (!tiposPermitidos.includes(file.type)) {
                        exibirBalao('Formato não suportado. Use JPG, PNG, WEBP ou GIF.', 'erro', btnPublicar);
                        return false;
                    }
                    const tamanhoKB = Math.round(file.size / 1024);
                    exibirBalao(`Arquivo aceito (${tamanhoKB} KB)`, 'sucesso', btnPublicar);
                    // 🔥 IMPORTANTE: se adicionar imagem, NÃO limpa o gifHiddenInput
                }

                if (tipo === 'gif' && url) {
                    const existe = this.anexos.some(item => item.tipo === 'gif' && item.url === url);
                    if (existe) {
                        exibirBalao('Este GIF já foi adicionado.', 'info', btnPublicar);
                        return false;
                    }
                    exibirBalao('GIF adicionado!', 'sucesso', btnPublicar);
                    if (inputFile) inputFile.value = ''; // limpa seleção de arquivo, mas NÃO remove imagens já adicionadas
                }

                if (this.anexos.length >= this.maxItems) {
                    exibirBalao(`Máximo de ${this.maxItems} anexos por post.`, 'erro', btnPublicar);
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

                console.log('[PostAnexos] Novo estado:', this.anexos.map(a => a.tipo + (a.url ? ' (GIF)' : ' (IMG)')));
                this.renderizar();
                this.verificarConteudo();
                return true;
            },

            remover(index) {
                if (index < 0 || index >= this.anexos.length) return;
                const item = this.anexos[index];
                if (item.preview && item.tipo === 'imagem') {
                    URL.revokeObjectURL(item.preview);
                }
                this.anexos.splice(index, 1);
                console.log('[PostAnexos] removido índice', index, '. Novo estado:', this.anexos.map(a => a.tipo));
                this.renderizar();
                this.verificarConteudo();
            },

            limparTodos() {
                this.anexos.forEach(item => {
                    if (item.preview && item.tipo === 'imagem') {
                        URL.revokeObjectURL(item.preview);
                    }
                });
                this.anexos = [];
                console.log('[PostAnexos] Todos os anexos removidos.');
                this.renderizar();
                this.verificarConteudo();
            },

            renderizar() {
                if (!this.gridElement) {
                    console.warn('[PostAnexos] gridElement não encontrado.');
                    return;
                }
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

            verificarConteudo() {
                const temTexto = textarea.value.trim().length > 0;
                const temAnexo = this.anexos.length > 0;
                if (temTexto || temAnexo) {
                    btnPublicar.disabled = false;
                    btnPublicar.style.opacity = '1';
                } else {
                    btnPublicar.disabled = true;
                    btnPublicar.style.opacity = '0.5';
                }
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
            PostAnexos.verificarConteudo();
        });

        if (inputFile) {
            inputFile.addEventListener('change', function() {
                console.log('[inputFile] change disparado. Files:', this.files.length);
                if (this.files.length > 0) {
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        if (PostAnexos.anexos.length >= PostAnexos.maxItems) {
                            exibirBalao(`Máximo de ${PostAnexos.maxItems} anexos.`, 'erro', btnPublicar);
                            break;
                        }
                        PostAnexos.adicionar(file, 'imagem');
                    }
                    this.value = '';
                }
            });
        }

        document.addEventListener('gifSelecionado', function(e) {
            console.log('[gifSelecionado] Evento recebido:', e.detail);
            if (e.detail && e.detail.url) {
                if (gifHiddenInput) {
                    gifHiddenInput.value = e.detail.url;
                    console.log('[gifHiddenInput] valor atualizado para:', e.detail.url);
                } else {
                    console.warn('[gifHiddenInput] campo não encontrado.');
                }
                PostAnexos.adicionar(null, 'gif', e.detail.url);
            }
        });

        // ============================================================
        // ENVIO DO FORMULÁRIO
        // ============================================================
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const texto = textarea.value.trim();
            const temAnexo = PostAnexos.anexos.length > 0;

            if (texto === '' && !temAnexo) {
                exibirBalao('Escreva algo ou adicione uma imagem/GIF.', 'info', btnPublicar);
                return;
            }

            const formData = new FormData(form);
            PostAnexos.prepararFormData(formData);

            btnPublicar.disabled = true;
            const originalText = btnPublicar.innerHTML;
            btnPublicar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            exibirBalao('Enviando post...', 'info', btnPublicar, 1500);

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
                        exibirBalao('Erro ao publicar. Tente novamente.', 'erro', btnPublicar);
                    } else {
                        exibirBalao('Post publicado com sucesso! 🎉', 'sucesso', btnPublicar);
                        PostAnexos.limparTodos();
                        textarea.value = '';
                        contador.textContent = '0/600';
                        PostAnexos.verificarConteudo();
                        if (typeof fecharModalPostLimpo === 'function') fecharModalPostLimpo();
                    }
                })
                .catch(err => {
                    console.error('Erro na requisição:', err);
                    exibirBalao('Erro de conexão. Tente novamente.', 'erro', btnPublicar);
                })
                .finally(() => {
                    btnPublicar.disabled = false;
                    btnPublicar.innerHTML = originalText;
                });
        });

        // ============================================================
        // FECHAR MODAL (apenas no modo modal)
        // ============================================================
        <?php if (!$modo_inline): ?>
            window.fecharModalPostLimpo = function() {
                PostAnexos.limparTodos();
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
            window.fecharModalPostLimpo = function() {
                PostAnexos.limparTodos();
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

        // Inicializa
        contador.textContent = '0/600';
        PostAnexos.verificarConteudo();
        window.PostAnexos = PostAnexos;
        console.log('[card-postar] Inicialização concluída. PostAnexos disponível globalmente.');

    })();
</script>