<section id="postar" class="main-novo-post">
    <div class="form-container form-container-vivo">

        <form action="enviar-post.php" method="POST" enctype="multipart/form-data" id="form-postar-vivo">

            <!-- Categoria -->
            <div class="campo-categoria-vivo">
                <select name="categoria" id="categoria-vivo" aria-label="Selecione a categoria">
                    <option value="anonimo">🕵️ Anônimo</option>
                    <option value="comunidade">👥 Comunidade</option>
                    <option value="academico">❓ Dúvidas Acadêmicas</option>
                    <option value="elogio">💖 Correio Elegante</option>
                    <option value="tenho-ranco">👌 Ranço</option>
                    <option value="acaba-pelo-amor-de-deus">😭 Eu não estou suportando mais</option>
                    <option value="caronas">🚗 Caronas</option>
                    <option value="esportes">🏀 Esportes</option>
                    <option value="games">🎮 Games</option>
                </select>
            </div>

            <!-- Área de texto -->
            <div class="area-texto-vivo area-post-vivo">
                <textarea name="mensagem" id="mensagem-vivo" placeholder="O que tá rolando na UNIFEV?" required maxlength="600"></textarea>
                
                <!-- 🔥 NOVO GRID DE ANEXOS (substitui a prévia única) -->
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
                    <!-- GIFs são adicionados via JavaScript, não via input hidden -->
                </div>

                <div class="acoes-direita">
                    <span class="contador-caracteres" id="contador-vivo">0/600</span>
                    <button type="button" class="btn-cancelar btn-cancelar-vivo" onclick="fecharModalPostLimpo()">Cancelar</button>
                    <button type="submit" class="btn-lancar btn-lancar-vivo">Publicar</button>
                </div>
            </div>

        </form>

        <div style="margin-top: 8px; text-align: center; font-size: 12px; opacity: 0.6;">
            <small>🔍 Perdeu algo? <a href="perdidos.php" style="color: var(--dourado);">Página Especializada</a></small>
        </div>
    </div>
</section>

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

    // ============================================================
    // 🖼️ GERENCIADOR DE ANEXOS (MODAL POST)
    // ============================================================
    const ModalAnexos = {
        anexos: [],
        maxItems: 3,
        gridElement: gridElement,

        // Adiciona um anexo (imagem ou GIF)
        adicionar(file, tipo = 'imagem', url = null) {
            // Validação de tamanho e tipo para imagens
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

            // Verifica duplicata de GIF
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

        // Remove um anexo pelo índice
        remover(index) {
            if (index < 0 || index >= this.anexos.length) return;
            const item = this.anexos[index];
            if (item.preview && item.tipo === 'imagem') {
                URL.revokeObjectURL(item.preview);
            }
            this.anexos.splice(index, 1);
            this.renderizar();
        },

        // Limpa todos os anexos
        limparTodos() {
            this.anexos.forEach(item => {
                if (item.preview && item.tipo === 'imagem') {
                    URL.revokeObjectURL(item.preview);
                }
            });
            this.anexos = [];
            this.renderizar();
        },

        // Renderiza o grid
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

        // Prepara o FormData com todos os anexos
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

    // Atualiza contador de caracteres
    textarea.addEventListener('input', function() {
        const len = this.value.length;
        contador.textContent = len + '/600';
        contador.style.color = len >= 550 ? '#ff3c00d0' : '#888';
    });

    // Input file (múltiplos arquivos)
    inputFile.addEventListener('change', function() {
        if (this.files.length > 0) {
            // Adiciona cada arquivo selecionado
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                // Se atingiu o limite, para de adicionar
                if (ModalAnexos.anexos.length >= ModalAnexos.maxItems) {
                    alert(`⚠️ Máximo de ${ModalAnexos.maxItems} anexos por post.`);
                    break;
                }
                ModalAnexos.adicionar(file, 'imagem');
            }
            // Limpa o input para permitir re-selecionar os mesmos arquivos
            this.value = '';
        }
    });

    // Evento customizado do GIPHY (quando um GIF é selecionado)
    document.addEventListener('gifSelecionado', function(e) {
        if (e.detail && e.detail.url) {
            // Adiciona o GIF ao grid
            ModalAnexos.adicionar(null, 'gif', e.detail.url);
            // Limpa o campo hidden (se houver) – não precisamos mais
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

        // Monta o FormData
        const formData = new FormData(form);

        // 🔥 Adiciona os anexos (imagens e GIFs) via ModalAnexos
        ModalAnexos.prepararFormData(formData);

        // Remove o campo 'imagem' que pode estar vazio ou conflitar
        // Não removemos, pois o PHP vai ignorar se não houver arquivo.

        // Desabilita botão de envio
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
                // Se o PHP redirecionou, significa sucesso ou erro com session
                window.location.href = response.url;
                return;
            }
            return response.text();
        })
        .then(data => {
            // Se chegou aqui, algo deu errado (erro no PHP)
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
    // FECHAR MODAL E LIMPAR ANEXOS
    // ============================================================
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

    // Inicializa contador
    contador.textContent = '0/600';
})();
</script>