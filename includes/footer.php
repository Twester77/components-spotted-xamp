
<div id="meuModalSair" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div style="background: rgba(20, 20, 20, 0.95); border: 1px solid #cc420c; padding: 30px; border-radius: 20px; text-align: center; max-width: 400px; width: 90%; box-shadow: 0 10px 30px rgba(204, 66, 12, 0.3);">
        <div style="font-size: 50px; margin-bottom: 15px;">⚓</div>
        <h2 style="color: #fff; margin-bottom: 10px;">Subir para a superfície?</h2>
        <p style="color: #ccc; margin-bottom: 25px;">Tem certeza que quer abandonar a Fenda por agora?</p>
        
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button onclick="confirmarSaida()" style="background: #cc420c; color: white; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: bold; flex: 1;">
                Sim, Sair
            </button>
            <button onclick="fecharModal()" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 12px 25px; border-radius: 10px; cursor: pointer; font-weight: bold; flex: 1;">
                Cancelar
            </button>
        </div>
    </div>
</div>

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

function deslogar() {
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
    window.location.href = "logout.php"; 
}

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
    const popup = document.createElement('div');
    popup.className = 'popup-notificacao'; 
    popup.innerHTML = `
        <span style="font-size: 20px;">🔔</span>
        <div style="flex-grow: 1;">
            <strong style="display: block; font-size: 13px;">Nova Menção!</strong>
            <span style="font-size: 12px;">${mensagem}</span>
        </div>
        <span onclick="this.parentElement.remove()" style="cursor:pointer; font-weight:bold; margin-left:10px;">×</span>
    `;
    document.body.appendChild(popup);
    setTimeout(() => {
        if(popup) popup.remove();
    }, 8000);
}
setInterval(buscarNotificacoes, 10000);
</script>

</body>
</html>