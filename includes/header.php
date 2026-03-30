<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_logado = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Fenda - Spotted Universitário (Votuporanga)</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
</head>
<body> <header>
        <h1>A Fenda - Spotted Universitário</h1>
        <a href="perfil.php" class="btn-config" title="Configurações do Perfil">
    <i class="fas fa-cog icon-fenda"></i>
</a>
<style>
    .icon-fenda {
        font-size: 1.8rem; 
        color: #ffbc00;    
        transition: all 0.3s ease;
        display: inline-block;
        vertical-align: middle; 
        margin-top: 10px;
        margin-bottom: 20px;
    }

    .btn-config:hover .icon-fenda {
        transform: rotate(90deg) scale(1.1); 
        color: #fff; 
        filter: drop-shadow(0 0 5px rgba(255, 188, 0, 0.7)); 
    }
</style>
    </header>