<?php
include_once 'conexao.php'; // Segurança contra o erro de "Redeclare"

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// O header.php agora faz a própria consulta de preferência de Swipe para o <body>
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';

$id_meu = $_SESSION['usuario_id'];

// BUSCA DE DADOS - Incluindo Vibes e Swipe
$query = "SELECT id, nome, foto, bio, capa, username, atletica_id, pref_vibe_padrao, pref_cor_padrao, pref_swipe FROM usuarios WHERE id = '$id_meu'";
$resultado = mysqli_query($conn, $query);
$dados = mysqli_fetch_assoc($resultado);

// Configuração dos caminhos
$foto_atual = !empty($dados['foto']) ? "uploads/" . $dados['foto'] : "imagensfoto/default.jpg";
$capa_atual = !empty($dados['capa']) ? "uploads/" . $dados['capa'] : "imagensfoto/capa_padrao.jpg";

// Variáveis para as Vibes
$vibe_default = $dados['pref_vibe_padrao'] ?? 'vibe-glass';
$cor_default = $dados['pref_cor_padrao'] ?? '#70cde4';

// Diferenciação para a conta "Presença"
$classe_presenca = ($id_meu == 1) ? 'perfil-gold' : '';
?>

<main class="main-perfil-container <?php echo $classe_presenca; ?>">
    <?php if (isset($_GET['sucesso'])): ?>
        <div id="toast-sucesso" class="toast-fenda">
            <i class="fa-solid fa-circle-check"></i>
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

    <form action="processa-perfil.php" method="POST" enctype="multipart/form-data">
        <div class="perfil-header-container">
            <div class="capa-wrapper">
                <?php if (!empty($dados['capa'])): ?>
                    <img src="<?php echo $capa_atual; ?>" class="img-capa-preview">
                <?php else: ?>
                    <div class="capa-default-fenda" style="background: linear-gradient(135deg, #004a8f 0%, #00a896 100%); display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-weight: bold; font-size: 1.3rem;">BEM-VINDO À FENDA!</span>
                    </div>
                <?php endif; ?>

                <label class="btn-mudar-capa">
                    <i class="fas fa-camera"></i>
                    <input type="file" name="capa" style="display:none;">
                </label>
            </div>
        </div>

        <div class="avatar-wrapper">
            <img src="<?php echo $foto_atual; ?>" class="img-avatar-perfil">
            <label class="btn-mudar-avatar">
                <i class="fas fa-pencil-alt"></i>
                <input type="file" name="foto" style="display:none;">
            </label>
        </div>

        <div class="form-perfil-corpo">
            <h2 class="titulo-pagina">Configurações de Habitante</h2>

            <div class="campo-grupo">
                <label>Nome</label>
                <input type="text" name="nome" maxlength="25" value="<?php echo htmlspecialchars($dados['nome']); ?>" required>
            </div>

            <div class="campo-grupo">
                <label>Username</label>
                <div class="input-username-wrapper">
                    <span>@</span>
                    <input type="text" name="username" maxlength="20" value="<?php echo htmlspecialchars($dados['username']); ?>">
                </div>
            </div>

            <div class="campo-grupo">
                <label>Sua Atlética</label>
                <select name="atletica_id" class="input-fenda-select">
                    <option value="">Selecione sua Atlética...</option>
                    <option value="agronomia" <?php echo ($dados['atletica_id'] == 'agronomia') ? 'selected' : ''; ?>>Engenharia Agronômica (Usagro)</option>
                    <option value="arquitetura" <?php echo ($dados['atletica_id'] == 'arquitetura') ? 'selected' : ''; ?>>Arquitetura (Arcana)</option>
                    <option value="biomedicina" <?php echo ($dados['atletica_id'] == 'biomedicina') ? 'selected' : ''; ?>>Biomedicina (Leptospirados)</option>
                    <option value="contabeis" <?php echo ($dados['atletica_id'] == 'contabeis') ? 'selected' : ''; ?>>Ciências Contábeis (Panda)</option>
                    <option value="direito" <?php echo ($dados['atletica_id'] == 'direito') ? 'selected' : ''; ?>>Direito (Soberana)</option>
                    <option value="ed-fisica" <?php echo ($dados['atletica_id'] == 'ed-fisica') ? 'selected' : ''; ?>>Educação Física (Demolidores)</option>
                    <option value="enfermagem" <?php echo ($dados['atletica_id'] == 'enfermagem') ? 'selected' : ''; ?>>Enfermagem (Ferma)</option>
                    <option value="eng-comp" <?php echo ($dados['atletica_id'] == 'eng-comp') ? 'selected' : ''; ?>>Engenharia de Computação (Octabit)</option>
                    <option value="eng-mecanica" <?php echo ($dados['atletica_id'] == 'eng-mecanica') ? 'selected' : ''; ?>>Engenharia Mecânica (MEC)</option>
                    <option value="farmacia" <?php echo ($dados['atletica_id'] == 'farmacia') ? 'selected' : ''; ?>>Farmácia (Narcótica)</option>
                    <option value="fisioterapia" <?php echo ($dados['atletica_id'] == 'fisioterapia') ? 'selected' : ''; ?>>Fisioterapia (Fisio)</option>
                    <option value="medicina" <?php echo ($dados['atletica_id'] == 'medicina') ? 'selected' : ''; ?>>Medicina (Javalaria)</option>
                    <option value="nutricao" <?php echo ($dados['atletica_id'] == 'nutricao') ? 'selected' : ''; ?>>Nutrição (Devoradores)</option>
                    <option value="pedagogia" <?php echo ($dados['atletica_id'] == 'pedagogia') ? 'selected' : ''; ?>>Pedagogia (Mediadores)</option>
                    <option value="psicologia" <?php echo ($dados['atletica_id'] == 'psicologia') ? 'selected' : ''; ?>>Psicologia (Psicose)</option>
                    <option value="propaganda" <?php echo ($dados['atletica_id'] == 'propaganda') ? 'selected' : ''; ?>>Publicidade (Puleiro)</option>
                    <option value="veterinaria" <?php echo ($dados['atletica_id'] == 'veterinaria') ? 'selected' : ''; ?>>Medicina Veterinária (MedVet)</option>
                </select>
            </div>

            <div class="campo-grupo">
                <label>Sua Bio</label>
                <textarea name="bio" maxlength="400" rows="3"><?php echo htmlspecialchars($dados['bio']); ?></textarea>
            </div>

            <div class="campo-grupo" style="margin-top: 15px;">
                <label>Configurações de Som</label>
                <div class="audio-settings-card">
                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">Som Ambiente</span>
                    <div class="audio-choices-container">
                        <button type="button" id="btn-som-chuva" class="btn-audio-choice" onclick="mudarSomAmbiente('chuva')">Chuva</button>
                        <button type="button" id="btn-som-ondas" class="btn-audio-choice" onclick="mudarSomAmbiente('ondas')">Oceano</button>
                        <button type="button" id="btn-som-off" class="btn-audio-choice" onclick="mudarSomAmbiente('off')">Mudo</button>
                    </div>

                    <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.05);"></div>

                    <span style="font-size: 0.85rem; color: #888; font-weight: bold; text-transform: uppercase;">Alertas</span>
                    <div class="audio-choices-container">
                        <button type="button" class="btn-audio-choice" onclick="mudarTemaNotif('padrao')"><i class="fas fa-dot-circle"></i> Padrão</button>
                        <button type="button" class="btn-audio-choice" onclick="mudarTemaNotif('resident')"><i class="fas fa-biohazard"></i> Biohazard</button>
                        <button type="button" class="btn-audio-choice" onclick="mudarTemaNotif('cs')"><i class="fas fa-crosshairs"></i> CS</button>
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
                </select>
            </div>

            <div class="config-item" style="margin: 15px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Modo Swipe (Beta)</span>
                    <label class="switch">
                        <input type="checkbox" name="pref_swipe" value="1" <?php echo ($dados['pref_swipe'] == 1) ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
                <small style="color: #888; display: block; margin-top: 5px;">Arraste para o lado para interagir (Experimental)</small>
            </div>

            <div class="campo-grupo">
                <label>Cor da Aura</label>
                <input type="color" name="pref_cor_padrao" value="<?php echo $cor_default; ?>" style="width: 100%; height: 40px; border: none; background: none; cursor: pointer;">
            </div>

            <div class="perfil-controles" style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn-editar-atalho">SALVAR ALTERAÇÕES</button>
                <a href="ver-perfil.php?user=<?php echo $dados['username']; ?>" class="btn-editar-atalho" style="text-align: center; text-decoration: none;">
                    VER PERFIL PÚBLICO
                </a>
            </div>
        </div>
    </form>
</main>

<?php include 'includes/footer.php'; ?>