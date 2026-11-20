<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Impede que o navegador guarde as páginas privadas em cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Se não houver sessão ativa, expulsa para o login
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>