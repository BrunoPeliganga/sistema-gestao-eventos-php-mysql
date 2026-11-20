<?php 
session_start();
include "../config/database.php";

$username = trim($_POST['username']);
$password = $_POST['password'];

//Selecionar todos os nomes do Banco de Dados.
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

/*
//Mostra os dados contidos no Banco de Dados -> username e password.
echo $user['username']."<br>";
echo $user['password']; 
*/

if($user && password_verify($password, $user['password'])){
    $_SESSION['id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Se houver uma página de origem salva na sessão, redireciona para lá!
    if(isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])){
        $destino = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: " . $destino);
        exit;
    }

    // Se não houver página de origem, redireciona o utilizador
    if($user['role'] === "user"){
        header("Location: ../events/index.php");
        exit();
    } else {
        header("Location: ../admin/index.php");
        exit();
    }
} else {
    $_SESSION['login_error'] = "Credenciais inválidas. Verifique o seu utilizador e palavra-passe.";
    header("Location: login.php");
     exit();
}
?>