// ==================== INTEGRAÇÃO COM GIPHY API (GIFs e Stickers) ====================
(function() {
    'use strict';

    // 🔑 SUA API KEY (substitua pela sua)
    const GIPHY_API_KEY = 'lGufKJv8wtztDnSovFTMFHhcJm1kofhL';

    // Endpoints da API
    const ENDPOINTS = {
        gifs: 'https://api.giphy.com/v1/gifs/search',
        stickers: 'https://api.giphy.com/v1/stickers/search'
    };

    // Configurações
    const CONFIG = {
        limit: 20,
        debounceMs: 500,
        rating: 'g'
    };

    let activeModal = null;
    let currentTab = 'gifs';
    let searchInput = null;
    let resultsContainer = null;
    let debounceTimer = null;

    // 🔥 ALVO DINÂMICO: ID do campo hidden (padrão para comentários)
    let gifTargetInputId = 'hidden-gif-url';

    // Função pública para mudar o alvo (chamada antes de abrir o modal)
    window.setGiphyTarget = function(targetId) {
        gifTargetInputId = targetId;
    };

    // Cria o modal de busca de GIFs (estilos inline)
    function criarModal() {
        if (activeModal) return activeModal;

        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 40000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, sans-serif;
        `;

        const modal = document.createElement('div');
        modal.style.cssText = `
            background: rgba(36, 36, 58, 0.45);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            width: 85%;
            max-width: 650px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255, 140, 0, 0.5);
            box-shadow: 0 25px 45px rgba(0,0,0,0.4);
        `;

        // Cabeçalho
        const header = document.createElement('div');
        header.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        `;
        const title = document.createElement('h3');
        title.textContent = 'Escolha um GIF ou Sticker';
        title.style.cssText = 'margin:0; color:#fff; font-size:1.2rem;';
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '✖';
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: #fff;
            font-size: 1.4rem;
            cursor: pointer;
            padding: 0 8px;
        `;
        closeBtn.onclick = fecharModal;
        header.appendChild(title);
        header.appendChild(closeBtn);

        // Abas
        const tabs = document.createElement('div');
        tabs.style.cssText = `
            display: flex;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        `;
        const tabGifs = document.createElement('button');
        tabGifs.textContent = 'GIFs';
        tabGifs.style.cssText = `
            flex: 1;
            background: none;
            border: none;
            padding: 12px;
            color: #fff;
            cursor: pointer;
            transition: 0.3s;
            font-weight: bold;
        `;
        const tabStickers = document.createElement('button');
        tabStickers.textContent = 'Stickers';
        tabStickers.style.cssText = `
            flex: 1;
            background: none;
            border: none;
            padding: 12px;
            color: #fff;
            cursor: pointer;
            transition: 0.2s;
            font-weight: bold;
        `;
        
        function setActiveTab(active) {
            tabGifs.style.background = active === 'gifs' ? 'rgba(255,140,0,0.3)' : 'none';
            tabGifs.style.borderBottom = active === 'gifs' ? '2px solid #ff8c00' : 'none';
            tabStickers.style.background = active === 'stickers' ? 'rgba(255,140,0,0.3)' : 'none';
            tabStickers.style.borderBottom = active === 'stickers' ? '2px solid #ff8c00' : 'none';
        }
        setActiveTab('gifs');

        tabGifs.onclick = () => {
            currentTab = 'gifs';
            setActiveTab('gifs');
            realizarBusca();
        };
        tabStickers.onclick = () => {
            currentTab = 'stickers';
            setActiveTab('stickers');
            realizarBusca();
        };
        tabs.appendChild(tabGifs);
        tabs.appendChild(tabStickers);

        // Barra de busca
        const searchBar = document.createElement('div');
        searchBar.style.cssText = 'padding: 16px;';
        searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar...';
        searchInput.style.cssText = `
            width: 100%;
            padding: 10px 16px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 40px;
            color: #fff;
            font-size: 1rem;
            outline: none;
        `;
        searchInput.addEventListener('input', () => {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => realizarBusca(), CONFIG.debounceMs);
        });
        searchBar.appendChild(searchInput);

        // Container dos resultados (rolável)
        resultsContainer = document.createElement('div');
        resultsContainer.style.cssText = `
            overflow-y: auto;
            padding: 12px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            max-height: 40vh;
        `;

        modal.appendChild(header);
        modal.appendChild(tabs);
        modal.appendChild(searchBar);
        modal.appendChild(resultsContainer);
        overlay.appendChild(modal);

        document.body.appendChild(overlay);
        activeModal = overlay;

        realizarBusca();
        return overlay;
    }

    async function realizarBusca() {
        if (!resultsContainer) return;
        const query = searchInput.value.trim();
        const endpoint = currentTab === 'gifs' ? ENDPOINTS.gifs : ENDPOINTS.stickers;
        
        let url = `${endpoint}?api_key=${GIPHY_API_KEY}&limit=${CONFIG.limit}&rating=${CONFIG.rating}`;
        if (query) {
            url += `&q=${encodeURIComponent(query)}`;
        } else {
            url = `${currentTab === 'gifs' ? 'https://api.giphy.com/v1/gifs/trending' : 'https://api.giphy.com/v1/stickers/trending'}?api_key=${GIPHY_API_KEY}&limit=${CONFIG.limit}&rating=${CONFIG.rating}`;
        }

        resultsContainer.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#aaa;">Carregando...</div>';

        try {
            const response = await fetch(url);
            const data = await response.json();
            if (data.data && data.data.length) {
                mostrarResultados(data.data);
            } else {
                resultsContainer.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#aaa;">Nenhum resultado encontrado.</div>';
            }
        } catch (err) {
            console.error('Erro ao buscar GIFs:', err);
            resultsContainer.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#aaa;">Erro ao carregar. Tente novamente.</div>';
        }
    }

    function mostrarResultados(gifs) {
        resultsContainer.innerHTML = '';
        gifs.forEach(gif => {
            const imgUrl = gif.images.fixed_height_downsampled?.url || gif.images.original?.url;
            if (!imgUrl) return;

            const item = document.createElement('div');
            item.style.cssText = `
                cursor: pointer;
                border-radius: 12px;
                overflow: hidden;
                transition: transform 0.2s;
                background: rgba(0,0,0,0.3);
            `;
            item.onmouseenter = () => item.style.transform = 'scale(1.02)';
            item.onmouseleave = () => item.style.transform = 'scale(1)';
            item.onclick = () => selecionarGif(imgUrl);

            const img = document.createElement('img');
            img.src = imgUrl;
            img.style.cssText = 'width: 100%; height: auto; display: block;';
            item.appendChild(img);
            resultsContainer.appendChild(item);
        });
    }

    function selecionarGif(url) {
        fecharModal();
        
        // 🔥 Usa o target dinâmico (pode ser de comentário ou post)
        let hiddenGifUrl = document.getElementById(gifTargetInputId);
        if (!hiddenGifUrl) {
            hiddenGifUrl = document.createElement('input');
            hiddenGifUrl.type = 'hidden';
            hiddenGifUrl.id = gifTargetInputId;
            hiddenGifUrl.name = 'gif_url';
            
            // Tenta encontrar o formulário correto (comentário ou post)
            const form = document.getElementById('form-comentario') || document.querySelector('#modal-postar-fenda form');
            if (form) form.appendChild(hiddenGifUrl);
        }
        hiddenGifUrl.value = url;
        
        // Opcional: preenche um placeholder no campo de texto adequado
        const campoTexto = document.querySelector('.textarea-chat') || document.querySelector('#modal-postar-fenda textarea');
        if (campoTexto && campoTexto.value.trim() === '') {
            campoTexto.value = '🎬 GIF enviado';
            campoTexto.dispatchEvent(new Event('input'));
        }
        campoTexto?.focus();
    }

    function fecharModal() {
        if (activeModal) {
            activeModal.remove();
            activeModal = null;
        }
    }

    window.abrirGiphyModal = function() {
        criarModal();
    };

    // Limpeza do hidden após envio (para comentários)
    function limparHiddenGif() {
        const hidden = document.getElementById('hidden-gif-url');
        if (hidden) hidden.value = '';
    }

    const observerEnvio = new MutationObserver(() => {
        const btnEnviar = document.querySelector('.btn-enviar-chat');
        if (btnEnviar && !btnEnviar._giphyHook) {
            btnEnviar._giphyHook = true;
            btnEnviar.addEventListener('click', () => {
                setTimeout(limparHiddenGif, 500);
            });
        }
    });
    observerEnvio.observe(document.body, { childList: true, subtree: true });
})();