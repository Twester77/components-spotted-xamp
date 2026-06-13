/* ============================================================
   FENDA INIT – DETECTOR ISOMÓRFICO (PC vs. MOBILE)
   ============================================================
   Carrega APENAS o adapter de swipe correto baseado no dispositivo.
   Sem core – o adapter é autossuficiente.
   ============================================================ */
(function() {
    'use strict';

    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    const adapterName = isTouchDevice ? 'fenda-swipe-mobile.js' : 'fenda-swipe-pc.js';

    // Carrega diretamente o adapter (sem core)
    const script = document.createElement('script');
    script.src = `js/${adapterName}`;
    script.defer = true;
    script.onload = () => {
        console.log(`[FENDA INIT] Adapter ${adapterName} carregado com sucesso.`);
        // Se o modo swipe já estiver ativo, inicializa a engine
        if (document.body.classList.contains('modo-swipe-ativo')) {
            if (typeof window.iniciarFisicaSwipe === 'function') {
                window.iniciarFisicaSwipe();
            } else {
                console.warn("[FENDA INIT] window.iniciarFisicaSwipe não encontrada.");
            }
        }
    };
    script.onerror = () => {
        console.error(`[FENDA INIT] Falha ao carregar ${adapterName}. O swipe não estará disponível.`);
    };
    document.head.appendChild(script);
})();