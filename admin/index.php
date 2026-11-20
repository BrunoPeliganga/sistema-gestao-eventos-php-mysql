<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 1. Verificação de Segurança de Administrador
if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$msgSucesso = '';
$msgErro = '';
$activeTab = $_GET['tab'] ?? 'events';

// 2. Ações de Gestão e Modificação via POST e GET (com CSRF e verificação de integridade)
// Suporte a exclusão direta GET e POST
$acaoDeleteEvent = false;
$delEventId = 0;
$acaoDeleteUser = false;
$delUserId = 0;
$acaoToggleStatus = false;
$togglePurchaseId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfEnviado = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfEnviado)) {
        $msgErro = "Operação não autorizada (Token de segurança CSRF inválido).";
    } else {
        if (isset($_POST['action_toggle_status'])) {
            $acaoToggleStatus = true;
            $togglePurchaseId = (int)$_POST['purchase_id'];
        }
        if (isset($_POST['action_delete_event'])) {
            $acaoDeleteEvent = true;
            $delEventId = (int)$_POST['event_id'];
        }
        if (isset($_POST['action_delete_user'])) {
            $acaoDeleteUser = true;
            $delUserId = (int)$_POST['user_id'];
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Compatibilidade caso seja acessado via link direto GET
    if (isset($_GET['delete_event'])) {
        $acaoDeleteEvent = true;
        $delEventId = (int)$_GET['delete_event'];
    }
    if (isset($_GET['delete_user'])) {
        $acaoDeleteUser = true;
        $delUserId = (int)$_GET['delete_user'];
    }
    if (isset($_GET['toggle_status'])) {
        $acaoToggleStatus = true;
        $togglePurchaseId = (int)$_GET['toggle_status'];
    }
}

// 2.1 Alternar Status de Compra (pending <-> completed)
if ($acaoToggleStatus && $togglePurchaseId > 0) {
    $stmtP = $pdo->prepare("SELECT status FROM purchases WHERE id = ?");
    $stmtP->execute([$togglePurchaseId]);
    $currStatus = $stmtP->fetchColumn();

    if ($currStatus !== false) {
        $newStatus = ($currStatus === 'completed' || $currStatus === 'pago') ? 'pending' : 'completed';
        $stmtUp = $pdo->prepare("UPDATE purchases SET status = ? WHERE id = ?");
        $stmtUp->execute([$newStatus, $togglePurchaseId]);
        header("Location: index.php?tab=purchases&msg=Estado+da+compra+atualizado+para+" . $newStatus);
        exit();
    }
}

// 2.2 Excluir Evento (Com verificação inteligente de compras e carrinho associados)
if ($acaoDeleteEvent && $delEventId > 0) {
    try {
        // 1. Verifica se este evento já tem compras/vendas registradas
        $stmtCheckPurchases = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE events_id = ?");
        $stmtCheckPurchases->execute([$delEventId]);
        $totalVendas = (int)$stmtCheckPurchases->fetchColumn();

        if ($totalVendas > 0) {
            $msgErro = "⚠️ Atenção: Não é possível apagar este evento porque já existem {$totalVendas} compra(s) associada(s) a ele. Para impedir novas vendas, altere o stock para 0 na edição.";
        } else {
            // Se não tem compras, limpa referências no carrinho antes de apagar
            $stmtCleanCart = $pdo->prepare("DELETE FROM cart WHERE events_id = ?");
            $stmtCleanCart->execute([$delEventId]);

            $stmtDel = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmtDel->execute([$delEventId]);
            header("Location: index.php?tab=events&msg=Evento+removido+com+sucesso");
            exit();
        }
    } catch (PDOException $e) {
        $msgErro = "Erro ao tentar remover o evento: " . $e->getMessage();
    }
}

// 2.3 Excluir Utilizador (Com verificação inteligente de compras associadas)
if ($acaoDeleteUser && $delUserId > 0) {
    if ($delUserId === (int)$_SESSION['id']) {
        $msgErro = "Não pode excluir a sua própria conta de administrador!";
    } else {
        try {
            // 1. Verifica se o utilizador já efetuou compras/encomendas no sistema
            $stmtCheckUserPurchases = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE user_id = ?");
            $stmtCheckUserPurchases->execute([$delUserId]);
            $totalCompras = (int)$stmtCheckUserPurchases->fetchColumn();

            if ($totalCompras > 0) {
                $msgErro = "⚠️ Atenção: Não é possível apagar este utilizador porque ele possui {$totalCompras} compra(s) registada(s) no histórico do sistema.";
            } else {
                // Se não tem compras, limpa o carrinho do utilizador antes de apagar a conta
                $stmtCleanCartUser = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmtCleanCartUser->execute([$delUserId]);

                $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmtDel->execute([$delUserId]);
                header("Location: index.php?tab=users&msg=Utilizador+removido+com+sucesso");
                exit();
            }
        } catch (PDOException $e) {
            $msgErro = "Erro ao tentar remover o utilizador: " . $e->getMessage();
        }
    }
}

// 4. Editar Utilizador (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_utilizador'])) {
    $editUserId   = (int)($_POST['user_id'] ?? 0);
    $editUsername = trim($_POST['username'] ?? '');
    $editEmail    = trim($_POST['email'] ?? '');
    $editRole     = in_array($_POST['role'] ?? '', ['user', 'admin']) ? $_POST['role'] : 'user';

    if (empty($editUsername) || empty($editEmail) || $editUserId <= 0) {
        $msgErro = "Todos os campos do utilizador são obrigatórios.";
    } else {
        // Verifica unicidade
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmtCheck->execute([$editUsername, $editEmail, $editUserId]);
        if ($stmtCheck->fetch()) {
            $msgErro = "O nome de utilizador ou e-mail já está em uso por outro utilizador.";
        } else {
            $stmtUpUser = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmtUpUser->execute([$editUsername, $editEmail, $editRole, $editUserId]);
            header("Location: index.php?tab=users&msg=Utilizador+atualizado+com+sucesso");
            exit();
        }
    }
}

// 4. Criar Novo Evento (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_evento'])) {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? 'Festival');
    $description = trim($_POST['description'] ?? '');
    $date        = $_POST['date'] ?? '';
    $time        = $_POST['time'] ?? '';
    $location    = trim($_POST['location'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = max(0, (int)($_POST['stock'] ?? 50));
    $customImage = trim($_POST['image'] ?? '');

    if (empty($title) || empty($date) || empty($location)) {
        $msgErro = "Título, Data e Localização são campos obrigatórios.";
    } elseif ($date < date('Y-m-d')) {
        $msgErro = "A data do evento não pode ser anterior à data de hoje (" . date('d/m/Y') . "). Deve registar eventos para hoje ou datas futuras.";
    } else {
        // Atribui imagem apropriada baseada na categoria se não informada
        $imageFinal = getCategoryEventImage($category, $customImage);

        $stmt = $pdo->prepare("
            INSERT INTO events (title, category, description, date, time, location, price, stock, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $category, $description, $date, $time, $location, $price, $stock, $imageFinal]);
        header("Location: index.php?tab=events&msg=Novo+evento+criado+com+sucesso");
        exit();
    }
}

// 5. Consultas para o Dashboard
$totalEventos  = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVendas   = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM purchases")->fetchColumn();
$totalPendente = $pdo->query("SELECT COUNT(*) FROM purchases WHERE status = 'pending'")->fetchColumn();

// Listagens
$eventos      = $pdo->query("SELECT * FROM events ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);
$utilizadores = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$compras      = $pdo->query("
    SELECT p.*, u.username, u.email, e.title 
    FROM purchases p 
    JOIN users u ON p.user_id = u.id 
    JOIN events e ON p.events_id = e.id 
    ORDER BY p.purchase_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração | LiveEvents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/images/icons8-flash-64.png">
</head>
<body class="bg-light">

    <!-- Navbar do Admin -->
    <nav class="navbar navbar-dark bg-dark sticky-top px-3 py-3 shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fa-solid fa-shield-halved text-danger me-2"></i>LiveEvents Admin
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="../events/index.php" class="btn btn-outline-light btn-sm rounded-pill px-3" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver Catálogo
                </a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-0">Painel de Controlo</h2>
                <small class="text-muted">Gestão completa de eventos, utilizadores e encomendas</small>
            </div>
            <button class="btn btn-danger fw-bold rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEvento">
                <i class="fa-solid fa-plus me-1"></i> Criar Novo Evento
            </button>
        </div>

        <?php if (!empty($msgSucesso) || isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
                <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($msgSucesso ?: $_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($msgErro)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($msgErro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Cartões de Visão Geral (Métricas) -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm rounded-4 text-center bg-white h-100">
                    <span class="text-muted small text-uppercase fw-semibold">Total de Eventos</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?= $totalEventos ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm rounded-4 text-center bg-white h-100">
                    <span class="text-muted small text-uppercase fw-semibold">Utilizadores</span>
                    <h3 class="fw-bold text-dark mt-2 mb-0"><?= $totalUsers ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm rounded-4 text-center bg-white h-100">
                    <span class="text-muted small text-uppercase fw-semibold">Receita Total</span>
                    <h3 class="fw-bold text-danger mt-2 mb-0"><?= number_format((float)$totalVendas, 2, ',', '.') ?> €</h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card p-3 border-0 shadow-sm rounded-4 text-center bg-white h-100">
                    <span class="text-muted small text-uppercase fw-semibold">Pedidos Pendentes</span>
                    <h3 class="fw-bold text-warning mt-2 mb-0"><?= $totalPendente ?></h3>
                </div>
            </div>
        </div>

        <!-- Abas de Gestão -->
        <ul class="nav nav-pills mb-3" id="adminTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link <?= $activeTab === 'events' ? 'active' : '' ?> fw-bold" data-bs-toggle="pill" data-bs-target="#tab-events">
                    <i class="fa-regular fa-calendar-check me-1"></i> Gestão de Eventos
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link <?= $activeTab === 'users' ? 'active' : '' ?> fw-bold" data-bs-toggle="pill" data-bs-target="#tab-users">
                    <i class="fa-solid fa-users me-1"></i> Utilizadores
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link <?= $activeTab === 'purchases' ? 'active' : '' ?> fw-bold" data-bs-toggle="pill" data-bs-target="#tab-purchases">
                    <i class="fa-solid fa-receipt me-1"></i> Histórico de Compras 
                    <?php if ($totalPendente > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $totalPendente ?> pendente(s)</span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content bg-white p-4 rounded-4 shadow-sm border">
            <!-- ABA 1: EVENTOS (Criar, Listar, Editar, Excluir) -->
            <div class="tab-pane fade <?= $activeTab === 'events' ? 'show active' : '' ?>" id="tab-events">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Eventos Cadastrados (<?= count($eventos) ?>)</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Evento</th>
                                <th>Categoria</th>
                                <th>Data / Hora</th>
                                <th>Local</th>
                                <th>Preço</th>
                                <th>Stock</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventos as $ev): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= htmlspecialchars(getCategoryEventImage($ev['category'] ?? 'Festival', $ev['image'] ?? '')) ?>" 
                                                 alt="<?= htmlspecialchars($ev['title']) ?>" 
                                                 class="rounded-3" style="width: 45px; height: 45px; object-fit: cover;">
                                            <div>
                                                <strong><?= htmlspecialchars($ev['title']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($ev['category'] ?? 'Festival') ?></span></td>
                                    <td>
                                        <?php 
                                            $evPassou = ($ev['date'] < date('Y-m-d'));
                                        ?>
                                        <?= date('d/m/Y', strtotime($ev['date'])) ?> às <?= date('H:i', strtotime($ev['time'])) ?>
                                        <?php if ($evPassou): ?>
                                            <br><span class="badge bg-warning text-dark"><i class="fa-solid fa-clock-rotate-left me-1"></i>Data expirada / Realizado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($ev['location']) ?></td>
                                    <td class="fw-bold text-dark"><?= number_format((float)$ev['price'], 2, ',', '.') ?> €</td>
                                    <td>
                                        <?php if ((int)($ev['stock'] ?? 0) <= 0): ?>
                                            <span class="badge bg-danger">Esgotado (0)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= (int)$ev['stock'] ?> un.</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="edit.php?id=<?= (int)$ev['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </a>
                                        <!-- Exclusão via formulário POST seguro com token CSRF -->
                                        <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Tem a certeza que deseja excluir este evento?');">
                                            <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                                            <input type="hidden" name="action_delete_event" value="1">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir evento">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA 2: UTILIZADORES -->
            <div class="tab-pane fade <?= $activeTab === 'users' ? 'show active' : '' ?>" id="tab-users">
                <h4 class="fw-bold mb-3">Utilizadores Registados (<?= count($utilizadores) ?>)</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Utilizador</th>
                                <th>E-mail</th>
                                <th>Função (Role)</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilizadores as $u): ?>
                                <tr>
                                    <td>#<?= $u['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>"><?= strtoupper($u['role']) ?></span></td>
                                    <td class="text-end text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalUser<?= $u['id'] ?>">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </button>
                                        <?php if ($u['id'] !== $_SESSION['id']): ?>
                                            <!-- Exclusão de utilizador via POST seguro com CSRF -->
                                            <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Tem a certeza que deseja excluir este utilizador?');">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                <input type="hidden" name="action_delete_user" value="1">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir utilizador">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Você</span>
                                        <?php endif; ?>

                                        <!-- Modal Editar Utilizador -->
                                        <div class="modal fade text-start" id="modalUser<?= $u['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="index.php">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-danger me-2"></i>Editar Utilizador #<?= $u['id'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Nome de Utilizador *</label>
                                                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">E-mail *</label>
                                                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Função (Role) *</label>
                                                                <select name="role" class="form-select" <?= $u['id'] === $_SESSION['id'] ? 'disabled' : '' ?>>
                                                                    <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Utilizador Comum (user)</option>
                                                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrador (admin)</option>
                                                                </select>
                                                                <?php if ($u['id'] === $_SESSION['id']): ?>
                                                                    <input type="hidden" name="role" value="admin">
                                                                    <small class="text-muted">Não pode alterar o seu próprio papel de administrador.</small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" name="editar_utilizador" class="btn btn-danger fw-bold">Guardar Alterações</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA 3: HISTÓRICO DE COMPRAS (Com Toggle de Status pending <-> completed) -->
            <div class="tab-pane fade <?= $activeTab === 'purchases' ? 'show active' : '' ?>" id="tab-purchases">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Todas as Compras Realizadas (<?= count($compras) ?>)</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID Pedido</th>
                                <th>Cliente</th>
                                <th>Evento</th>
                                <th>Qtd</th>
                                <th>Total</th>
                                <th>Método</th>
                                <th>Data Compra</th>
                                <th>Estado</th>
                                <th class="text-end">Gerir Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($compras)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Nenhuma compra registada até ao momento.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($compras as $cp): ?>
                                    <?php 
                                        $st = strtolower($cp['status'] ?? 'pending');
                                        $isCompleted = ($st === 'completed' || $st === 'pago');
                                    ?>
                                    <tr>
                                        <td><strong>#LEV-<?= str_pad((string)$cp['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($cp['username']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($cp['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($cp['title']) ?></td>
                                        <td><span class="badge bg-dark"><?= (int)$cp['quantity'] ?></span></td>
                                        <td class="fw-bold text-danger"><?= number_format((float)$cp['total_price'], 2, ',', '.') ?> €</td>
                                        <td><small class="text-muted"><?= htmlspecialchars($cp['payment_method'] ?? 'MB WAY') ?></small></td>
                                        <td><?= date('d/m/Y H:i', strtotime($cp['purchase_date'])) ?></td>
                                        <td>
                                            <?php if ($isCompleted): ?>
                                                <span class="badge bg-success px-2 py-1">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Concluída
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1">
                                                    <i class="fa-solid fa-clock me-1"></i> Pendente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <!-- Mudar estado de compra via POST seguro com CSRF -->
                                            <form method="POST" action="index.php" class="d-inline">
                                                <input type="hidden" name="purchase_id" value="<?= (int)$cp['id'] ?>">
                                                <input type="hidden" name="action_toggle_status" value="1">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                                <?php if ($isCompleted): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Mudar para Pendente">
                                                        <i class="fa-solid fa-clock-rotate-left"></i> Marcar Pendente
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-success" title="Mudar para Concluída">
                                                        <i class="fa-solid fa-check"></i> Marcar Concluída
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Criar Novo Evento -->
    <div class="modal fade" id="modalEvento" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="index.php">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle text-danger me-2"></i>Criar Novo Evento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título do Evento *</label>
                            <input type="text" name="title" class="form-control" placeholder="Ex: Summer Music Festival 2026" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Categoria *</label>
                                <select name="category" class="form-select" required>
                                    <option value="Festival">Festivais de Música</option>
                                    <option value="Tecnologia">Tecnologia & IA</option>
                                    <option value="Rock">Rock & Alternativo</option>
                                    <option value="Jazz">Jazz, Soul & Clássica</option>
                                    <option value="Comédia">Teatro & Stand-Up</option>
                                </select>
                                <small class="text-muted">A imagem correspondente será atribuída automaticamente.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stock de Bilhetes *</label>
                                <input type="number" name="stock" class="form-control" value="50" min="0" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Data do Evento *</label>
                                <input type="date" name="date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                <small class="text-muted">Apenas datas a partir de hoje são permitidas.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Hora de Início *</label>
                                <input type="time" name="time" class="form-control" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Local / Recinto *</label>
                                <input type="text" name="location" class="form-control" placeholder="Ex: Altice Arena, Lisboa" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Preço Base (€) *</label>
                                <input type="number" step="0.01" name="price" class="form-control" placeholder="25.00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">URL da Imagem (Opcional)</label>
                            <input type="url" name="image" class="form-control" placeholder="https://images.unsplash.com/... (ou deixe vazio para usar a imagem da categoria)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descrição do Evento</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Insira informações sobre o alinhamento, regras ou detalhes do espetáculo..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="salvar_evento" class="btn btn-danger fw-bold px-4">Guardar Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rodapé -->                            
    <?php include '../includes/footer_admin.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
