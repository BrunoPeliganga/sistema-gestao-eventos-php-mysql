<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['id'];

// Aceita exclusão via POST com proteção CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['erro_carrinho'] = "Ação não autorizada (token CSRF inválido).";
        header("Location: cart.php");
        exit();
    }
    $cart_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($cart_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
    }
}

// Redireciona de volta para o carrinho
header("Location: cart.php");
exit();
?>
