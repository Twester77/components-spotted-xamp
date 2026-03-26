<?php
session_start();
session_destroy(); // Mata a sessão
header("Location: index.php"); // Volta pra home limpo
exit();
?>