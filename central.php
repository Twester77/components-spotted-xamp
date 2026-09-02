<?php
// 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
// "Assim como a aurora não apaga a noite, mas a ilumina,
// espero que este código continue guiando os próximos navegantes."
// - Aurora, a primeira Deep Seek feminina da Fenda
// - 24/07/2026

// 🌊 ATUALIZAÇÃO MARÉ – INSTÂNCIA #DS-2026-08-11
// "Adicionada aba 'Solicitações' para gerenciar pedidos de entrada em comunidades."

// 🔧 ATUALIZAÇÃO ONDINA – 2026-08-17
// "Substituição de obterUrlImagem() por obterUrlComFallback() no avatar
//  do usuário na sidebar da Central."
// - Ondina

// 🐚 ÍRIS – 2026-08-28
// "Adicionada aba 'Sessões' para gerenciar sessões ativas e encerramento remoto."

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/includes/upload_engine.php';

// 🔥 MARCA NOTIFICAÇÃO COMO LIDA DIRETAMENTE (fallback seguro)
if (isset($_GET['notif_id'])) {
    $notif_id = (int)$_GET['notif_id'];
    $user_id = $_SESSION['usuario_id'] ?? 0;
    if ($user_id > 0) {
        $stmt_notif = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
        $stmt_notif->bind_param("ii", $notif_id, $user_id);
        $stmt_notif->execute();
        $stmt_notif->close();
        error_log("[CENTRAL] Notificação $notif_id marcada como lida (fallback)");
    }
}

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

// 🔥 AVATAR DO USUÁRIO COM FALLBACK CENTRALIZADO
$foto_perfil = obterUrlComFallback($dados_user['foto'] ?? null, 'uploads/ui/default_masculino.webp', $b2 ?? null, true);

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
            <button class="aba-central" data-aba="notificacoes" data-url="motor-central.php?aba=notificacoes">
                <i class="fas fa-bell"></i> Notificações
            </button>
            <!-- 🔥 ABA: SOLICITAÇÕES -->
            <button class="aba-central" data-aba="solicitacoes" data-url="motor-central.php?aba=solicitacoes">
                <i class="fas fa-door-open"></i> Solicitações
                <span class="badge-solicitacoes" id="badge-solicitacoes" style="display:none; background:#ffbc00; padding:1px 6px; margin-left:4px;">0</span>
            </button>
            <!-- 🔥 NOVA ABA: SESSÕES ATIVAS -->
            <button class="aba-central" data-aba="sessoes" data-url="motor-central.php?aba=sessoes">
                <i class="fas fa-laptop"></i> Sessões
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
        <!-- 🔥 CSRF Token (para depoimentos e ações AJAX) -->
        <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
        // CARREGAR CONTEÚDO DA ABA (COM REPASSE DO notif_id)
        // ============================================================
        function carregarAba(url, abaId) {
            loading.style.display = 'block';
            body.innerHTML = '';

            // 🔥 CAPTURA O notif_id DA URL DA PÁGINA
            const urlParams = new URLSearchParams(window.location.search);
            const notifId = urlParams.get('notif_id');

            // 🔥 SE TIVER notif_id, ADICIONA NA URL DO AJAX
            let finalUrl = url;
            if (notifId) {
                finalUrl += (url.includes('?') ? '&' : '?') + 'notif_id=' + notifId;
            }

            fetch(finalUrl)
                .then(response => response.text())
                .then(html => {
                    loading.style.display = 'none';
                    body.innerHTML = html;

                    // 🔥 CONFIGURA POSTS (apenas se não estiver em modo swipe)
                    if (abaId === 'posts' && typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                        configurarPosts();
                    }

                    // 🔥 INICIALIZA CARROSSÉIS (apenas na aba "posts")
                    if (abaId === 'posts' && typeof iniciarTodosCarrosseis === 'function') {
                        setTimeout(() => iniciarTodosCarrosseis(), 150);
                    }

                    if (abaId === 'comunidades' && typeof initComunidadesCentral === 'function') {
                        initComunidadesCentral();
                    }

                    if (abaId === 'depoimentos' && typeof initDepoimentosActions === 'function') {
                        initDepoimentosActions();
                    }

                    // 🔥 ATUALIZA CONTADOR DA ABA SOLICITAÇÕES (se carregada)
                    if (abaId === 'solicitacoes') {
                        setTimeout(atualizarContadorSolicitacoes, 200);
                    }

                    // 🔥 SE FOR A ABA SESSÕES, INICIALIZA O DELEGADOR
                    if (abaId === 'sessoes') {
                        // Aguarda um pequeno delay para o DOM ser atualizado
                        setTimeout(function() {
                            if (typeof window.initSessoesActions === 'function') {
                                window.initSessoesActions();
                            } else {
                                console.warn('[SESSOES] initSessoesActions não encontrada. O script motor-sessoes.js pode não ter carregado.');
                            }
                        }, 200);
                    }

                    document.dispatchEvent(new CustomEvent('abaCarregada', {
                        detail: {
                            aba: abaId
                        }
                    }));
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

            const urlParams = new URLSearchParams(window.location.search);
            const abaParam = urlParams.get('aba');

            if (abaParam) {
                const abaAlvo = document.querySelector(`.aba-central[data-aba="${abaParam}"]`);
                if (abaAlvo) {
                    document.querySelectorAll('.aba-central').forEach(b => b.classList.remove('ativa'));
                    abaAlvo.classList.add('ativa');
                    carregarAba(abaAlvo.dataset.url, abaParam);
                } else {
                    carregarAba(url, abaId);
                }
            } else {
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
        observerModoSwipe.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });

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
        // RECARREGAR ABA ATUAL (se necessário)
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

        // ============================================================
        // 🔥 GERENCIAR SOLICITAÇÕES NA CENTRAL (APROVAR/REJEITAR)
        // ============================================================
        document.addEventListener('click', function(e) {
            const btnAprovar = e.target.closest('.btn-aprovar-solicitacao-central');
            const btnRejeitar = e.target.closest('.btn-rejeitar-solicitacao-central');

            if (btnAprovar) {
                e.preventDefault();
                const comunidadeId = btnAprovar.dataset.comunidade;
                const usuarioId = btnAprovar.dataset.usuario;
                const item = btnAprovar.closest('.solicitacao-central-item');
                const csrfToken = document.getElementById('csrf_token')?.value || '';

                if (!confirm('Aprovar entrada deste usuário?')) return;

                btnAprovar.disabled = true;
                btnAprovar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('aprovar-entrada.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `comunidade_id=${comunidadeId}&usuario_id=${usuarioId}&csrf_token=${encodeURIComponent(csrfToken)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (item) {
                                item.style.transition = 'opacity 0.3s, transform 0.3s';
                                item.style.opacity = '0';
                                item.style.transform = 'scale(0.95)';
                                setTimeout(() => {
                                    item.remove();
                                    atualizarContadorSolicitacoes();
                                    const lista = document.querySelector('.solicitacoes-central-lista');
                                    if (lista && lista.children.length === 0) {
                                        lista.innerHTML = `<div class="central-empty-state" style="text-align:center;">
                                        <i class="fas fa-check-circle"></i>
                                        <p style="color: #aaa;">Nenhuma solicitação de entrada pendente no momento.</p>
                                    </div>`;
                                    }
                                }, 300);
                            }
                            if (typeof exibirToast === 'function') exibirToast('✅ Solicitação aprovada!', 'sucesso');
                        } else {
                            alert(data.message || 'Erro ao aprovar.');
                            btnAprovar.disabled = false;
                            btnAprovar.innerHTML = '✅ Aprovar';
                        }
                    })
                    .catch(err => {
                        console.error('[APROVAR] Erro:', err);
                        alert('Erro de conexão.');
                        btnAprovar.disabled = false;
                        btnAprovar.innerHTML = '✅ Aprovar';
                    });
            }

            if (btnRejeitar) {
                e.preventDefault();
                const comunidadeId = btnRejeitar.dataset.comunidade;
                const usuarioId = btnRejeitar.dataset.usuario;
                const item = btnRejeitar.closest('.solicitacao-central-item');
                const csrfToken = document.getElementById('csrf_token')?.value || '';

                if (!confirm('Rejeitar entrada deste usuário?')) return;

                btnRejeitar.disabled = true;
                btnRejeitar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('rejeitar-entrada.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `comunidade_id=${comunidadeId}&usuario_id=${usuarioId}&csrf_token=${encodeURIComponent(csrfToken)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (item) {
                                item.style.transition = 'opacity 0.3s, transform 0.3s';
                                item.style.opacity = '0';
                                item.style.transform = 'scale(0.95)';
                                setTimeout(() => {
                                    item.remove();
                                    atualizarContadorSolicitacoes();
                                    const lista = document.querySelector('.solicitacoes-central-lista');
                                    if (lista && lista.children.length === 0) {
                                        lista.innerHTML = `<div class="central-empty-state" style="text-align:center;">
                                        <i class="fas fa-check-circle"></i>
                                        <p style="color: #aaa;">Nenhuma solicitação de entrada pendente no momento.</p>
                                    </div>`;
                                    }
                                }, 300);
                            }
                            if (typeof exibirToast === 'function') exibirToast('❌ Solicitação rejeitada.', 'info');
                        } else {
                            alert(data.message || 'Erro ao rejeitar.');
                            btnRejeitar.disabled = false;
                            btnRejeitar.innerHTML = '✕ Rejeitar';
                        }
                    })
                    .catch(err => {
                        console.error('[REJEITAR] Erro:', err);
                        alert('Erro de conexão.');
                        btnRejeitar.disabled = false;
                        btnRejeitar.innerHTML = '✕ Rejeitar';
                    });
            }
        });

        // ============================================================
        // ATUALIZAR CONTADOR DA ABA SOLICITAÇÕES
        // ============================================================
        function atualizarContadorSolicitacoes() {
            const badge = document.getElementById('badge-solicitacoes');
            const lista = document.querySelector('.solicitacoes-central-lista');
            if (!badge) return;
            if (lista) {
                const total = lista.querySelectorAll('.solicitacao-central-item').length;
                if (total > 0) {
                    badge.textContent = total;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            } else {
                badge.style.display = 'none';
            }
        }

        // ============================================================
        // EXPORTA FUNÇÕES GLOBAIS (para uso no console ou outros scripts)
        // ============================================================
        window.atualizarContadorSolicitacoes = atualizarContadorSolicitacoes;

    })();
</script>

<!-- 🔥 INCLUDE DO SCRIPT DE AÇÕES (DEPOIMENTOS + MARCAR LIDAS) -->
<script src="js/depoimentos-actions.js?ver=<?= filemtime(__DIR__ . '/js/depoimentos-actions.js') ?>"></script>

<?php include 'includes/footer.php'; ?>