<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guarda a página de origem se o utilizador clicou em login a partir de outra página
if (!isset($_SESSION['redirect_after_login']) && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    if (strpos($referer, 'login.php') === false && strpos($referer, 'register.php') === false && strpos($referer, 'logout.php') === false) {
        $_SESSION['redirect_after_login'] = $referer;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiveEvents | Bilhetes para os Melhores Festivais & Espetáculos</title>

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
           if(isset($_SESSION['login_error'])){
            echo htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['login_error']);
           }
        ?>
      </p>
                  <a class="login-brand" href="../index.php">
                <i class="fa-solid fa-bolt me-2"></i>Live<span>Events</span>
            </a>
      <p class="login-subtitle">Login</p>


<form action="process_login.php" method="post">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" id="username" placeholder="" required autofocus>
        </div>
        
        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" id="password" placeholder="" required>
        </div>
        <button type="submit" class="btn-login">Entrar</button>
      </form>
      
      <a href="register.php" class="btn btn-login register">Registrar-se</a>

      <div class="login-footer">
        <a href="../index.php"><i class="bi bi-arrow-left"></i> Back to site</a>
      </div>
    </div>
  </div>

</body>
</html>