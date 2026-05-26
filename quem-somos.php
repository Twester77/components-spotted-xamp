<?php 
include 'conexao.php'; 
include 'includes/header.php'; 
include 'includes/navbar.php'; 
include 'includes/bolhas.php'; 
?>

<main style="max-width: 900px; margin: auto; padding: 20px 0;" id="conteudo-principal">
    
    <div class="presentation-deck">

        <section class="fenda-slide" aria-labelledby="slide-origem-titulo">
            <h2 id="slide-origem-titulo" class="slide-title">01 // QUEM SOMOS</h2>
            
            <div class="slide-body" style="display: flex; flex-direction: column; gap: 20px; align-items: center;">
                <div style="width: 100%;">
                    <p>Nós somos um grupo de estudantes da UNIFEV que tem como objetivo principal criar um espaço de interação e compartilhamento de informações, experiências e interesses entre os alunos da universidade.</p>
                    <p>A ideia surgiu depois de um trabalho em HTML, CSS e GIT... O nome <strong>"A Fenda"</strong> foi escolhido como uma referência a um local de encontro, relacionado a uma camada profunda, uma "brecha" para a morada ou refúgio de seres marinhos...</p>
                    <p>Ela figurativamente é ligada à liberdade de falar e melhor se expressar sem medo de julgamentos.</p>
                </div>
            </div>
        </section>

        <section class="fenda-slide" aria-labelledby="slide-missao-titulo">
            <h2 id="slide-missao-titulo" class="slide-title">02 // NOSSA MISSÃO</h2>
            
            <div class="slide-body">
                <p>Promover a integração e o engajamento dos estudantes, criando um ambiente virtual onde eles possam se sentir acolhidos, apoiados e inspirados durante a sua jornada acadêmica.</p>
                
                <picture>
                    <source srcset="imagensfoto/capa-quem-somos-missao.avif" type="image/avif">
                    <source srcset="imagensfoto/capa-quem-somos-missao.webp" type="image/webp">
                    <img src="imagensfoto/capa-quem-somos-missao.jpg"
                         alt="Ilustração conceitual representando união, trabalho em equipe e a missão do grupo de estudantes"
                         style="width: 100%; height: auto; object-fit: cover; border-radius: 15px; opacity: 0.5; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"
                         loading="lazy">
                </picture>
            </div>
        </section>

        <section class="fenda-slide" aria-labelledby="slide-qg-titulo">
            <h2 id="slide-qg-titulo" class="slide-title">03 // NOSSOS QGs</h2>
            
            <div class="slide-body">
                <p>Embora a Fenda viva no ambiente digital, nossa mente e nossos códigos ganham vida nos blocos físicos da faculdade. Se liga nossos QG's:</p>
                
                <article class="campus-container" aria-label="Galeria de fotos dos câmpus da UNIFEV">
                    <div class="campus-flex">
                        <picture>
                            <source srcset="imagensfoto/campus-centro.avif" type="image/avif">
                            <source srcset="imagensfoto/campus-centro.webp" type="image/webp">
                            <img src="imagensfoto/campus-centro.jpg" alt="Fotografia da fachada do Câmpus Centro da UNIFEV" class="img-campus" loading="lazy">
                        </picture>

                        <picture>
                            <source srcset="imagensfoto/cidade-universitaria.avif" type="image/avif">
                            <source srcset="imagensfoto/cidade-universitaria.webp" type="image/webp">
                            <img src="imagensfoto/cidade-universitaria.jpg" alt="Fotografia da fachada da Cidade Universitária da UNIFEV" class="img-campus" loading="lazy">
                        </picture>
                    </div>
                    <figcaption class="legenda-campus" aria-hidden="true">Câmpus Centro & Cidade Universitária</figcaption>
                </article>
            </div>
        </section>

    </div>
</main>

<?php include 'includes/footer.php'; ?>