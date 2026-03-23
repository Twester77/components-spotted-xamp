<?php
// Configurações do XAMPP (Padrão)
$host = "localhost";
$usuario = "root";    // O padrão do XAMPP é root
$senha = "";          // O padrão do XAMPP é vazio
$banco = "spotted_db"; // O nome criado no PHPMyAdmin

// Criando a conexão
$conn = mysqli_connect($host, $usuario, $senha, $banco);

// Verificando se deu ruim
if (!$conn) {
    die("Uga! Conexão falhou: " . mysqli_connect_error());
}

// Opcional: Ajusta para aceitar acentos do Brasil
mysqli_set_charset($conn, "utf8mb4");
?>