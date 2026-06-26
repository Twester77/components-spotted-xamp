<?php
include_once __DIR__ . '/conexao.php';
// Ajuste do fuso para SP
date_default_timezone_set('America/Sao_Paulo');

$hora = (int)date('H');
$tema_classe = "";
$classe_saudacao = "";

$nome_exibicao = isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : "A Presença";

// Lógica de tempo e tema (Hacker/Normal)
if (($hora >= 0 && $hora < 5) && isset($_SESSION['usuario_id'])) {
    $saudacao = "Acordado a essa hora criatura... Virou muguerço por acaso?!";
    $extra = "[SISTEMA]: ALERTA! Scan de desocupado ativo...";
    $cor_extra = "#00ff00; font-weight:bold; font-family: 'Courier New', monospace;";
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
    $extra = "Momento de glória do guerreiro: Netflix e cama ou rolezar? ";
    $cor_extra = "#fc492a;";
}

// O Header agora vai usar a variável $tema_classe definida acima
include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/bolhas.php';
?>

<?php if ($tema_classe === "tema-hacker"): ?>
    <audio id="hackerAudio" loop>
        <source src="sons/hacker_theme.mp3" type="audio/mpeg">
    </audio>
    <script>
        document.addEventListener('click', () => {
            const audio = document.getElementById('hackerAudio');
            if (audio) {
                audio.volume = 0.005;
                audio.play();
            }
        }, {
            once: true
        });
    </script>
<?php endif; ?>

<main class="<?php echo $tema_classe; ?>">
    
    <div class="fenda-container">

        <?php if (!isset($_SESSION['usuario_id'])): ?>
            <?php include 'includes/login.php'; ?>
        <?php else: ?>
            <div class="card-boas-vindas-fenda">
                <div style="font-size: 32px; margin-bottom: 15px;">🎓</div>
                <p style="color: #fff; margin-bottom: 25px; font-size: 1.4rem; font-family: 'Segoe UI', sans-serif; letter-spacing: 1px;">
                    <span class="<?php echo $classe_saudacao; ?>">
                        <?php echo $saudacao; ?>
                        <span style="color: #ffbc00; font-weight: bold;">
                            Seja bem vindo novamente, <?php echo htmlspecialchars($nome_exibicao); ?>
                        </span> !
                    </span>
                </p>
                <p>
                    <span style="font-size: 20px; opacity: 0.9; color: <?php echo $cor_extra; ?>">
                        <?php echo $extra; ?>
                    </span>
                </p>
                <div class="fenda-acoes-container">
                    <a href="feed.php" class="fenda-btn-glow fenda-primary"> Ir para o Feed</a>
                   <button onclick="deslogarUsuario()" class="fenda-btn-glow fenda-outline">🔒 Sair da Conta</button>
                </div>
            </div>
        <?php endif; ?>

        <article>
            <h2 class="titulo-home">Bem-vindos à "A Fenda" (e não, não é do biquíni)</h2>
            <img src="imagensfoto/capa-entrada.jpg" alt="Capa de Entrada do Site" class="img-home-capa">
        </article>

        <article class="conteudo-principal">
            <p>Aqui nós falamos de música, ciência, artes, paquera, fofocas (muitas inclusive), cinema, séries, hobbys diversos, cultura pop e por que não, a cultura underground também ?!</p>
            <p>Falar mal do: coleguinha / fulano / beltrano / herculano / vida acadêmica / perrengues cotidianos / presidente / do papa / obsolescência programada / aquecimento global / segunda guerra mundial / apocalipse zumbi / político / ex BBB e subcelebridades em geral / guardinha / enfim QUASE tudo... Mãe não pode. </p>
            <p>Marcar alguns rolês? Uma jogatina marota pelo Discord ou mesmo pra fechar a mesa do RPG no intervalo. Um futzinho, beach tênis, vôlei, barzinho de qualidade e procedência completamente duvidosa, talvez um churras com piscina (Votuporanga né, só por deus) no final de semana...</p>
            <p>E por que não, marcar um date e achar o amor da sua vida (ou um trauma e 6 meses de terapia, alô pessoal da Psico!).</p>
            <p class="aviso-legal"> * Lembrando que NÃO NOS RESPONSABILIZAMOS por quaisquer opiniões do usuário ou tomamos qualquer partido, somos somente mensageiros.</p>

            <blockquote class="citacao-anarquica">
                "Tratem todos: (Sim, isso inclui todos, desde animais, pessoas, bactérias, terraplanistas e até ET's) com educação. Ser doido e um tanto quanto anárquico não é desculpa para ser mal-educado, respeito é via de mão dupla."
            </blockquote>

            <article class="bloco-conselhos">
                <span class="conselho-header"> E por último, porém não menos importante:</span>
                <ul class="lista-conselhos">
                    <li> Usem camisinha;</li>
                    <li> Não construam casa no terreno da sogra;</li>
                    <li> O barato às vezes sai muito caro;</li>
                    <li> O diploma é o papel: o aprendizado, o trauma;</li>
                    <li> Faculdade é igual o Titanic: se for pra afundar, que seja de primeira classe e com música tocando;</li>
                    <li> Invistam em Bitcoin;</li>
                    <li> NÃO é NÃO;</li>
                    <li> Bebam água e, é claro... DIVIRTAM-SE!</li>
                </ul>
            </article>
        </article>
        <div id="bios-boot" class="hacker-boot-screen">
            <div class="boot-text">
                <p>> LOAD FENDA_OS_V1.0...</p>
                <p>> STATUS: <?php echo strtoupper(htmlspecialchars($nome_exibicao)); ?>_ROOT CONNECTED</p>
                <p>> SEARCHING FILES: ATLETICA_SYSTEM.DB</p>
                <p>> ACCESS GRANTED: ENCRYPTED_SESSION_ACTIVE</p>
                <div class="bios-bar">
                    <div class="loading"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>