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
        if (audio.volume < 0.07) { 
            audio.volume += 0.01;
        } else {
            audio.volume = 0.07;
            clearInterval(fadeIn);
        }
    }, 120); 
}, { once: true });
</script>

</body>
</html>
</body>
</html>