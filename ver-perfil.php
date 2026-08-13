<?php
// 1. Conexão em primeiro lugar (já starta a sessão pelo conexao.php)
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/includes/upload_engine.php';

// 🔥 GARANTE CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🚨 CURTO-CIRCUITO DE SEGURANÇA MÁXIMA (Sem confiar em username de sessão)
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

// 🔥 Usa prepared statement em vez de mysqli_real_escape_string
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

$foto_limpa = !empty($dados['foto']) ? htmlspecialchars($dados['foto'], ENT_QUOTES, 'UTF-8') : '';
$capa_limpa = !empty($dados['capa']) ? htmlspecialchars($dados['capa'], ENT_QUOTES, 'UTF-8') : '';

// OBTÉM AS URLs VIA PROXY (B2)
try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
}

$foto_user = !empty($foto_limpa) ? (obterUrlImagem($foto_limpa, $b2, true) ?? 'uploads/ui/default_masculino.jpg') : 'uploads/ui/default_masculino.jpg';
$capa_user = !empty($capa_limpa) ? (obterUrlImagem($capa_limpa, $b2, true) ?? 'uploads/ui/default_capa_masculino.webp') : 'uploads/ui/default_capa_masculino.webp';

$is_presenca = ($id_visto == 1);
$total_seguidores = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM seguidores WHERE id_seguido = '$id_visto'"))['total'];
?>

<style>
    .avatar-main {
        border: 3px solid <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?>;
        box-shadow: 0 0 8px <?php echo $cor_user; ?>55;
    }

    <?php if ($is_presenca): ?>.avatar-main {
        box-shadow: 0 0 5px rgba(255, 188, 0, 0.7);
    }

    <?php endif; ?>
</style>

<main class="main-perfil-container-publico <?php echo $vibe_user; ?> <?php echo $is_presenca ? 'perfil-gold' : ''; ?>" style="--aura-user: <?php echo $cor_user; ?>;">

    <!-- CSRF Token (para as avaliações) -->
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
                <div class="hex-item <?php echo $tipo; ?>"
                    style="animation-delay: <?php echo $floatDelay; ?>s;
                    <?php if ($tipo === 'dynamic'): ?>
                    data-index=" <?php echo $i; ?>"
                    <?php endif; ?>">
                </div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================
    CAPA + AVATAR + BIO
    ============================================================ -->
    <div class="perfil-header-container">
        <div class="capa-container">
            <?php if (!empty($dados['capa'])): ?>
                <img src="<?= htmlspecialchars($capa_user ?? '', ENT_QUOTES, 'UTF-8') ?>" class="capa-img" alt="Sua capa" onerror="this.src='uploads/ui/default_capa_masculino.webp';">
            <?php else: ?>
                <div class="capa-default" style="background: linear-gradient(135deg, <?php echo $cor_user; ?>88 0%, #000 100%); width: 100%; height: 100%;"></div>
            <?php endif; ?>
            <div class="avatar-posicionador">
                <img src="<?= htmlspecialchars($foto_user ?? '', ENT_QUOTES, 'UTF-8') ?>" class="avatar-main" alt="Sua foto de perfil" onerror="this.src='uploads/ui/default_masculino.jpg';">
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
                <span style="color: <?php echo $is_presenca ? 'var(--dourado)' : $cor_user; ?>; font-weight: bold;">
                    <?php echo $total_seguidores; ?> SEGUIDORES
                </span>
            </div>

            <div class="bio-texto">
                <?php echo !empty($dados['bio']) ? nl2br(htmlspecialchars($dados['bio'] ?? '', ENT_QUOTES, 'UTF-8')) : "Habitante da Fenda..."; ?>
            </div>

            <div class="perfil-controles-publico">
                <?php if ($_SESSION['usuario_id'] != $id_visto): ?>
                    <a href="seguir.php?id=<?php echo $id_visto; ?>&user=<?php echo $user_get; ?>"
                        class="btn-seguir-fenda <?php echo $ja_segue ? 'seguindo' : ''; ?>"
                        style="background: <?php echo $ja_segue ? 'transparent' : $cor_user; ?>; border-color: <?php echo $cor_user; ?>;">
                        <?php echo $ja_segue ? '<i class="fa-solid fa-check"></i> Seguindo' : '+ Seguir'; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
    SOCIAL COLLAPSE (DEPOIMENTOS + AVALIAÇÕES) – ESTRUTURA OTIMIZADA
    ============================================================ -->
    <div class="social-collapse-container">
        <button class="btn-toggle-social" id="btn-toggle-social" aria-expanded="false">
            <i class="fas fa-chevron-down"></i>
            <span>Ver depoimentos e avaliações</span>
        </button>

        <!-- 🔥 NOVA ESTRUTURA: uma div filha exclusiva do .social-collapse com overflow hidden -->
        <div class="social-collapse" id="social-collapse">
            <div> <!-- esta div garante que o truque do grid funcione -->
                <!-- DEPOIMENTOS -->
                <section class="depoimentos-section">
                    <div class="depoimentos-header">
                        <h3><i class="fas fa-quote-left"></i> Depoimentos</h3>
                        <div class="depoimentos-actions">
                            <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] != $id_visto): ?>
                                <button type="button" class="btn-escrever-depoimento" id="btn-abrir-modal-depoimento" data-destinatario="<?= $id_visto ?>" data-username="<?= htmlspecialchars($dados['username']) ?>">
                                    <i class="fas fa-pen"></i> Escrever
                                </button>
                            <?php endif; ?>
                            <button id="btn-toggle-depoimentos" class="btn-toggle-depoimentos" aria-expanded="false">
                                <i class="fas fa-chevron-down"></i> <span id="depoimentos-toggle-texto">Ver mais</span>
                            </button>
                        </div>
                    </div>
                    <div id="depoimentos-container" data-usuario="<?= $id_visto ?>">
                        <div class="loading-depoimentos">Carregando depoimentos...</div>
                    </div>
                </section>

                <!-- AVALIAÇÕES -->
                <section class="avaliacoes-section">
                    <div class="avaliacoes-header">
                        <h3><i class="fas fa-star"></i> Avaliações</h3>
                    </div>
                    <div id="avaliacoes-container" data-usuario="<?= $id_visto ?>">
                        <div class="loading-avaliacoes">Carregando avaliações...</div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- ============================================================
    MODAL DE ESCREVER DEPOIMENTO
    ============================================================ -->
    <div id="modal-depoimento" class="modal-depoimento-overlay" style="display: none;">
        <div class="modal-depoimento-content">
            <button type="button" class="modal-depoimento-fechar" id="btn-fechar-modal-depoimento">&times;</button>
            <div id="modal-depoimento-body">
                <div class="loading-depoimentos">Carregando formulário...</div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    FEED PESSOAL
    ============================================================ -->
    <section class="feed-usuario-fenda" style="margin-top: 30px;">
        <h3 style="text-align: center; margin-bottom: 20px;">ÚLTIMAS POSTAGENS DE @<?php echo strtoupper($user_get); ?></h3>
        <div class="container-feed"><!-- O Motor Universal vai preencher aqui --></div>
        <div class="container-load-more" style="text-align: center; margin-top: 20px;">
            <button id="btn-load-more" class="btn-fenda-padrao">Exibir Mais</button>
        </div>
    </section>
</main>

<script>
    // ============================================================
    // LOG DE INICIALIZAÇÃO
    // ============================================================
    console.log('[VER-PERFIL] 🟢 Página carregada. Inicializando módulos...');

    // ============================================================
    // TOGGLE SOCIAL COLLAPSE (com a nova estrutura)
    // ============================================================
    (function() {
        const btn = document.getElementById('btn-toggle-social');
        const collapse = document.getElementById('social-collapse');
        if (!btn || !collapse) return;

        btn.addEventListener('click', function() {
            const isOpen = collapse.classList.toggle('aberto');
            this.classList.toggle('aberto');
            this.setAttribute('aria-expanded', isOpen);
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-chevron-down', !isOpen);
                icon.classList.toggle('fa-chevron-up', isOpen);
            }
            const span = this.querySelector('span');
            if (span) {
                span.textContent = isOpen ? 'Esconder depoimentos e avaliações' : 'Ver depoimentos e avaliações';
            }
        });
    })();

    // ============================================================
    // MODAL DE ESCREVER DEPOIMENTO
    // ============================================================
    (function() {
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
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.text();
                })
                .then(html => {
                    body.innerHTML = html;
                    const csrfInput = body.querySelector('input[name="csrf_token"]');
                    if (csrfInput) {
                        const token = document.getElementById('csrf_token')?.value || '';
                        csrfInput.value = token;
                    }
                    configurarEnvioDepoimento();
                })
                .catch(err => {
                    console.error('[MODAL DEPOIMENTO] Erro ao carregar:', err);
                    body.innerHTML = '<p style="text-align:center; color:#ff6b6b; padding:20px;">Erro ao carregar formulário. Tente novamente.</p>';
                });
        }

        function fecharModal() {
            modal.style.display = 'none';
            body.innerHTML = '';
        }

        btnAbrir.addEventListener('click', abrirModal);
        btnFechar.addEventListener('click', fecharModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) fecharModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                fecharModal();
            }
        });

        function configurarEnvioDepoimento() {
            const form = body.querySelector('#form-depoimento');
            if (!form) return;

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
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Depoimento enviado com sucesso! Aguarde a aprovação.');
                            fecharModal();
                            // 🔥 CORREÇÃO: Recarrega a lista de depoimentos do zero
                            const container = document.getElementById('depoimentos-container');
                            if (container) {
                                // Reseta o estado de carregamento para recarregar do início
                                const btnToggle = document.getElementById('btn-toggle-depoimentos');
                                // Chama a função de recarga com substituição total
                                if (typeof carregarDepoimentos === 'function') {
                                    carregarDepoimentos(3, true);
                                } else {
                                    // Fallback: recarrega a página
                                    location.reload();
                                }
                            }
                        } else {
                            alert('❌ ' + (data.message || 'Erro ao enviar depoimento.'));
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = originalText;
                        }
                    })
                    .catch(err => {
                        console.error('[MODAL DEPOIMENTO] Erro no envio:', err);
                        alert('Erro de conexão. Tente novamente.');
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalText;
                    });
            });
        }
    })();

    // ============================================================
    // DEPOIMENTOS (AJAX + BOTÃO "VER MAIS" COM OFFSET)
    // ============================================================
    (function() {
        console.log('[DEPOIMENTOS] Inicializando módulo...');

        const container = document.getElementById('depoimentos-container');
        const btnToggle = document.getElementById('btn-toggle-depoimentos');
        const textoBtn = document.getElementById('depoimentos-toggle-texto');

        if (!container) {
            console.error('[DEPOIMENTOS] ❌ Container #depoimentos-container não encontrado!');
            return;
        }

        let limite = 3;
        let carregando = false;
        let todosCarregados = false;
        let usuarioId = container.dataset.usuario;

        console.log('[DEPOIMENTOS] Usuário ID:', usuarioId);

        // 🔥 EXPORTA A FUNÇÃO PARA O ESCOPO GLOBAL
        window.carregarDepoimentos = function(novoLimite, substituir = true) {
            if (carregando) {
                console.log('[DEPOIMENTOS] ⏳ Já está carregando, ignorando...');
                return;
            }
            carregando = true;
            console.log(`[DEPOIMENTOS] 🔄 Carregando depoimentos (limite: ${novoLimite}, substituir: ${substituir})...`);

            if (substituir) {
                container.innerHTML = '<div class="loading-depoimentos">Carregando...</div>';
            }

            const url = `motor-depoimentos.php?usuario_id=${usuarioId}&status=aprovado&limite=${novoLimite}`;
            console.log('[DEPOIMENTOS] 📡 URL:', url);

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status} - ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('[DEPOIMENTOS] ✅ Resposta recebida (tamanho:', html.length, 'bytes)');
                    if (substituir) {
                        container.innerHTML = html;
                    } else {
                        container.insertAdjacentHTML('beforeend', html);
                    }

                    // Verifica se acabaram os depoimentos
                    if (html.includes('sem-depoimentos') || html.trim() === '') {
                        todosCarregados = true;
                        if (btnToggle) btnToggle.style.display = 'none';
                        console.log('[DEPOIMENTOS] 📭 Nenhum depoimento restante.');
                    } else {
                        const totalDepoimentos = container.querySelectorAll('.depoimento-item').length;
                        if (totalDepoimentos < novoLimite) {
                            todosCarregados = true;
                            if (btnToggle) btnToggle.style.display = 'none';
                            console.log('[DEPOIMENTOS] 📭 Todos os depoimentos carregados.');
                        } else {
                            todosCarregados = false;
                            if (btnToggle) {
                                btnToggle.style.display = 'flex';
                                textoBtn.textContent = 'Ver mais';
                                btnToggle.disabled = false;
                            }
                            console.log('[DEPOIMENTOS] ✅ Mais depoimentos disponíveis.');
                        }
                    }
                    carregando = false;
                })
                .catch(err => {
                    console.error('[DEPOIMENTOS] ❌ Erro no fetch:', err);
                    container.innerHTML = `<p class="sem-depoimentos">Erro ao carregar depoimentos: ${err.message}</p>`;
                    carregando = false;
                });
        };

        // ============================================================
        // BOTÃO "VER MAIS" – AUMENTA O LIMITE E RECARREGA A LISTA
        // ============================================================
        if (btnToggle) {
            btnToggle.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('[DEPOIMENTOS] 🖱️ Botão "Ver mais" clicado.');
                if (todosCarregados || carregando) {
                    console.log('[DEPOIMENTOS] ⏳ Já carregado ou em andamento.');
                    return;
                }
                // 🔥 AUMENTA O LIMITE EM 3 E RECARREGA A LISTA INTEIRA
                const novoLimite = limite + 3;
                textoBtn.textContent = 'Carregando...';
                this.disabled = true;
                window.carregarDepoimentos(novoLimite, true); // substitui a lista
                limite = novoLimite; // atualiza o limite para o próximo clique
            });
        }

        // Carrega os primeiros depoimentos (3)
        window.carregarDepoimentos(limite, true);
    })();

    // ============================================================
    // AVALIAÇÕES (CARREGAR E VOTAR) – COM CONFIRMAÇÃO
    // ============================================================
    (function() {
        console.log('[AVALIACOES] Inicializando módulo...');

        const container = document.getElementById('avaliacoes-container');
        if (!container) {
            console.error('[AVALIACOES] ❌ Container #avaliacoes-container não encontrado!');
            return;
        }

        const usuarioId = container.dataset.usuario;
        console.log('[AVALIACOES] Usuário ID:', usuarioId);

        // Estado para armazenar a nota selecionada por categoria
        let notasSelecionadas = {};

        function carregarAvaliacoes() {
            console.log('[AVALIACOES] 🔄 Carregando avaliações...');
            container.innerHTML = '<div class="loading-avaliacoes">Carregando...</div>';

            const url = `motor-avaliacoes.php?usuario_id=${usuarioId}`;
            console.log('[AVALIACOES] 📡 URL:', url);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.text();
                })
                .then(html => {
                    console.log('[AVALIACOES] ✅ Resposta recebida');
                    container.innerHTML = html;
                    iniciarEventosEstrelas();
                })
                .catch(err => {
                    console.error('[AVALIACOES] ❌ Erro:', err);
                    container.innerHTML = `<p class="sem-avaliacoes">Erro ao carregar avaliações: ${err.message}</p>`;
                });
        }

        function iniciarEventosEstrelas() {
            console.log('[AVALIACOES] ⭐ Ativando eventos das estrelas...');

            // Reseta estado de seleção
            notasSelecionadas = {};

            // Para cada estrela, ao clicar, seleciona a nota (não envia voto)
            document.querySelectorAll('.estrela').forEach(estrela => {
                estrela.removeEventListener('click', handlerSelecionarEstrela);
                estrela.addEventListener('click', handlerSelecionarEstrela);
            });

            // Botão "Votar" agora envia o voto com a nota selecionada
            document.querySelectorAll('.btn-votar-estrela').forEach(btn => {
                btn.removeEventListener('click', handlerVotar);
                btn.addEventListener('click', handlerVotar);
            });

            // Atualiza visualmente as estrelas com base na seleção
            // (se já houver uma nota selecionada para a categoria)
            Object.keys(notasSelecionadas).forEach(tipo => {
                atualizarEstrelas(tipo, notasSelecionadas[tipo]);
            });

            console.log('[AVALIACOES] ✅ Eventos ativados.');
        }

        function handlerSelecionarEstrela(e) {
            const estrela = e.currentTarget;
            const tipo = estrela.dataset.tipo;
            const nota = parseInt(estrela.dataset.nota);

            // Armazena a nota selecionada para esta categoria
            notasSelecionadas[tipo] = nota;

            // Atualiza visualmente as estrelas da categoria
            atualizarEstrelas(tipo, nota);

            // Habilita o botão "Votar" da categoria
            const btnVotar = document.querySelector(`.btn-votar-estrela[data-tipo="${tipo}"]`);
            if (btnVotar) {
                btnVotar.disabled = false;
                btnVotar.style.opacity = '1';
                btnVotar.textContent = '✅ Votar com ' + nota + ' ★';
            }

            // Feedback visual temporário (opcional)
            if (typeof exibirBalao === 'function') {
                exibirBalao(`Nota ${nota} selecionada para ${tipo}. Clique em "Votar" para confirmar.`, 'info', btnVotar, 2000);
            }
        }

        function atualizarEstrelas(tipo, notaSelecionada) {
            const estrelas = document.querySelectorAll(`.estrela[data-tipo="${tipo}"]`);
            estrelas.forEach((el, index) => {
                const nota = index + 1;
                // Remove classes antigas
                el.classList.remove('cheia', 'meia', 'vazia');
                if (nota <= notaSelecionada) {
                    el.classList.add('cheia');
                } else {
                    el.classList.add('vazia');
                }
            });
        }

        function handlerVotar(e) {
            const btn = e.currentTarget;
            const tipo = btn.dataset.tipo;
            const nota = notasSelecionadas[tipo];

            if (!nota) {
                alert('Selecione uma nota clicando nas estrelas primeiro.');
                return;
            }

            // Desabilita o botão para evitar duplo clique
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            const csrfToken = document.getElementById('csrf_token')?.value || '';
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('honeypot', '');
            formData.append('usuario_id', usuarioId);
            formData.append('tipo', tipo);
            formData.append('nota', nota);

            console.log('[AVALIACOES] 📤 Enviando voto...', {
                tipo,
                nota,
                usuarioId
            });

            fetch('motor-avaliacoes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        console.log('[AVALIACOES] ✅ Voto registrado!');
                        if (typeof exibirBalao === 'function') {
                            exibirBalao('✅ Voto registrado com sucesso!', 'sucesso', btn);
                        } else {
                            alert('✅ Voto registrado com sucesso!');
                        }
                        // Recarrega as avaliações para mostrar a nova média
                        carregarAvaliacoes();
                    } else {
                        console.error('[AVALIACOES] ❌ Erro:', data.erro);
                        if (typeof exibirBalao === 'function') {
                            exibirBalao('❌ ' + (data.erro || 'Erro ao votar.'), 'erro', btn);
                        } else {
                            alert('❌ ' + (data.erro || 'Erro ao votar.'));
                        }
                        btn.disabled = false;
                        btn.innerHTML = 'Votar';
                    }
                })
                .catch(err => {
                    console.error('[AVALIACOES] ❌ Erro de rede:', err);
                    if (typeof exibirBalao === 'function') {
                        exibirBalao('❌ Erro de conexão. Tente novamente.', 'erro', btn);
                    } else {
                        alert('Erro de conexão. Tente novamente.');
                    }
                    btn.disabled = false;
                    btn.innerHTML = 'Votar';
                });
        }

        // Inicializa
        carregarAvaliacoes();
    })();

    // ============================================================
    // HEXÁGONOS (ADS)
    // ============================================================
    (function() {
        console.log('[HEXAGONOS] Inicializando módulo...');

        const container = document.querySelector('.main-perfil-container-publico.vibe-ads');
        if (!container) {
            console.log('[HEXAGONOS] ⏭️ Vibe ADS não ativa. Pulando.');
            return;
        }
        const hexItems = container.querySelectorAll('.hex-item.dynamic');
        if (!hexItems.length) {
            console.log('[HEXAGONOS] ⏭️ Nenhum hexágono dinâmico encontrado.');
            return;
        }

        console.log('[HEXAGONOS] ✅', hexItems.length, 'hexágonos dinâmicos encontrados.');

        const config = {
            waveInterval: 1600,
            maxActive: Math.min(12, hexItems.length),
            minActive: 4,
            activeHexes: new Set()
        };

        function toggleHex(index, state) {
            const hex = hexItems[index];
            if (!hex) return;
            state ? hex.classList.add('active') : hex.classList.remove('active');
        }

        function wave() {
            const toRemove = Math.floor(config.activeHexes.size * 0.6);
            const removeList = Array.from(config.activeHexes);
            for (let i = 0; i < Math.min(toRemove, removeList.length); i++) {
                const idx = removeList[i];
                toggleHex(idx, false);
                config.activeHexes.delete(idx);
            }
            const available = [];
            for (let i = 0; i < hexItems.length; i++) {
                if (!config.activeHexes.has(i)) available.push(i);
            }
            const shuffled = available.sort(() => Math.random() - 0.5);
            const targetCount = Math.floor(Math.random() * (config.maxActive - config.minActive + 1)) + config.minActive;
            const toActivate = shuffled.slice(0, Math.min(targetCount, shuffled.length));
            toActivate.forEach(idx => {
                toggleHex(idx, true);
                config.activeHexes.add(idx);
            });
            setTimeout(wave, config.waveInterval + (Math.random() * 800) - 400);
        }
        setTimeout(wave, 500);
        console.log('[HEXAGONOS] 🎬 Ondas iniciadas.');
    })();

    // ============================================================
    // FEED PESSOAL
    // ============================================================
    (function() {
        console.log('[FEED] Inicializando módulo...');

        let offset = 0;
        const urlParams = new URLSearchParams(window.location.search);
        const usuarioAlvo = urlParams.get('user');
        const btnLoad = document.getElementById('btn-load-more');
        const feedContainer = document.querySelector('.container-feed');

        if (!feedContainer) {
            console.error('[FEED] ❌ Container .container-feed não encontrado!');
            return;
        }

        console.log('[FEED] Usuário alvo:', usuarioAlvo);

        function carregarFeedPerfil() {
            console.log('[FEED] 🔄 Carregando feed (offset:', offset, ')...');
            if (btnLoad) {
                btnLoad.innerText = "BUSCANDO NA FENDA...";
                btnLoad.disabled = true;
            }

            const url = `motor-feed.php?offset=${offset}&tipo=perfil&user=${usuarioAlvo}`;
            console.log('[FEED] 📡 URL:', url);

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status} - ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(data => {
                    if (data.trim() === "FIM_DADOS") {
                        console.log('[FEED] 📭 Fim do feed.');
                        if (btnLoad) {
                            btnLoad.style.display = "none";
                            btnLoad.disabled = false;
                        }
                    } else {
                        console.log('[FEED] ✅ Dados recebidos (tamanho:', data.length, 'bytes)');
                        feedContainer.insertAdjacentHTML('beforeend', data);
                        if (typeof configurarPosts === 'function' && !document.body.classList.contains('modo-swipe-ativo')) {
                            configurarPosts();
                        }
                        offset += 10;
                        if (btnLoad) {
                            btnLoad.innerText = "EXIBIR MAIS";
                            btnLoad.disabled = false;
                        }
                    }
                })
                .catch(err => {
                    console.error('[FEED] ❌ Erro no fetch:', err);
                    if (btnLoad) {
                        btnLoad.innerText = "ERRO AO CARREGAR";
                        btnLoad.disabled = false;
                    }
                });
        }

        carregarFeedPerfil();

        if (btnLoad) {
            btnLoad.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('[FEED] 🖱️ Botão "Exibir Mais" clicado.');
                if (this.disabled) return;
                carregarFeedPerfil();
            });
        }

        const observerModoSwipe = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (!document.body.classList.contains('modo-swipe-ativo')) {
                        if (typeof configurarPosts === 'function') {
                            console.log('[FEED] 🔄 Saindo do modo swipe, reconfigurando posts...');
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
    })();

    // ============================================================
    // 🔥 REAÇÕES – POPUP E CLIQUE (adaptado para o perfil)
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

    console.log('[VER-PERFIL] ✅ Todos os módulos inicializados.');
</script>

<?php include 'includes/footer.php'; ?>