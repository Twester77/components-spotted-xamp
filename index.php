<?php
include 'conexao.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
$hora = (int)date('H'); // O (int) garante que seja um número para comparar no IF 
$tema_classe = "";
$classe_saudacao = "";

$nome_exibicao = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : "A Presença";

if (($hora >= 0 && $hora < 5) && isset($_SESSION['usuario_id'])) {
    $saudacao = "Acordado ainda criatura... VIROU MORCEGO?!";
    $extra = "[SISTEMA]: ALERTA! Scan de curioso ativo...";
    $cor_extra = "#00ff00; font-family: 'Courier New', monospace;";
    $tema_classe = "tema-hacker";
    $classe_saudacao = "fenda-saudacao";
} elseif ($hora >= 5 && $hora < 12) {
    $saudacao = "Bom dia, ";
    $extra = "De café tomado ... Vem tranquilo leão !";
    $cor_extra = "#ffcc00;";
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = "Boa tarde, ";
    $extra = "Aquela deprê pós-almoço... 😴";
    $cor_extra = "#08d888ab;";
} else {
    $saudacao = "Boa noite, ";
    $extra = "Hora do decanso do guerreiro: Netflix e cama ou rolezar? ";
    $cor_extra = "#fc492a;";
}

include 'includes/header.php';
?>

<script>
    document.body.className = "<?php echo $tema_classe; ?>";
</script>

<?php
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<?php if ($tema_classe === "tema-hacker"): ?>
    <audio id="hackerAudio" loop>
        <source src="imagensfoto/hacker_theme.mp3" type="audio/mpeg">
    </audio>
    <script>
        document.addEventListener('click', () => {
            const audio = document.getElementById('hackerAudio');
            if(audio) {
                audio.volume = 0.2;
                audio.play();
            }
        }, { once: true });
    </script>
<?php endif; ?>

<main class="<?php echo $tema_classe; ?>">
    <div class="fenda-container">

        <?php if (!isset($_SESSION['usuario_id'])): ?>
            <?php include 'includes/login.php'; ?>
        <?php else: ?>
            <div class="card-boas-vindas-fenda">
                <div style="font-size: 38px; margin-bottom: 10px;">🎓</div>
                <p style="color: #fff; margin-bottom: 25px; font-size: 1.4rem; font-family: 'Segoe UI', sans-serif;">
                    <span class="<?php echo $classe_saudacao; ?>">
                        <?php echo $saudacao; ?>
                        <span style="color: #ffbc00; font-weight: bold;">
                            seja bem vindo, <?php echo htmlspecialchars($nome_exibicao); ?>
                        </span>!
                    </span> <br>
                    <span style="font-size: 18px; opacity: 0.9; color: <?php echo $cor_extra; ?>">
                        <?php echo $extra; ?>
                    </span>
                </p>
                <div class="fenda-acoes-container">
                    <a href="feed.php" class="fenda-btn-glow fenda-primary"> Ir para o Feed</a>
                    <button onclick="deslogar()" class="fenda-btn-glow fenda-outline">🔒 Sair da Conta</button>
                </div>
            </div>
        <?php endif; ?>

        <article>
            <h2 class="titulo-home">Bem-vindos à "A Fenda" (e não, não é do biquíni)</h2>
            <img src="imagensfoto/capa-entrada.jpg" alt="Capa Home do Site" class="img-home-capa">
        </article>

        <article class="conteudo-principal">
            <p>Aqui nós falamos de música, ciência, artes, paquera, fofocas (muitas inclusive), cinema, séries, hobbys diversos, cultura pop e por que não, a cultura underground também?!</p>
            <p>Falar mal do: coleguinha / fulano / beltrano / herculano / vida acadêmica / perrengues cotidianos / presidente / do papa / influencers / MEC / obsolecência programada / aquecimento global / segunda guerra mundial / apocalipse zumbi / político / ex BBB e subcelebridades em geral / porteiro enfim QUASE tudo..</p>
            <p>Marcar alguns rolês? Uma jogatina marota pelo Discord ou mesmo pra fechar a mesa do RPG no intervalo. Um futzinho, beach tênis, vôlei, talvez um churras com piscina (Votuporanga né, só por deus) no final de semana...</p>
            <p>E por que não, marcar um date e achar o amor da sua vida (ou um trauma e 6 meses de terapia, alô pessoal da Psico!).</p>
            <p class="aviso-legal"> * Lembrando que NÃO NOS RESPONSABILIZAMOS por quaisquer opiniões do usuário ou tomamos qualquer partido político, somos somente mensageiros.</p>

            <blockquote class="citacao-anarquica">
                "Tratem todos: (Sim, isso inclui todos, desde animais, pessoas, bactérias, terraplanistas e até ET's) com educação. Ser doido e um tanto quanto anárquico não é desculpa para ser mal-educado, respeito é via de mão dupla."
            </blockquote>

            <article class="bloco-conselhos">
                <ul class="lista-conselhos">
                    <span class="conselho-header">E por último, porém não menos importante:</span>
                    <li> Usem camisinha;</li>
                    <li> Não construam casa no terreno da sogra;</li>
                    <li> O barato às vezes sai caro;</li>
                    <li> O diploma é o papel: o aprendizado, o trauma;</li>
                    <li> Faculdade é igual o Titanic: se for pra afundar, que seja de primeira classe e com a música tocando;</li>
                    <li> Invistam em Bitcoin;</li>
                    <li> NÃO é NÃO;</li>
                    <li> Bebam água e, é claro... DIVIRTAM-SE!</li>
                </ul>
            </article>
        </article>

        <article class="campus-container">
            <div class="campus-flex">
                <img src="imagensfoto/campus-centro.jpg" alt="UNIFEV - Câmpus Centro" class="img-campus">
                <img src="imagensfoto/cidade-universitaria.jpg" alt="Cidade Universitária" class="img-campus">
            </div>
            <figcaption class="legenda-campus">Nossos QGs: Câmpus Centro e Cidade Universitária</figcaption>
        </article>

           <div id="bios-boot" class="hacker-boot-screen">
   <div id="bios-boot" class="hacker-boot-screen">
    <div class="boot-text">
        <p>> LOAD FENDA_OS_V2.0...</p>
        <p>> STATUS: <?php echo strtoupper($nome_exibicao); ?>_ROOT CONNECTED</p>
        <p>> SEARCHING FILES: ATLETICA_<?php echo isset($user_data['atletica_nome']) ? strtoupper($user_data['atletica_nome']) : 'NONE'; ?>.DB</p>
        <p>> ACCESS GRANTED: ENCRYPTED_SESSION_ACTIVE</p>
        <div class="bios-bar"><div class="loading"></div></div>
    </div>
</div>

    </div> </main>

<?php include 'includes/footer.php'; ?>