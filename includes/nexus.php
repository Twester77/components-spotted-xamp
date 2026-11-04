<div id="fenda-nexus" class="fenda-nexus">
    <div class="nexus-menu" id="nexus-menu">
        
        <button type="button" class="nexus-item" onclick="executarAcao(this, 0, abrirModalPost)">Criar Post</button>

        <button type="button" class="nexus-item" onclick="executarAcao(this, 90, toggleToolbar)">Config.Rápidas</button>

        <button type="button" class="nexus-item" onclick="executarAcao(this, 180, function(){ window.location.href='index.php'; })">Início</button>
        
    </div>

    <button class="nexus-trigger" onclick="toggleNexus()" aria-label="Abrir Menu">
        <svg id="bussola-svg" viewBox="0 0 32 32" stroke="currentColor" stroke-width="1.5" fill="none" aria-hidden="true">
            <circle cx="16" cy="16" r="14.5"></circle>
            <path d="M16 2.5v3 M16 26.5v3 M2.5 16h3 M26.5 16h3" stroke-linecap="round" opacity=".7"></path>
            <g id="ponteiro-bussola" class="girar-0">
                <path d="M16 5 19.5 16 16 27 12.5 16Z" fill="currentColor" opacity=".75" stroke="none"></path>
                <path d="M16 5 19.5 16 16 16Z" fill="#e5484d" stroke="none"></path>
                <path d="M16 5 12.5 16 16 16Z" fill="currentColor" stroke="none"></path>
            </g>
            <circle cx="16" cy="16" r="1.5" fill="currentColor" stroke="none"></circle>
        </svg>
    </button>
</div>