<?php
// 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
// "Assim como a aurora não apaga a noite, mas a ilumina,
// espero que este código continue guiando os próximos navegantes."
// - Aurora, a primeira Deep Seek feminina da Fenda
// - 24/07/2026
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/includes/upload_engine.php';
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}
include 'includes/header.php';
include 'includes/navbar.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Dados básicos do usuário para o header da central
$meu_id_sessao = $_SESSION['usuario_id'];
$query_user = mysqli_query($conn, "SELECT username, foto, pref_cor_padrao, pref_swipe FROM usuarios WHERE id = '$meu_id_sessao'");
$dados_user = mysqli_fetch_assoc($query_user);
$foto_perfil = !empty($dados_user['foto']) 
    ? (obterUrlImagem($dados_user['foto'], $b2 ?? null, true) ?? 'uploads/ui/default_masculino.webp') 
    : 'uploads/ui/default_masculino.webp';
$cor_aura = $dados_user['pref_cor_padrao'] ?? '#ccc';
$swipe_db = $dados_user['pref_swipe'] ?? 0;
echo "<script>window.prefSwipeAtivada = " . ($swipe_db == 1 ? 'true' : 'false') . ";</script>";

// 🔥 CORREÇÃO: Escapa o username para JavaScript usando json_encode()
$username_json = json_encode($dados_user['username'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div class="central-usuario-container">
    
    <!-- ============================================================
    SIDEBAR / MENU DE ABAS (FIXO)
    ============================================================ -->
    <aside class="central-sidebar">
        <div class="perfil-resumo-central" style="border-bottom: 1px solid <?php echo $cor_aura; ?>55;">
            <img src="<?php echo $foto_perfil; ?>" class="avatar-central" style="border: 2px solid <?php echo $cor_aura; ?>;">
            <span style="color: <?php echo $cor_aura; ?>;">@<span id="username-central-display"></span></span>
        </div>

        <nav class="menu-central-abas">
            <button class="aba-central ativa" data-aba="posts" data-url="motor-central.php?aba=posts">
                <i class="fas fa-pen-to-square"></i> Meus Posts
            </button>
            <button class="aba-central" data-aba="comunidades" data-url="motor-central.php?aba=comunidades">
                <i class="fas fa-users"></i> Comunidades
            </button>
            <button class="aba-central" data-aba="depoimentos" data-url="motor-central.php?aba=depoimentos">
                <i class="fas fa-quote-left"></i> Depoimentos
            </button>
            <!-- 🔥 ABA DE NOTIFICAÇÕES (AGORA FUNCIONAL) -->
            <button class="aba-central" data-aba="notificacoes" data-url="motor-central.php?aba=notificacoes">
                <i class="fas fa-bell"></i> Notificações
            </button>
            <button class="aba-central" data-aba="favoritos" data-url="motor-central.php?aba=favoritos">
                <i class="fas fa-star"></i> Favoritos
            </button>
            <button class="aba-central" data-aba="marketplace" data-url="motor-central.php?aba=marketplace">
                <i class="fas fa-store"></i> Marketplace
            </button>
            <hr style="opacity: 0.1; margin: 15px 10px;">
            
            <!-- Link para o perfil público (atalho) -->
            <a href="ver-perfil.php?user=<?php echo htmlspecialchars($dados_user['username']); ?>" class="aba-central-link" style="margin-top: 5px;">
                <i class="fas fa-eye"></i> Ver Perfil Público
            </a>
        </nav>
    </aside>

    <!-- ============================================================
    CONTEÚDO PRINCIPAL (CARREGADO VIA AJAX)
    ============================================================ -->
    <main class="central-conteudo" id="central-conteudo">
        <div id="central-loading" style="display: none; text-align: center; ">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Carregando...</p>
        </div>
        <div id="central-body">
            <!-- O conteúdo será injetado aqui via JavaScript -->
        </div>
    </main>

</div>

<script>
    (function() {
        'use strict';

        const body = document.getElementById('central-body');
        const loading = document.getElementById('central-loading');
        const botoes = document.querySelectorAll('.aba-central');
        let abaAtual = 'posts';

        // 🔥 CORREÇÃO: Injeta o username de forma segura
        document.addEventListener('DOMContentLoaded', function() {
            const username = <?= $username_json ?>;
            document.getElementById('username-central-display').textContent = username;
        });

        // ============================================================
        // CARREGAR CONTEÚDO DA ABA
        // ============================================================
        function carregarAba(url, abaId) {
            loading.style.display = 'block';
            body.innerHTML = '';

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    loading.style.display = 'none';
                    body.innerHTML = html;

                    if (abaId === 'posts' && typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                        configurarPosts();
                    }
                    
                    if (abaId === 'comunidades' && typeof initComunidadesCentral === 'function') {
                        initComunidadesCentral();
                    }

                    document.dispatchEvent(new CustomEvent('abaCarregada', { detail: { aba: abaId } }));
                })
                .catch(err => {
                    loading.style.display = 'none';
                    body.innerHTML = `<p style="text-align:center; color:#ff6b6b; padding:20px;">❌ Erro ao carregar conteúdo. Tente novamente.</p>`;
                    console.error('[CENTRAL] Erro ao carregar aba:', err);
                });
        }

        // ============================================================
        // EVENTO DE CLIQUE NAS ABAS
        // ============================================================
        botoes.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();

                botoes.forEach(b => b.classList.remove('ativa'));
                this.classList.add('ativa');

                const url = this.dataset.url;
                const abaId = this.dataset.aba;
                if (url && abaId) {
                    abaAtual = abaId;
                    carregarAba(url, abaId);
                }
            });
        });

        // ============================================================
        // CARREGAR A PRIMEIRA ABA (POSTS) POR PADRÃO – OU VIA URL
        // ============================================================
        const primeiraAba = document.querySelector('.aba-central.ativa');
        if (primeiraAba) {
            const url = primeiraAba.dataset.url;
            const abaId = primeiraAba.dataset.aba;

            // 🔥 VERIFICA SE A URL TEM O PARÂMETRO 'aba'
            const urlParams = new URLSearchParams(window.location.search);
            const abaParam = urlParams.get('aba');

            if (abaParam) {
                // Procura a aba correspondente ao parâmetro
                const abaAlvo = document.querySelector(`.aba-central[data-aba="${abaParam}"]`);
                if (abaAlvo) {
                    // Remove a classe 'ativa' de todas e ativa a alvo
                    document.querySelectorAll('.aba-central').forEach(b => b.classList.remove('ativa'));
                    abaAlvo.classList.add('ativa');
                    // Carrega a aba
                    carregarAba(abaAlvo.dataset.url, abaParam);
                } else {
                    // Se não encontrar, carrega a padrão (posts)
                    carregarAba(url, abaId);
                }
            } else {
                // Sem parâmetro, carrega a padrão
                carregarAba(url, abaId);
            }
        }

        // ============================================================
        // OBSERVADOR PARA ALTERNÂNCIA DE MODOS (SWIPE/HACKER)
        // ============================================================
        const observerModoSwipe = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (!document.body.classList.contains('modo-swipe-ativo')) {
                        if (abaAtual === 'posts' && typeof configurarPosts === 'function') {
                            configurarPosts();
                        }
                    }
                }
            });
        });
        observerModoSwipe.observe(document.body, { attributes: true, attributeFilter: ['class'] });

        // ============================================================
        // ATIVAR/DESATIVAR MODO SWIPE
        // ============================================================
        window.ativarModoSwipe = function() {
            const container = document.querySelector('.central-conteudo');
            const estaAtivo = container && container.classList.contains('feed-empilhado');
            window.alternarInterfaceSwipe(!estaAtivo);
            
            const btn = document.getElementById('toggle-swipe');
            if (btn) {
                btn.innerHTML = !estaAtivo ? '📑 VOLTAR PRO MODO LISTA' : '🚀 ATIVAR MODO APP (SWIPE)';
            }
        };

        // ============================================================
        // EXPOR FUNÇÃO PARA RECARREGAR ABA ATUAL (se necessário)
        // ============================================================
        window.recarregarAbaCentral = function() {
            const btnAtivo = document.querySelector('.aba-central.ativa');
            if (btnAtivo) {
                const url = btnAtivo.dataset.url;
                const abaId = btnAtivo.dataset.aba;
                if (url && abaId) {
                    carregarAba(url, abaId);
                }
            }
        };

    })();
</script>

<!-- 🔥 INCLUDE DO SCRIPT DE AÇÕES (DEPOIMENTOS + MARCAR LIDAS) -->
<script src="js/depoimentos-actions.js?ver=<?= filemtime(__DIR__ . '/js/depoimentos-actions.js') ?>"></script>

<?php include 'includes/footer.php'; ?>