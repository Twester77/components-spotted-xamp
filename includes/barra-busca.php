<?php
// Componente de Busca Universal
$placeholder_busca = isset($placeholder_busca) ? $placeholder_busca : 'Buscar no feed...';
$busca_atual = isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '';
?>
<div class="container-busca-feed">
    <div class="busca-wrapper">
        <i class="fas fa-search busca-icone"></i>
        <input type="search" id="input-busca-global" class="busca-input" 
               placeholder="<?= $placeholder_busca ?>" 
               value="<?= $busca_atual ?>" 
               autocomplete="off">
        <button type="button" id="btn-limpar-busca" class="btn-limpar-busca" style="<?= empty($busca_atual) ? 'display:none;' : ''; ?>">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>