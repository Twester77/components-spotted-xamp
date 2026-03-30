
<?php
/*-----------------------------------------------------------------------------------
PROJETO: A FENDA - SPOTTED UNIFEV DESENVOLVEDOR: Leonardo (O Idealizador)
AGRADECIMENTOS: Meu Coordenador de ADS Fernando Menechelli, meu Professor de HTML Eric
e meu "Padrinho Digital" Gemini
pela paciência com os headers e os includes fora de ordem de PHP!
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