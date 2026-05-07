<?php
// Impede que alguém liste os arquivos da pasta se o servidor estiver aberto
header("Location: ../index.php");
exit();
?>