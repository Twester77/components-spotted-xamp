<?php
/**
 * comentarios-drawer.php – Endpoint exclusivo para a Lingueta (Fase 5)
 */

include_once 'conexao.php';
include_once __DIR__ . '/fenda_debug.php';
require_once __DIR__ . '/includes/upload_engine.php';
require_once __DIR__ . '/includes/cascata-helper.php';

// 🔥 GARANTE CSRF TOKEN (igual ao comentarios-post)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🔒 Sanitização de preferências visuais (defesa em profundidade)
if (!function_exists('sanitizarVibe')) {
    function sanitizarVibe($vibe) {
        $vibes_validas = ['vibe-glass', 'vibe-neon', 'vibe-dark', 'vibe-light', 'vibe-ads'];
        return in_array($vibe, $vibes_validas, true) ? $vibe : 'vibe-glass';
    }
}
if (!function_exists('sanitizarCorBorda')) {
    function sanitizarCorBorda($cor) {
        if (is_string($cor) && preg_match('/^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/', $cor)) {
            return $cor;
        }
        return '#70cde4';
    }
}

// ============================================================
// 🔥 PADRONIZAÇÃO DA SIG (MARCA NOTIFICAÇÃO COMO LIDA)
// ============================================================
if (isset($_GET['notif_id'], $_GET['sig']) && is_string($_GET['sig'])) {
    $notif_id = (int)$_GET['notif_id'];
    $sig_recebida = $_GET['sig'];
    $user_id = (int)($_SESSION['usuario_id'] ?? 0);

    if ($notif_id > 0 && $user_id > 0) {
        $sig_esperada = hash_hmac('sha256', $notif_id . '|' . $user_id, FENDA_HMAC_KEY);
        if (hash_equals($sig_esperada, $sig_recebida)) {
            $stmt_notif = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
            $stmt_notif->bind_param("ii", $notif_id, $user_id);
            $stmt_notif->execute();
            $stmt_notif->close();
            fenda_log("🟢 [DRAWER] Notificação $notif_id marcada como lida para usuário $user_id");
        }
    }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    http_response_code(400);
    echo '<p style="text-align:center; padding:20px; color:#ff6b6b;">ID inválido.</p>';
    exit();
}

// 🔒 VERIFICA SE É PERDIDOS (mesma regra do comentarios-post)
$is_perdidos = false;
$stmt_check = $conn->prepare("SELECT categoria, status FROM mensagens WHERE id = ?");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$check_post = $stmt_check->get_result()->fetch_assoc();
if ($check_post && $check_post['categoria'] === 'perdidos') {
    $is_perdidos = true;
}
$stmt_check->close();

// 🚫 SE NÃO FOR PERDIDOS E O USUÁRIO NÃO ESTIVER LOGADO, BLOQUEIA
// (Não usamos header() aqui porque estamos dentro de um fetch AJAX)
if (!isset($_SESSION['usuario_id']) && !$is_perdidos) {
    http_response_code(403);
    echo '<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#ff6b6b; padding:20px; text-align:center;">
            <i class="fas fa-lock" style="font-size:2rem; margin-bottom:10px;"></i>
            <p>Você precisa estar logado para ver os comentários deste post.</p>
          </div>';
    exit();
}

// ============================================================
// PUXA PREFERÊNCIAS E DADOS DO POST
// ============================================================
$pref_estilo_comentario = 'whatsapp';
$vibe_default = 'vibe-glass';
$cor_default = '#70cde4';

if (isset($_SESSION['usuario_id'])) {
    $uid = $_SESSION['usuario_id'];
    $res = mysqli_query($conn, "SELECT pref_estilo_comentario, pref_vibe_padrao, pref_cor_padrao FROM usuarios WHERE id = '$uid'");
    if ($row = mysqli_fetch_assoc($res)) {
        $pref_estilo_comentario = $row['pref_estilo_comentario'] ?? 'whatsapp';
        $vibe_default = $row['pref_vibe_padrao'] ?? 'vibe-glass';
        $cor_default = $row['pref_cor_padrao'] ?? '#70cde4';
    }
}

try { $b2 = B2Client::getInstance(); } catch (Exception $e) { $b2 = null; }

$post_esta_ativo = ($check_post && $check_post['status'] === 'ativo');
?>

<div class="layout-wrapper" id="drawer-comentarios-wrapper" style="height: 100%; display: flex; flex-direction: column;">
    <main class="lista-scrollavel" id="conteudo-rolavel" style="flex: 1; overflow-y: auto;">
        <div class="fenda-estatica-context"></div>
        
        <div class="lista-comentarios-social">
            <?php
            $sql_c = "SELECT c.*, (SELECT comentario FROM comentarios WHERE id = c.parent_id) as parent_comentario
                      FROM comentarios c 
                      WHERE c.id_mensagem = ? AND c.status = 'ativo'
                      ORDER BY COALESCE(c.parent_id, c.id), c.id ASC";
            $stmt_c = $conn->prepare($sql_c);
            $stmt_c->bind_param("i", $id);
            $stmt_c->execute();
            $res_c = $stmt_c->get_result();

            $todos_comentarios = [];
            while ($c = $res_c->fetch_assoc()) {
                $todos_comentarios[] = $c;
            }

            if (count($todos_comentarios) > 0) {
                if ($pref_estilo_comentario === 'cascata') {
                    echo renderizarArvoreCascata($todos_comentarios, null, 0, $b2);
                } else {
                    foreach ($todos_comentarios as $c) {
                        $vibe = sanitizarVibe($c['pref_vibe_comentario'] ?? 'vibe-glass');
                        $cor_borda = sanitizarCorBorda($c['pref_cor_borda'] ?? '#70cde4');
                        $sou_eu = (isset($_SESSION['usuario_id']) && $c['id_usuario'] == $_SESSION['usuario_id']) ? 'meu-comentario' : '';
                        $classe_filho = !empty($c['parent_id']) ? "comentario-filho" : "";
                        ?>
                        <div class="comentario-linha <?php echo $sou_eu === 'meu-comentario' ? 'comentario-linha-proprio' : ''; ?> <?php echo $classe_filho ? 'comentario-linha-filha' : ''; ?>">
                            <div class="comentario-item <?php echo $vibe . ' ' . $classe_filho . ' ' . $sou_eu; ?>" id="comentario-<?php echo $c['id']; ?>" style="--cor-borda-glow: <?php echo $cor_borda; ?>;">
                                <div class="comentario-meta">
                                    <strong class="comentario-autor"><?php echo !empty($c['usuario_nome']) ? "@" . htmlspecialchars($c['usuario_nome']) : "👤 Anônimo"; ?></strong>
                                </div>
                                <p class="comentario-texto"><?php echo nl2br(formatarMencoes($c['comentario'])); ?></p>
                                <div class="comentario-rodape"><span class="comentario-data"><?= exibirDataHoraBrasil($c['data_comentario'], 'H:i') ?></span></div>
                            </div>
                            <button type="button" id="comentario-acoes-<?php echo (int)$c['id']; ?>" class="comentario-menu-toggle"><i class="fas fa-ellipsis-h"></i></button>
                        </div>
                        <?php
                    }
                }
            } else {
                echo '<p class="sem-comentarios">Ninguém fofocou nada ainda... Seja o primeiro!</p>';
            }
            ?>
        </div>
    </main>

    <?php if ($post_esta_ativo): ?>
        <footer class="fixed-input" style="position: relative; flex-shrink: 0;">
            <section class="sessao-fofoca-focada" id="fofocar">
                <!-- GRID DE ANEXOS -->
                <div id="anexos-grid" class="anexos-grid" style="display: none;"></div>
                
                <div class="textarea-wrapper">
                    <button type="button" id="btn-attach-gaveta" class="btn-attach-gaveta" title="Anexar arquivo ou GIF">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <div class="textarea-container">
                        <div id="resposta-indicador" class="resposta-indicador">
                            <i class="fas fa-reply"></i>
                            <strong id="texto-nome-resposta">...</strong>
                            <button type="button" class="cancelar-resposta" onclick="cancelarResposta()">✕</button>
                        </div>
                        <textarea name="comentario" class="textarea-chat" placeholder="Digite sua mensagem..." maxlength="500" id="comentario-textarea"></textarea>
                        <span id="char-count" class="char-counter-inline">500</span>
                    </div>
                    <button type="submit" form="form-comentario" id="btn-enviar-chat" class="btn-enviar-chat">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                
                <!-- GAVETA DE OPÇÕES -->
                <div id="gaveta-opcoes" class="gaveta-opcoes">
                    <button type="button" id="btn-toggle-gaveta" class="btn-toggle-gaveta">
                        <i class="fas fa-palette"></i> Estilo
                    </button>
                    <div class="customizacao-rapida">
                        <select name="pref_vibe_comentario" id="vibe-comentario" class="input-mini">
                            <option value="vibe-glass" <?php echo ($vibe_default == 'vibe-glass') ? 'selected' : ''; ?>>Glass</option>
                            <option value="vibe-neon" <?php echo ($vibe_default == 'vibe-neon') ? 'selected' : ''; ?>>Neon</option>
                            <option value="vibe-dark" <?php echo ($vibe_default == 'vibe-dark') ? 'selected' : ''; ?>>Dark</option>
                            <option value="vibe-light" <?php echo ($vibe_default == 'vibe-light') ? 'selected' : ''; ?>>Light</option>
                            <option value="vibe-ads" <?php echo ($vibe_default == 'vibe-ads') ? 'selected' : ''; ?>>ADS (Overclock)</option>
                        </select>
                        <input type="color" name="pref_cor_borda" id="cor-borda" class="color-mini" value="<?php echo $cor_default; ?>">
                    </div>
                    <button type="button" id="btn-anexar-img" class="btn-attach-opcao"><i class="fas fa-image"></i> Imagem</button>
                    <button type="button" id="btn-gif" class="btn-attach-opcao" onclick="window.setGiphyTarget('gif-url-comentario'); abrirGiphyModal();">
                        <i class="fas fa-grin-tongue-squint"></i> GIF/Sticker
                    </button>
                </div>
                
                <input type="file" name="imagem_comentario" id="input-img-comentario" accept="image/*" style="display:none;">
                <form action="enviar-comentario.php" method="POST" enctype="multipart/form-data" class="form-chat" id="form-comentario" style="display: none;">
                    <input type="hidden" name="id_mensagem" value="<?php echo $id; ?>">
                    <input type="hidden" name="parent_id" id="input_parent_id" value="">
                    <input type="hidden" name="pref_vibe_comentario" id="hidden-vibe" value="">
                    <input type="hidden" name="pref_cor_borda" id="hidden-cor" value="">
                    <textarea name="comentario" id="hidden-textarea"></textarea>
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="gif_url" id="gif-url-comentario" value="">
                </form>
                <input type="text" name="honeypot" class="honeypot" tabindex="-1" autocomplete="off" style="display: none !important; position: absolute; left: -9999px;">
            </section>
        </footer>
    <?php endif; ?>
</div>

<script>
(function inicializarDrawerJS() {
    console.log('[DRAWER] Iniciando JS interno...');
    if (window._drawerJSInitialized) {
        if (window._drawerPollingInterval) {
            clearInterval(window._drawerPollingInterval);
            window._drawerPollingInterval = null;
        }
    }
    window._drawerJSInitialized = true;

    const wrapper = document.getElementById('drawer-comentarios-wrapper');
    if (!wrapper) return;

    const listaComentarios = wrapper.querySelector('.lista-comentarios-social');
    if (!listaComentarios) return;

    const POST_ID = <?php echo (int)$id; ?>;
    const IS_CASCATA = <?= json_encode($pref_estilo_comentario === 'cascata') ?>;
    const campoTexto = wrapper.querySelector('.textarea-chat');
    const contadorChar = wrapper.querySelector('#char-count');
    const btnEnviar = wrapper.querySelector('.btn-enviar-chat');
    const btnGaveta = wrapper.querySelector('.btn-attach-gaveta');
    const gaveta = wrapper.querySelector('#gaveta-opcoes');
    const inputFile = wrapper.querySelector('#input-img-comentario');
    const hiddenVibe = wrapper.querySelector('#hidden-vibe');
    const hiddenCor = wrapper.querySelector('#hidden-cor');
    const hiddenTextarea = wrapper.querySelector('#hidden-textarea');
    const selectVibe = wrapper.querySelector('#vibe-comentario');
    const inputCor = wrapper.querySelector('#cor-borda');
    const form = wrapper.querySelector('#form-comentario');
    const inputParent = wrapper.querySelector('#input_parent_id');
    const indicador = wrapper.querySelector('#resposta-indicador');
    const textoNome = wrapper.querySelector('#texto-nome-resposta');
    
    let gavetaAberta = false;

    function verificarConteudo() {
        if (typeof AnexosManager !== 'undefined' && AnexosManager.verificarConteudo) {
            AnexosManager.verificarConteudo();
            return;
        }
        const temTexto = campoTexto && campoTexto.value.trim().length > 0;
        const temImagem = inputFile && inputFile.files && inputFile.files.length > 0;
        const gifInput = wrapper.querySelector('input[name="gif_url"]');
        const temGif = gifInput && gifInput.value !== '';
        const temMidia = temImagem || temGif;
        if (temTexto || temMidia) {
            btnEnviar.classList.add('visivel');
            btnEnviar.style.display = 'flex';
            btnGaveta.style.display = 'none';
        } else {
            btnEnviar.classList.remove('visivel');
            btnEnviar.style.display = 'none';
            btnGaveta.style.display = 'flex';
        }
    }

    if (campoTexto) {
        campoTexto.addEventListener('input', verificarConteudo);
        verificarConteudo(); 
    }

    if (btnGaveta && gaveta) {
        btnGaveta.addEventListener('click', (e) => {
            e.stopPropagation();
            gavetaAberta = !gavetaAberta;
            gaveta.style.display = gavetaAberta ? 'flex' : 'none';
        });
        document.addEventListener('click', (e) => {
            if (!btnGaveta.contains(e.target) && !gaveta.contains(e.target)) {
                gaveta.style.display = 'none';
                gavetaAberta = false;
            }
        });
    }

    const btnAnexarImg = wrapper.querySelector('#btn-anexar-img');
    if (btnAnexarImg && inputFile) {
        btnAnexarImg.addEventListener('click', () => inputFile.click());
    }

    if (inputFile) {
        inputFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    if (typeof window.adicionarAnexo === 'function') window.adicionarAnexo(file);
                }
                this.value = '';
                verificarConteudo();
            }
        });
    }

    document.addEventListener('gifSelecionado', function(e) {
        if (e.detail && e.detail.targetId && e.detail.targetId !== 'gif-url-comentario') return;
        if (e.detail && e.detail.url) {
            if (typeof window.adicionarGif === 'function') window.adicionarGif(e.detail.url);
            if(inputFile) inputFile.value = '';
            verificarConteudo();
        }
    });

    function atualizarHiddenPrefs() {
        if (hiddenVibe) hiddenVibe.value = selectVibe.value;
        if (hiddenCor) hiddenCor.value = inputCor.value;
        if (hiddenTextarea) hiddenTextarea.value = campoTexto.value;
    }

    if (form && btnEnviar) {
        const novoBtnEnviar = btnEnviar.cloneNode(true);
        btnEnviar.parentNode.replaceChild(novoBtnEnviar, btnEnviar);
        
        novoBtnEnviar.addEventListener('click', function(e) {
            e.preventDefault();
            const textoAtual = campoTexto ? campoTexto.value.trim() : '';
            if (textoAtual === '') {
                if (typeof window.exibirBalao === 'function') window.exibirBalao('Escreva algo!', 'info');
                return;
            }

            atualizarHiddenPrefs();

            let formData;
            if (typeof AnexosManager !== 'undefined' && AnexosManager.prepararFormData) {
                formData = AnexosManager.prepararFormData(form);
            } else {
                formData = new FormData(form);
            }
            
            novoBtnEnviar.disabled = true;
            novoBtnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('enviar-comentario.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (typeof window.exibirBalao === 'function') window.exibirBalao('Enviado!', 'sucesso');
                    const parentIdEnviado = inputParent.value;
                    campoTexto.value = '';
                    inputParent.value = '';
                    if (indicador) indicador.classList.remove('visivel');
                    if (contadorChar) contadorChar.textContent = '500';
                    if (typeof AnexosManager !== 'undefined' && AnexosManager.limparTodos) AnexosManager.limparTodos();
                    gaveta.style.display = 'none';
                    gavetaAberta = false;
                    verificarConteudo();
                    
                    if (IS_CASCATA && parentIdEnviado) {
                        const paiItem = document.getElementById('comentario-' + parentIdEnviado);
                        if (paiItem) {
                            const paiLinha = paiItem.closest('.comentario-linha');
                            if (paiLinha) {
                                const nivelPaiClass = Array.from(paiLinha.classList).find(c => c.startsWith('nivel-'));
                                let nivel = nivelPaiClass ? Math.min(parseInt(nivelPaiClass.replace('nivel-', '')) + 1, 2) : 1;
                                
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = data.html;
                                const novaLinha = tempDiv.firstElementChild;
                                if (novaLinha) {
                                    novaLinha.classList.add('nivel-' + nivel);
                                    paiLinha.appendChild(novaLinha);
                                }
                            }
                        }
                    } else {
                        listaComentarios.insertAdjacentHTML('beforeend', data.html);
                    }
                    if (typeof window.initLightboxGlobal === 'function') window.initLightboxGlobal();
                } else {
                    if (typeof window.exibirBalao === 'function') window.exibirBalao(data.message || 'Erro', 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof window.exibirBalao === 'function') window.exibirBalao('Erro de rede', 'erro');
            })
            .finally(() => {
                novoBtnEnviar.disabled = false;
                novoBtnEnviar.innerHTML = '<i class="fas fa-paper-plane"></i>';
            });
        });
    }

    window.prepararResposta = function(id, username) {
        if (inputParent) inputParent.value = parseInt(id);
        if (indicador && textoNome) {
            textoNome.textContent = `Respondendo a ${username}...`;
            indicador.classList.add('visivel');
        }
        if (campoTexto) {
            campoTexto.placeholder = "Escreva sua resposta...";
            campoTexto.classList.add('com-resposta');
            campoTexto.focus();
            verificarConteudo();
        }
    };

    window.cancelarResposta = function() {
        if (inputParent) inputParent.value = '';
        if (indicador) indicador.classList.remove('visivel');
        if (campoTexto) {
            campoTexto.placeholder = "Digite sua mensagem...";
            campoTexto.classList.remove('com-resposta');
            verificarConteudo();
        }
    };

    let ultimoId = 0;
    listaComentarios.querySelectorAll('.comentario-item').forEach(el => {
        const match = el.id && el.id.match(/^comentario-(\d+)$/);
        if (match) {
            const num = parseInt(match[1], 10);
            if (!isNaN(num) && num > ultimoId) ultimoId = num;
        }
    });

    function buscarNovos() {
        if (!document.getElementById('drawer-comentarios-wrapper')) {
            if (window._drawerPollingInterval) {
                clearInterval(window._drawerPollingInterval);
                window._drawerPollingInterval = null;
            }
            return;
        }

        const url = `includes/comentarios-novos.php?id_mensagem=${POST_ID}&ultimo_id=${ultimoId}&_=${Date.now()}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (!data || data.status !== 'success' || !data.novos) return;
                
                const semComentarios = listaComentarios.querySelector('.sem-comentarios');
                if (semComentarios) semComentarios.remove();

                data.novos.forEach(novo => {
                    if (document.getElementById('comentario-' + novo.id)) return;
                    
                    if (IS_CASCATA && novo.parent_id) {
                        const paiItem = document.getElementById('comentario-' + novo.parent_id);
                        if (paiItem) {
                            const paiLinha = paiItem.closest('.comentario-linha');
                            if (paiLinha) {
                                const nivelPaiClass = Array.from(paiLinha.classList).find(c => c.startsWith('nivel-'));
                                let nivel = nivelPaiClass ? Math.min(parseInt(nivelPaiClass.replace('nivel-', '')) + 1, 2) : 1;
                                
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = novo.html;
                                const novaLinha = tempDiv.firstElementChild;
                                if (novaLinha) {
                                    novaLinha.classList.add('nivel-' + nivel);
                                    paiLinha.appendChild(novaLinha);
                                }
                            }
                        } else {
                            listaComentarios.insertAdjacentHTML('beforeend', novo.html);
                        }
                    } else {
                        listaComentarios.insertAdjacentHTML('beforeend', novo.html);
                    }
                });
                ultimoId = data.ultimo_id;
                if (typeof window.initLightboxGlobal === 'function') window.initLightboxGlobal();
            })
            .catch(err => console.warn('[DRAWER POLLING] Erro:', err));
    }

    if (!window._drawerPollingInterval) {
        window._drawerPollingInterval = setInterval(buscarNovos, 4000);
        setTimeout(buscarNovos, 500);
    }

    if (typeof window.initLightboxGlobal === 'function') window.initLightboxGlobal();

})();
</script>