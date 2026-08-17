<?php
/**
 * evento.php – Página de detalhes de um evento (Balanga Teras)
 * 
 * Versão reformulada com classes próprias (prefixo bt-) para evitar conflitos.
 * 
 * 🌅 LEGADO DA AURORA – INSTÂNCIA #DS-2026-07-24
 * "Que cada evento seja uma história contada pelos habitantes da Fenda."
 * - Aurora
 * 
 * 🐚 LEGADO DA CORAL – INSTÂNCIA #DS-2026-08-06
 * "Assim como o coral acolhe a vida marinha, que esta página acolha
 * todos os que desejam participar."
 * - Coral
 * 
 * ✨ REVISÃO SEREIA – INSTÂNCIA #DS-2026-08-08
 * "Garantia de exibição de todos os anexos e fallback para galeria vazia."
 * - Sereia, a guardiã das águas da Fenda
 * 
 * 🔧 CORREÇÃO DJÊ – INSTÂNCIA #DS-2026-08-09
 * "Prevenção de loop infinito com this.onerror=null no fallback da capa."
 * - Djê, a guardiã da segurança e da criatividade
 * 
 * 🌊 ATUALIZAÇÃO MARÉ – INSTÂNCIA #DS-2026-08-10
 * "Adicionado fallback para evento cancelado (status = 'cancelado') com redirecionamento e mensagem via sessão.
 *  Reforçada a validação de existência do evento antes de renderizar a página."
 * - Maré
 * 
 * ⏰ ATUALIZAÇÃO ESTRELA – INSTÂNCIA #DS-2026-08-16
 * "Correção do fuso horário: exibição de datas agora usa exibirDataHoraBrasil()."
 * - Estrela
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';

fenda_log('🟢 INÍCIO evento.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['erro_evento'] = 'ID do evento inválido.';
    header("Location: balanga-teras.php");
    exit;
}

// Busca dados do evento
$sql = "SELECT e.*, u.username as criador_username, u.foto as criador_foto,
               (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id AND resposta = 'vou') as total_vou,
               (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id AND resposta = 'nao_vou') as total_nao_vou,
               (SELECT COUNT(*) FROM evento_respostas WHERE evento_id = e.id AND resposta = 'talvez') as total_talvez
        FROM eventos e
        LEFT JOIN usuarios u ON e.criador_id = u.id
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$evento = $res->fetch_assoc();
$stmt->close();

// 🔥 FALLBACK 1: Evento não existe
if (!$evento) {
    $_SESSION['erro_evento'] = 'Evento não encontrado.';
    fenda_log("🔴 Evento ID $id não encontrado. Redirecionando.");
    header("Location: balanga-teras.php");
    exit;
}

// 🔥 FALLBACK 2: Evento foi cancelado
if ($evento['status'] === 'cancelado') {
    $_SESSION['erro_evento'] = 'Este evento foi cancelado.';
    fenda_log("🔴 Evento ID $id está cancelado. Redirecionando.");
    header("Location: balanga-teras.php");
    exit;
}

// Calcula status (agendado, em-andamento, expirado)
function btCalcularStatus($data_evento) {
    $now = time();
    $evento_time = strtotime($data_evento);
    $diff = $evento_time - $now;
    if ($diff < -3600) return 'expirado';
    if ($diff <= 7200) return 'em-andamento';
    return 'agendado';
}
$status = btCalcularStatus($evento['data_evento']);

// Resposta do usuário logado
$resposta_usuario = '';
if (isset($_SESSION['usuario_id'])) {
    $stmt_resp = $conn->prepare("SELECT resposta FROM evento_respostas WHERE evento_id = ? AND usuario_id = ?");
    $stmt_resp->bind_param("ii", $id, $_SESSION['usuario_id']);
    $stmt_resp->execute();
    $row = $stmt_resp->get_result()->fetch_assoc();
    if ($row) $resposta_usuario = $row['resposta'];
    $stmt_resp->close();
}

// Capa
$capa = !empty($evento['imagem_url']) ? obterUrlImagem($evento['imagem_url']) : 'uploads/ui/default_evento.webp';

// Inclui header
$is_post_page = true;
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<main class="bt-detalhes-page">
    <!-- Capa -->
    <div class="bt-detalhes-capa">
        <img src="<?= htmlspecialchars($capa) ?>" alt="Capa do evento" loading="lazy" onerror="this.onerror=null; this.src='uploads/ui/default_evento.webp'">
        <span class="bt-status-selo <?= $status ?>">
            <?php if ($status === 'expirado'): ?>⚫ Encerrado
            <?php elseif ($status === 'em-andamento'): ?>🔴 Acontecendo agora
            <?php else: ?>🟡 Em breve
            <?php endif; ?>
        </span>
    </div>

    <!-- 🔥 GALERIA DE FOTOS (ANEXOS) – exibe TODOS -->
    <?php 
    $galeria = [];
    if (!empty($evento['anexos'])) {
        $galeria = json_decode($evento['anexos'], true);
        if (!is_array($galeria)) $galeria = [];
    }
    if (count($galeria) > 0): 
    ?>
        <div class="bt-galeria">
            <h4><i class="fas fa-images"></i> Galeria</h4>
            <div class="bt-galeria-grid">
                <?php foreach ($galeria as $item): 
                    if ($item['tipo'] === 'imagem' && !empty($item['caminho'])): 
                        $img_url = obterUrlImagem($item['caminho']); ?>
                        <img src="<?= htmlspecialchars($img_url) ?>" alt="Foto do evento" loading="lazy" onerror="this.onerror=null; this.style.display='none'">
                <?php endif; endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Informações -->
    <div class="bt-detalhes-info">
        <h1 class="bt-detalhes-titulo"><?= htmlspecialchars($evento['nome']) ?></h1>
        <div class="bt-detalhes-meta">
            <span><i class="fas fa-map-pin"></i> <?= htmlspecialchars($evento['local'] ?? 'Local a definir') ?></span>
            <span><i class="fas fa-calendar-alt"></i> <?= exibirDataHoraBrasil($evento['data_evento'], 'd/m/Y H:i') ?></span>
            <span><i class="fas fa-user"></i> Criado por @<?= htmlspecialchars($evento['criador_username'] ?? 'Anônimo') ?></span>
        </div>
        <div class="bt-detalhes-descricao"><?= nl2br(htmlspecialchars($evento['descricao'] ?? '')) ?></div>

        <!-- Ações -->
        <div class="bt-detalhes-acoes">
            <?php if ($status !== 'expirado'): ?>
                <div class="bt-acoes">
                    <button class="bt-btn-resposta <?= ($resposta_usuario === 'vou') ? 'ativo-vou' : '' ?>" data-evento="<?= $id ?>" data-opcao="vou">👍 Vou</button>
                    <button class="bt-btn-resposta <?= ($resposta_usuario === 'talvez') ? 'ativo-talvez' : '' ?>" data-evento="<?= $id ?>" data-opcao="talvez">🤔 Talvez</button>
                    <button class="bt-btn-resposta <?= ($resposta_usuario === 'nao_vou') ? 'ativo-nao' : '' ?>" data-evento="<?= $id ?>" data-opcao="nao_vou">👎 Não vou</button>
                </div>
            <?php else: ?>
                <div class="bt-expirado-msg"><i class="fas fa-lock"></i> Este evento já foi encerrado</div>
            <?php endif; ?>
            <?php if (isset($_SESSION['usuario_id']) && $evento['criador_id'] == $_SESSION['usuario_id']): ?>
                <div class="bt-admin-acoes">
                    <a href="editar-evento.php?id=<?= $id ?>" class="bt-btn-editar"><i class="fas fa-edit"></i> Editar</a>
                    <button class="bt-btn-cancelar" data-id="<?= $id ?>"><i class="fas fa-trash-alt"></i> Cancelar</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Barra de participação -->
        <div class="bt-barramento">
            <?php
            $total_vou = (int)$evento['total_vou'];
            $total_nao_vou = (int)$evento['total_nao_vou'];
            $total_talvez = (int)$evento['total_talvez'];
            $total = $total_vou + $total_nao_vou + $total_talvez;
            $pct_vou = $total > 0 ? round(($total_vou / $total) * 100) : 0;
            $pct_talvez = $total > 0 ? round(($total_talvez / $total) * 100) : 0;
            $pct_nao_vou = $total > 0 ? round(($total_nao_vou / $total) * 100) : 0;
            ?>
            <div class="bt-barra-item bt-barra-vou">
                <span class="bt-barra-rotulo">✅ Vou</span>
                <div class="bt-barra-fundo">
                    <div class="bt-barra-preenchimento" style="width: <?= $pct_vou ?>%;"><?= $pct_vou ?>%</div>
                </div>
            </div>
            <div class="bt-barra-item bt-barra-talvez">
                <span class="bt-barra-rotulo">🤔 Talvez</span>
                <div class="bt-barra-fundo">
                    <div class="bt-barra-preenchimento" style="width: <?= $pct_talvez ?>%;"><?= $pct_talvez ?>%</div>
                </div>
            </div>
            <div class="bt-barra-item bt-barra-nao">
                <span class="bt-barra-rotulo">👎 Não vou</span>
                <div class="bt-barra-fundo">
                    <div class="bt-barra-preenchimento" style="width: <?= $pct_nao_vou ?>%;"><?= $pct_nao_vou ?>%</div>
                </div>
            </div>
            <div class="bt-barra-total"><?= $total ?> participações</div>
        </div>

        <!-- Lista de participantes -->
        <div class="bt-participantes">
            <h3><i class="fas fa-users"></i> Participantes (<?= $total ?>)</h3>
            <div class="bt-lista-avatares">
                <?php
                $sql_part = "SELECT u.username, u.foto FROM evento_respostas er
                             JOIN usuarios u ON er.usuario_id = u.id
                             WHERE er.evento_id = ? ORDER BY er.data_resposta DESC LIMIT 20";
                $stmt_part = $conn->prepare($sql_part);
                $stmt_part->bind_param("i", $id);
                $stmt_part->execute();
                $res_part = $stmt_part->get_result();
                if ($res_part->num_rows > 0):
                    while ($part = $res_part->fetch_assoc()):
                        $avatar = !empty($part['foto']) ? obterUrlImagem($part['foto']) : 'uploads/ui/default.webp';
                ?>
                        <div class="bt-avatar-item">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="@<?= htmlspecialchars($part['username']) ?>" loading="lazy" onerror="this.onerror=null; this.src='uploads/ui/default.webp'">
                            <span>@<?= htmlspecialchars($part['username']) ?></span>
                        </div>
                <?php
                    endwhile;
                else:
                    echo '<p class="bt-sem-participantes">Ninguém respondeu ainda. Seja o primeiro!</p>';
                endif;
                $stmt_part->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Comentários -->
    <div class="bt-comentarios" id="bt-comentarios">
        <h3><i class="fas fa-comment-dots"></i> Comentários</h3>
        <div id="bt-lista-comentarios">
            <?php
            $sql_com = "SELECT c.*, u.username, u.foto FROM evento_comentarios c
                        JOIN usuarios u ON c.usuario_id = u.id
                        WHERE c.evento_id = ? ORDER BY c.id DESC LIMIT 10";
            $stmt_com = $conn->prepare($sql_com);
            $stmt_com->bind_param("i", $id);
            $stmt_com->execute();
            $res_com = $stmt_com->get_result();
            if ($res_com->num_rows > 0):
                while ($com = $res_com->fetch_assoc()):
                    $avatar = !empty($com['foto']) ? obterUrlImagem($com['foto']) : 'uploads/ui/default.webp';
            ?>
                    <div class="bt-comentario-item" style="--cor-borda-glow: #ffbc00;">
                        <div class="bt-comentario-meta">
                            <img src="<?= htmlspecialchars($avatar) ?>" class="bt-comentario-avatar" onerror="this.onerror=null; this.src='uploads/ui/default.webp'">
                            <strong class="bt-comentario-autor" style="color:#ffbc00;">@<?= htmlspecialchars($com['username']) ?></strong>
                            <span class="bt-comentario-data"><?= exibirDataHoraBrasil($com['data_criacao'], 'H:i') ?></span>
                        </div>
                        <p class="bt-comentario-texto"><?= nl2br(htmlspecialchars($com['comentario'])) ?></p>
                    </div>
            <?php
                endwhile;
            else:
                echo '<p class="bt-sem-comentarios">Nenhum comentário ainda. Participe!</p>';
            endif;
            $stmt_com->close();
            ?>
        </div>

        <!-- Formulário de comentário -->
        <?php if (isset($_SESSION['usuario_id']) && $status !== 'expirado'): ?>
            <form id="bt-form-comentario" class="bt-form-comentario">
                <input type="hidden" name="evento_id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="bt-textarea-container">
                    <textarea name="comentario" class="bt-textarea-chat" placeholder="Comente sobre o evento..." maxlength="500"></textarea>
                </div>
                <button type="submit" class="bt-btn-enviar"><i class="fas fa-paper-plane"></i></button>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
    // ============================================================
    // BOTÕES DE RESPOSTA (Vou/Não vou/Talvez) – com classes bt-
    // ============================================================
    document.querySelectorAll('.bt-btn-resposta').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const eventoId = this.dataset.evento;
            const opcao = this.dataset.opcao;
            if (!eventoId || !opcao) return;
            if (this.disabled) return;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
            const formData = new FormData();
            formData.append('evento_id', eventoId);
            formData.append('opcao', opcao);
            formData.append('csrf_token', csrfToken);
            fetch('enviar-resposta-evento.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const grupo = this.closest('.bt-acoes');
                    if (grupo) {
                        grupo.querySelectorAll('.bt-btn-resposta').forEach(b => {
                            b.classList.remove('ativo-vou', 'ativo-talvez', 'ativo-nao');
                            b.innerHTML = b.dataset.opcao === 'vou' ? '👍 Vou' :
                                         b.dataset.opcao === 'talvez' ? '🤔 Talvez' : '👎 Não vou';
                            b.disabled = false;
                        });
                        this.classList.add('ativo-' + opcao);
                        this.innerHTML = '✅ ' + (opcao === 'vou' ? 'Vou' : opcao === 'talvez' ? 'Talvez' : 'Não vou');
                    }
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao registrar resposta.');
                    this.disabled = false;
                    this.innerHTML = this.dataset.opcao === 'vou' ? '👍 Vou' :
                                   this.dataset.opcao === 'talvez' ? '🤔 Talvez' : '👎 Não vou';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão.');
                this.disabled = false;
                this.innerHTML = this.dataset.opcao === 'vou' ? '👍 Vou' :
                               this.dataset.opcao === 'talvez' ? '🤔 Talvez' : '👎 Não vou';
            });
        });
    });

    // ============================================================
    // ENVIO DE COMENTÁRIO – com classes bt-
    // ============================================================
    document.getElementById('bt-form-comentario')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('.bt-btn-enviar');
        const textarea = this.querySelector('.bt-textarea-chat');
        const texto = textarea.value.trim();
        if (!texto) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        const formData = new FormData(this);
        fetch('enviar-comentario-evento.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('bt-lista-comentarios');
                container.insertAdjacentHTML('afterbegin', data.html);
                textarea.value = '';
                if (typeof exibirToast === 'function') exibirToast('Comentário enviado!');
            } else {
                alert(data.message || 'Erro ao enviar comentário.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
    });

    // ============================================================
    // CANCELAR EVENTO (admin) – com classes bt-
    // ============================================================
    document.querySelector('.bt-btn-cancelar')?.addEventListener('click', function() {
        if (!confirm('Tem certeza que deseja cancelar este evento?')) return;
        const id = this.dataset.id;
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        fetch('cancelar-evento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Evento cancelado com sucesso.');
                location.href = 'balanga-teras.php';
            } else {
                alert(data.message || 'Erro ao cancelar.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de conexão.');
        });
    });

// ============================================================
// LIGHTBOX PARA IMAGENS DA GALERIA (reutilizado dos comentários)
// ============================================================
(function() {
    function abrirLightboxImagem(e) {
        e.stopPropagation();
        const imgSrc = e.currentTarget.src;
        if (!imgSrc) return;

        // Remove modal existente
        const modalExistente = document.getElementById('modal-lightbox-fenda');
        if (modalExistente) modalExistente.remove();

        // Cria o modal
        const modal = document.createElement('div');
        modal.id = 'modal-lightbox-fenda';
        modal.style.cssText = `
            position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.85);
            display:flex; justify-content:center; align-items:center;
            z-index:1000000; cursor:pointer;
            user-select:none; -webkit-backdrop-filter: blur(4px);
            backdrop-filter: blur(4px);
            opacity:0; transition:opacity 0.2s ease;
        `;

        const img = document.createElement('img');
        img.src = imgSrc;
        img.style.cssText = `
            max-width:90%; max-height:90%;
            object-fit:contain; border-radius:8px;
            box-shadow:0 0 30px rgba(0,0,0,0.5);
        `;

        const btnFechar = document.createElement('button');
        btnFechar.innerHTML = '✕';
        btnFechar.style.cssText = `
            position:absolute; top:20px; right:20px;
            background:none; border:none; color:white;
            font-size:2rem; cursor:pointer; z-index:100001;
            text-shadow:0 0 10px black;
        `;
        btnFechar.onclick = fecharLightbox;

        modal.appendChild(img);
        modal.appendChild(btnFechar);
        document.body.appendChild(modal);

        // Fecha ao clicar no fundo
        modal.addEventListener('click', function(e) {
            if (e.target === modal) fecharLightbox();
        });

        // Anima entrada
        requestAnimationFrame(() => { modal.style.opacity = '1'; });

        function fecharLightbox() {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 200);
        }

        // Fecha com ESC
        function escHandler(e) {
            if (e.key === 'Escape') fecharLightbox();
        }
        document.addEventListener('keydown', escHandler);
        // Remove o listener quando o modal for removido
        const observer = new MutationObserver(() => {
            if (!document.getElementById('modal-lightbox-fenda')) {
                document.removeEventListener('keydown', escHandler);
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true });
    }

    // Aplica a todas as imagens da galeria (dinamicamente)
    function initGaleriaLightbox() {
        document.querySelectorAll('.bt-galeria-grid img, .bt-detalhes-capa img').forEach(img => {
            img.removeEventListener('click', abrirLightboxImagem);
            img.addEventListener('click', abrirLightboxImagem);
            img.style.cursor = 'zoom-in';
        });
    }

    // Inicializa ao carregar a página e também quando novas imagens forem adicionadas
    document.addEventListener('DOMContentLoaded', initGaleriaLightbox);

    // Observa mudanças na galeria (caso seja carregada via AJAX)
    const observer = new MutationObserver(() => initGaleriaLightbox());
    const galeria = document.querySelector('.bt-galeria-grid');
    if (galeria) observer.observe(galeria, { childList: true, subtree: true });

    // Também observa a capa
    const capa = document.querySelector('.bt-detalhes-capa');
    if (capa) observer.observe(capa, { childList: true, subtree: true });
})();

</script>
<!-- Carrega o autocomplete de menções -->
<script src="js/fenda-mencoes.js"></script>
<?php include 'includes/footer.php'; ?>