<?php
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