<footer>
    <p> <strong> Aviso:</strong> "A Fenda" é uma plataforma independente e colaborativa. Não possuímos vínculo administrativo ou oficial com a UNIFEV. O conteúdo é de responsabilidade exclusiva de seus autores.</p>
    <strong> Entre em contato com a gente : 0800 7070 6969 ou mande um email para floorspotted.fev@outlook.com </strong>
    <p>&copy; <?php echo date ('Y'); ?>  Desenvolvido por Leonardo - Todos os Direitos Reservados </p>  
</footer>

<script>
    function deslogar() {
        if (confirm("Deseja realmente sair da conta?")) {
           
            alert("Você saiu com sucesso!");
            window.location.href = "login.html"; 
        }
    }