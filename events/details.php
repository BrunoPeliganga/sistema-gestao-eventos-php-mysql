<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';

// 1. Obter o ID do evento via URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// 2. Buscar o evento na base de dados
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o evento não existir, volta ao catálogo
if (!$evento) {
    header("Location: index.php");
    exit();
}

$dia  = date('d', strtotime($evento['date']));
$mes  = date('M', strtotime($evento['date']));
$ano  = date('Y', strtotime($evento['date']));
$hora = date('H:i', strtotime($evento['time']));
$stock = (int)($evento['stock'] ?? 0);
$imagemBanner = getCategoryEventImage($evento['category'] ?? 'Festival', $evento['image'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($evento['title']) ?> | Bilhetes Oficiais LiveEvents</title>
    <meta name="description" content="Garanta os seus bilhetes para <?= htmlspecialchars($evento['title']) ?> em <?= htmlspecialchars($evento['location']) ?> no dia <?= $dia ?> de <?= $mes ?>.">
    <meta name="keywords" content="bilhetes, <?= htmlspecialchars($evento['title']) ?>, eventos <?= htmlspecialchars($evento['location']) ?>, festivais">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../assets/images/icons8-flash-64.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/header_details.php'; ?>    

    <!-- Breadcrumb e Topo -->
    <section class="bg-white border-bottom py-3">
        <div class="container">
            <p class="page-breadcrumb mb-0">
                <a href="../index.php">Home</a> / <a href="index.php">Eventos</a> / <span class="text-dark fw-semibold"><?= htmlspecialchars($evento['title']) ?></span>
            </p>
        </div>
    </section>

    <!-- Conteúdo Principal -->
    <main class="py-4">
        <div class="container">
            <!-- Banner Principal do Evento -->
            <div class="event-detail-banner mb-4 shadow-sm rounded-4 overflow-hidden" style="max-height: 420px;">
                <img src="<?= htmlspecialchars($imagemBanner) ?>" alt="<?= htmlspecialchars($evento['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>

            <div class="row g-5">
                <!-- Coluna Esquerda: Descrição Detalhada e Informações -->
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-danger text-uppercase px-3 py-2"><?= htmlspecialchars($evento['category'] ?? 'Festival') ?></span>
                        <span class="badge bg-dark text-uppercase px-3 py-2">Bilheteira Oficial</span>
                    </div>

                    <h1 class="display-6 fw-bold text-dark mb-3"><?= htmlspecialchars($evento['title']) ?></h1>

                    <!-- Barra de Informações Rápidas -->
                    <div class="row g-3 p-3 bg-white rounded-3 border mb-4 shadow-sm">
                        <div class="col-sm-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-regular fa-calendar-days text-danger fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">Data</small>
                                    <strong class="text-dark"><?= $dia ?> <?= $mes ?> <?= $ano ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-regular fa-clock text-danger fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">Horário</small>
                                    <strong class="text-dark"><?= $hora ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-location-dot text-danger fs-4"></i>
                                <div>
                                    <small class="text-muted d-block">Local</small>
                                    <strong class="text-dark"><?= htmlspecialchars($evento['location']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sinopse do Evento -->
                    <section class="mb-4">
                        <h4 class="fw-bold mb-3">Sobre o Evento</h4>
                        <p class="text-muted" style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($evento['description'] ?? 'Sem descrição detalhada disponível.')) ?>
                        </p>
                    </section>

                    <!-- Detalhes da Programação -->
                    <section class="mb-4">
                        <h4 class="fw-bold mb-3"><i class="fa-solid fa-circle-info text-danger me-2"></i>Informações do Recinto & Regulamento</h4>
                        <div class="p-4 bg-white border rounded-3 shadow-sm">
                            <ul class="list-unstyled text-muted small mb-0">
                                <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i>Entrada imediata com bilhete digital no smartphone (QR Code).</li>
                                <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i>Abertura de portas 1h30 antes do início do espetáculo para controlo de acesso.</li>
                                <li class="mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i>Localização acessível para pessoas com mobilidade reduzida.</li>
                                <li class="mb-0"><i class="fa-solid fa-circle-xmark text-danger me-2"></i>Proibida a entrada com recipientes de vidro ou objetos cortantes.</li>
                            </ul>
                        </div>
                    </section>
                </div>

                <!-- Coluna Direita: Box de Compra & Seleção de Bilhetes (Sticky) -->
                <div class="col-lg-5">
                    <div class="booking-card-sticky bg-white p-4 rounded-4 shadow-sm border">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold mb-0">Comprar Bilhete</h4>
                            <?php if ($stock <= 0): ?>
                                <span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i>Esgotado</span>
                            <?php elseif ($stock <= 10): ?>
                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-fire me-1"></i>Últimos <?= $stock ?> bilhetes</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="fa-solid fa-boxes-stacked me-1"></i><?= $stock ?> disponíveis</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-3">Selecione a quantidade de bilhetes para adicionar ao seu carrinho ou finalizar compra:</p>

                        <div class="ticket-select-box selected p-3 rounded-3 border mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1">Entrada Oficial</h6>
                                    <small class="text-muted d-block">Acesso completo ao recinto</small>
                                </div>
                                <div class="text-end">
                                    <strong class="fs-5 text-danger"><?= number_format((float)$evento['price'], 2, ',', '.') ?> €</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Quantidade de Bilhetes -->
                        <div class="my-3 p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Quantidade:</span>
                                <div class="qty-control d-flex align-items-center gap-1">
                                    <button class="btn btn-outline-dark btn-sm fw-bold px-3" type="button" onclick="alterarQtd(-1)" <?= $stock <= 0 ? 'disabled' : '' ?>>-</button>
                                    <input type="text" id="qtdInput" class="form-control form-control-sm text-center fw-bold" style="width: 50px;" value="<?= $stock > 0 ? 1 : 0 ?>" readonly>
                                    <button class="btn btn-outline-dark btn-sm fw-bold px-3" type="button" onclick="alterarQtd(1)" <?= $stock <= 0 ? 'disabled' : '' ?>>+</button>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-muted small">Total estimado:</span>
                                <span id="totalEstimado" class="fw-bold fs-4 text-dark"><?= number_format((float)($stock > 0 ? $evento['price'] : 0), 2, ',', '.') ?> €</span>
                            </div>
                        </div>

                        <!-- Botões de Ação para Carrinho e Sessão -->
                        <div class="d-grid gap-2">
                            <?php 
                                $hoje = date('Y-m-d');
                                $eventoJaPassou = ($evento['date'] < $hoje);
                            ?>
                            <?php if ($eventoJaPassou): ?>
                                <button type="button" class="btn btn-secondary py-3 text-center fw-bold rounded-3" disabled>
                                    <i class="fa-solid fa-calendar-check me-2"></i> Evento já Realizado (Inscrições Encerradas)
                                </button>
                            <?php elseif ($stock <= 0): ?>
                                <button type="button" class="btn btn-secondary py-3 text-center fw-bold rounded-3" disabled>
                                    <i class="fa-solid fa-ban me-2"></i> Bilhetes Esgotados
                                </button>
                            <?php elseif (isset($_SESSION['id'])): ?>
                                <button type="button" class="btn btn-danger py-3 text-center fw-bold rounded-3" onclick="adicionarComQtd(<?= (int)$evento['id'] ?>)">
                                    <i class="fa-solid fa-cart-plus me-2"></i> Adicionar ao Carrinho
                                </button>
                            <?php else: ?>
                                <a href="../auth/login.php" class="btn btn-outline-danger py-3 text-center fw-bold rounded-3">
                                    <i class="fa-solid fa-lock me-2"></i> Inicie Sessão para Comprar
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted"><i class="fa-solid fa-lock me-1 text-success"></i>Pagamento seguro e bilhete digital garantido</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Rodapé Unificado -->
    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap 5.3 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    var stockDisponivel = <?= $stock ?>;
    var precoUnitario = <?= (float)$evento['price'] ?>;
    var quantidadeAtual = stockDisponivel > 0 ? 1 : 0;

    function alterarQtd(delta) {
        if (stockDisponivel <= 0) return;

        quantidadeAtual += delta;
        if (quantidadeAtual < 1) quantidadeAtual = 1;
        if (quantidadeAtual > stockDisponivel) quantidadeAtual = stockDisponivel;
        if (quantidadeAtual > 10) quantidadeAtual = 10;

        document.getElementById('qtdInput').value = quantidadeAtual;
        atualizarTotal();
    }

    function atualizarTotal() {
        var total = (precoUnitario * quantidadeAtual).toFixed(2);
        document.getElementById('totalEstimado').innerText = total.replace('.', ',') + ' €';
    }

    function adicionarComQtd(eventId) {
        var inputQtd = document.getElementById('qtdInput');
        var qtd = inputQtd ? parseInt(inputQtd.value, 10) : 1;
        if (qtd < 1) qtd = 1;
        window.location.href = '../cart/add.php?id=' + eventId + '&qty=' + qtd;
    }
    </script>
</body>
</html>
