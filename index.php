<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Buscar eventos reais em destaque do banco de dados
$stmtDestaques = $pdo->query("SELECT * FROM events ORDER BY date ASC LIMIT 6");
$eventosDestaque = $stmtDestaques->fetchAll(PDO::FETCH_ASSOC);

// Contar eventos por categoria
$totalFestivais = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE category = 'Festival'")->fetchColumn();
$totalTech      = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE category = 'Tecnologia'")->fetchColumn();
$totalRock      = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE category = 'Rock'")->fetchColumn();
$totalJazz      = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE category = 'Jazz'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LiveEvents | Bilhetes Oficiais para Festivais, Concertos e Conferências 2026</title>
    <meta name="description" content="Compre bilhetes oficiais para os maiores concertos, festivais de música e conferências em Portugal. Bilheteira 100% digital e segura.">
    <meta name="keywords" content="bilhetes, festivais portugal, concertos lisboa, concertos porto, live events 2026, comprar bilhetes">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="assets/images/icons8-flash-64.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Menu de navegação + Hero Banner -->
    <?php include 'includes/header.php'; ?>

    <!-- Mensagem de Sucesso de Compra (Flash Message do Checkout) -->
    <?php if (isset($_SESSION['compra_sucesso'])): ?>
        <div class="container mt-4">
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 p-4 border-0 d-flex align-items-center gap-3" role="alert">
                <i class="fa-solid fa-circle-check fs-2 text-success"></i>
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Pagamento Concluído com Sucesso!</h5>
                    <p class="mb-0 text-muted">
                        <?= htmlspecialchars($_SESSION['compra_sucesso']) ?>
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="admin/index.php?tab=purchases" class="fw-bold text-danger text-decoration-underline ms-1">
                                <i class="fa-solid fa-shield-halved me-1"></i>Aceder ao Painel Admin (Histórico de Compras) &raquo;
                            </a>
                        <?php else: ?>
                            <a href="profile/index.php" class="fw-bold text-dark text-decoration-underline ms-1">
                                Aceder às Minhas Compras &raquo;
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['compra_sucesso']); ?>
    <?php endif; ?>

    <!-- Conteúdo Principal -->
    <main>
        <!-- Categorias Populares -->
        <section class="py-4">
            <div class="container">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <a href="events/index.php?categoria[]=Festival" class="d-block p-4 bg-white rounded-3 border text-dark shadow-sm h-100 text-decoration-none">
                            <i class="fa-solid fa-compact-disc fs-2 text-danger mb-2"></i>
                            <h6 class="fw-bold mb-1">Festivais</h6>
                            <small class="text-muted"><?= $totalFestivais ?> Eventos ativos</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="events/index.php?categoria[]=Tecnologia" class="d-block p-4 bg-white rounded-3 border text-dark shadow-sm h-100 text-decoration-none">
                            <i class="fa-solid fa-laptop-code fs-2 text-danger mb-2"></i>
                            <h6 class="fw-bold mb-1">Tecnologia</h6>
                            <small class="text-muted"><?= $totalTech ?> Cimeiras & Talks</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="events/index.php?categoria[]=Rock" class="d-block p-4 bg-white rounded-3 border text-dark shadow-sm h-100 text-decoration-none">
                            <i class="fa-solid fa-guitar fs-2 text-danger mb-2"></i>
                            <h6 class="fw-bold mb-1">Rock</h6>
                            <small class="text-muted"><?= $totalRock ?> Espetáculos</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="events/index.php?categoria[]=Jazz" class="d-block p-4 bg-white rounded-3 border text-dark shadow-sm h-100 text-decoration-none">
                            <i class="fa-solid fa-music fs-2 text-danger mb-2"></i>
                            <h6 class="fw-bold mb-1">Jazz & Soul</h6>
                            <small class="text-muted"><?= $totalJazz ?> Sessões</small>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seção: Eventos em Destaque Dinâmicos do Banco de Dados -->
        <section class="py-5">
            <div class="container my-3">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <div>
                        <p class="section-subtitle mb-1">Imperdíveis</p>
                        <h2 class="section-title mb-0">Eventos em Destaque</h2>
                    </div>
                    <a href="events/index.php" class="btn btn-outline-dark fw-bold rounded-pill px-4">
                        Ver Catálogo Completo <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="row g-4">
                    <?php if (empty($eventosDestaque)): ?>
                        <div class="col-12 py-5 text-center text-muted">
                            <i class="fa-solid fa-calendar-xmark fa-3x mb-3 text-secondary"></i>
                            <h5>Nenhum evento em destaque registado.</h5>
                            <p>Aceda ao catálogo para explorar novos eventos.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($eventosDestaque as $ev): ?>
                            <?php
                                $timestamp = strtotime($ev['date']);
                                $dia = date('d', $timestamp);
                                $mes = date('M', $timestamp);
                                $hora = date('H:i', strtotime($ev['time']));
                                $imgSrc = getCategoryEventImage($ev['category'] ?? 'Festival', $ev['image'] ?? '');
                            ?>
                            <div class="col-lg-4 col-md-6">
                                <article class="event-card h-100 d-flex flex-column">
                                    <div class="event-img-wrap">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($ev['title']) ?>">
                                        <span class="event-badge"><?= htmlspecialchars($ev['category'] ?? 'Evento') ?></span>
                                        <div class="event-date-flag">
                                            <span class="day"><?= $dia ?></span>
                                            <span class="month"><?= $mes ?></span>
                                        </div>
                                    </div>
                                    <div class="event-body d-flex flex-column flex-grow-1">
                                        <span class="event-category">Bilheteira Oficial</span>
                                        <h3 class="event-name"><?= htmlspecialchars($ev['title']) ?></h3>
                                        <div class="event-meta">
                                            <i class="fa-regular fa-clock"></i> <?= $hora ?>
                                        </div>
                                        <div class="event-meta">
                                            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($ev['location']) ?>
                                        </div>
                                        <p class="event-desc flex-grow-1">
                                            <?= htmlspecialchars(mb_strimwidth($ev['description'] ?? 'Sem descrição.', 0, 120, '...')) ?>
                                        </p>
                                        <div class="event-footer mt-auto">
                                            <div class="event-price">
                                                <?= number_format((float)$ev['price'], 2, ',', '.') ?> € <small>desde</small>
                                            </div>
                                            <a href="events/details.php?id=<?= $ev['id'] ?>" class="btn-reserve">Ver Detalhes</a>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Como Funciona a Compra -->
        <section class="py-5 bg-white border-top">
            <div class="container my-3">
                <div class="section-header">
                    <p class="section-subtitle">Simples e 100% Digital</p>
                    <h2 class="section-title">Como Funciona a LiveEvents</h2>
                </div>

                <div class="row g-4 text-center">
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="step-number mx-auto mb-3" style="width:50px; height:50px; font-size:20px; font-weight:800; background-color:#ff4757; color:#fff; display:flex; align-items:center; justify-content:center; border-radius:50%;">1</div>
                            <h5 class="fw-bold">Escolha o Evento</h5>
                            <p class="text-muted small">Navegue pelo catálogo e selecione o espetáculo ou festival desejado.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="step-number mx-auto mb-3" style="width:50px; height:50px; font-size:20px; font-weight:800; background-color:#111827; color:#fff; display:flex; align-items:center; justify-content:center; border-radius:50%;">2</div>
                            <h5 class="fw-bold">Selecione os Bilhetes</h5>
                            <p class="text-muted small">Escolha a quantidade de bilhetes e adicione ao carrinho sem sair da página.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="step-number mx-auto mb-3" style="width:50px; height:50px; font-size:20px; font-weight:800; background-color:#111827; color:#fff; display:flex; align-items:center; justify-content:center; border-radius:50%;">3</div>
                            <h5 class="fw-bold">Pagamento Seguro</h5>
                            <p class="text-muted small">Pague por MB WAY, Multibanco ou Cartão em ambiente encriptado.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3">
                            <div class="step-number mx-auto mb-3" style="width:50px; height:50px; font-size:20px; font-weight:800; background-color:#2ed573; color:#fff; display:flex; align-items:center; justify-content:center; border-radius:50%;">4</div>
                            <h5 class="fw-bold">Entrada Imediata</h5>
                            <p class="text-muted small">Receba o comprovativo e consulte os seus bilhetes digitais no seu perfil.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Rodapé Unificado -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap 5.3 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
