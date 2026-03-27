<?php
include 'conexao.php'; 
include 'includes/header.php'; 

if(isset($_SESSION['id_usuario'])) {
    header("Location: feed.php"); 
    exit(); 
}
?>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/bolhas.php'; ?>

<main style="max-width: 800px; margin: auto; padding: 20px;">
    <?php include 'includes/login.php'; ?>

    <article style="text-align: center;">
        <h2 style="font-size: 22px; margin-bottom: 20px; margin-top: 40px;"> 
            Bem-vindos à "A Fenda" (e não, não é do biquíni)
        </h2>
        <img src="imagensfoto/Capa Entrada.jpg" alt="Capa Home do Site" style="width: 80%; border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    </article>

    <article style="font-size: 15px; line-height: 1.6; text-align: left; color: #e0e0e0; word-wrap: break-word;">
        
        <p style="margin-bottom: 15px;">Aqui nós falamos de música, ciência, artes, paquera, fofocas (muitas), cinema, séries, hobbys diversos, cultura pop e por que não, a cultura underground também?!</p>
        
        <p style="margin-bottom: 15px;">Falar mal do: coleguinha / fulano / beltrano / herculano / vida acadêmica / perrengues cotidianos / presidente / do papa / MEC / obsolecência programada / aquecimento global / segunda guerra mundial / apocalipse zumbi / político / ex BBB / porteiro (mãe não, porque não pode), ou só desabafar um pouco e afogar as lágrimas depois de um semestre nada fácil.</p> 

        <p style="margin-bottom: 15px;">Marcar alguns rolês? Uma jogatina marota pelo Discord ou mesmo pra fechar a mesa do RPG no intervalo. Um futzinho, beach tênis, vôlei, talvez um churras com piscina (Votuporanga né, só por deus) no final de semana, marcar um karaokê pra postar nos stories (ou melhor não, depois do álcool a gente faz cada coisa que não é bom nem comentar). Quem sabe combinar uma carona?</p> 

        <p style="margin-bottom: 15px;">E por que não, marcar um date e achar o amor da sua vida (ou um trauma e 6 meses de terapia, alô pessoal da Psico!). Porque não marcar um duelo ao meio dia? (embora eu duvide muito que alguém vai ter tanto tempo sobrando assim mas enfim.. Minha nossa senhora, é tanta coisa que deu até preguiça de digitar.</p>

        <p style="font-style: italic; opacity: 0.8; margin-bottom: 20px;"> * Lembrando que NÃO NOS RESPONSABILIZAMOS por quaisquer opiniões do usuário ou tomamos qualquer partido político, somos somente mensageiros.</p>

        <blockquote style="border-left: 4px solid #cc420c; padding-left: 15px; margin: 25px 0; font-style: italic; background: rgba(255,255,255,0.03); padding: 15px;">
            "Tratem todos: (Sim, isso inclui todos, desde animais, pessoas, bactérias, terraplanistas e até ET's) com educação. Ser doido e um tanto quanto anárquico não é desculpa para ser mal-educado, respeito é via de mão dupla."
        </blockquote>

        <p style="font-weight: bold; text-align: left; color: #ffbc00; margin-top: 30px; line-height: 1.5;"> 
            E por último porém não menos importante: <br>
            Usem camisinha, não construam casa no terreno da sogra, invistam em Bitcoin, NÃO é NÃO, bebam água e é claro, divirtam-se! 
        </p>  
    </article>

    <article style="text-align: center; margin-top: 40px;">
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <img src="imagensfoto/Campus Centro.jpg" alt="UNIFEV- Câmpus Centro" style="width: 45%; min-width: 280px; border-radius: 8px;">
            <img src="imagensfoto/Cidade Universitaria.jpg" alt="Cidade Universitária" style="width: 45%; min-width: 280px; border-radius: 8px;">
        </div>
        <figcaption style="margin-top: 15px; opacity: 0.8;">Nossos QGs: Câmpus Centro e Cidade Universitária</figcaption>
    </article>
</main>

<?php include 'includes/footer.php'; ?>