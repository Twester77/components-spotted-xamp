<?php
/**
 * ver-perfil.php – Página pública de perfil de um habitante
 * 🔥 FASE 5 (Sprint 3): Refatorado para Abas AJAX (Posts, Depoimentos, Avaliações)
 */

// 1. Conexão em primeiro lugar (já starta a sessão pelo conexao.php)
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/includes/upload_engine.php';

// 🔥 GARANTE CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🚨 CURTO-CIRCUITO DE SEGURANÇA MÁXIMA
if (!isset($_GET['user'])) {
    if (isset($_SESSION['usuario_id'])) {
        $meu_id_fallback = $_SESSION['usuario_id'];
        $busca_nome = mysqli_query($conn, "SELECT username FROM usuarios WHERE id = '$meu_id_fallback'");
        if ($busca_nome && $dados_nome = mysqli_fetch_assoc($busca_nome)) {
            header("Location: ver-perfil.php?user=" . $dados_nome['username']);
            exit();
        }
    }
    header("Location: feed.php");
    exit();
}

// SÓ DAQUI PRA BAIXO O PHP PODE CUSPIR LAYOUT NA TELA
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php'; 


$user_get = $_GET['user'];
$sql = "SELECT * FROM usuarios WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_get);
$stmt->execute();
$res = $stmt->get_result();
$dados = $res->fetch_assoc();
$stmt->close();

if (!$dados) {
    echo "<main class='erro-fenda'><h2>Habitante não localizado, tente outro nome por favor!</h2></main>";
    include 'includes/footer.php';
    exit();
}

$id_visto = $dados['id'];
$meu_id = $_SESSION['usuario_id'];
$ja_segue = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'")) > 0;

// PREFERÊNCIAS DE AURA
$vibe_user = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
$cor_user  = $dados['pref_cor_padrao'] ?? '#08f7ff';

// 🔒 Sanitização defensiva
if (!is_string($cor_user) || !preg_match('/^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/', $cor_user)) {
    $cor_user = '#08f7ff';
}
$vibes_validas = ['vibe-glass', 'vibe-neon', 'vibe-dark', 'vibe-light', 'vibe-ads'];
if (!in_array($vibe_user, $vibes_validas, true)) {
    $vibe_user = 'vibe-glass';
}

$foto_limpa = !empty($dados['foto']) ? htmlspecialchars($dados['foto'], ENT_QUOTES, 'UTF-8') : '';
$capa_limpa = !empty($dados['capa']) ? htmlspecialchars($dados['capa'], ENT_QUOTES, 'UTF-8') : '';

try { $b2 = B2Client::getInstance(); } catch (Exception $e) { $b2 = null; }

$foto_user = obterUrlComFallback($foto_limpa, 'uploads/ui/default_masculino.webp', $b2, true);
$capa_user = obterUrlComFallback($capa_limpa, 'uploads/ui/default_capa_masculino.webp', $b2, true);

$is_presenca = ($id_visto == 1);
$total_seguidores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'"))['total'];
?>

<style>
    .avatar-main {
        border: 3px solid <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?>;
        box-shadow: 0 0 8px <?php echo $cor_user; ?>55;
    }
    <?php if ($is_presenca): ?>
    .avatar-main { box-shadow: 0 0 5px rgba(255, 188, 0, 0.7); }
    <?php endif; ?>

</style>

<main class="main-perfil-container-publico <?php echo $vibe_user; ?> <?php echo $is_presenca ? 'perfil-gold' : ''; ?>" style="--aura-user: <?php echo $cor_user; ?>;">

    <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <!-- HEXÁGONOS (ADS) -->
    <?php if ($vibe_user === 'vibe-ads'): ?>
        <div class="hex-bg">
            <?php
            $total = 30;
            for ($i = 0; $i < $total; $i++):
                $tipo = ($i % 3 === 0) ? 'dynamic' : 'static';
                $floatDelay = number_format(mt_rand(0, 80) / 10, 1);
            ?>
                <div class="hex-item <?php echo $tipo; ?>" style="animation-delay: <?php echo $floatDelay; ?>s; <?php if ($tipo === 'dynamic'): ?> data-index="<?php echo $i; ?>" <?php endif; ?>"></div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- CAPA + AVATAR + BIO -->
    <div class="perfil-header-container">
        <div class="capa-container">
            <?php if (!empty($dados['capa'])): ?>
                <button type="button" class="perfil-imagem-trigger capa-imagem-trigger" aria-label="Ampliar capa do perfil">
                    <img src="<?= htmlspecialchars($capa_user ?? '', ENT_QUOTES, 'UTF-8') ?>" class="capa-img" alt="Capa do perfil" onerror="this.src='uploads/ui/default_capa_masculino.webp';">
                </button>
            <?php else: ?>
                <div class="capa-default" style="background: linear-gradient(135deg, <?php echo $cor_user; ?>88 0%, #000 100%); width: 100%; height: 100%;"></div>
            <?php endif; ?>
            <div class="avatar-posicionador">
                <button type="button" class="perfil-imagem-trigger avatar-imagem-trigger" aria-label="Ampliar foto de perfil">
                    <img src="<?= htmlspecialchars($foto_user ?? '', ENT_QUOTES, 'UTF-8') ?>" class="avatar-main" alt="Foto de perfil" onerror="this.src='uploads/ui/default_masculino.webp';">
                </button>
                <?php if ($is_presenca): ?>
                    <div class="badge-presenca-bottom"><i class="fa-solid fa-crown"></i></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-usuario-section">
            <div class="nome-linha">
                <h1 class="nome-publico"><?php echo htmlspecialchars($dados['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php if (!empty($dados['atletica_id'])): ?>
                    <a href="atleticas.php?id=<?php echo urlencode($dados['atletica_id'] ?? ''); ?>">
                        <img src="badges/<?php echo htmlspecialchars($dados['atletica_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>.webp" class="insignia-atletica-bottom" alt="Seu bottom de atlética - link para comunidade">
                    </a>
                <?php endif; ?>
            </div>

            <div class="stats-perfil">
                <span style="font-weight: bold;"><?php echo $total_seguidores; ?> SEGUIDORES</span>
            </div>

            <div class="bio-texto">
                <?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'] ?? '', ENT_QUOTES, 'UTF-8')) : "Habitante da Fenda..."; ?>
            </div>

            <div class="perfil-controles-publico">
                <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
                    <button type="button"
                        class="btn-seguir-fenda <?php echo $ja_segue ? 'seguindo' : ''; ?>"
                        id="btn-seguir-perfil"
                        data-id="<?php echo (int)$id_visto; ?>"
                        data-user="<?php echo htmlspecialchars($user_get, ENT_QUOTES, 'UTF-8'); ?>"
                        data-cor="<?php echo htmlspecialchars($cor_user, ENT_QUOTES, 'UTF-8'); ?>"
                        style="background: <?php echo $ja_segue ? 'transparent' : htmlspecialchars($cor_user, ENT_QUOTES, 'UTF-8'); ?>; border-color: <?php echo htmlspecialchars($cor_user, ENT_QUOTES, 'UTF-8'); ?>; cursor: pointer;">
                        <?php echo $ja_segue ? '<i class="fa-solid fa-check"></i> Seguindo' : '+ Seguir'; ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
    ABAS DO PERFIL (SUBSTITUI O SOCIAL COLLAPSE)
    ============================================================ -->
    <div class="perfil-abas-container">
        <nav class="perfil-abas-nav">
            <button class="aba-perfil ativa" data-aba="posts" data-url="motor-perfil.php?usuario_id=<?= $id_visto ?>&aba=posts">
                <i class="fas fa-pen-to-square"></i> Posts
            </button>
            <button class="aba-perfil" data-aba="depoimentos" data-url="motor-perfil.php?usuario_id=<?= $id_visto ?>&aba=depoimentos">
                <i class="fas fa-quote-left"></i> Depoimentos
            </button>
            <button class="aba-perfil" data-aba="avaliacoes" data-url="motor-perfil.php?usuario_id=<?= $id_visto ?>&aba=avaliacoes">
                <i class="fas fa-star"></i> Avaliações
            </button>
        </nav>

        <div id="perfil-conteudo" class="perfil-conteudo">
            <div class="loading-perfil" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ffbc00;"></i>
                <p style="color: #ccc; margin-top: 10px;">Carregando...</p>
            </div>
        </div>
    </div>

    <!-- MODAL DE ESCREVER DEPOIMENTO (Fica oculto, mas é chamado pela aba) -->
    <div id="modal-depoimento" class="modal-depoimento-overlay" style="display: none;">
        <div class="modal-depoimento-content">
            <button type="button" class="modal-depoimento-fechar" id="btn-fechar-modal-depoimento">&times;</button>
            <div id="modal-depoimento-body">
                <div class="loading-depoimentos">Carregando formulário...</div>
            </div>
        </div>
    </div>

</main>

<dialog id="perfil-imagem-lightbox" class="perfil-imagem-lightbox" aria-label="Imagem ampliada do perfil">
    <button type="button" class="perfil-imagem-lightbox-fechar" aria-label="Fechar imagem ampliada">&times;</button>
    <img class="perfil-imagem-lightbox-conteudo" alt="">
</dialog>

<script>
    // ============================================================
    // LIGHTBOX DO PERFIL
    // ============================================================
    (function() {
        const dialog = document.getElementById('perfil-imagem-lightbox');
        const imagemAmpliada = dialog?.querySelector('.perfil-imagem-lightbox-conteudo');
        const botaoFechar = dialog?.querySelector('.perfil-imagem-lightbox-fechar');
        if (!dialog || !imagemAmpliada || !botaoFechar) return;

        function abrirImagem(imagem) {
            imagemAmpliada.src = imagem.currentSrc || imagem.src;
            imagemAmpliada.alt = imagem.alt;
            dialog.showModal();
            botaoFechar.focus();
        }

        document.querySelectorAll('.main-perfil-container-publico .perfil-imagem-trigger').forEach(botao => {
            const imagem = botao.querySelector('img');
            if (imagem) botao.addEventListener('click', () => abrirImagem(imagem));
        });

        botaoFechar.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => { imagemAmpliada.removeAttribute('src'); imagemAmpliada.alt = ''; });
    })();

    // ============================================================
    // SEGUIR/DESSEGUIR
    // ============================================================
    (function() {
        const btn = document.getElementById('btn-seguir-perfil');
        if (!btn) return;

        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (btn.disabled) return;

            const seguidoId = btn.dataset.id;
            const username  = btn.dataset.user;
            const auraColor = btn.dataset.cor || '#28a745';
            const csrfToken = document.getElementById('csrf_token')?.value || '';

            if (!csrfToken) { alert('Erro de segurança.'); return; }

            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('id', seguidoId);
            formData.append('user', username);
            formData.append('csrf_token', csrfToken);

            fetch('seguir.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const seguindo = data.seguindo;
                    btn.classList.toggle('seguindo', seguindo);
                    btn.style.background = seguindo ? '' : auraColor;
                    btn.style.borderColor = auraColor;
                    btn.innerHTML = seguindo ? '<i class="fa-solid fa-check"></i> Seguindo' : '+ Seguir';

                    if (typeof data.total_seguidores !== 'undefined') {
                        const statsSpan = document.querySelector('.stats-perfil span');
                        if (statsSpan) statsSpan.textContent = data.total_seguidores + ' SEGUIDORES';
                    }

                    if (typeof window.exibirBalaoFenda === 'function') {
                        window.exibirBalaoFenda(seguindo ? '✅ Seguindo!' : '👋 Deixou de seguir.', 'sucesso', btn);
                    }
                } else {
                    btn.innerHTML = originalHtml;
                    alert(data.message || 'Erro ao processar.');
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = originalHtml;
                alert('Erro de conexão.');
            })
            .finally(() => { btn.disabled = false; });
        });
    })();

    // ============================================================
    // SISTEMA DE ABAS (AJAX)
    // ============================================================
    (function() {
        const abas = document.querySelectorAll('.aba-perfil');
        const container = document.getElementById('perfil-conteudo');
        const usuarioId = <?php echo $id_visto; ?>;

            function carregarAba(btn) {
            const aba = btn.dataset.aba;
            const url = btn.dataset.url;

            abas.forEach(b => b.classList.remove('ativa'));
            btn.classList.add('ativa');

            container.innerHTML = `
                <div class="loading-perfil" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ffbc00;"></i>
                    <p style="color: #ccc; margin-top: 10px;">Carregando...</p>
                </div>
            `;

            fetch(url + '&_=' + Date.now())
                .then(res => res.text())
                .then(html => {
                    // 🔥 O SEGREDO ESTÁ AQUI: Se for posts, envolve no container-feed para pegar o grid!
                    if (aba === 'posts') {
                        container.innerHTML = `<div class="container-feed">${html}</div>`;
                    } else {
                        container.innerHTML = html;
                    }
                    
                    // Hooks de inicialização para cada aba
                    if (aba === 'posts' && typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                        configurarPosts();
                    }
                    if (aba === 'depoimentos') {
                        if (typeof window.initModalDepoimento === 'function') {
                            window.initModalDepoimento();
                        }
                    }
                    if (aba === 'avaliacoes') {
                        if (typeof window.initAvaliacoesActions === 'function') {
                            window.initAvaliacoesActions();
                        }
                    }
                })
                .catch(err => {
                    console.error('[PERFIL ABAS] Erro:', err);
                    container.innerHTML = `<p style="text-align:center; color:#ff6b6b; padding:20px;">Erro ao carregar. Tente novamente.</p>`;
                });
        }

        if (abas.length > 0) carregarAba(abas[0]);
        abas.forEach(btn => btn.addEventListener('click', () => carregarAba(btn)));
    })();

    // ============================================================
    // MODAL DE ESCREVER DEPOIMENTO (GLOBAL)
    // ============================================================
    window.initModalDepoimento = function() {
        const btnAbrir = document.getElementById('btn-abrir-modal-depoimento');
        const modal = document.getElementById('modal-depoimento');
        const btnFechar = document.getElementById('btn-fechar-modal-depoimento');
        const body = document.getElementById('modal-depoimento-body');

        if (!btnAbrir || !modal) return;

        function abrirModal() {
            const destinatarioId = btnAbrir.dataset.destinatario;
            modal.style.display = 'flex';
            body.innerHTML = '<div class="loading-depoimentos">Carregando formulário...</div>';

            fetch(`escrever-depoimento-modal.php?destinatario=${destinatarioId}`)
                .then(res => res.text())
                .then(html => {
                    body.innerHTML = html;
                    const csrfInput = body.querySelector('input[name="csrf_token"]');
                    if (csrfInput) {
                        csrfInput.value = document.getElementById('csrf_token')?.value || '';
                    }
                    
                    // Handler do form
                    const form = body.querySelector('#form-depoimento');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            const btnSubmit = form.querySelector('button[type="submit"]');
                            const originalText = btnSubmit.innerHTML;
                            btnSubmit.disabled = true;
                            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

                            const formData = new FormData(form);
                            fetch('processa-depoimento.php', {
                                method: 'POST',
                                body: formData,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('✅ Depoimento enviado! Aguarde aprovação.');
                                    fecharModal();
                                    // Recarrega a aba de depoimentos
                                    const btnAbaDep = document.querySelector('.aba-perfil[data-aba="depoimentos"]');
                                    if (btnAbaDep) btnAbaDep.click();
                                } else {
                                    alert('❌ ' + (data.message || 'Erro.'));
                                    btnSubmit.disabled = false;
                                    btnSubmit.innerHTML = originalText;
                                }
                            })
                            .catch(() => {
                                alert('Erro de conexão.');
                                btnSubmit.disabled = false;
                                btnSubmit.innerHTML = originalText;
                            });
                        });
                    }
                });
        }

        function fecharModal() {
            modal.style.display = 'none';
            body.innerHTML = '';
        }

        btnAbrir.addEventListener('click', abrirModal);
        btnFechar.addEventListener('click', fecharModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) fecharModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.style.display === 'flex') fecharModal(); });
    };

    // ============================================================
    // HEXÁGONOS (ADS)
    // ============================================================
    (function() {
        const container = document.querySelector('.main-perfil-container-publico.vibe-ads');
        if (!container) return;
        const hexItems = container.querySelectorAll('.hex-item.dynamic');
        if (!hexItems.length) return;

        const config = { waveInterval: 1600, maxActive: Math.min(12, hexItems.length), minActive: 4, activeHexes: new Set() };

        function toggleHex(index, state) {
            const hex = hexItems[index];
            if (hex) state ? hex.classList.add('active') : hex.classList.remove('active');
        }

        function wave() {
            const toRemove = Math.floor(config.activeHexes.size * 0.6);
            Array.from(config.activeHexes).slice(0, toRemove).forEach(idx => {
                toggleHex(idx, false); config.activeHexes.delete(idx);
            });
            const available = [];
            for (let i = 0; i < hexItems.length; i++) if (!config.activeHexes.has(i)) available.push(i);
            const shuffled = available.sort(() => Math.random() - 0.5);
            const targetCount = Math.floor(Math.random() * (config.maxActive - config.minActive + 1)) + config.minActive;
            shuffled.slice(0, Math.min(targetCount, shuffled.length)).forEach(idx => {
                toggleHex(idx, true); config.activeHexes.add(idx);
            });
            setTimeout(wave, config.waveInterval + (Math.random() * 800) - 400);
        }
        setTimeout(wave, 500);
    })();

    // ============================================================
    // REAÇÕES (GLOBAL)
    // ============================================================
    document.addEventListener('click', function(e) {
        const btnReagir = e.target.closest('.btn-reagir');
        if (btnReagir) {
            e.stopPropagation();
            const wrapper = btnReagir.closest('.reacao-wrapper');
            document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => { if (w !== wrapper) w.classList.remove('popup-ativo'); });
            wrapper.classList.toggle('popup-ativo');
            return;
        }
        if (!e.target.closest('.reacoes-popup')) {
            document.querySelectorAll('.reacao-wrapper.popup-ativo').forEach(w => w.classList.remove('popup-ativo'));
        }
    });

    console.log('[VER-PERFIL] ✅ Abas inicializadas. Módulos prontos.');
</script>

<?php include 'includes/footer.php'; ?>