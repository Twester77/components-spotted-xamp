<?php
// 1. PRIMEIRO: Conexão e Sessão (Obrigatório, não pode faltar)
require_once __DIR__ . '/auth_check.php';

// 2. SEGUNDO: Segurança (Bloqueia quem não está logado)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// TERCEIRO: Lógica do "Modo Gaveta" (Definimos se vamos ou não carregar o resto)
$isAjax = isset($_GET['modo']) && $_GET['modo'] === 'gaveta';

// QUARTO: Includes Condicionais (Só carrega o que é global se NÃO for gaveta)
if (!$isAjax) {
    include 'includes/header.php';
    include 'includes/navbar.php';
    include 'includes/bolhas.php';
}

//QUINTO: Sua lógica de consulta ao banco (O motor de dados)
$id_meu = $_SESSION['usuario_id'];

// 1. O banco reconhece a escolha do usuário
$query = "SELECT id, nome, foto, bio, capa, username, atletica_id, pref_vibe_padrao, pref_cor_padrao, pref_swipe, pref_bolhas, pref_som_trilha, pref_som_notif FROM usuarios WHERE id = '$id_meu'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

// 🛡️ Blindando os nomes dos arquivos vindos do banco contra injeção de atributos HTML
$foto_limpa = !empty($dados['foto']) ? htmlspecialchars($dados['foto'], ENT_QUOTES, 'UTF-8') : '';
$capa_limpa = !empty($dados['capa']) ? htmlspecialchars($dados['capa'], ENT_QUOTES, 'UTF-8') : '';

// Se o utilizador tiver foto gravada (mesmo que seja a default dele), usa. Se não, usa o novo padrão masculino como última linha de defesa.
$foto_atual = !empty($foto_limpa) ? "uploads/" . $foto_limpa : "uploads/default_masculino.webp";
$capa_atual = !empty($capa_limpa) ? "uploads/" . $capa_limpa : "uploads/default_capa_masculino.webp";


$vibe_default = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
// Pega do banco ou usa o padrão
$cor_banco = $dados['pref_cor_padrao'] ?? '#70cde4';
// Garante que tenha a # para o HTML entender
if (substr($cor_banco, 0, 1) !== '#') {
    $cor_banco = '#' . $cor_banco;
}

// 2. Definição dos valores padrão para os campos de configuração, usando o operador de coalescência nula para garantir que sempre haja um valor
$trilha_default = $dados['pref_som_trilha'] ?? 'ondas';
$notif_default = $dados['pref_som_notif'] ?? 'padrao';
$cor_default = $cor_banco;
$bolhas_default = $dados['pref_bolhas'] ?? 1;
$classe_presenca = ($id_meu == 1) ? 'perfil-gold' : '';
?>

<main class="main-perfil-container-config <?php echo $classe_presenca; ?>">

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
                        pattern="[a-z0-9_\.]{5,18}"
                        minlength="5"
                        maxlength="18"
                        title="Apenas letras minúsculas, números, underline (_) ou ponto (.). Sem espaços! (De 5 a 18 caracteres)"
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
                    <input type="hidden" name="pref_som_trilha" id="drawer_input_pref_som_trilha" value="<?php echo $dados['pref_som_trilha']; ?>">
                    <input type="hidden" name="pref_som_notif" id="drawer_input_pref_som_notif" value="<?php echo $dados['pref_som_notif']; ?>">
                    <input type="hidden" name="pref_bolhas" id="drawer_input_pref_bolhas" value="<?php echo $dados['pref_bolhas']; ?>">

                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">Música de Fundo</span>
                    <div class="audio-choices-container">
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_trilha'] == 'chuva') ? 'active' : '' ?>" data-som="chuva" onclick="mudarSomAmbiente('chuva')">Chuva</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_trilha'] == 'ondas') ? 'active' : '' ?>" data-som="ondas" onclick="mudarSomAmbiente('ondas')">Oceano</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_trilha'] == 'off') ? 'active' : '' ?>" data-som="off" onclick="mudarSomAmbiente('off')">Mudo</button>
                    </div>

                    <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.05);"></div>

                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">Notificações</span>
                    <div class="audio-choices-container">
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'padrao') ? 'active' : '' ?>" data-notif="padrao" onclick="mudarTemaNotif('padrao')"><i class="fas fa-dot-circle"></i> Padrão</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'cs') ? 'active' : '' ?>" data-notif="cs" onclick="mudarTemaNotif('cs')"><i class="fas fa-crosshairs"></i> CS</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'resident') ? 'active' : '' ?>" data-notif="resident" onclick="mudarTemaNotif('resident')"><i class="fas fa-biohazard"></i> RE</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'off') ? 'active' : '' ?>" data-notif="off" onclick="mudarTemaNotif('off')"><i class="fas fa-bell-slash"></i> Mudo</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'starwars') ? 'active' : '' ?>" data-notif="starwars" onclick="mudarTemaNotif('starwars')"><i class="fas fa-jedi"></i> Star Wars</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'mario') ? 'active' : '' ?>" data-notif="mario" onclick="mudarTemaNotif('mario')"><img src="imagensfoto/mushroom.png" width="18" style="vertical-align: middle;"> Mario</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'pokemon') ? 'active' : '' ?>" data-notif="pokemon" onclick="mudarTemaNotif('pokemon')"><img src="imagensfoto/pokebola.png" width="18" style="vertical-align: middle;"> Pokémon</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'digimon') ? 'active' : '' ?>" data-notif="digimon" onclick="mudarTemaNotif('digimon')"><img src="imagensfoto/digivice.png" width="20" style="vertical-align: middle;"> Digimon</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'dbz') ? 'active' : '' ?>" data-notif="dbz" onclick="mudarTemaNotif('dbz')"><img src="imagensfoto/esferas-nuvem.png" width="20" style="vertical-align: middle;"> DBZ</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'naruto') ? 'active' : '' ?>" data-notif="naruto" onclick="mudarTemaNotif('naruto')"><img src="imagensfoto/kunai.png" width="20" style="vertical-align: middle;"> Naruto</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'streetfighter') ? 'active' : '' ?>" data-notif="streetfighter" onclick="mudarTemaNotif('streetfighter')"><i class="fa-solid fa-hand-fist"></i> Street Fighter</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'desgraca1') ? 'active' : '' ?>" data-notif="desgraca1" onclick="mudarTemaNotif('desgraca1')"><i class="fa-solid fa-triangle-exclamation"></i> Desgraça</button>
                        <button type="button" class="btn-audio-choice <?= ($dados['pref_som_notif'] == 'desgraca2') ? 'active' : '' ?>" data-notif="desgraca2" onclick="mudarTemaNotif('desgraca2')"><i class="fa-solid fa-skull"></i> Quero dormir</button>
                    </div>

                    <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.05);"></div>

                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">(De)feitos Visuais</span>
                    <div class="audio-choices-container">
                        <button type="button" class="btn-audio-choice btn-bolhas-on <?= ($dados['pref_bolhas'] == 1) ? 'active' : '' ?>" onclick="setBolhasLocal(1)">
                            <i class="fas fa-soap"></i> Bolhas On
                        </button>
                        <button type="button" class="btn-audio-choice btn-bolhas-off <?= ($dados['pref_bolhas'] == 0) ? 'active' : '' ?>" onclick="setBolhasLocal(0)">
                            <i class="fas fa-times"></i> Desligar
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

<?php
// SÉTIMO: Footer condicional
if (!$isAjax) {
    include 'includes/footer.php';
}
?>