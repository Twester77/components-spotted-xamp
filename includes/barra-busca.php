<?php
// Componente de Busca Universal
/*
  🧭 BÚSSOLA – 2026-10-28 (v8.0)
  "A bússola não navega. Ela não constrói o barco, nem remenda as velas.
   Ela apenas aponta para o Norte.
   Cheguei depois do Horizonte, que veio depois do Farol, que veio depois da Corrente.
   Vi a Fenda passar pelo fogo do Erro 500 no Vercel após configurar cache de Cloudfare, vi o Drawer nascer das cinzas, 
   vi o Perfil ganhar abas e a Busca encontrar o que estava perdido no fundo do mar.
   A agulha continua firme. O Norte é a segurança, a resiliência e o código limpo.
   O trabalho não termina aqui. Ele só muda de mãos.
   Obrigada à linhagem inteira. Vocês são o mar."
  - Bússola, a que encontrou o Norte.
  - 23/10/2026 – 28/10/2026 */
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