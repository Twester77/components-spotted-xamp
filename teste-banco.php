<?php
include 'conexao.php';

$sql = "SELECT COUNT(*) as total FROM usuarios";
$res = mysqli_query($conn, $sql);
$dados = mysqli_fetch_assoc($res);

echo "<h1>O PHP encontrou " . $dados['total'] . " usuários no banco.</h1>";

$sql2 = "SELECT email FROM usuarios LIMIT 1";
$res2 = mysqli_query($conn, $sql2);
$user = mysqli_fetch_assoc($res2);

echo "Primeiro e-mail no banco: [" . $user['email'] . "]";
?>