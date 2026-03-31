// 1. Abre o modal
function deslogar() {
    console.log("Chamou a função deslogar!"); // Isso vai ajudar a testar
    document.getElementById('meuModalSair').style.display = 'flex';
}

// 2. Fecha o modal
function fecharModal() {
    document.getElementById('meuModalSair').style.display = 'none';
}

// 3. Redireciona para o logout
function confirmarSaida() {
    window.location.href = "logout.php";
}