<?php       

session_start();
include "../config/database.php";

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirmpassword = $_POST['confpassword'];
$role = $_POST['role'];

//Verifica se o nome possui 3 caracteres!
if(strlen($username) < 3){
    $_SESSION['register_error'] = "O nome deve ter pelo menos 3 caracteres.";
    header("Location: register.php");
    exit;
}

//Email verifica se o email é válido
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    $_SESSION['register_error'] = "Email Inválido!";
    header("Location: register.php");
    exit;
}

//Verifica a senha e confirmsenha são realmente iquais.
if($password !== $confirmpassword){
    $_SESSION['register_error'] = "As senhas não coincidem.";
    header("Location: register.php");
    exit;
}

//Fazendo um select para saber se já há alguem com esses dados cadastrados no Database.
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email =? ");
$stmt->execute([$username, $email]);

//Se houver vai enviar uma mensagem com a session
if($stmt->rowCount() > 0){
    $_SESSION['register_error'] = "Nome ou email já existem.";
    header("Location: register.php");
    exit;
}

//Pega na password digitado e criptografa ela
$hash = password_hash($password, PASSWORD_DEFAULT);

//Inseri no banco de dados (Senha já com o hash).
$stmt = $pdo->prepare("INSERT INTO users (username, email, password, role)
    VALUES (?, ?, ?, ?)");
$stmt->execute([$username,$email,$hash, $role]);

//Redireciona tudo para o login.php
header("Location: login.php");

?>