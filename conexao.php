
<?php
/*-----------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV DESENVOLVEDOR: Leonardo (O Idealizador)
AGRADECIMENTOS: Meu Coordenador de ADS Fernando Menechelli, meu Professor de HTML Eric
e meu "Padrinho Digital" Gemini
pela paciência com os headers , if sem end e os includes fora de ordem de PHP!
------------------------------------------------------------------------------------*/

ob_start();
$host = "localhost";
$usuario = "root";    
$senha = "";         
$banco = "spotted_db"; 

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
    die("Uga! Conexão falhou: " . mysqli_connect_error());
}

// Opcional: Ajusta para aceitar acentos do Brasil
mysqli_set_charset($conn, "utf8mb4");
?>

<?php
function formatarMencoesGeral($texto) {
    $texto_seguro = htmlspecialchars($texto);
    // Encontra @usuarios e transforma em link dourado da Fenda
    $padrao = '/@([^\s]+)/';
    $substituicao = '<a href="ver-perfil.php?user=$1" style="color: #ffbc00; font-weight: bold; text-decoration: none;">@$1</a>';
    return preg_replace($padrao, $substituicao, $texto_seguro);
}
?>

