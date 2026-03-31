
<div id="meuModalSair" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
    <div style="background:#1a1a1a; padding:30px; border-radius:20px; text-align:center; border:2px solid #ff7011; box-shadow: 0 0 20px rgba(255,112,17,0.5); max-width: 300px;">
        <h3 style="color:#fff; margin-bottom: 20px; font-family: sans-serif;">Deseja mesmo sair da Fenda? 🌊</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <button onclick="confirmarSaida()" style="background:#ff7011; color:#fff; border:none; padding:12px; border-radius:10px; font-weight:bold; cursor:pointer;">Sim, Sair</button>
            <button onclick="fecharModal()" style="background:#333; color:#eee; border:none; padding:10px; border-radius:10px; cursor:pointer;">Cancelar</button>
        </div>
    </div>
</div>

<footer>
    <p> <strong> Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    <strong> Entre em contato com a gente: 0800 7070 6969 ou mande um email para floorspotted.fev@outlook.com </strong>
    <p>&copy; <?php echo date('Y'); ?> Desenvolvido por Leonardo - Todos os Direitos Reservados </p>  
</footer>

<audio id="som-oceano" loop preload="auto">
    <source src="imagensfoto/ondas.mp3" type="audio/mpeg">
</audio>

<script>
document.addEventListener('click', function() {
    var audio = document.getElementById('som-oceano');
    
    // Se o áudio já estiver tocando, não faz nada
    if (!audio.paused) return;

    audio.volume = 0; 
    audio.play();
    
    var fadeIn = setInterval(function() {
        if (audio.volume < 0.05) { 
            audio.volume += 0.01;
        } else {
            audio.volume = 0.05;
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
</body>
</html>