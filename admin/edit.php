<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Apenas administradores acedem
if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit();
}

$msgErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_evento'])) {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? 'Festival');
    $description = trim($_POST['description'] ?? '');
    $date        = $_POST['date'] ?? '';
    $time        = $_POST['time'] ?? '';
    $location    = trim($_POST['location'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = max(0, (int)($_POST['stock'] ?? 0));
    $customImage = trim($_POST['image'] ?? '');

    if (empty($title) || empty($date) || empty($location)) {
        $msgErro = "Título, Data e Localização são obrigatórios.";
    } else {
        $imageFinal = getCategoryEventImage($category, $customImage);

        $stmtUpdate = $pdo->prepare("
            UPDATE events 
            SET title = ?, category = ?, description = ?, date = ?, time = ?, location = ?, price = ?, stock = ?, image = ? 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$title, $category, $description, $date, $time, $location, $price, $stock, $imageFinal, $id]);
        header("Location: index.php?tab=events&msg=Evento+atualizado+com+sucesso");
        exit();
    }
}

// Buscar dados atuais do evento
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evento #<?= $evento['id'] ?> | LiveEvents Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/images/icons8-flash-64.png">
</head>
<body class="bg-light">

    <div class="container py-5" style="max-width: 750px;">
        <div class="card p-4 shadow-sm border-0 rounded-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-danger me-2"></i>Editar Evento #<?= $evento['id'] ?></h4>
                <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i> Voltar ao Painel
                </a>
            </div>

            <?php if (!empty($msgErro)): ?>
                <div class="alert alert-danger rounded-3"><?= htmlspecialchars($msgErro) ?></div>
            <?php endif; ?>

            <form method="POST" action="edit.php?id=<?= $evento['id'] ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Título do Evento *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($evento['title']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Categoria do Evento *</label>
                        <select name="category" class="form-select" required>
                            <option value="Festival" <?= ($evento['category'] ?? '') === 'Festival' ? 'selected' : '' ?>>Festivais de Música</option>
                            <option value="Tecnologia" <?= ($evento['category'] ?? '') === 'Tecnologia' ? 'selected' : '' ?>>Tecnologia & IA</option>
                            <option value="Rock" <?= ($evento['category'] ?? '') === 'Rock' ? 'selected' : '' ?>>Rock & Alternativo</option>
                            <option value="Jazz" <?= ($evento['category'] ?? '') === 'Jazz' ? 'selected' : '' ?>>Jazz, Soul & Clássica</option>
                            <option value="Comédia" <?= ($evento['category'] ?? '') === 'Comédia' ? 'selected' : '' ?>>Teatro & Stand-Up</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Stock de Bilhetes *</label>
                        <input type="number" name="stock" class="form-control" value="<?= (int)($evento['stock'] ?? 50) ?>" min="0" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data do Evento *</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($evento['date']) ?>" min="<?= date('Y-m-d') ?>" required>
                        <?php if ($evento['date'] < date('Y-m-d')): ?>
                            <small class="text-danger fw-semibold d-block mt-1">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>A data atual deste evento já expirou. Selecione uma data de hoje em diante para reativá-lo.
                            </small>
                        <?php else: ?>
                            <small class="text-muted">Apenas datas de hoje em diante.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hora de Início *</label>
                        <input type="time" name="time" class="form-control" value="<?= htmlspecialchars($evento['time']) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Local / Recinto *</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($evento['location']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Preço do Bilhete (€) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?= (float)$evento['price'] ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">URL da Imagem</label>
                    <input type="url" name="image" class="form-control" value="<?= htmlspecialchars($evento['image'] ?? '') ?>" placeholder="https://images.unsplash.com/...">
                    <small class="text-muted">Se deixar vazio, será usada a imagem pré-definida da categoria.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Descrição do Evento</label>
                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($evento['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" name="atualizar_evento" class="btn btn-danger w-100 py-3 fw-bold rounded-3">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Alterações
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
