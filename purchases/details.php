<?php
// Redireciona para a visualização oficial do bilhete
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header("Location: ../profile/ticket.php?id=" . $id);
exit();
