<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// Contar o total de bilhetes no carrinho do utilizador logado
$totalItensCarrinho = 0;
if (isset($_SESSION['id'])) {
    $stmtCount = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmtCount->execute([(int)$_SESSION['id']]);
    $totalItensCarrinho = (int)$stmtCount->fetchColumn();
}
?>
<!-- Menu Superior de Navegação -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fa-solid fa-bolt me-2"></i>Live<span>Events</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events/index.php">Todos os Eventos</a>
                </li>
                <?php if (isset($_SESSION['id'])): ?>
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="admin/index.php">
                                <i class="fas fa-shield-alt me-1"></i> Admin
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="profile/index.php">
                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Perfil') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3">
                        <a href="cart/cart.php" class="btn btn-nav-cart">
                            <i class="fa-solid fa-cart-shopping me-1"></i> Carrinho
                            <span class="badge bg-danger rounded-pill ms-1"><?= $totalItensCarrinho ?></span>
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="nav-link text-danger" href="auth/logout.php" title="Encerrar sessão">
                            <i class="fas fa-sign-out-alt me-1"></i> Sair
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-4">
                        <a class="nav-link text-white" href="auth/login.php">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Entrar
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-sm btn-danger rounded-pill px-3" href="auth/register.php">
                            Criar Conta
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Banner Hero Principal -->
<header class="hero-home">
    <div class="container">
        <div class="hero-badge">
            <i class="fa-solid fa-ticket me-1"></i> Bilheteira Oficial 2026
        </div>
        <h1 class="hero-title">Viva Momentos Inesquecíveis.<br>Garanta os Seus Bilhetes.</h1>
        <p class="hero-subtitle">
            Descubra os maiores concertos, festivais de música e conferências de tecnologia em Portugal. Compra 100% segura com entrada digital no smartphone.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="events/index.php" class="btn-primary-action">
                <i class="fa-solid fa-magnifying-glass me-2"></i>Explorar Todos os Eventos
            </a>
            <a href="events/index.php" class="btn-secondary-action">
                <i class="fa-solid fa-star me-2"></i>Ver Eventos em Destaque
            </a>
        </div>
    </div>
</header>
