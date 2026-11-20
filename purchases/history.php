<?php
session_start();
require_once '../config/database.php';

// Segurança: só utilizador com sessão iniciada acede ao histórico
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['id'];

// Busca todas as compras deste utilizador no banco
$stmt = $pdo->prepare("SELECT p.*, e.title, e.date, e.time, e.location 
                       FROM purchases p 
                       INNER JOIN events e ON p.events_id = e.id 
                       WHERE p.user_id = ? 
                       ORDER BY p.purchase_date DESC");
$stmt->execute([$user_id]);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Bilhetes | LiveEvents</title>
    <meta name="description" content="Consulte o histórico dos seus bilhetes comprados na LiveEvents.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <?php include '../includes/header_event.php'; ?>

    <main class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h2 class="fw-bold mb-0"><i class="fa-solid fa-receipt text-danger me-2"></i>Os Meus Bilhetes</h2>
                <a href="../events/index.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-plus me-1"></i> Comprar Mais Bilhetes
                </a>
            </div>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><strong>Pagamento Aprovado com Sucesso!</strong> O seu bilhete digital foi emitido e encontra-se disponível abaixo.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($compras)): ?>
                <div class="card p-5 text-center shadow-sm border-0 my-4">
                    <i class="fa-solid fa-ticket-simple fa-3x text-muted mb-3"></i>
                    <h4>Ainda não comprou nenhum bilhete</h4>
                    <p class="text-muted">Aceda ao catálogo para descobrir os melhores festivais e concertos.</p>
                    <div>
                        <a href="../events/index.php" class="btn btn-danger px-4">Explorar Catálogo</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($compras as $compra): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0 rounded-4 p-4 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-success text-uppercase px-3 py-2">Bilhete Ativo</span>
                                    <small class="text-muted fw-bold">#LEV-<?= str_pad((string)$compra['id'], 5, '0', STR_PAD_LEFT) ?></small>
                                </div>
                                <h5 class="fw-bold mb-2 text-dark"><?= htmlspecialchars($compra['title']) ?></h5>
                                <p class="small text-muted mb-1">
                                    <i class="fa-regular fa-calendar text-danger me-1"></i> <?= date('d/m/Y', strtotime($compra['date'])) ?> às <?= date('H:i', strtotime($compra['time'])) ?>
                                </p>
                                <p class="small text-muted mb-3">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i> <?= htmlspecialchars($compra['location']) ?>
                                </p>
                                <div class="p-3 bg-light rounded-3 mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Quantidade:</span>
                                        <strong class="text-dark"><?= (int)$compra['quantity'] ?> entrada(s)</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Total Pago:</span>
                                        <strong class="text-danger fs-6"><?= number_format((float)$compra['total_price'], 2, ',', '.') ?> €</strong>
                                    </div>
                                </div>
                                <a href="../profile/ticket.php?id=<?= (int)$compra['id'] ?>" target="_blank" class="btn btn-outline-dark btn-sm w-100 fw-semibold">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver / Imprimir Bilhete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
