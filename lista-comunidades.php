<?php
/**
 * lista-comunidades.php – Lista todas as comunidades com busca e carregamento dinâmico
 * 
 * 🔍 Busca: parâmetro GET 'q'
 * 📄 Paginação: offset via GET (usado via AJAX)
 * 🔄 Carregamento mais: AJAX com parâmetro 'ajax=1'
 * 
 * 🔥 OTIMIZAÇÃO: SQL_CALC_FOUND_ROWS (compatível com TiDB)
 * 🌙 CORREÇÃO FINAL: URL do fetch construída dinamicamente para evitar 404
 * 🔧 CORREÇÃO V2: try/catch em cada card para isolar falhas do B2
 */

require_once __DIR__ . '/auth_check.php';
include_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

// ============================================================
// 1. PARÂMETROS DE BUSCA E PAGINAÇÃO
// ============================================================
$busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limite = 10;
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// ============================================================
// 2. CONSTRUÇÃO DA QUERY COM SQL_CALC_FOUND_ROWS
// ============================================================
$sql = "SELECT SQL_CALC_FOUND_ROWS c.*, 
        (SELECT COUNT(*) FROM comunidade_membros WHERE comunidade_id = c.id) as total_membros,
        u.username as criador_username
        FROM comunidades c
        LEFT JOIN usuarios u ON c.criador_id = u.id
        WHERE 1=1";

// 🔥 Busca com LIKE (funciona em todos os bancos, seguro e eficiente)
if (!empty($busca)) {
    $busca_like = '%' . $conn->real_escape_string($busca) . '%';
    $sql .= " AND (c.nome LIKE '$busca_like' OR c.descricao LIKE '$busca_like')";
}

$sql .= " ORDER BY c.data_criacao DESC LIMIT $limite OFFSET $offset";

$result = mysqli_query($conn, $sql);

// Obtém o total de registros (para o botão "Carregar mais")
$total_registros = 0;
if ($result) {
    $res_count = mysqli_query($conn, "SELECT FOUND_ROWS() as total");
    if ($res_count) {
        $total_registros = (int)mysqli_fetch_assoc($res_count)['total'];
    }
}

// ============================================================
// 3. SE FOR AJAX, RETORNA APENAS OS CARDS (SEM HEADER/FOOTER)
// ============================================================
if ($is_ajax) {
    if (mysqli_num_rows($result) === 0) {
        exit;
    }
    while ($com = mysqli_fetch_assoc($result)) {
        // 🔥 CADA CARD AGORA É PROTEGIDO POR try/catch
        try {
            renderizarCardComunidade($com);
        } catch (Exception $e) {
            error_log("[LISTA-COMUNIDADES AJAX] Erro ao renderizar card ID {$com['id']}: " . $e->getMessage());
            // Fallback mínimo para não quebrar o grid
            echo '<div class="comunidade-card erro" style="border:1px solid #ff6b6b; padding:10px;">';
            echo '  <h4>' . htmlspecialchars($com['nome'] ?? 'Comunidade', ENT_QUOTES, 'UTF-8') . '</h4>';
            echo '  <p style="color:#ff6b6b; font-size:0.8rem;">Erro ao carregar detalhes.</p>';
            echo '</div>';
        }
    }
    exit;
}

// ============================================================
// 4. PÁGINA COMPLETA (COM HEADER, NAVBAR, ETC.)
// ============================================================
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

/**
 * Função auxiliar para renderizar um card de comunidade (reutilizada no loop e no AJAX)
 * 🔥 AGORA COM TRY/CATCH INTERNO E FALLBACK SEGURO
 */
function renderizarCardComunidade($com) {
    $membros = $com['total_membros'] ?? 0;
    $tipo = $com['tipo'] ?? 'publica';
    $tipo_label = $tipo === 'privada' ? '🔒 Privada' : '🌐 Pública';
    $tipo_classe = $tipo === 'privada' ? 'privada' : 'publica';

    // Capa via B2 – com try/catch para isolar falhas
    $capa_exibicao = 'uploads/ui/default_comunidade.webp';
    $capa_nome = !empty($com['capa']) ? $com['capa'] : 'default_comunidade.webp';
    try {
        $b2 = B2Client::getInstance();
        $capa_exibicao = obterUrlImagem($capa_nome, $b2, true) ?? 'uploads/ui/default_comunidade.webp';
    } catch (Exception $e) {
        error_log("[RENDER CARD] Erro ao obter capa para comunidade {$com['id']}: " . $e->getMessage());
        // Fallback mantido
    }

    // Verifica se o usuário é membro (apenas para exibir)
    $is_membro = false;
    if (isset($_SESSION['usuario_id'])) {
        $meu_id = $_SESSION['usuario_id'];
        $check = mysqli_query($GLOBALS['conn'], "SELECT 1 FROM comunidade_membros WHERE comunidade_id = {$com['id']} AND usuario_id = $meu_id");
        $is_membro = mysqli_num_rows($check) > 0;
    }

    // 🛡️ SANITIZAÇÃO DE SAÍDA (proteção XSS)
    $nome_seguro = htmlspecialchars($com['nome'] ?? 'Comunidade', ENT_QUOTES, 'UTF-8');
    $descricao_segura = htmlspecialchars($com['descricao'] ?? 'Sem descrição', ENT_QUOTES, 'UTF-8');
    $criador_seguro = htmlspecialchars($com['criador_username'] ?? 'Anônimo', ENT_QUOTES, 'UTF-8');
?>
    <div class="comunidade-card">
        <a href="comunidade.php?id=<?php echo $com['id']; ?>" class="card-link">
            <div class="capa-wrapper">
                <img src="<?php echo htmlspecialchars($capa_exibicao, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $nome_seguro; ?>" onerror="this.src='uploads/ui/default_comunidade.webp'">
                <span class="badge-membros">
                    <i class="fas fa-users"></i> <?php echo $membros; ?>
                </span>
                <span class="badge-tipo <?php echo $tipo_classe; ?>">
                    <?php echo $tipo_label; ?>
                </span>
            </div>
            <div class="info-comunidade">
                <h3><?php echo $nome_seguro; ?></h3>
                <p class="descricao"><?php echo $descricao_segura; ?></p>
                <div class="meta">
                    <span>Criada por @<?php echo $criador_seguro; ?></span>
                    <span><?php echo date('d/m/Y', strtotime($com['data_criacao'])); ?></span>
                </div>
            </div>
        </a>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="card-actions">
                <a href="comunidade.php?id=<?php echo $com['id']; ?>" class="btn-entrar-comunidade <?php echo $is_membro ? 'membro' : ''; ?>" style="<?php echo $is_membro ? 'background:#ffbc00; color:#000;' : ''; ?>">
                    <?php echo $is_membro ? '✅ Membro' : '🔗 Ver comunidade'; ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php
}
?>

<main class="main-comunidades">
    <div class="comunidades-header">
        <h1>🌐 Comunidades da Fenda</h1>
        <p class="subtitle">Encontre seu grupo, compartilhe ideias e faça parte de algo maior.</p>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="create-community-wrapper">
                <a href="criar-comunidade.php" class="btn-criar-comunidade">
                    <i class="fas fa-plus"></i> Criar Nova Comunidade
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 🔍 BARRA DE PESQUISA COM AUTOCOMPLETE -->
    <div class="comunidades-search">
        <div class="search-container">
            <input type="text" id="search-comunidades" class="search-input" placeholder="Buscar comunidade pelo nome ou descrição..." autocomplete="off">
            <button type="button" id="search-btn" class="search-btn btn-fenda-padrao">Buscar</button>
            <div id="search-dropdown" class="search-dropdown" style="display: none;"></div>
        </div>
    </div>

    <!-- 📋 GRID DE COMUNIDADES -->
    <div class="comunidades-grid" id="comunidades-grid">
        <?php
        $total_cards = mysqli_num_rows($result);
        if ($total_cards > 0):
            while ($com = mysqli_fetch_assoc($result)):
                // 🔥 CADA CARD AGORA É PROTEGIDO POR try/catch
                try {
                    renderizarCardComunidade($com);
                } catch (Exception $e) {
                    error_log("[LISTA-COMUNIDADES] Erro ao renderizar card ID {$com['id']}: " . $e->getMessage());
                    // Fallback seguro
                    echo '<div class="comunidade-card erro" style="border:1px solid #ff6b6b; padding:10px; margin-bottom:10px;">';
                    echo '  <h4>' . htmlspecialchars($com['nome'] ?? 'Comunidade', ENT_QUOTES, 'UTF-8') . '</h4>';
                    echo '  <p style="color:#ff6b6b; font-size:0.8rem;">Erro ao carregar detalhes. Tente novamente mais tarde.</p>';
                    echo '</div>';
                }
            endwhile;
        else:
        ?>
            <div class="empty-state">
                <p><?php echo !empty($busca) ? 'Nenhuma comunidade encontrada com "' . htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') . '".' : 'Nenhuma comunidade ainda.'; ?></p>
                <?php if (!empty($busca)): ?>
                    <a href="lista-comunidades.php" class="btn-fenda-padrao" style="margin-top: 10px;">Ver todas</a>
                <?php else: ?>
                    <a href="criar-comunidade.php" class="btn-criar-comunidade">Seja o primeiro a criar uma!</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 🔄 BOTÃO "EXIBIR MAIS" (aparece apenas se houver mais registros) -->
    <?php
    $carregados = $offset + $limite;
    $tem_mais = ($total_registros > $carregados);
    ?>
    <div class="container-load-more" style="text-align: center; margin-top: 30px; <?php echo (!$tem_mais && $total_registros > 0) ? 'display:none;' : ''; ?>" id="load-more-wrapper">
        <button id="btn-load-more-comunidades" class="btn-fenda-padrao" <?php echo !$tem_mais ? 'disabled' : ''; ?>>
            <?php echo $tem_mais ? 'Exibir Mais Comunidades' : 'Fim da lista'; ?>
        </button>
    </div>
</main>

<script>
    // ============================================================
    // 🔍 AUTOCOMPLETE PARA COMUNIDADES
    // ============================================================
    (function() {
        'use strict';

        const input = document.getElementById('search-comunidades');
        const dropdown = document.getElementById('search-dropdown');
        const btnBuscar = document.getElementById('search-btn');
        let debounceTimer = null;
        let currentTerm = '';

        // Função para renderizar os resultados
        function renderizarResultados(data) {
            dropdown.innerHTML = '';
            if (data.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            dropdown.style.display = 'block';
            data.forEach(com => {
                const item = document.createElement('a');
                item.className = 'search-result-item';
                item.href = com.url;
                item.innerHTML = `
                    <img src="${com.capa}" class="search-result-avatar" onerror="this.src='uploads/ui/default_comunidade.webp'">
                    <div class="search-result-info">
                        <span class="search-result-nome">${com.nome}</span>
                        <span class="search-result-descricao">${com.descricao}</span>
                    </div>
                `;
                dropdown.appendChild(item);
            });
        }

        // 🔥 FUNÇÃO DE BUSCA COM URL DINÂMICA (RESOLVE O 404 DE UMA VEZ)
        function buscar(termo) {
            termo = termo.trim();
            if (termo.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            currentTerm = termo;

            // 🔥 CONSTRÓI A URL COMPLETA DINAMICAMENTE
            const baseUrl = window.location.pathname.replace(/\/[^/]*$/, '/');
            const endpoint = `${baseUrl}buscar-comunidades.php`;

            fetch(`${endpoint}?q=${encodeURIComponent(termo)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (currentTerm !== termo) return;
                    renderizarResultados(data);
                })
                .catch(err => {
                    console.error('[BUSCA] Erro:', err);
                    dropdown.style.display = 'none';
                });
        }

        // Evento de input (com debounce de 300ms)
        input.addEventListener('input', function() {
            const termo = this.value;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                buscar(termo);
            }, 300);
        });

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                dropdown.style.display = 'none';
            }
        });

        // Botão "Buscar" redireciona para a página com a busca via GET (fallback)
        btnBuscar.addEventListener('click', function() {
            const termo = input.value.trim();
            if (termo) {
                window.location.href = `lista-comunidades.php?q=${encodeURIComponent(termo)}`;
            }
        });

        // Permitir Enter para enviar a busca (fallback)
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const termo = this.value.trim();
                if (termo) {
                    window.location.href = `lista-comunidades.php?q=${encodeURIComponent(termo)}`;
                }
            }
        });

        // Se houver um termo na URL (busca tradicional), preenche o input e foca
        const urlParams = new URLSearchParams(window.location.search);
        const qParam = urlParams.get('q');
        if (qParam) {
            input.value = qParam;
            setTimeout(() => buscar(qParam), 200);
        }
    })();

    // ============================================================
    // 📄 CARREGAR MAIS (AJAX)
    // ============================================================
    (function() {
        'use strict';

        const grid = document.getElementById('comunidades-grid');
        const btnLoad = document.getElementById('btn-load-more-comunidades');
        const wrapper = document.getElementById('load-more-wrapper');

        if (!grid || !btnLoad) return;

        let offset = <?php echo $offset + $limite; ?>;
        let carregando = false;
        let acabou = <?php echo $tem_mais ? 'false' : 'true'; ?>;
        const busca = '<?php echo addslashes($busca); ?>';

        function carregarMais() {
            if (carregando || acabou) return;
            carregando = true;
            btnLoad.disabled = true;
            btnLoad.innerText = 'CARREGANDO...';

            const url = `lista-comunidades.php?ajax=1&offset=${offset}&q=${encodeURIComponent(busca)}`;
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    if (html.trim() === '') {
                        acabou = true;
                        btnLoad.innerText = 'FIM DA LISTA';
                        btnLoad.disabled = true;
                        carregando = false;
                        return;
                    }
                    grid.insertAdjacentHTML('beforeend', html);
                    offset += 10;
                    btnLoad.disabled = false;
                    btnLoad.innerText = 'EXIBIR MAIS';
                    carregando = false;

                    const novosCards = html.trim().split('</div>').length - 1;
                    if (novosCards < 10) {
                        acabou = true;
                        btnLoad.innerText = 'FIM DA LISTA';
                        btnLoad.disabled = true;
                    }
                })
                .catch(err => {
                    console.error('[COMUNIDADES] Erro ao carregar mais:', err);
                    btnLoad.disabled = false;
                    btnLoad.innerText = 'ERRO AO CARREGAR';
                    carregando = false;
                });
        }

        btnLoad.addEventListener('click', carregarMais);

        <?php if (!$tem_mais && $total_registros > 0): ?>
            btnLoad.innerText = 'FIM DA LISTA';
            btnLoad.disabled = true;
        <?php endif; ?>

    })();
</script>

<?php include 'includes/footer.php'; ?>