<div class="bubbles-container">
    <?php
    // Gera 15 bolhas automaticamente
    for ($i = 0; $i < 15; $i++) {
        $left = rand(0, 100); // Posição horizontal aleatória (0 a 100%)
        $delay = rand(0, 8);  // Atraso aleatório para não subirem todas juntas
        $size = rand(10, 40); // Tamanhos diferentes para dar profundidade
        
        echo "<div class='bolha' style='left: {$left}%; animation-delay: {$delay}s; width: {$size}px; height: {$size}px;'></div>";
    }
    ?>
</div>