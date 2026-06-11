<div id="fenda-nexus" class="fenda-nexus">
    <div class="nexus-menu" id="nexus-menu">
        
        <button type="button" class="nexus-item" onclick="executarAcao(this, 0, abrirModalPost)">Novo Post</button>

        <button type="button" class="nexus-item" onclick="executarAcao(this, 90, toggleToolbar)">Toolbar</button>

        <button type="button" class="nexus-item" onclick="executarAcao(this, 180, function(){ window.location.href='index.php'; })">Início</button>
        
    </div>

    <button class="nexus-trigger" onclick="toggleNexus()" aria-label="Abrir Menu">
        <svg id="bussola-svg" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
            <circle cx="12" cy="12" r="10"></circle>
            <polygon id="ponteiro-bussola" class="girar-0" points="12 4 15 12 12 20 9 12" fill="currentColor"></polygon>
        </svg>
    </button>
</div>