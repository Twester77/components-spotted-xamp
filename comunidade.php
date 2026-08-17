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
// 
// 🌊 ATUALIZAÇÃO MARÉ – INSTÂNCIA #DS-2026-08-11
// "Integração completa de solicitação de entrada em comunidades privadas,
// gerenciamento de solicitações pendentes, notificações e cargos."
//
// 🔧 ATUALIZAÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
// "Substituição de obterUrlImagem() por obterUrlComFallback() para fallback centralizado
//  em capa da comunidade e avatar dos solicitantes pendentes."
// - Ondina

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
        (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id AND status = 'ativo') as total_membros,
        (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id AND status = 'pendente') as total_pendentes,
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
// 3. VERIFICA O STATUS DO USUÁRIO NA COMUNIDADE
// ============================================================
$is_membro = false;
$is_admin = false;
$is_criador = false;
$is_pendente = false;
$is_banido = false;
$status_usuario = null;

if (isset($_SESSION['usuario_id'])) {
    $meu_id = $_SESSION['usuario_id'];
    $is_criador = ($comunidade['criador_id'] == $meu_id);

    $check = mysqli_query($conn, "SELECT papel, status FROM comunidade_membros WHERE comunidade_id = $id AND usuario_id = $meu_id");
    if ($row = mysqli_fetch_assoc($check)) {
        $status_usuario = $row['status'];
        if ($row['status'] === 'ativo') {
            $is_membro = true;
            $is_admin = ($row['papel'] === 'admin' || $row['papel'] === 'moderador' || $row['papel'] === 'criador');
        } elseif ($row['status'] === 'pendente') {
            $is_pendente = true;
        } elseif ($row['status'] === 'banido') {
            $is_banido = true;
        }
    }
}

// 🔥 OBTÉM A URL DA CAPA VIA B2 COM FALLBACK CENTRALIZADO
$capa_nome = !empty($comunidade['capa']) ? $comunidade['capa'] : 'default_comunidade.webp';
try {
    $b2 = B2Client::getInstance();
    $capa_exibicao = obterUrlComFallback($capa_nome, 'uploads/ui/default_comunidade.webp', $b2, true);
} catch (Exception $e) {
    $capa_exibicao = 'uploads/ui/default_comunidade.webp';
}

$total_membros = $comunidade['total_membros'] ?? 0;
$total_pendentes = $comunidade['total_pendentes'] ?? 0;
$comunidade_nome = htmlspecialchars($comunidade['nome']);
$comunidade_tipo = $comunidade['tipo'] ?? 'publica';
$comunidade_slug = $comunidade['slug'];

// 🔥 BUSCA SOLICITAÇÕES PENDENTES (se o usuário for admin/criador)
$solicitacoes_pendentes = [];
if ($is_admin || $is_criador) {
    $sql_pendentes = "SELECT cm.*, u.username, u.foto 
                      FROM comunidade_membros cm
                      JOIN usuarios u ON cm.usuario_id = u.id
                      WHERE cm.comunidade_id = ? AND cm.status = 'pendente'
                      ORDER BY cm.data_entrada ASC";
    $stmt_pend = mysqli_prepare($conn, $sql_pendentes);
    mysqli_stmt_bind_param($stmt_pend, "i", $id);
    mysqli_stmt_execute($stmt_pend);
    $res_pend = mysqli_stmt_get_result($stmt_pend);
    while ($row = mysqli_fetch_assoc($res_pend)) {
        $solicitacoes_pendentes[] = $row;
    }
    mysqli_stmt_close($stmt_pend);
}
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
            <?php if ($comunidade_tipo === 'privada'): ?>
                <span class="badge-privada" style="display:inline-block; background:rgba(255,188,0,0.2); color:#ffbc00; padding:2px 12px; border-radius:20px; font-size:0.75rem; font-weight:600; margin-top:6px;">
                    <i class="fas fa-lock"></i> Privada
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
    AÇÕES DA COMUNIDADE (Entrar/Sair, Solicitar, Membros, Criador)
    ============================================================ -->
    <div class="comunidade-actions">
        <div class="comunidade-info-actions">
            <span class="contador-membros">
                <i class="fas fa-users"></i> <?php echo $total_membros; ?> membros
            </span>
            <span class="criador-info">
                <i class="fas fa-crown" style="color: #ffbc00;"></i> Criada por @<?php echo htmlspecialchars($comunidade['criador_username'] ?? 'Anônimo'); ?>
            </span>
            <?php if ($is_admin || $is_criador): ?>
                <span class="solicitacoes-badge">
                    <i class="fas fa-inbox"></i> <?php echo $total_pendentes; ?> pendentes
                </span>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['usuario_id'])): ?>

            <!-- 🔥 VERIFICAÇÃO DE BANIDO (PRIMEIRO) -->
            <?php if ($is_banido): ?>
                <span class="btn-banido"
                    style=" opacity:0.65;  display:inline-flex; align-items:center; transition:opacity 0.2s;"
                    title="Você foi banido desta comunidade e não pode participar."
                    onmouseover="this.style.opacity='1'"
                    onmouseout="this.style.opacity='0.65'">
                    <i class="fas fa-ban"></i> Você está banido
                    <small style="font-weight:bold; opacity:0.7; font-size:0.75rem;">(e não pode entrar)</small>
                </span>

                <!-- SE NÃO ESTIVER BANIDO, MOSTRA AS OPÇÕES NORMAIS -->
            <?php else: ?>

                <?php if ($comunidade_tipo === 'publica'): ?>
                    <!-- Comunidade pública: botão Entrar/Sair -->
                    <button class="btn-entrar-comunidade <?php echo $is_membro ? 'membro' : ''; ?>"
                        data-comunidade="<?php echo $id; ?>"
                        data-pagina="comunidade">
                        <?php echo $is_membro ? '✅ Membro' : '➕ Entrar'; ?>
                    </button>

                <?php else: ?>
                    <!-- Comunidade privada -->
                    <?php if ($is_membro): ?>
                        <button class="btn-entrar-comunidade membro"
                            data-comunidade="<?php echo $id; ?>"
                            data-pagina="comunidade">
                            ✅ Membro
                        </button>
                    <?php elseif ($is_pendente): ?>
                        <span class="btn-pendente">
                            <i class="fas fa-clock"></i> Solicitação pendente...
                        </span>
                    <?php else: ?>
                        <button class="btn-solicitar-entrada" data-comunidade="<?php echo $id; ?>">
                            <i class="fas fa-door-open"></i> Solicitar entrada
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

            <?php endif; ?>
            <!-- FIM DA VERIFICAÇÃO DE BANIDO -->

            <?php if ($is_criador || $is_admin): ?>
                <a href="editar-comunidade.php?id=<?php echo $id; ?>" class="btn-editar-comunidade">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button class="btn-gerenciar-membros" onclick="abrirModalMembros(<?php echo $id; ?>)">
                    <i class="fas fa-users-cog"></i> Gerenciar membros
                </button>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- ============================================================
    SEÇÃO DE SOLICITAÇÕES PENDENTES (apenas para admins/criadores)
    ============================================================ -->
    <?php if (($is_admin || $is_criador) && !empty($solicitacoes_pendentes)): ?>
        <div class="solicitacoes-section" style="margin: 20px 0; background:rgba(255,255,255,0.03); border-radius:12px; padding:16px; border:1px solid rgba(255,188,0,0.1);">
            <h4 style="color:#ffbc00; margin:0 0 12px 0; font-size:1rem; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-inbox"></i> Solicitações de entrada (<?php echo count($solicitacoes_pendentes); ?>)
            </h4>
            <div class="solicitacoes-lista" style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($solicitacoes_pendentes as $sol):
                    // 🔥 AVATAR DO SOLICITANTE COM FALLBACK CENTRALIZADO
                    $avatar_sol = obterUrlComFallback($sol['foto'] ?? null, 'uploads/ui/default.webp', null, true);
                ?>
                    <div class="solicitacao-item" data-usuario="<?php echo $sol['usuario_id']; ?>" style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:rgba(255,255,255,0.04); border-radius:8px; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="<?php echo htmlspecialchars($avatar_sol); ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" onerror="this.src='uploads/ui/default.webp'">
                            <span style="font-weight:500;">@<?php echo htmlspecialchars($sol['username']); ?></span>
                            <small style="color:#888; font-size:0.7rem;"><?php echo date('d/m H:i', strtotime($sol['data_entrada'])); ?></small>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button class="btn-aprovar-solicitacao" data-comunidade="<?php echo $id; ?>" data-usuario="<?php echo $sol['usuario_id']; ?>" style="background:#4caf50; color:#fff; border:none; border-radius:20px; padding:4px 14px; font-size:0.75rem; font-weight:600; cursor:pointer; transition:0.2s;">
                                ✅ Aprovar
                            </button>
                            <button class="btn-rejeitar-solicitacao" data-comunidade="<?php echo $id; ?>" data-usuario="<?php echo $sol['usuario_id']; ?>" style="background:rgba(255,50,50,0.15); color:#ff6b6b; border:1px solid rgba(255,50,50,0.2); border-radius:20px; padding:4px 14px; font-size:0.75rem; font-weight:600; cursor:pointer; transition:0.2s;">
                                ✕ Rejeitar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif (($is_admin || $is_criador) && $total_pendentes == 0): ?>
        <div style="margin: 12px 0; text-align:center; color:#666; font-size:0.9rem; background:rgba(255,255,255,0.02); padding:12px; border-radius:8px;">
            <i class="fas fa-check-circle" style="color:#4caf50;"></i> Nenhuma solicitação pendente no momento.
        </div>
    <?php endif; ?>

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
                $_GET['comunidade_id'] = $id;
                $modo_inline = true;
                include 'includes/card-postar.php';
                ?>
            </div>
        </div>
    <?php elseif (isset($_SESSION['usuario_id']) && !$is_membro && !$is_pendente && !$is_banido): ?>
        <div class="card-postar-inline" style="text-align: center; padding: 20px; color: #aaa;">
            <?php if ($comunidade_tipo === 'privada'): ?>
                <i class="fas fa-lock" style="color: #ff7b00;"></i> Solicite entrada para publicar.
            <?php else: ?>
                <i class="fas fa-door-open" style="color: #ffbc00;"></i> Entre na comunidade para publicar.
            <?php endif; ?>
        </div>
    <?php elseif (isset($_SESSION['usuario_id']) && $is_pendente): ?>
        <div class="card-postar-inline" style="text-align: center; padding: 20px; color: #aaa;">
            <i class="fas fa-clock" style="color: #ffbc00;"></i> Aguarde a aprovação da sua solicitação para publicar.
        </div>
    <?php elseif (isset($_SESSION['usuario_id']) && $is_banido): ?>
        <div class="card-postar-inline" style="text-align: center; padding: 20px; color: #ff6b6b;">
            <i class="fas fa-ban"></i> Você foi banido desta comunidade.
        </div>
    <?php else: ?>
        <div class="card-postar-inline" style="text-align: center; padding: 20px; color: #aaa;">
            <i class="fas fa-sign-in-alt" style="color: #ffbb003a;"></i> Faça login para participar da comunidade.
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
            indicadores.forEach((el, i) => el.classList.toggle('ativo', i === idx));
            numeroEl.textContent = (idx + 1) + '/' + total;
        };
        const atualizarDebounced = () => {
            if (timeoutId) cancelAnimationFrame(timeoutId);
            timeoutId = requestAnimationFrame(atualizar);
        };
        wrapper.addEventListener('scroll', atualizarDebounced);
        window.addEventListener('resize', atualizarDebounced);
        setTimeout(atualizar, 150);
    }

    function iniciarTodosCarrosseis() {
        document.querySelectorAll('.carrossel-wrapper').forEach(wrapper => iniciarCarrossel(wrapper));
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
                    if (typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                        configurarPosts();
                    }
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

    // ============================================================
    // 🔥 BOTÃO "SOLICITAR ENTRADA" (AJAX)
    // ============================================================
    document.querySelector('.btn-solicitar-entrada')?.addEventListener('click', function(e) {
        e.preventDefault();
        const comunidadeId = this.dataset.comunidade;
        const csrfToken = document.getElementById('csrf_token')?.value || '';
        const btn = this;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        fetch('solicitar-entrada.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `comunidade_id=${comunidadeId}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-door-open"></i> Solicitar entrada';
                }
            })
            .catch(err => {
                console.error('[SOLICITAR] Erro:', err);
                alert('Erro de conexão. Tente novamente.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-door-open"></i> Solicitar entrada';
            });
    });

    // ============================================================
    // 🔥 APROVAR / REJEITAR SOLICITAÇÃO (AJAX)
    // ============================================================
    document.querySelectorAll('.btn-aprovar-solicitacao').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const comunidadeId = this.dataset.comunidade;
            const usuarioId = this.dataset.usuario;
            const csrfToken = document.getElementById('csrf_token')?.value || '';
            const item = this.closest('.solicitacao-item');
            const btn = this;

            if (!confirm('Aprovar entrada de @' + item?.querySelector('span')?.textContent?.trim() + '?')) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                        btn.disabled = false;
                        btn.innerHTML = '✅ Aprovar';
                    }
                })
                .catch(err => {
                    console.error('[APROVAR] Erro:', err);
                    alert('Erro de conexão.');
                    btn.disabled = false;
                    btn.innerHTML = '✅ Aprovar';
                });
        });
    });

    document.querySelectorAll('.btn-rejeitar-solicitacao').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const comunidadeId = this.dataset.comunidade;
            const usuarioId = this.dataset.usuario;
            const csrfToken = document.getElementById('csrf_token')?.value || '';
            const item = this.closest('.solicitacao-item');
            const btn = this;

            if (!confirm('Rejeitar entrada de @' + item?.querySelector('span')?.textContent?.trim() + '?')) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                        btn.disabled = false;
                        btn.innerHTML = '✕ Rejeitar';
                    }
                })
                .catch(err => {
                    console.error('[REJEITAR] Erro:', err);
                    alert('Erro de conexão.');
                    btn.disabled = false;
                    btn.innerHTML = '✕ Rejeitar';
                });
        });
    });

    // ============================================================
    // BOTÃO ENTRAR/SAIR (COMUNIDADE PÚBLICA – já existente, mantido)
    // ============================================================
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
        iniciarTodosCarrosseis();
        if (feedContainer) {
            const observerCarrossel = new MutationObserver(function() {
                iniciarTodosCarrosseis();
            });
            observerCarrossel.observe(feedContainer, {
                childList: true,
                subtree: true
            });
        }
    });

    carregarFeedComunidade();

    if (btnLoad) {
        btnLoad.addEventListener('click', carregarFeedComunidade);
    }

    // ============================================================
    // REAÇÕES – POPUP E CLIQUE (adaptado para comunidade)
    // ============================================================
    document.addEventListener('click', function(e) {
        const btnReagir = e.target.closest('.btn-reagir');
        if (btnReagir) {
            e.stopPropagation();
            const wrapper = btnReagir.closest('.reacao-wrapper');
            document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => {
                if (w !== wrapper) w.classList.remove('popup-ativo');
            });
            wrapper.classList.toggle('popup-ativo');
            return;
        }
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

<!-- =======================
    MODAL GERENCIAR MEMBROS 
    ======================== -->
<?php include 'gerenciar-membros-modal.php'; ?>

<?php include 'includes/footer.php'; ?>