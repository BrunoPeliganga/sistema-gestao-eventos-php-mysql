<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 1. Só utilizadores logados acedem ao perfil
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['id'];
$mensagemSucesso = '';
$mensagemErro = '';

// 2. Processar Atualização de Dados (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confSenha = $_POST['conf_senha'] ?? '';

    if (empty($username) || empty($email)) {
        $mensagemErro = "Nome de utilizador e E-mail são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagemErro = "Por favor, introduza um e-mail válido.";
    } elseif (!empty($novaSenha) && strlen($novaSenha) < 6) {
        $mensagemErro = "A nova palavra-passe deve ter pelo menos 6 caracteres.";
    } elseif (!empty($novaSenha) && $novaSenha !== $confSenha) {
        $mensagemErro = "A confirmação da nova palavra-passe não coincide.";
    } else {
        // Verifica se outro utilizador já usa este username ou email
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmtCheck->execute([$username, $email, $user_id]);
        
        if ($stmtCheck->fetch()) {
            $mensagemErro = "Esse nome de utilizador ou e-mail já está a ser utilizado por outra conta.";
        } else {
            if (!empty($novaSenha)) {
                $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
                $stmtUpdate = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmtUpdate->execute([$username, $email, $hash, $user_id]);
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmtUpdate->execute([$username, $email, $user_id]);
            }
            $_SESSION['username'] = $username;
            $_SESSION['email']    = $email;
            $mensagemSucesso = "Os seus dados foram atualizados com sucesso!";
        }
    }
}

// 3. Buscar dados atualizados do utilizador
$stmtUser = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

// 4. Buscar histórico de compras deste utilizador com detalhes do evento
$stmtCompras = $pdo->prepare("
    SELECT p.*, e.title, e.date, e.time, e.location, e.category, e.price AS unit_price, e.image 
    FROM purchases p 
    INNER JOIN events e ON p.events_id = e.id 
    WHERE p.user_id = ? 
    ORDER BY p.purchase_date DESC
");
$stmtCompras->execute([$user_id]);
$compras = $stmtCompras->fetchAll(PDO::FETCH_ASSOC);

// Contadores de status para o resumo
$totalConcluidas = 0;
$totalPendentes = 0;
foreach ($compras as $cp) {
    $st = strtolower($cp['status'] ?? 'pending');
    if ($st === 'completed' || $st === 'pago') {
        $totalConcluidas++;
    } else {
        $totalPendentes++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Minha Conta | LiveEvents 2026</title>
    <meta name="description" content="Página de perfil do cliente. Atualize os seus dados, altere a palavra-passe e consulte o histórico de compras.">
    <link rel="icon" href="../assets/images/icons8-flash-64.png">

    <!-- Bootstrap 5, FontAwesome e Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/profile.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Cabeçalho Unificado -->
<?php include '../includes/header_profile.php'; ?>

<main class="container py-4 my-2">

    <?php if (($usuario['role'] ?? '') === 'admin'): ?>
        <!-- Barra informativa exclusiva para Administradores -->
        <div class="alert alert-dark border-danger border-2 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 p-3 rounded-4 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-shield-halved text-danger fs-4"></i>
                <div>
                    <strong class="text-white">Sessão de Administrador Ativa:</strong>
                    <span class="text-light">Está a visualizar a sua conta pessoal. Para gerir eventos, compras globais e utilizadores, utilize o Painel Administrativo.</span>
                </div>
            </div>
            <a href="../admin/index.php" class="btn btn-danger btn-sm fw-bold px-3 py-2 rounded-pill">
                <i class="fa-solid fa-gear me-1"></i> Ir para o Painel Admin
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Hero Banner de Perfil -->
    <div class="profile-hero mb-4">
        <div class="row align-items-center g-4">
            <div class="col-auto">
                <div class="avatar-circle">
                    <?= strtoupper(substr($usuario['username'] ?? 'U', 0, 1)) ?>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h2 class="text-white fw-bold mb-0">Olá, <?= htmlspecialchars($usuario['username'] ?? '') ?></h2>
                    <span class="badge-client">
                        <i class="fa-solid fa-circle-check me-1"></i><?= $usuario['role'] === 'admin' ? 'Administrador' : 'Cliente Verificado' ?>
                    </span>
                </div>
                <p class="hero-subtitle mb-0">
                    <i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($usuario['email'] ?? '') ?> • Gestão da sua conta pessoal e histórico de bilhetes digitais.
                </p>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
                <a href="../events/index.php" class="btn btn-bm-store">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Explorar Eventos
                </a>
                <a href="../auth/logout.php" class="btn btn-bm-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                </a>
            </div>
        </div>
    </div>

    <!-- Alertas de Feedback -->
    <?php if (!empty($mensagemSucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2 fs-5 align-middle"></i>
            <?= htmlspecialchars($mensagemSucesso) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensagemErro)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2 fs-5 align-middle"></i>
            <?= htmlspecialchars($mensagemErro) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Separadores (Tabs) de Navegação -->
    <ul class="nav nav-pills-custom" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-dados-tab" data-bs-toggle="pill" data-bs-target="#pills-dados" type="button" role="tab">
                <i class="fa-solid fa-user-pen me-2"></i>Os Meus Dados
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-compras-tab" data-bs-toggle="pill" data-bs-target="#pills-compras" type="button" role="tab">
                <i class="fa-solid fa-ticket me-2"></i>As Minhas Compras & Bilhetes
                <span class="badge bg-danger rounded-pill ms-2"><?= count($compras) ?></span>
            </button>
        </li>
    </ul>

    <!-- Conteúdo das Abas -->
    <div class="tab-content" id="profileTabsContent">

        <!-- ABA 1: DADOS PESSOAIS & SENHA -->
        <div class="tab-pane fade show active" id="pills-dados" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="profile-card">
                        <div class="card-header-custom">
                            <h5><i class="fa-solid fa-id-card me-2"></i>Atualizar Informações de Perfil</h5>
                        </div>
                        <form method="POST" action="index.php">
                            <div class="mb-3">
                                <label class="form-label">Nome de Utilizador *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($usuario['username'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Endereço de E-mail *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                                </div>
                                <small class="text-muted">Os comprovativos e bilhetes digitais são emitidos para este endereço.</small>
                            </div>

                            <hr class="my-4">

                            <h6 class="fw-bold mb-3 text-dark">
                                <i class="fa-solid fa-key me-2 text-danger"></i>Segurança da Conta (Opcional)
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nova Palavra-passe</label>
                                    <input type="password" name="nova_senha" class="form-control" placeholder="Mínimo 6 caracteres">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmar Nova Palavra-passe</label>
                                    <input type="password" name="conf_senha" class="form-control" placeholder="Repita a nova palavra-passe">
                                </div>
                            </div>

                            <button type="submit" name="atualizar_perfil" class="btn btn-bm-purple w-100">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Alterações
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="profile-card">
                        <div class="card-header-custom">
                            <h5><i class="fa-solid fa-shield-halved me-2"></i>Resumo da Conta</h5>
                        </div>
                        <div class="info-box">
                            <span class="label">Identificador de Conta</span>
                            <span class="value">#USR-<?= str_pad((string)$user_id, 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-box">
                            <span class="label">Tipo de Utilizador</span>
                            <span class="value"><?= strtoupper($usuario['role']) ?></span>
                        </div>
                        <div class="info-box">
                            <span class="label">Total de Encomendas</span>
                            <span class="value"><?= count($compras) ?> pedido(s)</span>
                        </div>
                        <div class="p-3 bg-light rounded-3 mt-3 border">
                            <small class="text-muted d-block mb-1">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i> Dica de Segurança
                            </small>
                            <small class="text-muted">
                                Mantenha os seus dados atualizados para garantir a receção de notificações sobre alterações de horário ou cancelamentos de eventos.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 2: AS MINHAS COMPRAS & BILHETES (ORGANIZAÇÃO ESTRUTURADA) -->
        <div class="tab-pane fade" id="pills-compras" role="tabpanel">
            <?php if (empty($compras)): ?>
                <div class="profile-card text-center py-5">
                    <div class="mb-3">
                        <i class="fa-solid fa-ticket-simple fa-4x text-muted opacity-50"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Ainda não tem compras registadas</h4>
                    <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto;">
                        Quando comprar bilhetes para concertos, festivais ou conferências, os seus pedidos e bilhetes aparecerão organizados aqui.
                    </p>
                    <a href="../events/index.php" class="btn btn-danger px-4 py-2 rounded-pill fw-bold">
                        <i class="fa-solid fa-compass me-2"></i>Explorar Eventos Disponíveis
                    </a>
                </div>
            <?php else: ?>
                <!-- Métricas Rápidas do Utilizador -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                            <span class="text-muted small text-uppercase fw-semibold">Total de Reservas</span>
                            <h4 class="fw-bold text-dark mb-0 mt-1"><?= count($compras) ?></h4>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                            <span class="text-muted small text-uppercase fw-semibold">Bilhetes Aprovados</span>
                            <h4 class="fw-bold text-success mb-0 mt-1"><?= $totalConcluidas ?></h4>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                            <span class="text-muted small text-uppercase fw-semibold">Pedidos Pendentes</span>
                            <h4 class="fw-bold text-warning mb-0 mt-1"><?= $totalPendentes ?></h4>
                        </div>
                    </div>
                </div>

                <!-- Tabela Estruturada e Elegante de Bilhetes -->
                <div class="profile-card p-0 overflow-hidden border">
                    <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt text-danger me-2"></i>Histórico Oficial de Bilhetes Comprados</h5>
                            <small class="text-muted">Clique em "Ver Bilhete Digital" para abrir o comprovativo individual numa nova aba e imprimir.</small>
                        </div>
                        <span class="badge bg-dark rounded-pill px-3 py-2"><?= count($compras) ?> bilhete(s)</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Referência</th>
                                    <th>Evento & Categoria</th>
                                    <th>Data & Local</th>
                                    <th>Entradas</th>
                                    <th>Total Pago</th>
                                    <th>Estado</th>
                                    <th class="text-end pe-4">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compras as $c): ?>
                                    <?php 
                                        $status = strtolower($c['status'] ?? 'pending');
                                        $isCompleted = ($status === 'completed' || $status === 'pago');
                                        $ticketNum = '#LEV-' . str_pad((string)$c['id'], 5, '0', STR_PAD_LEFT);
                                    ?>
                                    <tr>
                                        <!-- Referência do Pedido -->
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark font-monospace"><?= $ticketNum ?></span>
                                            <small class="text-muted d-block"><?= date('d/m/Y H:i', strtotime($c['purchase_date'])) ?></small>
                                        </td>

                                        <!-- Evento & Categoria -->
                                        <td>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($c['title']) ?></strong>
                                            <span class="badge bg-secondary" style="font-size: 0.72rem;"><?= htmlspecialchars($c['category'] ?? 'Festival') ?></span>
                                        </td>

                                        <!-- Data & Recinto -->
                                        <td>
                                            <span class="text-dark small d-block">
                                                <i class="fa-regular fa-calendar text-danger me-1"></i><?= date('d/m/Y', strtotime($c['date'])) ?> às <?= date('H:i', strtotime($c['time'])) ?>
                                            </span>
                                            <small class="text-muted">
                                                <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($c['location']) ?>
                                            </small>
                                        </td>

                                        <!-- Entradas -->
                                        <td>
                                            <span class="badge bg-dark px-2 py-1"><?= (int)$c['quantity'] ?> entrada(s)</span>
                                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($c['payment_method'] ?? 'MB WAY') ?></small>
                                        </td>

                                        <!-- Total Pago -->
                                        <td>
                                            <span class="fw-bold text-danger fs-6"><?= number_format((float)$c['total_price'], 2, ',', '.') ?> €</span>
                                        </td>

                                        <!-- Estado do Pedido -->
                                        <td>
                                            <?php if ($isCompleted): ?>
                                                <span class="status-badge-completed">
                                                    <i class="fa-solid fa-circle-check"></i> Concluído
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge-pending">
                                                    <i class="fa-solid fa-clock"></i> Pendente
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- AÇÃO: ABRE EM UMA NOVA ABA (TARGET="_BLANK") EXCLUSIVAMENTE ESTE BILHETE -->
                                        <td class="text-end pe-4 text-nowrap">
                                            <a href="ticket.php?id=<?= (int)$c['id'] ?>" target="_blank" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-sm" title="Abrir e imprimir este bilhete individual numa nova aba">
                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Ver Bilhete Digital
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Rodapé Unificado -->
<?php include '../includes/footer_profile.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
