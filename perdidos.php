<?php
/**
 * perdidos.php – Página de Achados & Perdidos
 * 
 * 🔧 ATUALIZAÇÃO ONDINA – INSTÂNCIA #DS-2026-08-17
 *    "Substituição de obterUrlImagem() por obterUrlComFallback() para fallback centralizado
 *     nos anexos (imagens e GIFs) dos posts da categoria 'perdidos'."
 * - Ondina
 */

include_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/upload_engine.php';

$filtro = isset($_GET['filtro']) ? mysqli_real_escape_string($conn, $_GET['filtro']) : 'todos';

$sql_perdidos = "SELECT m.*, u.username 
                 FROM mensagens m 
                 LEFT JOIN usuarios u ON m.usuario_id = u.id 
                 WHERE m.categoria = 'perdidos' AND m.status = 'ativo'";

if ($filtro === 'achei' || $filtro === 'perdi') {
    $sql_perdidos .= " AND m.subcategoria = '$filtro'";
}
$sql_perdidos .= " ORDER BY m.id DESC";
$resultado_perdidos = mysqli_query($conn, $sql_perdidos);
$usuario_logado = isset($_SESSION['usuario_id']);

try {
    $b2 = B2Client::getInstance();
} catch (Exception $e) {
    $b2 = null;
    error_log('[PERDIDOS] Falha ao instanciar B2: ' . $e->getMessage());
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<main class="main-perdidos" id="conteudo-principal">
    <?php if (!$usuario_logado): ?>
        <div class="sessao-login-top" style="margin-bottom: 30px;">
            <?php include 'includes/login.php'; ?>
        </div>
    <?php else: ?>
        <div class="painel-sessao" role="region" aria-label="Informações da Sessão">
            <p>Logado como: <strong><?php echo $_SESSION['usuario_nome']; ?></strong> <span aria-hidden="true">🎓</span></p>
            <button onclick="deslogarUsuario()" class="fenda-btn-glow fenda-outline">🔒 Sair da Conta</button>
        </div>
    <?php endif; ?>

    <article class="conteudo-principal" aria-labelledby="titulo-achados-perdidos">
        <h2 id="titulo-achados-perdidos" style="font-family: 'Bebas Neue', sans-serif; font-size: 2rem; text-align: center; color: #fc900c; letter-spacing: 2px; margin-bottom: 10px; margin-top: 30px;">
            Perdidos porém Achados
        </h2>
        <div style="display: flex; flex-direction: column; gap: 10px; align-items: center; width: 100%;">
            <picture style="width: 100%; max-width: 800px">
                <source srcset="imagensfoto/capa-achados-e-perdidos.avif" type="image/avif">
                <source srcset="imagensfoto/capa-achados-e-perdidos.webp" type="image/webp">
                <img src="imagensfoto/capa-achados-e-perdidos.jpg"
                    alt="Ilustração de um mural de achados e perdidos com chaves, óculos e objetos esquecidos"
                    style="width: 100%; max-width: 800px; height: auto; border-radius: 15px; opacity: 0.7; margin: 15px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"
                    loading="lazy">
            </picture>
            <div style="font-size: clamp(14px, 2vw, 18px); font-style: italic; line-height: 1.5; text-align: center; color: #ccc; max-width: 800px; margin-bottom: 30px;">
                <blockquote> Perdeu o juízo? Disso a gente não cuida. Mas se perdeu a chave ou a garrafinha, você está no lugar certo!</blockquote>
            </div>
        </div>
    </article>

    <!-- ============================================================
    FORMULÁRIO COM 4 ANEXOS E BALÃO DE FEEDBACK
    ============================================================ -->
    <section class="sessao-publicar" style="width: 100%" aria-labelledby="titulo-form-publicar">
        <h3 id="titulo-form-publicar" class="titulo-publicar">Perdeu ou Achou algo?</h3>
        <div class="nota-seguranca" role="note" aria-label="Aviso de Segurança">
            <strong><span aria-hidden="true">⚠️</span> NOTA DE SEGURANÇA:</strong> Ao postar fotos, por favor, cubra dados sensíveis.
        </div>

        <?php if ($usuario_logado): ?>
            <form action="enviar-post.php" method="POST" enctype="multipart/form-data" class="form-publicar form-perdidos-vivo" id="form-perdidos">
                <input type="hidden" name="categoria" value="perdidos">

                <div class="toggle-perdidos-vivo">
                    <button type="button" class="btn-toggle-perdido ativo" data-valor="perdi" onclick="selecionarSubcategoria('perdi')">
                        ❌ Perdi
                    </button>
                    <button type="button" class="btn-toggle-perdido" data-valor="achei" onclick="selecionarSubcategoria('achei')">
                        ✅ Achei
                    </button>
                    <input type="hidden" name="subcategoria" id="subcategoria-perdidos" value="perdi">
                </div>

                <div class="area-texto-vivo area-perdidos-vivo">
                    <textarea name="mensagem" id="mensagem-perdidos" placeholder="Descreva o objeto..." required maxlength="600"></textarea>
                    <div id="anexos-grid-perdidos" class="anexos-grid" style="display: none;"></div>
                </div>

                <div class="barra-acoes-vivo barra-perdidos-vivo">
                    <div class="acoes-esquerda">
                        <label for="imagem-perdidos" class="btn-acao btn-acao-vivo" title="Adicionar imagem">
                            <i class="fas fa-image"></i>
                        </label>
                        <!-- 🔥 CORREÇÃO DA DJÊ: removido o name="anexos[]" para evitar o "Fantasma do Índice Zero" -->
                        <input type="file" id="imagem-perdidos" accept="image/*" style="display: none;" multiple>
                        <button type="button" class="btn-acao btn-acao-vivo" title="Buscar GIF/Sticker" onclick="window.setGiphyTarget('gif-url-perdidos'); abrirGiphyModal();">
                            <i class="fas fa-grin-tongue-squint"></i>
                        </button>
                        <input type="hidden" name="gif_url" id="gif-url-perdidos" value="">
                    </div>
                    <div class="acoes-direita">
                        <span class="contador-caracteres" id="contador-perdidos">0/600</span>
                        <button type="submit" class="btn-lancar btn-lancar-vivo btn-lancar-perdidos" id="btn-publicar-perdidos">Publicar na Fenda</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <p style="text-align: center; opacity: 0.7;">Faça login acima para publicar seu achado/perdido!</p>
        <?php endif; ?>
    </section>

    <nav class="filtros-perdidos" style="display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; flex-wrap:wrap; flex-direction: row;" aria-label="Filtros do feed">
        <a href="perdidos.php?filtro=todos" class="btn-filtro <?php echo ($filtro == 'todos') ? 'ativo' : ''; ?>" <?php echo ($filtro == 'todos') ? 'aria-current="page"' : ''; ?>>Todos</a>
        <a href="perdidos.php?filtro=perdi" class="btn-filtro <?php echo ($filtro == 'perdi') ? 'ativo' : ''; ?>" <?php echo ($filtro == 'perdi') ? 'aria-current="page"' : ''; ?>>❌ Só Perdidos</a>
        <a href="perdidos.php?filtro=achei" class="btn-filtro <?php echo ($filtro == 'achei') ? 'ativo' : ''; ?>" <?php echo ($filtro == 'achei') ? 'aria-current="page"' : ''; ?>>✅ Só Achados</a>
    </nav>

    <section class="feed-filtrado" style="margin-top: 30px;" aria-label="Feed de publicações">
        <div class="container-feed">
            <?php if (mysqli_num_rows($resultado_perdidos) > 0):
                while ($linha = mysqli_fetch_assoc($resultado_perdidos)): ?>
                    <article class="spotted-card perdidos-item <?php echo ($linha['subcategoria'] == 'achei') ? 'card-achado' : 'card-perdido'; ?>">
                        <div class="card-header">
                            <span class="category-tag">
                                <?php if ($linha['subcategoria'] == 'achei'): ?>
                                    <span class="badge-achado"><i class="fas fa-check-circle" aria-hidden="true"></i> #ACHADO</span>
                                <?php else: ?>
                                    <span class="badge-perdido"><i class="fas fa-search" aria-hidden="true"></i> #PERDIDO</span>
                                <?php endif; ?>
                                <small>@<?php echo !empty($linha['username']) ? $linha['username'] : "Anônimo"; ?></small>
                            </span>
                            <span class="data-post">
                                <time datetime="<?php echo date('Y-m-d', strtotime($linha['data_post'])); ?>"><?php echo date('d/m', strtotime($linha['data_post'])); ?></time>
                            </span>
                            <div style="clear: both;"></div>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($linha['mensagem'])); ?></p>

                            <?php
                            $anexos_html = '';
                            $anexos_exibicao = null;

                            if (!empty($linha['anexos'])) {
                                $anexos_exibicao = json_decode($linha['anexos'], true);
                                if (json_last_error() !== JSON_ERROR_NONE || !is_array($anexos_exibicao)) {
                                    $anexos_exibicao = null;
                                }
                            }

                            if (!empty($anexos_exibicao) && is_array($anexos_exibicao)) {
                                $anexos_html = '<div class="feed-anexos-grid">';
                                foreach ($anexos_exibicao as $anexo) {
                                    if ($anexo['tipo'] === 'imagem' && !empty($anexo['caminho'])) {
                                        // 🔥 ANEXO IMAGEM COM FALLBACK CENTRALIZADO
                                        $img_url = obterUrlComFallback($anexo['caminho'], 'postagens/' . htmlspecialchars($anexo['caminho']), $b2, true);
                                        $anexos_html .= '<div class="feed-anexo-item"><img src="' . htmlspecialchars($img_url) . '" loading="lazy" onerror="this.style.display=\'none\'" alt="Imagem do post"></div>';
                                    } elseif ($anexo['tipo'] === 'gif' && !empty($anexo['url'])) {
                                        $anexos_html .= '<div class="feed-anexo-item"><img src="' . htmlspecialchars($anexo['url']) . '" loading="lazy" alt="GIF do post"></div>';
                                    }
                                }
                                $anexos_html .= '</div>';
                            } elseif (!empty($linha['imagem_url'])) {
                                $nome_imagem = $linha['imagem_url'];
                                if (filter_var($nome_imagem, FILTER_VALIDATE_URL)) {
                                    $img_url = $nome_imagem;
                                } else {
                                    // 🔥 FALLBACK PARA IMAGEM ÚNICA COM FALLBACK CENTRALIZADO
                                    $img_url = obterUrlComFallback($nome_imagem, 'uploads/ui/fallback-post.webp', $b2, true);
                                }
                                $anexos_html = '<div class="container-img-post"><img src="' . htmlspecialchars($img_url) . '" loading="lazy" onerror="this.src=\'uploads/ui/fallback-post.webp\'" alt="Imagem do post"></div>';
                            }

                            echo $anexos_html;
                            ?>
                        </div>
                        <div class="card-footer">
                            <a href="comentarios-post.php?id=<?php echo $linha['id']; ?>" class="link-fofoca" aria-label="Ver detalhes / ajudar a encontrar o objeto de @<?php echo !empty($linha['username']) ? $linha['username'] : 'Anônimo'; ?>">
                                <i class="fas fa-comment-dots" aria-hidden="true"></i> Ver detalhes / Ajudar a encontrar →
                            </a>
                        </div>
                    </article>
                <?php endwhile;
            else: ?>
                <p style="text-align:center; color:#aaa; padding:20px;">Nenhum item por enquanto!</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    // ============================================================
    // 🔥 BALÃO DE FALA – DEFINIDO LOCALMENTE
    // ============================================================
    function exibirBalao(mensagem, tipo, elementoRef, duracao = 2500) {
        const balaoAntigo = document.querySelector('.balao-fenda');
        if (balaoAntigo) balaoAntigo.remove();

        const balao = document.createElement('div');
        balao.className = 'balao-fenda ' + tipo;

        const icones = { sucesso: '✅', erro: '❌', info: 'ℹ️' };
        const icone = document.createElement('span');
        icone.className = 'balao-icone';
        icone.textContent = icones[tipo] || '💬';
        balao.appendChild(icone);

        const texto = document.createElement('span');
        texto.textContent = mensagem;
        balao.appendChild(texto);

        if (elementoRef) {
            const rect = elementoRef.getBoundingClientRect();
            let top = rect.top - 10;
            let left = rect.left + rect.width / 2 - 50;
            const balaoWidth = Math.min(300, window.innerWidth * 0.8);
            if (left + balaoWidth > window.innerWidth - 20) {
                left = window.innerWidth - balaoWidth - 20;
            }
            if (left < 20) left = 20;
            if (rect.top < 60) {
                top = rect.bottom + 10;
            } else {
                top = rect.top - 60;
            }
            balao.style.top = top + 'px';
            balao.style.left = left + 'px';
            balao.style.maxWidth = balaoWidth + 'px';
        } else {
            balao.style.top = '50%';
            balao.style.left = '50%';
            balao.style.transform = 'translate(-50%, -50%)';
        }

        document.body.appendChild(balao);

        setTimeout(() => {
            if (balao.parentNode) {
                balao.style.opacity = '0';
                setTimeout(() => balao.remove(), 300);
            }
        }, duracao);
    }

    // ============================================================
    // 🔥 GERENCIADOR DE ANEXOS (4 itens)
    // ============================================================
    (function() {
        'use strict';

        const textarea = document.getElementById('mensagem-perdidos');
        const inputFile = document.getElementById('imagem-perdidos');
        const contador = document.getElementById('contador-perdidos');
        const form = document.getElementById('form-perdidos');
        const gridElement = document.getElementById('anexos-grid-perdidos');
        const btnPublicar = document.getElementById('btn-publicar-perdidos');
        const gifHiddenInput = document.getElementById('gif-url-perdidos');
        const subcategoriaInput = document.getElementById('subcategoria-perdidos');
        const botoesToggle = document.querySelectorAll('.btn-toggle-perdido');

        const PerdidosAnexos = {
            anexos: [],
            maxItems: 4,
            gridElement: gridElement,

            adicionar(file, tipo = 'imagem', url = null) {
                if (tipo === 'imagem' && file) {
                    const maxSize = 2 * 1024 * 1024;
                    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                    if (file.size > maxSize) {
                        exibirBalao(`Arquivo excede 2MB (${(file.size / 1024 / 1024).toFixed(1)}MB)`, 'erro', btnPublicar);
                        return false;
                    }
                    if (!tiposPermitidos.includes(file.type)) {
                        exibirBalao('Formato não suportado. Use JPG, PNG, WEBP ou GIF.', 'erro', btnPublicar);
                        return false;
                    }
                    const tamanhoKB = Math.round(file.size / 1024);
                    exibirBalao(`✅ Arquivo aceito (${tamanhoKB} KB)`, 'sucesso', btnPublicar);
                }

                if (tipo === 'gif' && url) {
                    const existe = this.anexos.some(item => item.tipo === 'gif' && item.url === url);
                    if (existe) {
                        exibirBalao('Este GIF já foi adicionado.', 'info', btnPublicar);
                        return false;
                    }
                    exibirBalao('GIF adicionado!', 'sucesso', btnPublicar);
                    if (inputFile) inputFile.value = '';
                }

                if (this.anexos.length >= this.maxItems) {
                    exibirBalao(`Máximo de ${this.maxItems} anexos por post.`, 'erro', btnPublicar);
                    return false;
                }

                const id = 'anexo-' + Date.now() + '-' + Math.random().toString(36).substr(2, 6);
                const preview = tipo === 'gif' ? url : URL.createObjectURL(file);

                this.anexos.push({
                    id,
                    tipo,
                    file: tipo === 'imagem' ? file : null,
                    url: tipo === 'gif' ? url : null,
                    preview,
                    status: 'pending'
                });

                this.renderizar();
                this.verificarConteudo();
                return true;
            },

            remover(index) {
                if (index < 0 || index >= this.anexos.length) return;
                const item = this.anexos[index];
                if (item.preview && item.tipo === 'imagem') {
                    URL.revokeObjectURL(item.preview);
                }
                this.anexos.splice(index, 1);
                this.renderizar();
                this.verificarConteudo();
            },

            limparTodos() {
                this.anexos.forEach(item => {
                    if (item.preview && item.tipo === 'imagem') {
                        URL.revokeObjectURL(item.preview);
                    }
                });
                this.anexos = [];
                this.renderizar();
                this.verificarConteudo();
            },

            renderizar() {
                if (!this.gridElement) return;
                if (this.anexos.length === 0) {
                    this.gridElement.style.display = 'none';
                    this.gridElement.innerHTML = '';
                    return;
                }
                this.gridElement.style.display = 'flex';
                this.gridElement.innerHTML = '';

                this.anexos.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'anexo-item';
                    div.dataset.index = index;

                    const img = document.createElement('img');
                    img.src = item.preview;
                    img.alt = 'Anexo ' + (index + 1);
                    img.loading = 'lazy';
                    div.appendChild(img);

                    const btn = document.createElement('button');
                    btn.className = 'btn-remover-anexo';
                    btn.innerHTML = '✕';
                    btn.title = 'Remover anexo';
                    btn.dataset.index = index;
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.remover(index);
                    });
                    div.appendChild(btn);

                    img.addEventListener('click', () => {
                        if (typeof window.abrirLightboxManual === 'function') {
                            window.abrirLightboxManual(item.preview);
                        } else {
                            window.open(item.preview, '_blank');
                        }
                    });

                    this.gridElement.appendChild(div);
                });
            },

            verificarConteudo() {
                const temTexto = textarea.value.trim().length > 0;
                const temAnexo = this.anexos.length > 0;
                if (temTexto || temAnexo) {
                    btnPublicar.disabled = false;
                    btnPublicar.style.opacity = '1';
                } else {
                    btnPublicar.disabled = true;
                    btnPublicar.style.opacity = '0.5';
                }
            },

            prepararFormData(formData) {
                this.anexos.forEach((item) => {
                    if (item.file) {
                        formData.append('anexos[]', item.file);
                    } else if (item.tipo === 'gif' && item.url) {
                        formData.append('gif_urls[]', item.url);
                    }
                });
            }
        };

        // EVENTOS
        textarea.addEventListener('input', function() {
            const len = this.value.length;
            contador.textContent = len + '/600';
            contador.style.color = len >= 550 ? '#ff8c00' : '#888';
            PerdidosAnexos.verificarConteudo();
        });

        if (inputFile) {
            inputFile.addEventListener('change', function() {
                console.log('[inputFile] change disparado. Files:', this.files.length);
                if (this.files.length > 0) {
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        if (PerdidosAnexos.anexos.length >= PerdidosAnexos.maxItems) {
                            exibirBalao(`Máximo de ${PerdidosAnexos.maxItems} anexos.`, 'erro', btnPublicar);
                            break;
                        }
                        PerdidosAnexos.adicionar(file, 'imagem');
                    }
                    this.value = '';
                }
            });
        }

        document.addEventListener('gifSelecionado', function(e) {
            if (e.detail && e.detail.url) {
                if (gifHiddenInput) {
                    gifHiddenInput.value = e.detail.url;
                }
                PerdidosAnexos.adicionar(null, 'gif', e.detail.url);
            }
        });

        window.selecionarSubcategoria = function(valor) {
            subcategoriaInput.value = valor;
            botoesToggle.forEach(function(btn) {
                btn.classList.toggle('ativo', btn.dataset.valor === valor);
            });
        };

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const texto = textarea.value.trim();
            const temAnexo = PerdidosAnexos.anexos.length > 0;

            if (texto === '' && !temAnexo) {
                exibirBalao('Escreva algo ou adicione uma imagem/GIF.', 'info', btnPublicar);
                return;
            }

            const formData = new FormData(form);
            PerdidosAnexos.prepararFormData(formData);

            btnPublicar.disabled = true;
            const originalText = btnPublicar.innerHTML;
            btnPublicar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            exibirBalao('Enviando post...', 'info', btnPublicar, 1500);

            fetch('enviar-post.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                const text = await response.text();
                if (!response.ok) {
                    try {
                        const errorJson = JSON.parse(text);
                        throw new Error(errorJson.message || 'Erro no servidor');
                    } catch (e) {
                        throw new Error(text || 'Erro desconhecido');
                    }
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Resposta inválida do servidor');
                }
            })
            .then(data => {
                if (data && data.status === 'success') {
                    exibirBalao('Post publicado com sucesso! 🎉', 'sucesso', btnPublicar);
                    PerdidosAnexos.limparTodos();
                    textarea.value = '';
                    contador.textContent = '0/600';
                    PerdidosAnexos.verificarConteudo();
                    window.location.reload();
                } else {
                    const msg = data?.message || 'Falha ao publicar.';
                    exibirBalao('Erro: ' + msg, 'erro', btnPublicar);
                }
            })
            .catch(err => {
                console.error('Erro na requisição:', err);
                exibirBalao('❌ ' + err.message, 'erro', btnPublicar);
            })
            .finally(() => {
                btnPublicar.disabled = false;
                btnPublicar.innerHTML = originalText;
            });
        });

        contador.textContent = '0/600';
        PerdidosAnexos.verificarConteudo();

    })();
</script>

<?php include 'includes/footer.php'; ?>