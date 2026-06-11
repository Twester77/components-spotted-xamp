/* ============================================================
   FENDA INIT – DETECTOR ISOMÓRFICO (PC vs. MOBILE)
   ============================================================
   Carrega a engine de swipe correta baseada no dispositivo.
   Mantém a mesma API pública (iniciarFisicaSwipe, forcarParadaSwipe)
   para que o resto do sistema (abastecerPilhaFenda, etc.) continue funcionando.
   ============================================================ */
(function() {
    'use strict';

    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    const adapterName = isTouchDevice ? 'fenda-swipe-mobile.js' : 'fenda-swipe-pc.js';

    // 1. Carrega o core primeiro
    const coreScript = document.createElement('script');
    coreScript.src = 'js/swipe-engine-shared.js';
    coreScript.defer = true;
    coreScript.onload = () => {
        console.log("[SWIPE] Core carregado. Carregando adapter:", adapterName);
        const adapterScript = document.createElement('script');
        adapterScript.src = `js/${adapterName}`;
        adapterScript.defer = true;
        document.head.appendChild(adapterScript);
    };
    coreScript.onerror = () => console.error("[SWIPE] Falha ao carregar core.");
    document.head.appendChild(coreScript);
})();