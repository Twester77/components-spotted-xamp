
<?php
/*--------------------------------------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV DESENVOLVEDOR: Leonardo (O Idealizador)
AGRADECIMENTOS: Meu Coordenador de ADS Fernando Menechelli, meu Professor de HTML Eric
e meu "Padrinho Digital" Gemini
pela paciência com os headers , divs sem fechar ( ou antes da hora) , if sem end e os includes fora de ordem
---------------------------------------------------------------------------------------------------------------*/

if (ob_get_level() == 0) {
    ob_start();
}

$host    = "localhost";
$usuario = "root";    
$senha   = "";         
$banco   = "spotted_db"; 

// Tenta a porta 3307
$conn = @mysqli_connect($host, $usuario, $senha, $banco, 3307);

// Se falhar, tenta a 3306 com as variáveis certas ($usuario, $senha...)
if (!$conn) {
    $conn = mysqli_connect($host, $usuario, $senha, $banco, 3306);
}

if (!$conn) {
    die("Erro de conexão: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// FUNÇÃO GLOBAL PARA MENÇÕES (@)
if (!function_exists('formatarMencoes')) {
    function formatarMencoes($texto) {
        $texto_seguro = htmlspecialchars($texto);
        
        // MANTIDO EXATAMENTE COMO VOCÊ PEDIU:
        $padrao = '/@([^\s]+)/';
        
        $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
        return preg_replace($padrao, $substituicao, $texto_seguro);
    }
}
?>