
<footer>
    <p> <strong> Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    <strong> Entre em contato com a gente: 0800 7070 6969 ou mande um email para floorspotted.fev@outlook.com </strong>
    <p>&copy; <?php echo date('Y'); ?> Desenvolvido por Leonardo - Todos os Direitos Reservados </p>  
</footer>

<audio id="som-oceano" loop preload="auto">
    <source src="imagensfoto/chuva.mp3" type="audio/mpeg">
</audio>

<script>
document.addEventListener('click', function() {
    var audio = document.getElementById('som-oceano');
    
    // Se o áudio já estiver tocando, não faz nada
    if (!audio.paused) return;

    audio.volume = 0; 
    audio.play();
    
    var fadeIn = setInterval(function() {
        if (audio.volume < 0.04) { 
            audio.volume += 0.01;
        } else {
            audio.volume = 0.04;
            clearInterval(fadeIn);
        }
    }, 120); 
}, { once: true });
</script>


<script>
function deslogar() {
    console.log("Chamou a função deslogar!");
    var modal = document.getElementById('meuModalSair');
    if(modal) {
        modal.style.display = 'flex';
    } else {
        alert("Erro: O Modal não foi encontrado no HTML!");
    }
}

function fecharModal() {
    document.getElementById('meuModalSair').style.display = 'none';
}

function confirmarSaida() {
    // Verifique se o seu arquivo é sair.php ou logout.php (no seu JS está logout)
    window.location.href = "logout.php"; 
}


</script>

<script>
function buscarNotificacoes() {
   fetch('includes/checar_notificacoes.php')
        .then(response => response.json())
        .then(data => {
            if (data.tem) {
                mostrarPopup(data.msg);
            }
        });
}

function mostrarPopup(mensagem) {
    // Cria o elemento da notificação dinamicamente
    const popup = document.createElement('div');
    popup.className = 'popup-notificacao'; // Usa aquele CSS que a gente fez!
    popup.innerHTML = `
        <span style="font-size: 20px;">🔔</span>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 13px;">Nova Menção!</strong>
            <span style="font-size: 12px;">${mensagem}</span>
        </div>
        <span onclick="this.parentElement.remove()" style="cursor:pointer; font-weight:bold; margin-left:10px;">×</span>
    `;

    document.body.appendChild(popup);

    // Some sozinho depois de 8 segundos
    setTimeout(() => {
        if(popup) popup.remove();
    }, 8000);
}

// Verifica a cada 10 segundos (10000ms)
setInterval(buscarNotificacoes, 10000);

</script>

</body>
</html>