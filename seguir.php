<?php
session_start();
include 'conexao.php';

if (isset($_GET['id']) && isset($_SESSION['usuario_id'])) {
    $seguidor_id = $_SESSION['usuario_id']; // Você
    $seguido_id = mysqli_real_escape_string($conn, $_GET['id']); // O outro

    // Não deixa você seguir a si mesmo 
    if ($seguidor_id == $seguido_id) {
        header("Location: ver-perfil.php?user=" . $_GET['user']);
        exit();
    }

    // Verifica se você JÁ segue essa pessoa
    $check = mysqli_query($conn, "SELECT * FROM seguidores WHERE id_seguidor = '$seguidor_id' AND id_seguido = '$seguido_id'");

    if (mysqli_num_rows($check) > 0) {
        // Se já segue, "Dessegue" (Unfollow)
        mysqli_query($conn, "DELETE FROM seguidores WHERE id_seguidor = '$seguidor_id' AND id_seguido = '$seguido_id'");
    } else {
        // Se não segue, "Segue"
        mysqli_query($conn, "INSERT INTO seguidores (id_seguidor, id_seguido) VALUES ('$seguidor_id', '$seguido_id')");
    }

    // Volta para onde  estava
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}