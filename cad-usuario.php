<?php
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<main class="main-form-cadastro">
    <div class="container-cadastro-fenda">

        <div class="cadastro-header">
            <h2>Criar sua conta na Fenda</h2>
            <p>Junte-se à comunidade oficial da Fenda</p>
        </div>

        <form action="processa-cadastro.php" method="POST" class="form-fenda-estilizado">
            <div class="campo-grupo-fenda">
                <label for="nome">Nome ou Apelido</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-user"></i>
                    <input type="text" id="nome" name="nome" placeholder="Ex: Fulano, Furlas..." maxlength="30" required>
                </div>
            </div>

            <div class="campo-grupo-fenda">
                <label for="email">E-mail (Para ativação)</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-envelope"></i>
                    <input type="email"
                        id="email"
                        name="email"
                        placeholder="E-mail pessoal ou acadêmico"
                        required>
                </div>
            </div>

            <div class="campo-grupo-fenda">
                <label for="senha">Crie uma Senha</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="senha" name="senha" placeholder="8 a 20 caracteres" maxlength="20" minlength="8" required>
                </div>
            </div>

            <!-- Campo de Seleção de Atlética -->
            <div class="campo-grupo-fenda">
                <label for="atletica_id">Sua Atlética</label>
                <div class="fenda-reg-box">
                    <i class="fas fa-shield-alt"></i>
                    <select name="atletica_id" id="atletica_id" class="input-fenda-select" required>
                        <option value="" disabled selected>Selecione sua Atlética...</option>
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
                        <option value="propaganda">Publicidade (Puleiro)</option>
                        <option value="veterinaria">Medicina Veterinária (MedVet)</option>
                    </select>
                </div>
            </div>

            <!-- Escolha da Aura (Cor) -->
            <div class="campo-grupo-fenda">
                <label for="pref_cor_padrao">Sua Aura (Cor do Perfil)</label>
                <div class="fenda-reg-box" style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-palette"></i>
                    <input type="color" id="pref_cor_padrao" name="pref_cor_padrao" value="#70cde4" style="border: none; background: transparent; cursor: pointer; width: 40px; height: 30px;">
                </div>
            </div>
            <!-- NOVO: Seleção da Vibe (Estilo do Card) -->
            <div class="campo-grupo-fenda">
                <label for="pref_vibe_padrao">Vibe da Aura</label>               
                <div class="fenda-reg-box">                   
                    <i class="fas fa-ghost"></i>
                    <select name="pref_vibe_padrao" id="pref_vibe_padrao" class="input-fenda-select" required>
                        <option value="vibe-glass" selected>Padrão (Glass)</option>
                        <option value="vibe-neon">Neon (Preto Profundo)</option>
                        <option value="vibe-dark">Dark (Eigengrau)</option>
                        <option value="vibe-light">Light (Solar)</option>
                    </select>
                </div>
            </div>

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

            <div class="termos-wrapper">
                <label class="checkbox-custom">
                    <input type="checkbox" name="termos" required>
                    <span class="checkmark"></span>
                    Reafirmo que eu li e concordo com as <a href="diretrizes.php"> Diretrizes da Comunidade</a>.
                </label>
            </div>

            <button type="submit" class="btn-finalizar-fenda">
                FINALIZAR CADASTRO <i class="fas fa-rocket"></i>
            </button>

            <div class="form-footer">
                Já tem conta? <a href="index.php">Faça Login</a>
            </div>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>