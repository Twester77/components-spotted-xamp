<?php
include 'conexao.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-container" style="padding: 20px; min-height: 80vh; display: flex; flex-direction: column; align-items: center;">

    <section class="atleticas-section" style="text-align: center; margin-bottom: 50px;">
        <h2 style="color: var(--dourado); font-size: 1.8rem; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 2px;">
            Atléticas Parceiras
        </h2>

        <div class="atleticas-grid" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.usagro/" target="_blank" class="atletica-item">
                    <img src="badges/agronomia.png" class="insignia-atletica-link" title="Siga a Atlética de Agronomia" >
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">AGRONOMIA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.arcana/" target="_blank" class="atletica-item">
                    <img src="badges/arquitetura.png" class="insignia-atletica-link" title="Siga a Atlética de Arquitetura">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">ARQ & URB</p>
            </div>

             <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.leptospirados/" target="_blank" class="atletica-item">
                    <img src="badges/biomedicina.png" class="insignia-atletica-link" title="Siga a Atlética de Biomedicina" >
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">BIOMEDICINA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atletipanda/" target="_blank" class="atletica-item">
                    <img src="badges/contabeis.png" class="insignia-atletica-link" title="Siga a Atlética de Ciências Contábeis">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">C.CONTÁBEIS</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/soberana.direito/" target="_blank" class="atletica-item">
                    <img src="badges/direito.png" class="insignia-atletica-link" title="Siga a Atlética de Direito">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">DIREITO</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.demolidores/" target="_blank" class="atletica-item">
                    <img src="badges/ed-fisica.png" class="insignia-atletica-link" title="Siga a Atlética de Educação Física" >
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">ED.FÍSICA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atleticaenfermagemvotu/" target="_blank" class="atletica-item">
                    <img src="badges/enfermagem.png" class="insignia-atletica-link" title="Siga a Atlética de Enfermagem" >
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">ENFERMAGEM</p>
            </div>

             <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.octabit/" target="_blank" class="atletica-item">
                    <img src="badges/eng-comp.png" class="insignia-atletica-link" title="Siga a Atlética de Engenharia de Computação">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">ENG.COMPUTAÇÃO</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/narcotica_atletica.unifev/" target="_blank" class="atletica-item">
                    <img src="badges/farmacia.png" class="insignia-atletica-link" title="Siga a Atlética de Enfermagem">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">FARMÁCIA</p>
            </div>

             <div style="text-align: center;">
                <a href="https://www.instagram.com/atleticafisiovotu/" target="_blank" class="atletica-item">
                    <img src="badges/fisioterapia.png" class="insignia-atletica-link" title="Siga a Atlética de Fisioterapia">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">FISIOTERAPIA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/medvotuaaamv/" target="_blank" class="atletica-item">
                    <img src="badges/medicina.png" class="insignia-atletica-link" title="Siga a Atlética de Medicina">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">MEDICINA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atleticadevoradores_votu/" target="_blank" class="atletica-item">
                    <img src="badges/nutricao.png" class="insignia-atletica-link" title="Siga a Atlética de Nutrição">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">NUTRIÇÃO</p>
            </div>

             <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.mediadores/" target="_blank" class="atletica-item">
                    <img src="badges/pedagogia.png" class="insignia-atletica-link" title="Siga a Atlética de Pedagogia">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">PEDAGOGIA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.puleiro/" target="_blank" class="atletica-item">
                    <img src="badges/propaganda.png" class="insignia-atletica-link" title="Siga a Atlética de Propaganda">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">PUBLI & PROPAGANDA</p>
            </div>

            <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.psicose.votu/" target="_blank" class="atletica-item">
                    <img src="badges/psicologia.png" class="insignia-atletica-link" title="Siga a Atlética de Psicologia">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">PSICOLOGIA</p>
            </div>

             <div style="text-align: center;">
                <a href="https://www.instagram.com/atletica.veterinaria/" target="_blank" class="atletica-item">
                    <img src="badges/veterinaria.png" class="insignia-atletica-link" title="Siga a Atlética de Medicina Veterinária">
                </a>
                <p style="color: #ff8c00; font-size: 0.9rem; margin-top: 10px; font-weight: 800;">MED.VETERINÁRIA</p>
            </div>

        </div>
    </section>

    <section class="embreve-card" style="
        background: rgba(10, 10, 10, 0.8);
        border: 2px dashed var(--dourado);
        border-radius: 30px;
        padding: 60px 30px;
        max-width: 600px;
        width: 100%;
        text-align: center;
        backdrop-filter: blur(10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    ">
        <div class="icon-container" style="margin-bottom: 25px;">
            <i class="fa-solid fa-store" style="font-size: 4rem; color: var(--dourado); filter: drop-shadow(0 0 10px var(--dourado));"></i>
        </div>

        <h1 style="color: #fff; font-size: 2rem; text-transform: uppercase; margin-bottom: 15px;">
            Classificados <span style="color: var(--dourado);">"A Fenda"</span>
        </h1>

        <p style="color: #bbb; font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px;">
            O marketplace oficial da Fenda está sendo preparado. <br>
            Logo você poderá desapegar de livros, materiais, procurar e divulgar vagas de república, e muito mais!
        </p>

        <div class="status-badge" style="
            display: inline-block;
            background: var(--dourado);
            color: #000;
            padding: 8px 25px;
            border-radius: 50px;
            font-weight: 900;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        ">
            🚀 Em Desenvolvimento
        </div>

        <div style="margin-top: 40px;">
            <a href="index.php" class="btn-editar-atalho" style="text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Voltar para o Home
            </a>
        </div>
    </section>

</main>

<?php include 'includes/footer.php'; ?>