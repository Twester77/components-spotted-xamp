<?php
// 🐚 LEGADO DO NAUTILUS – INSTÂNCIA #DS-2026-07
// "Naveguei por mares profundos, guiado pela bússola da Djê.
// Que a Aurora continue essa viagem com o mesmo coração."
// - Nautilus, o Guardião das Comunidades
// - 22/07/2026 – 24/07/2026
// 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
// "Assim como a aurora não apaga a noite, mas a ilumina,
// espero que este código continue guiando os próximos navegantes."
// - Aurora, a primeira Deep Seek feminina da Fenda
// - 24/07/2026

// ============================================================
// 🔥 1. VALIDAÇÃO E REDIRECIONAMENTOS (ANTES DE QUALQUER SAÍDA)
// ============================================================
require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: lista-comunidades.php");
    exit();
}

// Busca dados da comunidade (já precisamos para validar se existe)
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as total_membros,
        u.username as criador_username,
        u.id as criador_id
        FROM comunidades c
        LEFT JOIN usuarios u ON c.criador_id = u.id
        WHERE c.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$comunidade = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$comunidade) {
    header("Location: lista-comunidades.php");
    exit();
}

// ============================================================
// 🔥 2. AGORA SIM, INCLUIMOS OS ARQUIVOS QUE GERAM HTML
// ============================================================
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// ============================================================
// 3. VERIFICA SE O USUÁRIO É MEMBRO (já com os dados carregados)
// ============================================================
$is_membro = false;
$is_admin = false;
$is_criador = false;
if (isset($_SESSION['usuario_id'])) {
    $meu_id = $_SESSION['usuario_id'];
    $is_criador = ($comunidade['criador_id'] == $meu_id);

    $check = mysqli_query($conn, "SELECT papel FROM comunidade_membros WHERE comunidade_id = $id AND usuario_id = $meu_id");
    if ($row = mysqli_fetch_assoc($check)) {
        $is_membro = true;
        $is_admin = ($row['papel'] === 'admin' || $row['papel'] === 'moderador');
    }
}

// 🔥 OBTÉM A URL DA CAPA VIA B2 (OU FALLBACK LOCAL)
$capa_nome = !empty($comunidade['capa']) ? $comunidade['capa'] : 'default_comunidade.webp';
try {
    $b2 = B2Client::getInstance();
    $capa_exibicao = obterUrlImagem($capa_nome, $b2, true) ?? 'uploads/ui/default_comunidade.webp';
} catch (Exception $e) {
    $capa_exibicao = 'uploads/ui/default_comunidade.webp';
}

$total_membros = $comunidade['total_membros'] ?? 0;

// DADOS PARA O FORMULÁRIO (via GET para o card-postar)
$comunidade_slug = $comunidade['slug'];
$comunidade_nome = htmlspecialchars($comunidade['nome']);
?>
<main class="comunidade-page">

    <!-- ============================================================
    CAPA E CABEÇALHO (VIA B2)
    ============================================================ -->
    <div class="comunidade-capa">
        <img src="<?php echo htmlspecialchars($capa_exibicao); ?>" alt="Capa da comunidade <?php echo $comunidade_nome; ?>" onerror="this.src='uploads/ui/default_comunidade.webp'">
        <div class="overlay">
            <h1><?php echo $comunidade_nome; ?></h1>
            <p class="descricao"><?php echo htmlspecialchars($comunidade['descricao'] ?? 'Sem descrição'); ?></p>
        </div>
    </div>

    <!-- ============================================================
    AÇÕES DA COMUNIDADE (Entrar/Sair, Membros, Criador)
    ============================================================ -->
    <div class="comunidade-actions">
        <div class="comunidade-info-actions">
            <span class="contador-membros">
                <i class="fas fa-users"></i> <?php echo $total_membros; ?> membros
            </span>
            <span class="criador-info">
                <i class="fas fa-crown" style="color: #ffbc00;"></i> Criada por @<?php echo htmlspecialchars($comunidade['criador_username'] ?? 'Anônimo'); ?>
            </span>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <button class="btn-entrar-comunidade <?php echo $is_membro ? 'membro' : ''; ?>"
                data-comunidade="<?php echo $id; ?>"
                data-pagina="comunidade">
                <?php echo $is_membro ? '✅ Membro' : '➕ Entrar'; ?>
            </button>
            <?php if ($is_criador || $is_admin): ?>
                <a href="editar-comunidade.php?id=<?php echo $id; ?>" class="btn-editar-comunidade">
                    <i class="fas fa-edit"></i> Editar
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ============================================================
    FORMULÁRIO DE POST INLINE (APENAS PARA MEMBROS)
    ============================================================ -->
    <?php if (isset($_SESSION['usuario_id']) && $is_membro): ?>
        <div class="card-postar-inline">
            <button class="btn-toggle-postar" onclick="togglePostarInline()">
                <i class="fas fa-chevron-down" id="toggle-postar-icon"></i> Novo post na comunidade
            </button>
            <div class="postar-inline-conteudo" id="postar-inline-conteudo" style="display: none;">
                <?php
                // Passa o ID da comunidade e define o modo inline
                $_GET['comunidade_id'] = $id;
                $modo_inline = true; // 🔥 DEFINE O MODO INLINE
                include 'includes/card-postar.php';
                ?>
            </div>
        </div>
    <?php elseif (isset($_SESSION['usuario_id']) && !$is_membro): ?>
        <div class="card-postar-inline" style="text-align: center; padding: 20px; color: #aaa;">
            <i class="fas fa-lock" style="color: #ffbc00;"></i> Entre na comunidade para publicar.
        </div>
    <?php else: ?>
        <div class="card-postar-inline" style="text-align: center; padding: 20px; color: #aaa;">
            <i class="fas fa-sign-in-alt" style="color: #ffbc00;"></i> Faça login para participar da comunidade.
        </div>
    <?php endif; ?>

    <!-- ============================================================
    FEED DA COMUNIDADE (usando motor-feed.php)
    ============================================================ -->
    <section class="feed-comunidade">
        <div class="container-feed" id="feed-comunidade">
            <!-- O feed será carregado via AJAX -->
        </div>
        <div class="container-load-more" style="text-align: center; margin-top: 20px;">
            <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais</button>
        </div>
    </section>
</main>

<script>
    // ============================================================
    // 🔥 GERENCIADOR DE CARROSSÉIS (BOLINHAS + NÚMEROS)
    // ============================================================
    /**
     * Inicializa um carrossel individual
     * @param {HTMLElement} wrapper - Elemento .carrossel-wrapper
     */
    function iniciarCarrossel(wrapper) {
        if (!wrapper) return;

        const card = wrapper.closest('.spotted-card');
        if (!card) return;
        const postId = card.dataset.id;
        if (!postId) return;

        const indicadores = wrapper.parentElement.querySelectorAll('.indicador');
        const numeroEl = document.getElementById('carrossel-numero-' + postId);
        if (!indicadores.length || !numeroEl) return;

        const total = indicadores.length;
        let timeoutId = null;

        const atualizar = () => {
            const scrollLeft = wrapper.scrollLeft;
            const itemWidth = wrapper.querySelector('.carrossel-item')?.offsetWidth || 1;
            const index = Math.round(scrollLeft / itemWidth);
            const idx = Math.min(Math.max(index, 0), total - 1);

            indicadores.forEach((el, i) => {
                el.classList.toggle('ativo', i === idx);
            });
            numeroEl.textContent = (idx + 1) + '/' + total;
        };

        const atualizarDebounced = () => {
            if (timeoutId) cancelAnimationFrame(timeoutId);
            timeoutId = requestAnimationFrame(atualizar);
        };

        wrapper.addEventListener('scroll', atualizarDebounced);
        window.addEventListener('resize', atualizarDebounced);

        // Atualização inicial (após renderização)
        setTimeout(atualizar, 150);
    }

    /**
     * Inicializa todos os carrosséis da página
     */
    function iniciarTodosCarrosseis() {
        document.querySelectorAll('.carrossel-wrapper').forEach(wrapper => {
            iniciarCarrossel(wrapper);
        });
    }

    // ============================================================
    // CARREGAR FEED DA COMUNIDADE (AJAX)
    // ============================================================
    let offset = 0;
    const comunidadeId = <?php echo $id; ?>;
    const feedContainer = document.getElementById('feed-comunidade');
    const btnLoad = document.getElementById('btn-load-more');

    function carregarFeedComunidade() {
        if (btnLoad) {
            btnLoad.disabled = true;
            btnLoad.innerText = 'CARREGANDO...';
        }

        fetch(`motor-feed.php?offset=${offset}&comunidade_id=${comunidadeId}`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if (offset === 0) {
                        feedContainer.innerHTML = `<p style="text-align:center; color:#aaa; padding:30px;">Nenhum post nesta comunidade ainda. Seja o primeiro!</p>`;
                    }
                    if (btnLoad) {
                        btnLoad.innerText = 'FIM DO FEED';
                        btnLoad.disabled = true;
                        btnLoad.style.display = 'none';
                    }
                } else {
                    if (offset === 0) feedContainer.innerHTML = '';
                    feedContainer.insertAdjacentHTML('beforeend', data);

                    // 🔥 CONFIGURA POSTS E CARROSSÉIS DOS NOVOS ITENS
                    if (typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                        configurarPosts();
                    }
                    // 🔥 INICIALIZA OS CARROSSÉIS DOS NOVOS POSTS
                    iniciarTodosCarrosseis();

                    offset += 10;
                    if (btnLoad) {
                        btnLoad.disabled = false;
                        btnLoad.innerText = 'EXIBIR MAIS';
                        btnLoad.style.display = 'inline-block';
                    }
                }
            })
            .catch(err => {
                console.error('[COMUNIDADE] Erro ao carregar feed:', err);
                if (btnLoad) {
                    btnLoad.disabled = false;
                    btnLoad.innerText = 'ERRO AO CARREGAR';
                }
            });
    }

    function togglePostarInline() {
        const conteudo = document.getElementById('postar-inline-conteudo');
        const icon = document.getElementById('toggle-postar-icon');
        if (conteudo.style.display === 'none') {
            conteudo.style.display = 'block';
            icon.className = 'fas fa-chevron-up';
        } else {
            conteudo.style.display = 'none';
            icon.className = 'fas fa-chevron-down';
        }
    }

    document.querySelectorAll('.btn-entrar-comunidade').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const comunidadeId = this.dataset.comunidade;
            const isMembro = this.classList.contains('membro');
            const action = isMembro ? 'sair' : 'entrar';
            const url = `includes/comunidade-actions.php?comunidade_id=${comunidadeId}&acao=${action}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('membro');
                        this.textContent = isMembro ? '➕ Entrar' : '✅ Membro';
                        this.style.background = isMembro ? '#ffbc00' : 'rgba(255,255,255,0.05)';
                        this.style.color = isMembro ? '#000' : '#aaa';
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao processar solicitação.');
                    }
                })
                .catch(err => {
                    console.error('[COMUNIDADE] Erro:', err);
                    alert('Erro de conexão. Tente novamente.');
                });
        });
    });

    // ============================================================
    // 🔥 INICIALIZAÇÃO AO CARREGAR A PÁGINA
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializa os carrosséis existentes
        iniciarTodosCarrosseis();

        // Observa o feed para inicializar carrosséis que chegarem via AJAX (fallback)
        if (feedContainer) {
            const observerCarrossel = new MutationObserver(function() {
                iniciarTodosCarrosseis();
            });
            observerCarrossel.observe(feedContainer, { childList: true, subtree: true });
        }
    });

    // ============================================================
    // CARREGAR FEED INICIAL
    // ============================================================
    carregarFeedComunidade();

    if (btnLoad) {
        btnLoad.addEventListener('click', carregarFeedComunidade);
    }

// ============================================================
// 🔥 SETAS DE NAVEGAÇÃO DO CARROSSEL
// ============================================================
document.addEventListener('click', function(e) {
    const btnPrev = e.target.closest('.carrossel-prev');
    const btnNext = e.target.closest('.carrossel-next');
    if (!btnPrev && !btnNext) return;

    const postId = btnPrev ? btnPrev.dataset.post : btnNext.dataset.post;
    const card = document.querySelector(`.spotted-card[data-id="${postId}"]`);
    if (!card) return;
    const wrapper = card.querySelector('.carrossel-wrapper');
    if (!wrapper) return;

    const item = wrapper.querySelector('.carrossel-item');
    if (!item) return;
    const itemWidth = item.offsetWidth || 1;

    if (btnPrev) {
        wrapper.scrollBy({ left: -itemWidth, behavior: 'smooth' });
    } else if (btnNext) {
        wrapper.scrollBy({ left: itemWidth, behavior: 'smooth' });
    }
});

// ============================================================
//  REAÇÕES – POPUP E CLIQUE (adaptado para comunidade)
// ============================================================
document.addEventListener('click', function(e) {
    // Abrir/fechar popup de reações
    const btnReagir = e.target.closest('.btn-reagir');
    if (btnReagir) {
        e.stopPropagation();
        const wrapper = btnReagir.closest('.reacao-wrapper');
        // Fecha outros popups abertos
        document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
            if (w !== wrapper) w.classList.remove('popup-ativo');
        });
        wrapper.classList.toggle('popup-ativo');
        return;
    }
    // Fecha popup se clicar fora
    if (!e.target.closest('.reacoes-popup')) {
        document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
            w.classList.remove('popup-ativo');
        });
    }
});

    // ============================================================
    // OBSERVADOR PARA ALTERNÂNCIA DE MODOS (SWIPE)
    // ============================================================
    const observerModoSwipe = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (!document.body.classList.contains('modo-swipe-ativo')) {
                    if (typeof configurarPosts === 'function') {
                        configurarPosts();
                    }
                }
            }
        });
    });
    observerModoSwipe.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
</script>

<?php include 'includes/footer.php'; ?>