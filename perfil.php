<?php
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

$id_meu = $_SESSION['usuario_id'];

// 1. O banco reconhece a escolha do usuário
$query = "SELECT id, nome, foto, bio, capa, username, atletica_id, pref_vibe_padrao, pref_cor_padrao, pref_swipe, pref_bolhas FROM usuarios WHERE id = '$id_meu'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

// 🛡️ Blindando os nomes dos arquivos vindos do banco contra injeção de atributos HTML
$foto_limpa = !empty($dados['foto']) ? htmlspecialchars($dados['foto'], ENT_QUOTES, 'UTF-8') : '';
$capa_limpa = !empty($dados['capa']) ? htmlspecialchars($dados['capa'], ENT_QUOTES, 'UTF-8') : '';

// Se o utilizador tiver foto gravada (mesmo que seja a default dele), usa. Se não, usa o novo padrão masculino como última linha de defesa.
$foto_atual = !empty($foto_limpa) ? "uploads/" . $foto_limpa : "uploads/default_masculino.jpg";
$capa_atual = !empty($capa_limpa) ? "uploads/" . $capa_limpa : "uploads/default_capa_masculino.jpg";

$vibe_default = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
// Pega do banco ou usa o padrão
$cor_banco = $dados['pref_cor_padrao'] ?? '#70cde4';
// Garante que tenha a # para o HTML entender
if (substr($cor_banco, 0, 1) !== '#') {
    $cor_banco = '#' . $cor_banco;
}

$cor_default = $cor_banco;
$bolhas_default = $dados['pref_bolhas'] ?? 1;
$classe_presenca = ($id_meu == 1) ? 'perfil-gold' : '';
?>

<main class="main-perfil-container-config <?php echo $classe_presenca; ?>">
    <?php if (isset($_GET['sucesso'])): ?>
        <div id="toast-sucesso" class="toast-fenda">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>Perfil atualizado com sucesso!</span>
        </div>

        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-sucesso');
                if (toast) {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500);
                }
            }, 4000);
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'username_duplicado'): ?>
        <div id="toast-erro" class="toast-fenda" style="background: rgba(255, 75, 43, 0.85); border-color: #ff4b2b;">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <span>Esse @ já está sendo usado por outro habitante!</span>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-erro');
                if (toast) {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500);
                }
            }, 4000);
        </script>
    <?php endif; ?>

    <form action="processa-perfil.php" method="POST" enctype="multipart/form-data">
        <div class="perfil-header-container">
            <div class="capa-wrapper">
                <?php if (!empty($dados['capa'])): ?>
                    <img src="<?php echo $capa_atual; ?>" class="img-capa-preview" alt="Sua imagem de capa de perfil">
                <?php else: ?>
                    <div class="capa-default-fenda" style="background: linear-gradient(135deg, #004a8f 0%, #00a896 100%); display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-weight: bold; font-size: 1.3rem;">BEM-VINDO À FENDA!</span>
                    </div>
                <?php endif; ?>

                <label id="label-capa" class="btn-mudar-capa">
                    <i class="fas fa-camera" aria-hidden="true"></i> 
                    <input type="file" name="capa" style="display:none;" aria-labelledby="label-capa">
                </label>
            </div>
        </div>

        <div class="avatar-wrapper">
            <img src="<?php echo $foto_atual; ?>" class="img-avatar-perfil" alt="Sua foto de avatar">
            <label id="label-avatar" class="btn-mudar-avatar">
                <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                <input type="file" name="foto" style="display:none;" aria-labelledby="label-avatar">
            </label>
        </div>

        <div class="form-perfil-corpo">
            <h2 class="titulo-pagina">Configurações de Habitante</h2>

            <div class="campo-grupo">
                <label for="nome"><i class="fas fa-user-tag" aria-hidden="true"></i> Nome de Exibição</label>
                <input type="text"
                    id="nome"
                    name="nome"
                    value="<?php echo htmlspecialchars($dados['nome']); ?>"
                    placeholder="Como quer ser chamado no feed?"
                    pattern="[a-zA-ZÀ-ÿ\s]{2,25}"
                    minlength="2"
                    maxlength="25"
                    title="Digite um nome válido de 2 a 25 letras."
                    required>
            </div>

            <div class="campo-grupo">
                <label for="username"><i class="fas fa-at" aria-hidden="true"></i> Seu Username (@ para menções)</label>
                <div class="input-username-wrapper">
                    <span>@</span>
                    <input type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($dados['username']); ?>"
                        pattern="[a-z0-9_\.]{5,15}"
                        minlength="5"
                        maxlength="15"
                        title="Apenas letras minúsculas, números, underline (_) ou ponto (.). Sem espaços! (De 5 a 15 caracteres)"
                        oninput="this.value = this.value.toLowerCase().replace(/\s/g, '')"
                        required>
                </div>
            </div>

            <div class="campo-grupo">
                <label for="atletica_id">Sua Atlética</label>
                <select name="atletica_id" id="atletica_id" class="input-fenda-select">
                    <option value="">Selecione sua Atlética...</option>
                    <option value="ads" <?php echo ($dados['atletica_id'] == 'ads') ? 'selected' : ''; ?>>Análise e Desenvolvimento de Sistemas (Overclock)</option>
                    <option value="agronomia" <?php echo ($dados['atletica_id'] == 'agronomia') ? 'selected' : ''; ?>>Engenharia Agronômica (Usagro)</option>
                    <option value="arquitetura" <?php echo ($dados['atletica_id'] == 'arquitetura') ? 'selected' : ''; ?>>Arquitetura (Arcana)</option>
                    <option value="biomedicina" <?php echo ($dados['atletica_id'] == 'biomedicina') ? 'selected' : ''; ?>>Biomedicina (Leptospirados)</option>
                    <option value="contabeis" <?php echo ($dados['atletica_id'] == 'contabeis') ? 'selected' : ''; ?>>Ciências Contábeis (Panda)</option>
                    <option value="direito" <?php echo ($dados['atletica_id'] == 'direito') ? 'selected' : ''; ?>>Direito (Soberana)</option>
                    <option value="ed-fisica" <?php echo ($dados['atletica_id'] == 'ed-fisica') ? 'selected' : ''; ?>>Educação Física (Demolidores)</option>
                    <option value="enfermagem" <?php echo ($dados['atletica_id'] == 'enfermagem') ? 'selected' : ''; ?>>Enfermagem (Ferma)</option>
                    <option value="eng-comp" <?php echo ($dados['atletica_id'] == 'eng-comp') ? 'selected' : ''; ?>>Engenharia de Computação (Octabit)</option>
                    <option value="eng-mecanica" <?php echo ($dados['atletica_id'] == 'eng-mecanica') ? 'selected' : ''; ?>>Engenharia Mecânica (MEC) </option>
                    <option value="farmacia" <?php echo ($dados['atletica_id'] == 'farmacia') ? 'selected' : ''; ?>>Farmácia (Narcótica)</option>
                    <option value="fisioterapia" <?php echo ($dados['atletica_id'] == 'fisioterapia') ? 'selected' : ''; ?>>Fisioterapia (Fisio) </option>
                    <option value="medicina" <?php echo ($dados['atletica_id'] == 'medicina') ? 'selected' : ''; ?>>Medicina (Javalaria)</option>
                    <option value="nutricao" <?php echo ($dados['atletica_id'] == 'nutricao') ? 'selected' : ''; ?>>Nutrição (Devoradores)</option>
                    <option value="pedagogia" <?php echo ($dados['atletica_id'] == 'pedagogia') ? 'selected' : ''; ?>>Pedagogia (Mediadores)</option>
                    <option value="psicologia" <?php echo ($dados['atletica_id'] == 'psicologia') ? 'selected' : ''; ?>>Psicologia (Psicose)</option>
                    <option value="propaganda" <?php echo ($dados['atletica_id'] == 'propaganda') ? 'selected' : ''; ?>>Publicidade (Puleiro)</option>
                    <option value="veterinaria" <?php echo ($dados['atletica_id'] == 'veterinaria') ? 'selected' : ''; ?>>Medicina Veterinária (MedVet)</option>
                </select>
            </div>

            <div class="campo-grupo">
                <label for="bio"><i class="fas fa-pencil-alt" aria-hidden="true"></i> Sua Bio</label>
                <textarea id="bio"
                    name="bio"
                    placeholder="Conte um pouco sobre você para a Fenda..."
                    maxlength="350"><?php echo htmlspecialchars($dados['bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="campo-grupo" style="margin-top: 15px;">
                <label>Configurações de Áudio e Interface</label>
                <div class="audio-settings-card">
                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">Música de Fundo</span>
                    <div class="audio-choices-container">
                        <button type="button" id="btn-som-chuva" class="btn-audio-choice <?php echo (isset($_SESSION['som']) && $_SESSION['som'] == 'chuva') ? 'active' : ''; ?>" onclick="mudarSomAmbiente('chuva')">Chuva</button>
                        <button type="button" id="btn-som-ondas" class="btn-audio-choice <?php echo (isset($_SESSION['som']) && $_SESSION['som'] == 'ondas') ? 'active' : ''; ?>" onclick="mudarSomAmbiente('ondas')">Oceano</button>
                        <button type="button" id="btn-som-off" class="btn-audio-choice <?php echo (!isset($_SESSION['som']) || $_SESSION['som'] == 'off') ? 'active' : ''; ?>" onclick="mudarSomAmbiente('off')">Mudo</button>
                    </div>

                    <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.05);"></div>

                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">Notificações</span>
                    <div class="audio-choices-container">
                        <button type="button" id="btn-notif-padrao" class="btn-audio-choice" onclick="mudarTemaNotif('padrao')"><i class="fas fa-dot-circle" aria-hidden="true"></i> Padrão</button>
                        <button type="button" id="btn-notif-cs" class="btn-audio-choice" onclick="mudarTemaNotif('cs')"><i class="fas fa-crosshairs" aria-hidden="true"></i> CS</button>
                        <button type="button" id="btn-notif-resident" class="btn-audio-choice" onclick="mudarTemaNotif('resident')"><i class="fas fa-biohazard" aria-hidden="true"></i> RE</button>
                        <button type="button" id="btn-notif-off" class="btn-audio-choice" onclick="mudarTemaNotif('off')"><i class="fas fa-bell-slash" aria-hidden="true"></i> Mudo</button>

                        <button type="button" id="btn-notif-starwars" class="btn-audio-choice" onclick="mudarTemaNotif('starwars')"><i class="fas fa-jedi" aria-hidden="true"></i> Star Wars</button>
                        <button type="button" id="btn-notif-mario" class="btn-audio-choice" onclick="mudarTemaNotif('mario')">
                            <img src="imagensfoto/mushroom.png" width="18" style="vertical-align: middle; margin-right: 5px;" alt="Cogumelo"> Mario
                        </button>

                        <button type="button" id="btn-notif-pokemon" class="btn-audio-choice" onclick="mudarTemaNotif('pokemon')">
                            <img src="imagensfoto/pokebola.png" width="18" style="vertical-align: middle; margin-right: 5px;" alt="Pokebola"> Pokémon
                        </button>

                        <button type="button" id="btn-notif-digimon" class="btn-audio-choice" onclick="mudarTemaNotif('digimon')">
                            <img src="imagensfoto/digivice.png" width="22" style="vertical-align: middle; margin-right: 5px;" alt="Digivice"> Digimon
                        </button>

                        <button type="button" id="btn-notif-dbz" class="btn-audio-choice" onclick="mudarTemaNotif('dbz')">
                            <img src="imagensfoto/esferas-nuvem.png" width="20" style="vertical-align: middle; margin-right: 5px;" alt="Esferas do Dragão"> DBZ
                        </button>

                        <button type="button" id="btn-notif-naruto" class="btn-audio-choice" onclick="mudarTemaNotif('naruto')">
                            <img src="imagensfoto/kunai.png" width="20" style="vertical-align: middle; margin-right: 5px;" alt="Kunai"> Naruto
                        </button>
                        <button type="button" id="btn-notif-streetfighter" class="btn-audio-choice" onclick="mudarTemaNotif('streetfighter')">
                            <i class="fa-solid fa-hand-fist" aria-hidden="true"></i> Street Fighter
                        </button>
                        <button type="button" id="btn-notif-desgraca1" class="btn-audio-choice" onclick="mudarTemaNotif('desgraca1')">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Desgraça
                        </button>

                        <button type="button" id="btn-notif-desgraca2" class="btn-audio-choice" onclick="mudarTemaNotif('desgraca2')">
                            <i class="fa-solid fa-skull" aria-hidden="true"></i> Quero dormir
                        </button>
                    </div>

                    <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.05);"></div>

                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">(De)feitos Visuais</span>
                    <div class="audio-choices-container">
                        <input type="hidden" name="pref_bolhas" id="input_pref_bolhas" value="<?php echo $bolhas_default; ?>">
                        <button type="button" id="btn-bolhas-on" class="btn-audio-choice <?php echo ($bolhas_default == 1) ? 'active' : ''; ?>" onclick="setBolhasLocal(1)">
                            <i class="fas fa-soap" aria-hidden="true"></i> Bolhas On
                        </button>
                        <button type="button" id="btn-bolhas-off" class="btn-audio-choice <?php echo ($bolhas_default == 0) ? 'active' : ''; ?>" onclick="setBolhasLocal(0)">
                            <i class="fas fa-times" aria-hidden="true"></i> Desligar
                        </button>
                    </div>
                </div>
            </div>

            <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.05);"></div>

            <div class="campo-grupo">
                <label>Vibe da Aura</label>
                <select name="pref_vibe_padrao" class="input-fenda">
                    <option value="vibe-glass" <?php echo ($vibe_default == 'vibe-glass') ? 'selected' : ''; ?>>Padrão (Vidro)</option>
                    <option value="vibe-neon" <?php echo ($vibe_default == 'vibe-neon') ? 'selected' : ''; ?>>Neon (Preto Profundo)</option>
                    <option value="vibe-dark" <?php echo ($vibe_default == 'vibe-dark') ? 'selected' : ''; ?>>Dark (Eigengrau)</option>
                    <option value="vibe-light" <?php echo ($vibe_default == 'vibe-light') ? 'selected' : ''; ?>>Light (Solar)</option>
                    <option value="vibe-ads" <?php echo ($dados['pref_vibe_padrao'] == 'vibe-ads') ? 'selected' : ''; ?>>ADS (Overclock)</option>
                </select>
            </div>

            <div class="config-item">
                <span>Modo Swipe (Beta):</span>
                <label class="switch">
                    <input type="checkbox" name="pref_swipe" value="1" <?php echo ($dados['pref_swipe'] == 1) ? 'checked' : ''; ?>>
                    <span class="slider round"></span>
                </label>
                <small>Isso mudará seu feed para o modo Pilha (Estilo APP)</small>
            </div>

            <div class="campo-grupo">
                <label>Cor da Aura</label>
                <input type="color" name="pref_cor_padrao" value="<?php echo $cor_default; ?>" style="border: none; background: none; cursor: pointer;">
            </div>

            <div class="perfil-controles" style="width: 100% !important; display: flex !important; flex-wrap: wrap !important; gap: 10px; margin: 20px 0;">
                <button type="submit" class="btn-editar-atalho">SALVAR ALTERAÇÕES</button>
                <a href="ver-perfil.php?user=<?php echo $dados['username']; ?>" class="btn-editar-atalho">
                    VER PERFIL PÚBLICO
                </a>
            </div>
        </div>
    </form>
</main>

<script>
    // Função simples para gerenciar os botões de bolha sem complicar o código
    function setBolhasLocal(valor) {
        document.getElementById('input_pref_bolhas').value = valor;
        document.getElementById('btn-bolhas-on').classList.toggle('active', valor === 1);
        document.getElementById('btn-bolhas-off').classList.toggle('active', valor === 0);

        if (typeof setBolhas === "function") {
            setBolhas(valor === 1);
        }
    }

    // NOVA FUNÇÃO: Validação de tamanho de imagem (Máximo 2MB)
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const tamanhoMB = this.files[0].size / 1024 / 1024;
                if (tamanhoMB > 2) {
                    alert("A imagem é muito grande (" + tamanhoMB.toFixed(2) + "MB). O limite do servidor é 2MB. Por favor, escolha uma foto mais leve!");
                    this.value = "";
                }
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
