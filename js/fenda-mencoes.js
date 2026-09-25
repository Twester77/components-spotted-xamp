// fenda-mencoes.js - Versão consolidada com Autocomplete + Contador
// 🐚 IARA – 2026-09-25 (auditoria v5.0)
//    - Lista flutuante agora posiciona ACIMA do input quando não tem
//      espaço embaixo (evita ser coberta pelo teclado mobile).
//    - Removido window.scrollY/scrollX do cálculo (position: fixed
//      é relativo ao viewport, não ao documento).
//    - Usa visualViewport.height pra medir o espaço "visível" real
//      (considera teclado aberto).

window.timerBusca = window.timerBusca || null;

document.addEventListener('input', (e) => {
    if (e.target.tagName.toLowerCase() === 'textarea') {
        const campo = e.target;

        // 1. Contador Universal (Busca o contador pelo ID char-count na página)
        const contador = document.getElementById('char-count');
        if (contador) {
            const limite = parseInt(campo.getAttribute('maxlength')) || 800;
            const restante = limite - campo.value.length;
            contador.textContent = restante;
            contador.style.color = restante < 50 ? '#fa2f2f' : '#ffffff';
        }

        // 2. Autocomplete de @ com Debounce (os 200ms)
        clearTimeout(timerBusca);
        timerBusca = setTimeout(() => {
            const textoAteCursor = campo.value.substring(0, campo.selectionStart);
            const match = textoAteCursor.match(/@(\w*)$/);

            if (match) {
                mostrarSugestoes(match[1], campo);
            } else {
                esconderSugestoes();
            }
        }, 200);
    }
});

// --- Funções de Suporte ---

function mostrarSugestoes(termo, campo) {
    fetch('buscar-mencoes.php?q=' + encodeURIComponent(termo))
        .then(response => response.text())
        .then(texto => {
            if (!texto || texto.trim() === "") return;

            try {
                const data = JSON.parse(texto);
                if (data.length > 0) {
                    renderizarLista(data, campo);
                } else {
                    esconderSugestoes();
                }
            } catch (e) {
                console.warn("Resposta não é JSON válido, ignorando...");
            }
        })
        .catch(error => {
            console.error('Erro na conexão:', error);
        });
}

function renderizarLista(usuarios, campo) {
    let divLista = document.getElementById('lista-mencoes');
    if (!divLista) {
        divLista = document.createElement('div');
        divLista.id = 'lista-mencoes';
        divLista.className = 'lista-mencoes-flutuante';
        document.body.appendChild(divLista);
    }

    const isModal = campo.closest('.form-container') !== null;
    divLista.classList.toggle('modal-theme', isModal);

    divLista.innerHTML = '';
    const rect = campo.getBoundingClientRect();

    // 🔥 IARA: primeiro renderiza vazio pra medir a altura
    divLista.style.visibility = 'hidden';
    divLista.style.display = 'block';
    divLista.style.top = '0px';
    divLista.style.left = '0px';

    usuarios.forEach(user => {
        const item = document.createElement('div');
        item.textContent = '@' + user;
        item.onclick = (e) => {
            e.stopPropagation();
            campo.value = campo.value.replace(/@\w*$/, '@' + user + ' ');
            esconderSugestoes();
            campo.focus();
        };
        divLista.appendChild(item);
    });

    // 🔥 IARA: medir depois de renderizar
    const listHeight = divLista.offsetHeight;
    const listWidth = divLista.offsetWidth || 200;

    // 🔥 IARA: usar visualViewport (considera teclado aberto no mobile)
    const vvHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;

    // Espaços disponíveis em cada direção
    const spaceBelow = vvHeight - rect.bottom;
    const spaceAbove = rect.top;
    const GAP = 5;

    // Decisão: cabe embaixo? Se sim, usa. Senão, usa cima (se couber).
    let top;
    if (spaceBelow >= listHeight + GAP) {
        top = rect.bottom + GAP;
    } else if (spaceAbove >= listHeight + GAP) {
        top = rect.top - listHeight - GAP;
    } else {
        // Não cabe em nenhum lado — usa o lado com mais espaço
        top = (spaceBelow >= spaceAbove) ? (vvHeight - listHeight - GAP) : GAP;
    }

    // Horizontal: alinha com o input, mas não deixa vazar da tela
    let left = rect.left;
    if (left + listWidth > window.innerWidth - 10) {
        left = window.innerWidth - listWidth - 10;
    }
    if (left < 10) left = 10;

    // 🔥 IMPORTANTE: position:fixed = NÃO somar scrollY/scrollX
    divLista.style.top = top + 'px';
    divLista.style.left = left + 'px';
    divLista.style.visibility = 'visible';
}

function esconderSugestoes() {
    const div = document.getElementById('lista-mencoes');
    if (div) div.style.display = 'none';
}

document.addEventListener('click', function(e) {
    const divLista = document.getElementById('lista-mencoes');
    if (divLista && divLista.style.display === 'block') {
        if (!divLista.contains(e.target) && e.target.tagName !== 'TEXTAREA') {
            esconderSugestoes();
        }
    }
});

window.addEventListener('scroll', function() {
    const divLista = document.getElementById('lista-mencoes');
    if (divLista && divLista.style.display === 'block') {
        esconderSugestoes();
    }
}, true);