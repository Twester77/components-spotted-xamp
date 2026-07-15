<?php
// 1. Conexão em primeiro lugar (já starta a sessão pelo conexao.php)
require_once __DIR__ . '/auth_check.php';

require_once __DIR__ . '/includes/upload_engine.php';

// 🚨 CURTO-CIRCUITO DE SEGURANÇA MÁXIMA (Sem confiar em username de sessão)
if (!isset($_GET['user'])) {
    if (isset($_SESSION['usuario_id'])) {
        $meu_id_fallback = $_SESSION['usuario_id'];

        // Vamos direto na Fonte da Verdade (O Banco de Dados) pelo ID que NUNCA muda!
        $busca_nome = mysqli_query($conn, "SELECT username FROM usuarios WHERE id = '$meu_id_fallback'");

        if ($busca_nome && $dados_nome = mysqli_fetch_assoc($busca_nome)) {
            // Redireciona com o username mais atualizado do universo
            header("Location: ver-perfil.php?user=" . $dados_nome['username']);
            exit();
        }
    }

    // Se não achar nada ou não estiver logado, feed nele
    header("Location: feed.php");
    exit();
}

//  SÓ DAQUI PRA BAIXO O PHP PODE CUSPIR LAYOUT NA TELA
include 'includes/header.php';
include 'includes/navbar.php';

$user_get = mysqli_real_escape_string($conn, $_GET['user']);
// BUSCA COMPLETA: Agora pegamos as preferências de visual
$sql = "SELECT * FROM usuarios WHERE username = '$user_get'";
$res = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($res);

if (!$dados) {
    echo "<main class= 'erro-fenda'><h2>Habitante não localizado, tente outro nome por favor! </h2></main>";
    include 'includes/footer.php';
    exit();
}

$id_visto = $dados['id'];
$meu_id = $_SESSION['usuario_id'];
$ja_segue = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM seguidores WHERE id_seguidor = '$meu_id' AND id_seguido = '$id_visto'")) > 0;

// PREFERÊNCIAS DE AURA
$vibe_user = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
$cor_user  = $dados['pref_cor_padrao'] ?? '#ffffff';

// 1. Limpa os dados vindos do banco para evitar erros
$foto_limpa = !empty($dados['foto']) ? htmlspecialchars($dados['foto'], ENT_QUOTES, 'UTF-8') : '';
$capa_limpa = !empty($dados['capa']) ? htmlspecialchars($dados['capa'], ENT_QUOTES, 'UTF-8') : '';

// ============================================================
// 🔥 CORREÇÃO: OBTÉM AS URLs VIA PROXY (B2)
// ============================================================
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}

// Foto (avatar) – usa proxy se existir, fallback local
if (!empty($foto_limpa)) {
    $foto_user = obterUrlImagem($foto_limpa, $b2, true) ?? 'uploads/ui/default_masculino.jpg';
} else {
    $foto_user = 'uploads/ui/default_masculino.jpg';
}

// Capa – usa proxy se existir, fallback local
if (!empty($capa_limpa)) {
    $capa_user = obterUrlImagem($capa_limpa, $b2, true) ?? 'uploads/ui/default_capa_masculino.webp';
} else {
    $capa_user = 'uploads/ui/default_capa_masculino.webp';
}

$is_presenca = ($id_visto == 1);

$total_seguidores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'"))['total'];
?>

<style>
    /* Aplicando a cor e brilho dinâmico no Avatar */
    .avatar-main {
        border: 3px solid <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?>;
        box-shadow: 0 0 8px <?php echo $cor_user; ?>55;
        /* Cor com transparência */
    }

    <?php if ($is_presenca): ?>.avatar-main {
        box-shadow: 0 0 5px rgba(255, 188, 0, 0.7);
    }

    <?php endif; ?>
</style>

<!-- Adicionamos a classe da Vibe e variavel de cor diretamente no container principal -->
<main class="main-perfil-container-publico <?php echo $vibe_user; ?> <?php echo $is_presenca ? 'perfil-gold' : ''; ?>"
    style="--aura-user: <?php echo $cor_user; ?>;">

    <!-- <?php if ($vibe_user === 'vibe-ads'): ?> -->
        <div class="hex-bg">
            <?php
            // Total de hexágonos para cobrir a tela (ajuste conforme necessário)
            $total = 30;
            for ($i = 0; $i < $total; $i++):
                // Define se é estático ou dinâmico (proporção ~70% estático, 30% dinâmico)
                $tipo = ($i % 3 === 0) ? 'dynamic' : 'static';
                // Delay aleatório para a flutuação (entre 0s e 8s)
                $floatDelay = number_format(mt_rand(0, 80) / 10, 1);
                // Se for dinâmico, o delay da ativação será controlado pelo JS
            ?>
                <div class="hex-item <?php echo $tipo; ?>"
                    style="animation-delay: <?php echo $floatDelay; ?>s;
                    <?php if ($tipo === 'dynamic'): ?>
                    data-index=" <?php echo $i; ?>"
                    <?php endif; ?>">
                </div>
            <?php endfor; ?>
        </div>
    <!-- <?php endif; ?> -->

    <div class="perfil-header-container">
        <div class="capa-container">
            <?php if (!empty($dados['capa'])): ?>
                <img src="<?= htmlspecialchars($capa_user) ?>" class="capa-img" alt="Sua capa" onerror="this.src='uploads/ui/default_capa_masculino.webp';">
            <?php else: ?>
                <div class="capa-default" style="background: linear-gradient(135deg, <?php echo $cor_user; ?>88 0%, #000 100%); width: 100%; height: 100%;"></div>
            <?php endif; ?>

            <div class="avatar-posicionador">
                <img src="<?= htmlspecialchars($foto_user) ?>" class="avatar-main" alt="Sua foto de perfil" onerror="this.src='uploads/ui/default_masculino.jpg';">
                <?php if ($is_presenca): ?>
                    <div class="badge-presenca-bottom"><i class="fa-solid fa-crown"></i></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-usuario-section">
            <div class="nome-linha">
                <h1 class="nome-publico"><?php echo htmlspecialchars($dados['nome']); ?></h1>
                <?php if (!empty($dados['atletica_id'])): ?>
                    <a href="atleticas.php?id=<?php echo urlencode($dados['atletica_id']); ?>">
                        <img src="badges/<?php echo htmlspecialchars($dados['atletica_id']); ?>.webp" class="insignia-atletica-bottom" alt="Seu bottom de atlética - link para comunidade">
                    </a>
                <?php endif; ?>
            </div>

            <div class="stats-perfil">
                <span style="color: <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?>; font-weight: bold;">
                    <?php echo $total_seguidores; ?> SEGUIDORES
                </span>
            </div>

            <div class="bio-texto">
                <?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'])) : "Habitante da Fenda..."; ?>
            </div>

            <div class="perfil-controles">
                <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
                    <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>"
                        class="btn-seguir-fenda <?php echo $ja_segue ? 'seguindo' : ''; ?>"
                        style="background: <?php echo $ja_segue ? 'transparent' : $cor_user; ?>; border-color: <?php echo $cor_user; ?>;">
                        <?php echo $ja_segue ? '<i class="fa-solid fa-check"></i> Seguindo' : '+ Seguir'; ?>
                    </a>
                <?php else: ?>
                    <a href="perfil.php" class="btn-editar-atalho">EDITAR MEU PERFIL</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ÁREA DO FEED PESSOAL -->
    <section class="feed-usuario-fenda" style="margin-top: 30px;">
        <h3 style="text-align: center; color: #ccc; margin-bottom: 20px;">ÚLTIMAS POSTAGENS DE @<?php echo strtoupper($user_get); ?></h3>
        <div class="container-feed">
            <!-- O Motor Universal vai preencher aqui -->
        </div>

        <div class="container-load-more" style="text-align: center; margin-top: 20px;">
            <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais</button>
        </div>
    </section>

    <script>
        (function() {
            'use strict';

            // Só executa se a vibe for ADS
            const container = document.querySelector('.main-perfil-container-publico.vibe-ads');
            if (!container) return;

            // Busca APENAS os hexágonos dinâmicos (com a classe .dynamic)
            const hexItems = container.querySelectorAll('.hex-item.dynamic');
            if (!hexItems.length) return;

            // Configurações
            const config = {
                waveInterval: 1600, // intervalo entre ondas (ms)
                maxActive: Math.min(10, hexItems.length), // máximo acesos por vez
                minActive: 3,
                activeHexes: new Set()
            };

            // Função para ativar/desativar um hexágono dinâmico
            function toggleHex(index, state) {
                const hex = hexItems[index];
                if (!hex) return;
                if (state) {
                    hex.classList.add('active');
                } else {
                    hex.classList.remove('active');
                }
            }

            // Função que gera uma "onda" aleatória
            function wave() {
                // Remove alguns hexágonos ativos (apaga ~60% deles)
                const toRemove = Math.floor(config.activeHexes.size * 0.6);
                const removeList = Array.from(config.activeHexes);
                for (let i = 0; i < Math.min(toRemove, removeList.length); i++) {
                    const idx = removeList[i];
                    toggleHex(idx, false);
                    config.activeHexes.delete(idx);
                }

                // Escolhe novos hexágonos para ativar (entre minActive e maxActive)
                const available = [];
                for (let i = 0; i < hexItems.length; i++) {
                    if (!config.activeHexes.has(i)) available.push(i);
                }

                // Embaralha e seleciona alguns
                const shuffled = available.sort(() => Math.random() - 0.5);
                const targetCount = Math.floor(Math.random() * (config.maxActive - config.minActive + 1)) + config.minActive;
                const toActivate = shuffled.slice(0, Math.min(targetCount, shuffled.length));

                toActivate.forEach(idx => {
                    toggleHex(idx, true);
                    config.activeHexes.add(idx);
                });

                // Agenda a próxima onda com variação aleatória
                const nextDelay = config.waveInterval + (Math.random() * 800) - 400;
                setTimeout(wave, nextDelay);
            }

            // Inicia o ciclo
            setTimeout(wave, 500);

            // (Opcional) Pausar quando a página não estiver visível – melhora performance
            document.addEventListener('visibilitychange', function() {
                // Se quiser pausar, pode adicionar lógica aqui
            });

        })();
    </script>
</main>

<script>
    let offset = 0;
    const urlParams = new URLSearchParams(window.location.search);
    const usuarioAlvo = urlParams.get('user');
    const btnLoad = document.getElementById('btn-load-more');
    const feedContainer = document.querySelector('.container-feed');

    function carregarFeedPerfil() {
        if (btnLoad) btnLoad.innerText = "BUSCANDO NA FENDA...";

        // Chamada ao Motor Universal com filtro de perfil
        fetch(`motor-feed.php?offset=${offset}&tipo=perfil&user=${usuarioAlvo}`)
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "FIM_DADOS") {
                    if (btnLoad) btnLoad.style.display = "none";
                } else {
                    // Insere os novos posts
                    feedContainer.insertAdjacentHTML('beforeend', data);

                    // 🔥 RECONFIGURA OS POSTS PARA ATIVAR O "LER MAIS" (SE NÃO ESTIVER NO MODO SWIPE)
                    if (typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                        configurarPosts();
                    }

                    offset += 10;
                    if (btnLoad) btnLoad.innerText = "EXIBIR MAIS";
                }
            })
            .catch(err => {
                console.error("[AJAX] Erro ao carregar feed do perfil:", err);
                if (btnLoad) btnLoad.innerText = "ERRO AO CARREGAR";
            });
    }

    // ==================== 🔥 OBSERVADOR PARA ALTERNÂNCIA DE MODOS ====================
    // Reconfigura os posts quando o usuário sai do modo swipe
    const observerModoSwipe = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                // Verifica se a classe 'modo-swipe-ativo' foi removida
                if (!document.body.classList.contains('modo-swipe-ativo')) {
                    // Saiu do modo swipe → reconfigura os posts para ativar "Ler Mais"
                    if (typeof configurarPosts === 'function') {
                        configurarPosts();
                    }
                }
            }
        });
    });

    // Inicia a observação no body
    observerModoSwipe.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    // ==================== INICIALIZAÇÃO ====================
    carregarFeedPerfil();

    if (btnLoad) {
        btnLoad.addEventListener('click', carregarFeedPerfil);
    }
</script>

<?php include 'includes/footer.php'; ?>