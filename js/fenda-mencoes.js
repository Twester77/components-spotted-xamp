// fenda-mencoes.js - Versão consolidada com Autocomplete + Contador
window.timerBusca = window.timerBusca || null; // Evita redeclaração
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

// --- Funções de Suporte (As mesmas que você criou) ---

function mostrarSugestoes(termo, campo) {
    fetch('buscar-mencoes.php?q=' + encodeURIComponent(termo))
    .then(response => response.text())
    .then(texto => {
        if (!texto || texto.trim() === "") return;

        try {
            const data = JSON.parse(texto);
            if (data.length > 0) {
                // CORRIGIDO: Agora chama o nome real da função estruturada abaixo
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
    
    divLista.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    divLista.style.left = (rect.left + window.scrollX) + 'px';
    divLista.style.display = 'block';

    usuarios.forEach(user => {
        let item = document.createElement('div');
        item.textContent = '@' + user;
        item.onclick = (e) => {
            e.stopPropagation();
            campo.value = campo.value.replace(/@\w*$/, '@' + user + ' ');
            esconderSugestoes();
            campo.focus();
        };
        divLista.appendChild(item);
    });
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



