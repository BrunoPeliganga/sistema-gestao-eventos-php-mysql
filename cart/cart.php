<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Busca todos os itens do carrinho deste utilizador com os dados do evento correspondente
$sql = "SELECT c.id AS cart_id, c.quantity, e.id AS event_id, e.title, e.category, e.image, e.date, e.location, e.price, e.stock
        FROM cart c 
        INNER JOIN events e ON c.events_id = e.id 
        WHERE c.user_id = :user_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$itensCarrinho = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o subtotal geral do carrinho
$subtotalGeral = 0;
foreach ($itensCarrinho as $item) {
    $subtotalGeral += ($item['price'] * $item['quantity']);
}
// Atualizar quantidades via POST (com verificação de stock)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_qtd'])) {
    $cart_id = (int)$_POST['cart_id'];
    $nova_qtd = (int)$_POST['nova_qtd'];

    // Buscar o stock disponível do evento
    $stmtStock = $pdo->prepare("SELECT e.stock, e.title FROM cart c JOIN events e ON c.events_id = e.id WHERE c.id = ? AND c.user_id = ?");
    $stmtStock->execute([$cart_id, $user_id]);
    $eventoInfo = $stmtStock->fetch();

    if ($eventoInfo) {
        $stockDisponivel = (int)$eventoInfo['stock'];
        if ($nova_qtd <= 0) {
            // Se colocar 0, remove do carrinho
            $stmtDel = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmtDel->execute([$cart_id, $user_id]);
        } elseif ($nova_qtd > $stockDisponivel) {
            $_SESSION['erro_carrinho'] = "Apenas existem {$stockDisponivel} bilhetes disponíveis para \"" . htmlspecialchars($eventoInfo['title']) . "\".";
            $stmtUp = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmtUp->execute([$stockDisponivel, $cart_id, $user_id]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmtUp->execute([$nova_qtd, $cart_id, $user_id]);
        }
    }
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Seu Carrinho de Bilhetes | LiveEvents</title>
    <meta name="description" content="Revise os seus bilhetes selecionados antes de finalizar o pagamento seguro.">
    <meta name="robots" content="noindex, nofollow">

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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <?php include '../includes/header_cart.php'; ?>

    <!-- Conteúdo Principal -->
    <main class="py-5">
        <div class="container"> 
                <?php if (isset($_SESSION['erro_carrinho'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['erro_carrinho']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['erro_carrinho']); ?>
                <?php endif; ?>
            <div class="row g-4">
                <!-- Coluna Esquerda: Itens do Carrinho -->
                <div class="col-lg-8">
                    <div class="cart-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0">Bilhetes Selecionados (<?= count($itensCarrinho) ?>)</h4>
                        </div>

                        <!-- Tabela Responsiva de Itens -->
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 45%;">Evento & Tipo</th>
                                        <th class="text-center">Preço Unit.</th>
                                        <th class="text-center">Qtd</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center" style="width: 40px;"></th>
                                    </tr>
                                </thead>

                                <tbody>
    <?php if (empty($itensCarrinho)): ?>
        <tr>
            <td colspan="5" class="text-center py-5">
                <i class="fa-solid fa-cart-shopping fa-3x text-muted mb-3"></i>
                <h5>O seu carrinho está vazio</h5>
                <p class="text-muted">Ainda não adicionou nenhum bilhete de evento.</p>
                <a href="../events/index.php" class="btn btn-danger btn-sm px-4">Explorar Eventos</a>
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($itensCarrinho as $item): ?>
            <?php 
                $subtotalItem = $item['price'] * $item['quantity']; 
            ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= htmlspecialchars(getCategoryEventImage($item['category'] ?? 'Festival', $item['image'] ?? '')) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="cart-item-img">
                        <div>
                            <h6 class="fw-bold mb-1">
                                <a href="../events/details.php?id=<?= $item['event_id'] ?>" class="text-dark">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h6>
                            <span class="badge bg-danger text-uppercase mb-1"><?= htmlspecialchars($item['category'] ?? 'Bilhete') ?></span>
                            <p class="small text-muted mb-0">
                                <i class="fa-regular fa-calendar me-1"></i> <?= date('d M Y', strtotime($item['date'])) ?> • <?= htmlspecialchars($item['location']) ?>
                                <small class="text-primary fw-semibold d-block mt-1">
                                    <i class="fa-solid fa-boxes-stacked me-1"></i>Stock disponível: <?= (int)$item['stock'] ?> un.
                                </small>
                            </p>
                        </div>
                    </div>
                </td>
                <td class="text-center fw-semibold"><?= number_format($item['price'], 2, ',', '.') ?> €</td>
                <td class="text-center">
                    <form method="POST" action="cart.php" class="d-inline-flex align-items-center gap-1">
                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                        <input type="hidden" name="atualizar_qtd" value="1">
                        <input type="number" name="nova_qtd" value="<?= (int)$item['quantity'] ?>" min="0" max="<?= (int)$item['stock'] ?>" class="form-control form-control-sm text-center" style="width: 70px;" onchange="this.form.submit()">
                    </form>
                </td>
                <td class="text-end fw-bold text-dark"><?= number_format($subtotalItem, 2, ',', '.') ?> €</td>
                <td class="text-center">
                    <!-- Formulário POST para remover o item com segurança e CSRF -->
                    <form method="POST" action="remove.php" onsubmit="return confirm('Tem a certeza que deseja remover este bilhete?');" class="d-inline">
                        <input type="hidden" name="id" value="<?= $item['cart_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                        <button type="submit" class="btn btn-link text-danger p-0" title="Remover bilhete">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>

                            </table>
                        </div>

                        <!-- Botão Continuar a Comprar -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap gap-2">
                            <a href="../events/index.php" class="btn btn-outline-dark fw-bold rounded-pill px-4">
                                <i class="fa-solid fa-arrow-left me-2"></i>Continuar a Escolher Eventos
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Coluna Direita: Resumo do Pedido -->
                <div class="col-lg-4">
                    <div class="order-summary-box">
                        <h4 class="fw-bold mb-4">Resumo da Encomenda</h4>

                    <div class="summary-row">
                        <span>Subtotal dos Bilhetes</span>
                        <strong><?= number_format((float)$subtotalGeral, 2, ',', '.') ?> €</strong>
                    </div>

                    <div class="summary-row">
                        <span>Taxa de Emissão Digital</span>
                        <span class="text-success fw-bold">Grátis (0.00 €)</span>
                    </div>

                    <div class="summary-row">
                        <span>Segurança de Transação</span>
                        <span class="text-muted">Incluída</span>
                    </div>

                    <div class="summary-total border-top my-3 pt-3">
                        <span>Total a Pagar</span>
                        <strong class="text-danger fs-4"><?= number_format((float)$subtotalGeral, 2, ',', '.') ?> €</strong>
                    </div>

                    <!-- Botão Finalizar Compra -->
                    <?php if (!empty($itensCarrinho)): ?>
                        <a href="../purchases/checkout.php" class="btn btn-primary-action w-100 py-3 text-center fs-6 fw-bold">
                            Prosseguir para o Checkout <i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 py-3 text-center fs-6" disabled>
                            Carrinho Vazio
                        </button>
                    <?php endif; ?>

                        <div class="mt-4 p-3 bg-light rounded-3 text-center border">
                            <p class="small text-muted mb-2">Métodos de pagamento disponíveis no próximo passo:</p>
                            <div class="d-flex justify-content-center gap-3 fs-4 text-muted">
                                <i class="fa-solid fa-mobile-screen" title="MB WAY"></i>
                                <i class="fa-solid fa-receipt" title="Multibanco"></i>
                                <i class="fa-regular fa-credit-card" title="Cartão Visa/Mastercard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Rodapé Unificado -->
    <?php include '../includes/footer_cart.php'; ?>

    <!-- Bootstrap 5.3 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Script de Quantidades do Carrinho -->
    <script src="../assets/js/Qtdcart.js"> </script>

</body>
</html>
