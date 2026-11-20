<?php
session_start();
require_once '../config/database.php';

// 1. Verificar se o utilizador está logado
if (!isset($_SESSION['id'])) {
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => false,
            'redirecionar' => '../auth/login.php',
            'mensagem' => 'Inicie sessão para adicionar bilhetes ao seu carrinho.'
        ]);
        exit();
    }
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['id'];
$events_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;

// 1.1 Validar se o utilizador da sessão realmente existe na base de dados
$stmtUserVal = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmtUserVal->execute([$user_id]);
if (!$stmtUserVal->fetch()) {
    // Sessão órfã/inválida: limpa a sessão e força novo login
    session_unset();
    session_destroy();
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => false,
            'redirecionar' => '../auth/login.php',
            'mensagem' => 'A sua sessão expirou. Por favor inicie sessão novamente.'
        ]);
        exit();
    }
    header("Location: ../auth/login.php?msg=Sessao+expirada.+Inicie+sessao+novamente.");
    exit();
}

if ($events_id > 0) {
    // 2. Verificar se o evento existe e tem stock disponível
    $stmtEvent = $pdo->prepare("SELECT id, title, stock, date FROM events WHERE id = ?");
    $stmtEvent->execute([$events_id]);
    $evento = $stmtEvent->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Evento não encontrado.']);
            exit();
        }
        header("Location: ../events/index.php");
        exit();
    }

    // 2.1 Bloquear adição caso o evento já tenha ocorrido
    $hoje = date('Y-m-d');
    if ($evento['date'] < $hoje) {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Este evento já foi realizado. As inscrições estão encerradas.']);
            exit();
        }
        $_SESSION['erro_carrinho'] = "Este evento já foi realizado. Não é possível adquirir bilhetes.";
        header("Location: cart.php");
        exit();
    }

    $stockDisponivel = (int)$evento['stock'];
    if ($stockDisponivel <= 0) {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Este evento encontra-se esgotado.']);
            exit();
        }
        $_SESSION['erro_carrinho'] = "O evento selecionado já se encontra esgotado.";
        header("Location: cart.php");
        exit();
    }

    // 3. Verificar se este bilhete já está no carrinho deste utilizador
    $stmtCheck = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND events_id = ?");
    $stmtCheck->execute([$user_id, $events_id]);
    $itemExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    $qtdJaNoCarrinho = $itemExistente ? (int)$itemExistente['quantity'] : 0;
    $qtdTotalPretendida = $qtdJaNoCarrinho + $quantity;

    // Se a soma ultrapassar o stock real disponível, bloqueia ou limita
    if ($qtdJaNoCarrinho >= $stockDisponivel) {
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => "Já atingiu o limite máximo em stock ({$stockDisponivel} un.) no seu carrinho."
            ]);
            exit();
        }
        $_SESSION['erro_carrinho'] = "Já tem a quantidade máxima disponível ({$stockDisponivel} un.) deste evento no seu carrinho.";
        header("Location: cart.php");
        exit();
    }

    if ($itemExistente) {
        $novaQtd = min($qtdTotalPretendida, $stockDisponivel);
        $stmtUpdate = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmtUpdate->execute([$novaQtd, $itemExistente['id']]);
    } else {
        $novaQtd = min($quantity, $stockDisponivel);
        $insert = $pdo->prepare("INSERT INTO cart (user_id, events_id, quantity) VALUES (?, ?, ?)");
        $insert->execute([$user_id, $events_id, $novaQtd]);
    }
}

// 4. Resposta AJAX com contagem atualizada do carrinho
if (isset($_GET['ajax'])) {
    $stmtCount = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmtCount->execute([$user_id]);
    $totalCarrinho = (int)$stmtCount->fetchColumn();

    header('Content-Type: application/json');
    echo json_encode([
        'sucesso'       => true,
        'totalCarrinho' => $totalCarrinho
    ]);
    exit();
}

// Se foi clique tradicional direto
header("Location: cart.php");
exit();
?>
