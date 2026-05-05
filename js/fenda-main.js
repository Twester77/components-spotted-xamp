/* A FENDA - ENGINE CENTRAL (JavaScript Geral) */

// 1. FUNÇÕES GLOBAIS DE CONTROLE
window.audioLiberado = false; // só pra não bugar

window.setBolhasLocal = function (valor) {
    // 1. Atualiza o input hidden para que o PHP saiba o que salvar no banco
    const inputHidden = document.getElementById('input_pref_bolhas');
    if (inputHidden) {
        inputHidden.value = valor;
    }

    // 2. Gerencia as classes 'active' para o visual mudar na hora
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');

    if (valor === 1) {
        btnOn.classList.add('active');
        btnOff.classList.remove('active');
    } else {
        btnOff.classList.add('active');
        btnOn.classList.remove('active');
    }

    // 3. (Opcional) Se você quiser que as bolhas sumam ou apareçam sem dar F5:
    const containerBolhas = document.querySelector('.bubbles-container');
    if (containerBolhas) {
        containerBolhas.style.display = (valor === 1) ? 'block' : 'none';
    }
};

window.atualizarInterfaceBolhas = function (ligar) {
    const btnOn = document.getElementById('btn-bolhas-on');
    const btnOff = document.getElementById('btn-bolhas-off');

    if (btnOn && btnOff) {
        if (ligar) {
            btnOn.style.backgroundColor = '#00a896';
            btnOff.style.backgroundColor = 'transparent';
        } else {
            btnOn.style.backgroundColor = 'transparent';
            btnOff.style.backgroundColor = '#ff4444';
        }
    }
};

// --- CONTROLE DA TOOLBAR (BÚSSOLA) ---
window.toggleToolbar = function () {
    const toolbar = document.getElementById('fenda-toolbar');
    const icon = document.getElementById('trigger-icon');
    if (!toolbar) return;

    toolbar.classList.toggle('toolbar-aberta');

    if (toolbar.classList.contains('toolbar-aberta')) {
        icon.innerText = '❌';
    } else {
        icon.innerText = '🧭';
    }
};

window.toggleHackerMode = function () {
    const body = document.body;
    const btnNav = document.getElementById('hacker-toggle');
    const btnToolbar = document.getElementById('hacker-toggle-lateral');

    body.classList.toggle('hacker-mode');
    const isHacker = body.classList.contains('hacker-mode');

    localStorage.setItem('fenda_hacker', isHacker ? 'active' : 'inactive');

    const texto = isHacker ? '[ DESLIGAR_TERMINAL ]' : '[ ACESSAR_TERMINAL ]';

    if (btnNav) btnNav.innerHTML = texto;
    if (btnToolbar) btnToolbar.innerHTML = isHacker ? 'MODO_NORMAL' : 'MODO_TERMINAL';
    
    // *** NOVO: Remove as bordas inline ao ativar/desativar o modo hacker ***
    window.removerBordasInlineHacker();
};


// --- FUNÇÃO PARA REMOVER BORDAS INLINE NO MODO HACKER ---
window.removerBordasInlineHacker = function() {
    // Só executa se o modo hacker estiver ativo
    if (!document.body.classList.contains('hacker-mode')) return;
    
    // Remove os estilos inline de border-left e border-right de todos os cards
    document.querySelectorAll('.spotted-card').forEach(card => {
        // Limpa os estilos inline que causam conflito
        card.style.borderLeft = '';
        card.style.borderRight = '';
        card.style.borderLeftColor = '';
        card.style.borderRightColor = '';
        card.style.border = '';
    });
    
    // Também limpa os comentários, se necessário
    document.querySelectorAll('.comentario-item').forEach(comentario => {
        comentario.style.borderLeft = '';
        comentario.style.borderRight = '';
        comentario.style.borderLeftColor = '';
        comentario.style.borderRightColor = '';
    });
};


/*------MODAL DE LOGOUT------*/
window.deslogar = function () {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'flex';
};

window.fecharModalSair = function () {
    const modal = document.getElementById('modal-sair-fenda');
    if (modal) modal.style.display = 'none';
};

function confirmarExclusao(idPost) {
    if (confirm(" ATENÇÃO: Deseja mesmo apagar esse registro?")) {
        window.location.href = 'includes/excluir.php?id=' + idPost;
    }
}

function abrirDenuncia(idPost) {
    alert("Denúncia do post #" + idPost + " enviada aos ADMs.");
}

// Abrir o Modal de Postagem
function abrirModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-aberto'); // Isso esconde o FAB (via CSS que você já tem)
        document.body.style.overflow = 'hidden'; // Trava o scroll da página ao fundo
    }
}

// Fechar o Modal de Postagem
function fecharModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-aberto'); // Mostra o FAB de volta
        document.body.style.overflow = 'auto'; // Destrava o scroll
    }
}

// Fechar ao clicar fora da caixa preta
window.addEventListener('click', function(event) {
    const modalPost = document.getElementById('modal-postar-fenda');
    if (event.target === modalPost) {
        fecharModalPost();
    }
});

// ... (outras funções acima)

// Fechar o Modal de Postagem
function fecharModalPost() {
    const modal = document.getElementById('modal-postar-fenda');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-aberto'); 
        document.body.style.overflow = 'auto';
    }
}

// === NOVA FUNÇÃO: GAVETA DE CONTROLE (MOBILE) ===
window.abrirGavetaControle = function() {
    const gaveta = document.getElementById('gaveta-pessoal');
    if (gaveta) {
        // Verifica o estado atual computado para alternar
        const displayAtual = window.getComputedStyle(gaveta).display;
        
        if (displayAtual === 'none') {
            gaveta.style.display = 'block';
            // Scroll suave no celular para o usuário não se perder
            gaveta.scrollIntoView({ behavior: 'smooth' });
        } else {
            gaveta.style.display = 'none';
        }
    }
};


window.toggleMenu = function (menuId) {
    document.querySelectorAll('.options-menu-popup').forEach(menu => {
        if (menu.id !== menuId) menu.style.display = 'none';
    });
    const menu = document.getElementById(menuId);
    if (menu) menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
};

// 2. INICIALIZAÇÃO E EVENTOS

// Gatilho simples para liberar o áudio
const destravarAudio = () => {
    window.audioLiberado = true;
    console.log("Áudio autorizado pelo usuário.");
    document.removeEventListener('click', destravarAudio);
    document.removeEventListener('touchstart', destravarAudio);
};
document.addEventListener('click', destravarAudio);
document.addEventListener('touchstart', destravarAudio);

document.addEventListener("DOMContentLoaded", function () {
    if (typeof atualizarInterfaceBolhas === 'function') atualizarInterfaceBolhas();
    // Libera o áudio pro Chrome no primeiro clique


    // --- ---
    const dropdownLinks = document.querySelectorAll('.menu-item.dropdown > a');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (window.innerWidth <= 1024) {
                const pai = this.parentElement;
                const estaAberto = pai.classList.contains('active');
                if (!estaAberto) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
                    pai.classList.add('active');
                }
            }
        });
    });
    // --- FIM DO BLOCO ---

    let somAmbiente = localStorage.getItem('fenda_tipo_som') || 'off';
    let temaNotif = localStorage.getItem('fenda_tema_notif') || 'padrao';

    window.atualizarInterfaceAudio = function () {
        document.querySelectorAll('[id^="btn-som-"]').forEach(btn => btn.classList.remove('active'));
        const btnM = document.getElementById('btn-som-' + somAmbiente);
        if (btnM) btnM.classList.add('active');
        document.querySelectorAll('[id^="btn-notif-"]').forEach(btn => btn.classList.remove('active'));
        const btnN = document.getElementById('btn-notif-' + temaNotif);
        if (btnN) btnN.classList.add('active');
    };
    atualizarInterfaceAudio();

    window.mudarSomAmbiente = function (tipo) {
        somAmbiente = tipo;
        localStorage.setItem('fenda_tipo_som', tipo);
        let audio = document.getElementById('som-oceano');
        if (audio) {
            if (tipo === 'off') { audio.pause(); }
            else {
                audio.src = (tipo === 'chuva') ? 'sons/chuva.mp3' : 'sons/oceano.mp3';
                audio.volume = 0.02;
                audio.play().catch(() => { });
            }
        }
        atualizarInterfaceAudio();
    };

    window.mudarTemaNotif = function (tema) {
        temaNotif = tema;
        localStorage.setItem('fenda_tema_notif', tema);
        atualizarInterfaceAudio();
    };


    // Roda uma vez ao carregar e depois a cada 8 segundos
    window.atualizarContadorAlertas = function () {
        fetch('includes/contar_alertas.php?cache=' + new Date().getTime())
            .then(res => res.text())
            .then(texto => {
                // Filtra o JSON para ignorar erros de PHP ou espaços em branco
                const match = texto.match(/\{.*\}/);
                if (!match) return;

                const data = JSON.parse(match[0]);
                const badge = document.getElementById('badge-alertas');

                if (badge && data.total !== undefined) {
                    let ultimoAviso = parseInt(sessionStorage.getItem('fenda_ultimo_aviso')) || 0;

                    if (data.total > ultimoAviso) {
                        if (typeof mostrarPopup === 'function') mostrarPopup("Nova interação na Fenda!");
                    }

                    sessionStorage.setItem('fenda_ultimo_aviso', data.total);
                    badge.innerText = data.total;
                    badge.style.display = data.total > 0 ? 'flex' : 'none';
                }
            })
            .catch(err => console.warn("Radar: Aguardando sinal limpo..."));
    };

    // Inicia o radar com um pequeno delay para estabilidade
    setTimeout(() => {
        window.atualizarContadorAlertas();
        setInterval(window.atualizarContadorAlertas, 8000);
    }, 1000);

    // Configura os posts (Ler mais) com delay para o CSS carregar
    setTimeout(() => {
        if (typeof configurarPosts === 'function') configurarPosts();
    }, 500);
}); // FIM DO DOMCONTENTLOADED - APENAS UMA CHAVE AQUI!


// 3. SISTEMAS EXTERNOS E AJAX
window.configurarPosts = function () {
    const posts = document.querySelectorAll('.post-content');
    posts.forEach(post => {
        // Verifica se o conteúdo transborda a altura máxima definida no CSS
        // Adicionamos uma margem para evitar falsos positivos em textos no limite
        const precisaExpandir = post.scrollHeight > post.offsetHeight + 10;

        if (precisaExpandir && !post.dataset.ouvinte) {
            post.classList.add('tem-mais');
            post.dataset.ouvinte = "true";
            post.style.cursor = "pointer";

            post.addEventListener('click', function (e) {
                this.classList.toggle('expandido');
            });
        }
    });
};


function mostrarPopup(mensagem) {
    let temaSalvo = localStorage.getItem('fenda_tema_notif') || 'padrao';
    let tempoExibicao = 5000;

    if (temaSalvo !== 'off') {
        let configuracaoSons = {
            'padrao': { arquivo: 'padrao.mp3', volume: 1.2 },
            'resident': { arquivo: 'resident.mp3', volume: 0.6 },
            'cs': { arquivo: 'cs.mp3', volume: 0.7 },
            'starwars': { arquivo: 'imperial-march.mp3', volume: 0.3 },
            'mario': { arquivo: 'mario-bros-1up.mp3', volume: 0.7 },
            'pokemon': { arquivo: 'pokemon_levelup.mp3', volume: 0.8 },
            'digimon': { arquivo: 'brave-heart_digimon.mp3', volume: 0.2 },
            'dbz': { arquivo: 'teletransporte_goku.mp3', volume: 0.6 },
            'naruto': { arquivo: 'naruto_shadow_clones.mp3', volume: 0.5 },
            'streetfighter': { arquivo: 'shoryuken.mp3', volume: 0.8 },
            'desgraca1': { arquivo: 'filosofo-piton-tudo-na-vida-e-pra-comer-alguem.mp3', volume: 0.4 },
            'desgraca2': { arquivo: 'eu-quero-dormir.mp3', volume: 0.4 }
        };

        if (temaSalvo === 'digimon') tempoExibicao = 11000;
        else if (temaSalvo === 'starwars') tempoExibicao = 9000;

        if (configuracaoSons[temaSalvo]) {
            let somEscolhido = configuracaoSons[temaSalvo];
            
            const dispararSom = () => {
                let somUrl = 'sons/' + somEscolhido.arquivo + '?v=' + Date.now();
                let bip = new Audio(somUrl);
                bip.volume = somEscolhido.volume;

                bip.play().then(() => {
                    // Lógica de Fade-out para sons longos
                    bip.onloadedmetadata = function () {
                        if (bip.duration > 3) {
                            setTimeout(() => {
                                let intervaloFade = setInterval(() => {
                                    if (bip.volume > 0.03) bip.volume -= 0.03;
                                    else {
                                        bip.volume = 0;
                                        bip.pause();
                                        clearInterval(intervaloFade);
                                    }
                                }, 50);
                            }, tempoExibicao - 1500);
                        }
                    };
                }).catch(err => console.warn("Áudio aguardando clique."));
            };

            if (window.audioLiberado) {
                dispararSom();
            } else {
                document.addEventListener('click', () => {
                    window.audioLiberado = true;
                    dispararSom();
                }, { once: true });
            }
        }
    }

    // --- PARTE VISUAL ---
    const popup = document.createElement('div');
    popup.className = 'notificacao-popup';
    popup.style.cursor = 'pointer';
    popup.innerHTML = `
        <div style="font-size: 20px;">🔔</div>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 14px; color: #ddc80e;">Nova Interação!</strong>
            <span>${mensagem}</span>
        </div>
    `;
    popup.onclick = () => window.location.href = 'notificacoes.php';
    document.body.appendChild(popup);

    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 500);
    }, tempoExibicao);
}

// 1. Função para carregar as notificações na janelinha (sem sair da página)
window.toggleJanelaNotificacoes = function () {
    const box = document.getElementById('dropdown-notificacoes');

    // Se a janela estiver fechada, vamos carregar os dados antes de abrir
    if (box.style.display === 'none' || box.style.display === '') {
        fetch('notificacoes-rapidas.php') // Um arquivo novo, versão leve do notificacoes.php
            .then(res => res.text())
            .then(html => {
                box.innerHTML = html;
                box.style.display = 'block';

                // Limpa o badge visual já que o usuário abriu a lista
                const badge = document.getElementById('badge-alertas');
                if (badge) badge.style.display = 'none';
            });
    } else {
        box.style.display = 'none';
    }
};

// 2. Fechar a janelinha se clicar fora (igual você fez com os outros menus)[cite: 26]
window.addEventListener('click', function (e) {
    const box = document.getElementById('dropdown-notificacoes');
    if (box && !e.target.closest('.notificacao-wrapper')) {
        box.style.display = 'none';
    }
});

// --- BLOCO DE REAÇÕES ATUALIZADO ---
window.enviarReacao = function (postId, tipo) {
    fetch(`includes/reagir.php?id=${postId}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Usa a função global que já está declarada no arquivo
                window.atualizarInterfaceReacao(postId, data.contagens, data.minhas_reacoes);
            }
        })
        .catch(err => console.error("Erro na requisição:", err));
};

window.atualizarInterfaceReacao = function (postId, contagens, minhas = []) {
    const container = document.getElementById(`reacoes-post-${postId}`);
    if (!container) return;

    const tradutor = {
        'amei': '💖', 'perplecto': '😲', 'haha': '😂',
        'ranco': '🙄', 'forca': '🫂', 'triste': '😢', 'tendi-nada': '🤔'
    };

    container.innerHTML = '';

    Object.keys(contagens).forEach(tipo => {
        const emoji = tradutor[tipo] || '👍';
        const total = contagens[tipo];
        const classeVoted = (minhas && minhas.includes(tipo)) ? 'voted' : '';

        const span = document.createElement('span');
        span.className = `reacao-item ${classeVoted}`;
        span.innerHTML = `${emoji} ${total}`;
        container.appendChild(span);
    });
};

// --- CONTROLE UNIFICADO DE CLIQUES GLOBAIS (Versão Final Corrigida) ---
window.addEventListener('click', function (event) {
    // 1. Modal Sair
    const modalSair = document.getElementById('modal-sair-fenda');
    if (event.target === modalSair) window.fecharModalSair();

    // 2. Elementos que NÃO devem fechar os menus ao serem clicados
    const clicouNoMenu = event.target.closest('.dropdown');
    const clicouNaReacao = event.target.closest('.reacao-wrapper') || event.target.closest('.btn-reagir');
    const clicouNaNotif = event.target.closest('.notificacao-wrapper') || event.target.closest('#btn-notificacoes');
    const clicouNoPost = event.target.closest('.post-content');

    // Se o clique for fora do post e fora do menu, fecha os dropdowns
    if (!clicouNoMenu && !clicouNaReacao && !clicouNoPost) {
        document.querySelectorAll('.menu-item.dropdown').forEach(m => m.classList.remove('active'));
        document.querySelectorAll('.reacoes-popup').forEach(p => {
            p.style.visibility = 'hidden';
            p.style.opacity = '0';
        });
    }

    // Fecha a janela de notificações se clicar fora dela
    if (!clicouNaNotif) {
        const box = document.getElementById('dropdown-notificacoes');
        if (box) box.style.display = 'none';
    }
});

// Verificação de Boot e Modo Hacker salvo
window.addEventListener('load', () => {
    if (localStorage.getItem('fenda_hacker') === 'active') {
        document.body.classList.add('hacker-mode');
        const hBtn = document.getElementById('hacker-toggle-lateral') || document.getElementById('hacker-toggle');
        if (hBtn) hBtn.innerHTML = '[ DESLIGAR_TERMINAL ]';
        
        // Remove as bordas inline ao carregar a página com modo hacker ativo
        window.removerBordasInlineHacker();
    }
    const bootScreen = document.getElementById('bios-boot');
    if (bootScreen && !sessionStorage.getItem('boot_concluido')) {
        setTimeout(() => {
            bootScreen.style.opacity = '0';
            setTimeout(() => { bootScreen.style.display = 'none'; sessionStorage.setItem('boot_concluido', 'true'); }, 500);
        }, 2500);
    } else if (bootScreen) { bootScreen.style.display = 'none'; }
});