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
            <div class="area-texto-vivo">
                <textarea name="mensagem" id="mensagem-vivo" placeholder="O que tá rolando na UNIFEV?" required maxlength="600"></textarea>
                
                <div class="previa-midia-vivo" id="previa-midia-vivo"></div>
            </div>

            <!-- Barra de ações -->
            <div class="barra-acoes-vivo">
                <div class="acoes-esquerda">
                    <label for="imagem-vivo" class="btn-acao btn-acao-vivo" title="Adicionar imagem">
                        <i class="fas fa-image"></i>
                    </label>
                    <input type="file" name="imagem" id="imagem-vivo" accept="image/*" style="display: none;">
                    
                    <button type="button" id="btn-gif-vivo" class="btn-acao btn-acao-vivo" title="Buscar GIF/Sticker" onclick="window.setGiphyTarget('gif-url-vivo'); abrirGiphyModal();">
                        <i class="fas fa-grin-tongue-squint"></i>
                    </button>
                    <input type="hidden" name="gif_url" id="gif-url-vivo" value="">
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
        const textarea = document.getElementById('mensagem-vivo');
        const previewMidia = document.getElementById('previa-midia-vivo');
        const inputFile = document.getElementById('imagem-vivo');
        const inputGif = document.getElementById('gif-url-vivo');
        const selectCategoria = document.getElementById('categoria-vivo');
        const contador = document.getElementById('contador-vivo');

        // Atualiza contador
        function atualizarContador() {
            const len = textarea.value.length;
            contador.textContent = len + '/600';
            contador.style.color = len >= 550 ? '#ff3c00d0' : '#888';
        }

        // Atualiza a prévia da mídia
        function atualizarMidia() {
            const gifUrl = inputGif.value.trim();
            const file = inputFile.files[0];

            previewMidia.innerHTML = '';

            if (gifUrl && gifUrl !== '') {
                const wrapper = document.createElement('div');
                wrapper.className = 'midia-wrapper';
                const img = document.createElement('img');
                img.src = gifUrl;
                img.alt = 'GIF/Sticker';
                img.loading = 'lazy';
                wrapper.appendChild(img);
                const btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'btn-remover-midia';
                btnRemove.innerHTML = '✕';
                btnRemove.title = 'Remover GIF';
                btnRemove.onclick = function(e) {
                    e.stopPropagation();
                    inputGif.value = '';
                    inputFile.value = '';
                    atualizarMidia();
                };
                wrapper.appendChild(btnRemove);
                previewMidia.appendChild(wrapper);
            } else if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'midia-wrapper';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Imagem selecionada';
                    img.loading = 'lazy';
                    wrapper.appendChild(img);
                    const btnRemove = document.createElement('button');
                    btnRemove.type = 'button';
                    btnRemove.className = 'btn-remover-midia';
                    btnRemove.innerHTML = '✕';
                    btnRemove.title = 'Remover imagem';
                    btnRemove.onclick = function(e) {
                        e.stopPropagation();
                        inputFile.value = '';
                        inputGif.value = '';
                        atualizarMidia();
                    };
                    wrapper.appendChild(btnRemove);
                    previewMidia.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            }
        }

        // Eventos
        textarea.addEventListener('input', atualizarContador);

        inputFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                inputGif.value = '';
            }
            atualizarMidia();
        });

        inputGif.addEventListener('change', function() {
            if (this.value && this.value !== '') {
                inputFile.value = '';
            }
            atualizarMidia();
        });

        // 🟢 FALLBACK: observa mudanças no input hidden (para casos onde o evento não é disparado)
        const observer = new MutationObserver(function() {
            if (inputGif.value && inputGif.value !== '') {
                atualizarMidia();
            }
        });
        observer.observe(inputGif, { attributes: true, attributeFilter: ['value'] });

        // Evento customizado do GIPHY
        document.addEventListener('gifSelecionado', function(e) {
            if (e.detail && e.detail.url) {
                inputGif.value = e.detail.url;
                inputFile.value = '';
                atualizarMidia();
            }
        });

        // Fechar modal e limpar mídia
        window.fecharModalPostLimpo = function() {
            if (inputFile.files.length > 0 || inputGif.value.trim() !== '') {
                inputFile.value = '';
                inputGif.value = '';
                atualizarMidia();
            }
            if (typeof fecharModalPost === 'function') {
                fecharModalPost();
            } else {
                const modal = document.getElementById('modal-postar-fenda');
                if (modal) modal.style.display = 'none';
                document.body.classList.remove('modal-aberto');
                document.body.style.overflow = 'auto';
            }
        };

        // Inicializa
        atualizarContador();
        atualizarMidia();
    })();
</script>