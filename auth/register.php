<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar-se LiveEvents | Bilhetes para os Melhores Festivais & Espetáculos</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../assets/images/icons8-flash-64.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Estilos Tradicionais (Sem var()) -->
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-wrapper">
    <div class="login-card">
        
    <p class="message text-danger mb-3">
        <?php   
            if(isset($_SESSION['register_error'])){
                echo htmlspecialchars($_SESSION['register_error'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['register_error']);
            }
        ?>
    </p>
    
        <a href="../index.php" class="login-brand">Bruno Mars<span>.</span></a>
      <p class="login-subtitle">Register</p>

    <form action="process_register.php" method="post">
        <div class="mb-3">
            <label class="form-label" for="username">Username :</label>
            <input type="text" class="form-control" name="username" id="username" min="3" required> <br>

            <label class="form-label" for="email">Email :</label>
            <input type="email" class="form-control" name="email" id="email" min="3" required> <br>

            <label class="form-label" for="password">Password :</label>
            <input type="password" class="form-control" name="password" id="password" min="3" required> <br>

            <label class="form-label" for="confpassword">Confirmar Password :</label>
            <input type="password" class="form-control" name="confpassword" id="confpassword" min="3" required> <br>

            <select name="role">
                <option value="user">Utilizador</option>
                <option value="admin">Administrador</option>
            </select>
            <input type="submit" class="btn-login" value="Cadastrar!">

        </div>
    </form>
   <a class="btn btn-login" href="login.php">Login</a>
</div>
</div>
</body>
</html>