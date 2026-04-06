<?php
include 'conexao.php'; 
include 'includes/header.php'; 

// O redirecionamento que estava aqui foi removido para você conseguir ver a Home logado!
?>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/bolhas.php'; ?>

<main>
    
    <?php if(!isset($_SESSION['usuario_id'])): ?>
        <?php include 'includes/login.php'; ?>
    <?php else: ?>
        <div style="background: rgba(255, 255, 255, 0.07); 
                    backdrop-filter: blur(10px); 
                    padding: 30px; 
                    border-radius: 20px; 
                    text-align: center; 
                    margin-bottom: 40px; 
                    border: 1px solid rgba(255, 188, 0, 0.3); 
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
            
            <div style="font-size: 40px; margin-bottom: 10px;">🎓</div>
            
            <p style="color: #fff; margin-bottom: 25px; font-size: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                E aí, <span style="color: #ffbc00; font-weight: bold;"><?php echo $_SESSION['usuario_nome']; ?></span>! <br>
                <span style="font-size: 16px; opacity: 0.8;">Bem-vindo à Fenda, o QG virtual da UNIFEV.</span>
            </p>
            

            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="feed.php" style="
                    background: #ffbc00; 
                    color: #000; 
                    padding: 12px 35px; 
                    border-radius: 12px; 
                    text-decoration: none; 
                    font-weight: bold; 
                    font-size: 16px;
                    display: flex;
                    align-items: center;
                    gap: 10px; 
                    ">
                    🚀 Ir para o Feed
                </a>

                <button onclick="deslogar()" style="
                    background: rgba(204, 66, 12, 0.2); 
                    color: #fa593d; 
                    border: 1px solid #cc420c; 
                    padding: 12px 35px; 
                    border-radius: 12px; 
                    cursor: pointer; 
                    font-weight: bold; 
                    font-size: 16px;
                    transition: all 0.3s ease;">
                    🔒 Sair da Conta
                </button>
            </div>
        </div>
    <?php endif; ?>

    <article>
        <h2 style="font-size: 2.0rem; margin-bottom: 20px; margin-top: 30px;text-align:center"> 
            Bem-vindos à "A Fenda" (e não, não é do biquíni)
        </h2>
        <img src="imagensfoto/capa-entrada.jpg" alt="Capa Home do Site" style="width: 100%; height: auto; border-radius: 15px; opacity: 0.7; margin: 35px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"> 
    </article>

    <article style="font-size: 16px; line-height: 1.6; text-align: left; color: #e0e0e0; word-wrap: break-word;">
        
        <p style="margin-bottom: 15px;">Aqui nós falamos de música, ciência, artes, paquera, fofocas (muitas inclusive), cinema, séries, hobbys diversos, cultura pop e por que não, a cultura underground também?!</p>
        
        <p style="margin-bottom: 15px;">Falar mal do: coleguinha / fulano / beltrano / herculano / vida acadêmica / perrengues cotidianos / presidente / do papa / influencers / MEC / obsolecência programada / aquecimento global / segunda guerra mundial / apocalipse zumbi / político / ex BBB e subcelebridades em geral / porteiro enfim QUASE tudo.. a mãe do amiguinho não, porque não pode. Ou por fim se quiser, só desabafar um pouco e afogar as lágrimas depois de um semestre nada fácil.</p> 

        <p style="margin-bottom: 15px;">Marcar alguns rolês? Uma jogatina marota pelo Discord ou mesmo pra fechar a mesa do RPG no intervalo. Um futzinho, beach tênis, vôlei, talvez um churras com piscina (Votuporanga né, só por deus) no final de semana... Marcar um karaokê pra postar nos stories (ou melhor não, depois do álcool a gente faz cada coisa que não é bom nem comentar). Quem sabe combinar uma carona?</p> 

        <p style="margin-bottom: 15px;">E por que não, marcar um date e achar o amor da sua vida (ou um trauma e 6 meses de terapia, alô pessoal da Psico!). Porque não marcar um duelo ao meio dia? Embora eu duvide muito que alguém vai ter tanto tempo sobrando assim mas enfim.. Minha nossa senhora, é tanta coisa que deu até preguiça de digitar.</p>

        <p style="font-style: italic; opacity: 0.7; margin-bottom: 20px;"> * Lembrando que NÃO NOS RESPONSABILIZAMOS por quaisquer opiniões do usuário ou tomamos qualquer partido político, somos somente mensageiros.</p>

        <blockquote style="border-left: 4px solid #cc420c; padding-left: 15px; margin: 25px 0; font-style: italic; background: rgba(255,255,255,0.03); padding: 15px;">
            "Tratem todos: (Sim, isso inclui todos, desde animais, pessoas, bactérias, terraplanistas e até ET's) com educação. Ser doido e um tanto quanto anárquico não é desculpa para ser mal-educado, respeito é via de mão dupla."
        </blockquote>

       <article style="padding: 20px; background: rgba(0, 0, 0, 0.2); border-radius: 15px;"> 
    <ul style="font-weight: bold; text-align: left; margin: 30px 0; padding-left: 25px; color: #ff9900; line-height: 1.8; list-style-type: ' ✅ ';">
        <span style="color: #fff; display: block; margin-bottom: 15px; margin-left: -20px; font-size: 1.2rem;">
            E por último, porém não menos importante:
        </span>
        <li>Usem camisinha;</li>
        <li>Não construam casa no terreno da sogra;</li>
        <li>O barato às vezes sai caro;</li>
        <li>O diploma é o papel: o aprendizado, o trauma;</li>
        <li>Faculdade é igual o Titanic: se for pra afundar, que seja de primeira classe e com a música tocando;</li>
        <li>Invistam em Bitcoin;</li>
        <li>NÃO é NÃO;</li>
        <li>Bebam água e, é claro, DIVIRTAM-SE!</li>
    </ul>
</article>
    </article>

    <article style="text-align: center; margin-top: 40px;">
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <img src="imagensfoto/campus-centro.jpg" alt="UNIFEV- Câmpus Centro" style="width: 40%; min-width: 280px; border-radius: 8px;">
            <img src="imagensfoto/cidade-universitaria.jpg" alt="Cidade Universitária" style="width: 40%; min-width: 280px; border-radius: 8px;">
        </div>
        <figcaption style="margin-top: 15px; opacity: 0.75; font-style: italic; text-align: center;">Nossos QGs: Câmpus Centro e Cidade Universitária</figcaption>
    </article>
</main>

</div>
<?php include 'includes/footer.php'; ?>
