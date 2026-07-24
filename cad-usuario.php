<?php
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

// ============================================================
//  EXIBE MENSAGENS DE ERRO (caso venham via GET)
// ============================================================
$erro = isset($_GET['erro']) ? $_GET['erro'] : '';
$mensagem_erro = '';
if ($erro === 'ja_existe') {
    $mensagem_erro = '⚠️ Este e-mail já está cadastrado. Tente fazer login.';
} elseif ($erro === 'turnstile') {
    $mensagem_erro = '⚠️ Falha na verificação de segurança. Tente novamente.';
}
?>

<main class="main-form-cadastro">
    <div class="container-cadastro-fenda" id="container-cadastro">

        <?php if (!empty($mensagem_erro)): ?>
            <div class="toast-erro-global" style="margin-bottom:15px; padding:12px; background:rgba(255,0,0,0.15); border-left:4px solid #ff4757; border-radius:8px; color:#ff6b6b;">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>

        <!-- SCRIPT DO TURNSTILE -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <!-- ============================================================
             FORMULÁRIO EM ETAPAS
             ============================================================ -->
        <form action="processa-cadastro.php" method="POST" class="form-fenda-estilizado" id="form-cadastro" novalidate>

            <!-- ========== ETAPA 1: BOAS-VINDAS ========== -->
            <div class="step-container active" data-step="1">
                <div class="step-content">
                    <div class="cadastro-header">
                        <h2>Bem-vindo à Fenda</h2>
                        <p>Vamos criar sua identidade digital na comunidade mais doida da UNIFEV.</p>
                    </div>
                    <div class="step-visual" style="text-align:center;">
                        <div style="font-size:4rem; color:var(--dourado);">
                            <i class="fas fa-ghost"></i>
                        </div>
                        <p style="color:#aaa; margin:20px 0;">Sua jornada começa aqui.</p>
                    </div>
                    <button type="button" class="btn-step-next btn-fenda-padrao" onclick="proximoPasso()" style="width:100%;">
                        Começar <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ========== ETAPA 2: ESCOLHA DA AURA (COR) ========== -->
            <div class="step-container" data-step="2">
                <div class="step-content">
                    <h3 style="color:#fff; text-align:center;">🎨 Escolha sua Aura</h3>
                    <p style="color:#aaa; text-align:center; margin-bottom:20px;">Essa cor vai brilhar no seu perfil.</p>

                    <div style="text-align:center; margin:20px 0;">
                        <div class="aura-preview" id="aura-preview" style="background:#70cde4;"></div>
                        <p style="color:#ccc; margin-top:8px; font-size:0.9rem;" id="cor-nome">#70cde4</p>
                    </div>

                    <div style="display:flex; justify-content:center; gap:15px; flex-wrap:wrap;">
                        <input type="color" id="pref_cor_padrao" name="pref_cor_padrao" value="#70cde4" style="width:60px; height:60px; border:none; cursor:pointer; border-radius:8px; background:transparent;" oninput="atualizarCor(this.value)">
                        <button type="button" class="btn-cor-sugestao" data-cor="#ff6b6b" style="background:#ff6b6b;"></button>
                        <button type="button" class="btn-cor-sugestao" data-cor="#ffd93d" style="background:#ffd93d;"></button>
                        <button type="button" class="btn-cor-sugestao" data-cor="#6bcb77" style="background:#6bcb77;"></button>
                        <button type="button" class="btn-cor-sugestao" data-cor="#4d96ff" style="background:#4d96ff;"></button>
                        <button type="button" class="btn-cor-sugestao" data-cor="#9b59b6" style="background:#9b59b6;"></button>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-top:30px; gap:12px;">
                        <button type="button" class="btn-step-prev" onclick="passoAnterior()">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </button>
                        <button type="button" class="btn-step-next" onclick="proximoPasso()" style="flex:1;">
                            Continuar <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========== ETAPA 3: ESCOLHA DA VIBE ========== -->
            <div class="step-container" data-step="3">
                <div class="step-content">
                    <h3 style="color:#fff; text-align:center;">🌀 Escolha sua Vibe</h3>
                    <p style="color:#aaa; text-align:center; margin-bottom:20px;">O estilo do seu card no Feed de Comunidades/Pagina de Perfil.</p>

                    <div class="grid-vibes">
                        <div class="vibe-option" data-vibe="vibe-glass" onclick="selecionarVibe('vibe-glass', this)">
                            <i class="fas fa-glass-whiskey" style="color:#70cde4;"></i>
                            <p>Glass</p>
                        </div>
                        <div class="vibe-option" data-vibe="vibe-neon" onclick="selecionarVibe('vibe-neon', this)">
                            <i class="fas fa-bolt" style="color:#ff00ff;"></i>
                            <p>Neon</p>
                        </div>
                        <div class="vibe-option" data-vibe="vibe-dark" onclick="selecionarVibe('vibe-dark', this)">
                            <i class="fas fa-moon" style="color:#444;"></i>
                            <p>Dark</p>
                        </div>
                        <div class="vibe-option" data-vibe="vibe-light" onclick="selecionarVibe('vibe-light', this)">
                            <i class="fas fa-sun" style="color:#ffd93d;"></i>
                            <p>Light</p>
                        </div>
                        <div class="vibe-option" data-vibe="vibe-ads" onclick="selecionarVibe('vibe-ads', this)">
                            <i class="fas fa-microchip" style="color:#ff8c00;"></i>
                            <p>ADS</p>
                        </div>
                    </div>

                    <!-- PRÉVIA DO PERFIL -->
                    <div id="preview-perfil" class="preview-perfil" style="display:none;">
                        <div class="preview-conteudo">
                            <div class="preview-avatar">🧑</div>
                            <div class="preview-info">
                                <p class="nome">Novo Habitante</p>
                                <p class="username">@novo_habitante</p>
                                <p class="bio-preview">"A Fenda me aguarda..."</p>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-top:30px; gap:12px;">
                        <button type="button" class="btn-step-prev" onclick="passoAnterior()">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </button>
                        <button type="button" class="btn-step-next" id="btn-proximo-vibe" onclick="proximoPasso()" disabled style="flex:1;">
                            Continuar <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========== ETAPA 4: DADOS PESSOAIS ========== -->
            <div class="step-container" data-step="4">
                <div class="step-content">
                    <h3 style="color:#fff; text-align:center;">📝 Quase lá</h3>
                    <p style="color:#aaa; text-align:center; margin-bottom:20px;">Preencha seus dados para finalizar.</p>

                    <!-- Campo Nome -->
                    <div class="campo-grupo-fenda">
                        <label for="nome">Nome ou Apelido</label>
                        <div class="fenda-reg-box">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nome" name="nome" placeholder="Ex: Fulano, Furlas..." maxlength="30" autocomplete="given-name" required>
                        </div>
                    </div>

                    <!-- Campo E-mail -->
                    <div class="campo-grupo-fenda">
                        <label for="email">E-mail (Para ativação)</label>
                        <div class="fenda-reg-box">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="E-mail pessoal ou acadêmico" autocomplete="email" required>
                        </div>
                    </div>

                    <!-- Campo Senha -->
                    <div class="campo-grupo-fenda">
                        <label for="senha">Crie uma Senha</label>
                        <div class="fenda-reg-box">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="senha" name="senha" placeholder="8 a 20 caracteres" maxlength="20" minlength="8" autocomplete="new-password" required>
                        </div>
                        <!-- O indicador de força será inserido via JS -->
                    </div>

                    <!-- Atlética -->
                    <div class="campo-grupo-fenda">
                        <label for="atletica_id">Sua Atlética</label>
                        <div class="fenda-reg-box">
                            <i class="fas fa-shield-alt"></i>
                            <select name="atletica_id" id="atletica_id" class="input-fenda-select" required>
                                <option value="" disabled selected>Selecione sua Atlética...</option>
                                <option value="ads">Análise e Desenvolvimento de Sistemas (Overclock)</option>
                                <option value="agronomia">Engenharia Agronômica (Usagro)</option>
                                <option value="arquitetura">Arquitetura (Arcana)</option>
                                <option value="biomedicina">Biomedicina (Leptospirados)</option>
                                <option value="contabeis">Ciências Contábeis (Panda)</option>
                                <option value="direito">Direito (Soberana)</option>
                                <option value="ed-fisica">Educação Física (Demolidores)</option>
                                <option value="enfermagem">Enfermagem (Ferma)</option>
                                <option value="eng-comp">Engenharia de Computação (Octabit)</option>
                                <option value="eng-mecanica">Engenharia Mecânica (MEC)</option>
                                <option value="farmacia">Farmácia (Narcótica)</option>
                                <option value="fisioterapia">Fisioterapia (Fisio)</option>
                                <option value="medicina">Medicina (Javalaria)</option>
                                <option value="nutricao">Nutrição (Devoradores)</option>
                                <option value="pedagogia">Pedagogia (Mediadores)</option>
                                <option value="psicologia">Psicologia (Psicose)</option>
                                <option value="propaganda">Publicidade e Propaganda (Puleiro)</option>
                                <option value="veterinaria">Medicina Veterinária (MedVet)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Aura (cor) – hidden, já foi escolhida -->
                    <input type="hidden" id="pref_cor_padrao_final" name="pref_cor_padrao" value="#70cde4">

                    <!-- Vibe – hidden, já foi escolhida -->
                    <input type="hidden" id="pref_vibe_padrao_final" name="pref_vibe_padrao" value="vibe-glass">

                    <!-- Aura inicial (estética) -->
                    <div class="campo-grupo-fenda">
                        <label for="aura_inicial">Estética da Aura Inicial</label>
                        <div class="fenda-reg-box">
                            <i class="fas fa-user-shield"></i>
                            <select name="aura_inicial" id="aura_inicial" class="input-fenda-select" required>
                                <option value="masculino" selected>Cyber Hacker (Masculino)</option>
                                <option value="feminino">Cyber Hacker (Feminino)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Termos -->
                    <div class="termos-wrapper">
                        <label class="checkbox-custom">
                            <input type="checkbox" name="termos" required>
                            <span class="checkmark"></span>
                            Reafirmo que eu li e concordo com as <a href="diretrizes.php"> Diretrizes da Comunidade</a>.
                        </label>
                    </div>

                    <!-- Honeypot -->
                    <div style="display: none !important;">
                        <label for="field_verification_backup">Não preencha</label>
                        <input type="text" name="field_verification_backup" id="field_verification_backup" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Turnstile -->
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAADtirduob0Sw9lJW" style="margin: 15px 0;"></div>

                    <div style="display:flex; justify-content:space-between; margin-top:20px; gap:10px; align-items:center;">
                        <button type="button" class="btn-step-prev" onclick="passoAnterior()">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </button>
                        <button type="submit" class="btn-finalizar-fenda" style="flex:1;">
                            FINALIZAR CADASTRO <i class="fas fa-rocket"></i>
                        </button>
                    </div>

                    <div class="form-footer">
                        Já tem conta? <a href="index.php">Faça Login</a>
                    </div>
                </div>
            </div>

        </form>
    </div>
</main>


<script>
    // ============================================================
    //  CONTROLE DE ETAPAS (STEPPER)
    // ============================================================
    let passoAtual = 1;
    const totalPassos = 4;
    let vibeSelecionada = null;
    let corSelecionada = '#70cde4';

    function irParaPasso(numero) {
        const steps = document.querySelectorAll('.step-container');
        steps.forEach((el, idx) => {
            const stepNum = idx + 1;
            if (stepNum === numero) {
                el.classList.add('active');
                el.classList.remove('saindo');
            } else if (stepNum < numero) {
                el.classList.remove('active');
                el.classList.remove('saindo');
            } else {
                el.classList.remove('active');
                el.classList.add('saindo');
                setTimeout(() => el.classList.remove('saindo'), 400);
            }
        });
        passoAtual = numero;
        document.querySelector('.container-cadastro-fenda').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    function proximoPasso() {
        if (passoAtual < totalPassos) {
            if (passoAtual === 3 && !vibeSelecionada) {
                alert('Escolha uma vibe para continuar.');
                return;
            }
            irParaPasso(passoAtual + 1);
        }
    }

    function passoAnterior() {
        if (passoAtual > 1) {
            irParaPasso(passoAtual - 1);
        }
    }

    // ============================================================
    //  FUNÇÕES DA ETAPA 2 (COR)
    // ============================================================
    function atualizarCor(hex) {
        corSelecionada = hex;
        document.querySelector('.aura-preview').style.background = hex;
        document.getElementById('cor-nome').textContent = hex;
        document.getElementById('pref_cor_padrao_final').value = hex;

        const container = document.getElementById('container-cadastro');
        if (container) {
            container.style.setProperty('--cor-aura', hex);
            const alpha = hex + '33';
            container.style.setProperty('--cor-aura-alpha', alpha);
            container.classList.add('aura-glow');
        }

        // Atualiza a prévia do perfil se já estiver visível
        const preview = document.getElementById('preview-perfil');
        if (preview && preview.style.display !== 'none') {
            preview.style.setProperty('--cor-aura', hex);
            const avatar = preview.querySelector('.preview-avatar');
            if (avatar) avatar.style.borderColor = hex;
        }
    }

    document.querySelectorAll('.btn-cor-sugestao').forEach(btn => {
        btn.addEventListener('click', function() {
            const cor = this.dataset.cor;
            document.getElementById('pref_cor_padrao').value = cor;
            atualizarCor(cor);
        });
    });

    // ============================================================
    //  FUNÇÕES DA ETAPA 3 (VIBE + PRÉVIA)
    // ============================================================
    function selecionarVibe(vibe, elemento) {
        document.querySelectorAll('.vibe-option').forEach(el => el.classList.remove('selecionado'));
        elemento.classList.add('selecionado');
        vibeSelecionada = vibe;
        document.getElementById('pref_vibe_padrao_final').value = vibe;
        document.getElementById('btn-proximo-vibe').disabled = false;

        const preview = document.getElementById('preview-perfil');
        if (preview) {
            preview.style.display = 'block';
            // Remove classes de vibe antigas
            preview.classList.remove('vibe-glass', 'vibe-neon', 'vibe-dark', 'vibe-light', 'vibe-ads');
            // Adiciona a classe da vibe escolhida
            preview.classList.add(vibe);
            // Aplica a cor da aura
            preview.style.setProperty('--cor-aura', corSelecionada);
            // Atualiza a borda do avatar
            const avatar = preview.querySelector('.preview-avatar');
            if (avatar) {
                avatar.style.borderColor = corSelecionada;
            }
        }
    }

    // ============================================================
    //  INICIALIZAÇÃO
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        irParaPasso(1);
        const corInicial = document.getElementById('pref_cor_padrao').value || '#70cde4';
        atualizarCor(corInicial);
    });

    // ============================================================
    //  PREVENÇÃO DE ENVIO ACIDENTAL
    // ============================================================
    document.getElementById('form-cadastro').addEventListener('submit', function(e) {
        if (passoAtual !== 4) {
            e.preventDefault();
            alert('Complete todas as etapas antes de finalizar.');
            irParaPasso(4);
            return;
        }
    });

    // ============================================================
    //  INDICADOR DE FORÇA DA SENHA
    // ============================================================
    (function() {
        const senhaInput = document.getElementById('senha');
        if (!senhaInput) return;

        const parentBox = senhaInput.closest('.fenda-reg-box');
        if (!parentBox) return;

        const helper = document.createElement('div');
        helper.style.cssText = 'font-size:0.75rem; margin-top:4px; color:#888; transition: color 0.2s;';
        parentBox.parentNode.insertBefore(helper, parentBox.nextSibling);

        function atualizarForca() {
            const val = senhaInput.value;
            let cor = '#888';
            let texto = '';
            if (val.length === 0) {
                texto = 'Digite uma senha (mín. 8 caracteres)';
                cor = '#888';
            } else if (val.length < 8) {
                texto = '⚠️ Mínimo 8 caracteres';
                cor = '#ff6b6b';
            } else if (val.length < 12) {
                texto = '✅ Boa senha';
                cor = '#ffbc00';
            } else {
                texto = '✅ Senha forte';
                cor = '#4caf50';
            }
            helper.textContent = texto;
            helper.style.color = cor;
        }

        senhaInput.addEventListener('input', atualizarForca);
        atualizarForca();
    })();
</script>

<?php include 'includes/footer.php'; ?>