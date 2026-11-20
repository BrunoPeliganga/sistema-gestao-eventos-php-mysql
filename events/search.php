<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Incluir a conexão e funções existentes
require_once '../config/database.php';
require_once '../includes/functions.php';

// 2. Capturar todos os filtros com segurança
$titulo     = isset($_GET['titulo']) ? trim($_GET['titulo']) : '';
$data_inicio = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : '';
$data_fim    = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : '';
// Suporte retrocompatível se vier apenas 'data'
if (empty($data_inicio) && isset($_GET['data']) && !empty($_GET['data'])) {
    $data_inicio = trim($_GET['data']);
}
$localidade = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
$preco_max  = isset($_GET['preco_max']) ? floatval($_GET['preco_max']) : 0;
$categorias = isset($_GET['categoria']) && is_array($_GET['categoria']) ? $_GET['categoria'] : [];

// 3. Montar a consulta SQL dinâmica padronizada com parâmetros nomeados (:nome)
$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

// Filtro: Título
if (!empty($titulo)) {
    $sql .= " AND title LIKE :titulo";
    $params[':titulo'] = '%' . $titulo . '%';
}

// Filtro: Intervalo de Datas
if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND date BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $data_inicio;
    $params[':data_fim'] = $data_fim;
} elseif (!empty($data_inicio)) {
    // A partir da data indicada
    $sql .= " AND date >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
} elseif (!empty($data_fim)) {
    // Até à data limite
    $sql .= " AND date <= :data_fim";
    $params[':data_fim'] = $data_fim;
}

// Filtro: Localidade / Venue
if (!empty($localidade)) {
    $sql .= " AND location LIKE :localidade";
    $params[':localidade'] = '%' . $localidade . '%';
}

// Filtro: Preço Máximo
if ($preco_max > 0) {
    $sql .= " AND price <= :preco_max";
    $params[':preco_max'] = $preco_max;
}

// Filtro: Categorias (com parâmetros nomeados :cat_0, :cat_1 sem misturar com ?)
if (!empty($categorias)) {
    $catNamed = [];
    foreach ($categorias as $index => $cat) {
        $key = ":cat_" . $index;
        $catNamed[] = $key;
        $params[$key] = $cat;
    }
    $sql .= " AND category IN (" . implode(',', $catNamed) . ")";
}

$sql .= " ORDER BY date ASC";

// 4. Executar via Prepared Statements
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Responder com os Cards Renderizados
if (empty($eventos)): ?>
    <div class="col-12 py-5 text-center">
        <div class="p-5 bg-white rounded-4 border shadow-sm mx-auto" style="max-width: 500px;">
            <i class="fa-solid fa-calendar-xmark fa-3x text-danger mb-3"></i>
            <h4 class="fw-bold mb-2">Nenhum evento encontrado</h4>
            <p class="text-muted mb-0">Tente ajustar os filtros (localidade, preço ou data).</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($eventos as $evento): ?>
        <?php
            $timestamp = strtotime($evento['date']);
            $dia  = date('d', $timestamp);
            $mes  = date('M', $timestamp);
            $hora = date('H:i', strtotime($evento['time']));
            $stock = (int)($evento['stock'] ?? 0);
            $imgSrc = getCategoryEventImage($evento['category'] ?? 'Festival', $evento['image'] ?? '');
        ?>
        <div class="col-md-6 col-xl-4">
            <article class="event-card h-100 d-flex flex-column">
                <div class="event-img-wrap">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($evento['title']) ?>">
                    <span class="event-badge"><?= htmlspecialchars($evento['category'] ?? 'Evento') ?></span>
                    <div class="event-date-flag">
                        <span class="day"><?= $dia ?></span>
                        <span class="month"><?= $mes ?></span>
                    </div>
                </div>
                <div class="event-body d-flex flex-column flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="event-category">Bilheteira Oficial</span>
                        <?php 
                            $hoje = date('Y-m-d');
                            $eventoJaPassou = ($evento['date'] < $hoje);
                        ?>
                        <?php if ($eventoJaPassou): ?>
                            <span class="badge bg-secondary"><i class="fa-solid fa-flag-checkered me-1"></i>Já Realizado</span>
                        <?php elseif ($stock <= 0): ?>
                            <span class="badge bg-danger">Esgotado</span>
                        <?php elseif ($stock <= 10): ?>
                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-fire me-1"></i>Últimos <?= $stock ?> bilhetes</span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= $stock ?> disponíveis</span>
                        <?php endif; ?>
                    </div>
                    <h4 class="event-name"><?= htmlspecialchars($evento['title']) ?></h4>
                    <div class="event-meta">
                        <i class="fa-regular fa-clock"></i> <?= $hora ?>
                    </div>
                    <div class="event-meta">
                        <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($evento['location']) ?>
                    </div>
                    <p class="event-desc flex-grow-1">
                        <?= htmlspecialchars(mb_strimwidth($evento['description'] ?? 'Sem descrição disponível.', 0, 110, '...')) ?>
                    </p>

                    <div class="event-footer flex-wrap gap-2 mt-auto">
                        <div class="event-price">
                            <?= number_format((float)$evento['price'], 2, ',', '.') ?> € <small>desde</small>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <a href="details.php?id=<?= $evento['id'] ?>" class="btn btn-sm btn-outline-dark fw-semibold px-3 py-2">
                                Detalhes
                            </a>

                            <?php 
                                $hoje = date('Y-m-d');
                                $eventoJaPassou = ($evento['date'] < $hoje);
                            ?>
                            <?php if ($eventoJaPassou): ?>
                                <button class="btn btn-sm btn-secondary fw-semibold px-3 py-2" disabled title="Este evento já foi realizado">
                                    <i class="fa-solid fa-calendar-check me-1"></i> Evento Realizado
                                </button>
                            <?php elseif ($stock <= 0): ?>
                                <button class="btn btn-sm btn-secondary fw-semibold px-3 py-2" disabled>
                                    <i class="fa-solid fa-ban me-1"></i> Esgotado
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-danger fw-semibold px-3 py-2 d-inline-flex align-items-center gap-1" onclick="adicionarAoCarrinhoAjax(<?= (int)$evento['id'] ?>)">
                                    <i class="fa-solid fa-cart-plus"></i> Carrinho
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
