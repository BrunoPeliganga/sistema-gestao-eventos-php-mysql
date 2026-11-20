<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 1. VERIFICAR SE O UTILIZADOR ESTÁ AUTENTICADO
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['id'];
$erroPagamento = null;
$itens = [];
$totalPagar = 0.00;

// 2. FUNÇÃO PARA FORMATAR PREÇOS
function formatarPreco($valor) {
    return number_format((float)$valor, 2, ',', '.') . ' €';
}

// 3. PROCESSAR A COMPRA (BACKEND)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pagamento'])) {

    // Validar método de pagamento
    $metodosPermitidos = [
        'mbway'      => 'MB WAY',
        'multibanco' => 'Multibanco',
        'cartao'     => 'Cartão'
    ];
    $metodoEscolhido = $_POST['metodo_pagamento'] ?? 'mbway';
    if (!isset($metodosPermitidos[$metodoEscolhido])) {
        $metodoEscolhido = 'mbway';
    }

    $metodo = $metodosPermitidos[$metodoEscolhido];
    try {
        // Iniciar transação segura
        $pdo->beginTransaction();

        // 4. BUSCAR O CARRINHO ATUAL E BLOQUEAR AS LINHAS
        $stmtCartCheckout = $pdo->prepare("
            SELECT
                c.id AS cart_id,
                c.quantity,
                c.events_id,
                e.title,
                e.price,
                e.date,
                e.stock
            FROM cart c
            INNER JOIN events e ON c.events_id = e.id
            WHERE c.user_id = ?
            ORDER BY c.events_id ASC
            FOR UPDATE
        ");
        $stmtCartCheckout->execute([$user_id]);
        $itensCompra = $stmtCartCheckout->fetchAll(PDO::FETCH_ASSOC);

        // Verificar se o carrinho tem artigos
        if (empty($itensCompra)) {
            throw new Exception("O teu carrinho está vazio. Não é possível finalizar a compra.");
        }

        // PREPARAR AS QUERIES DE STOCK E COMPRA
        $stmtStock = $pdo->prepare("
            SELECT id, title, stock, price
            FROM events
            WHERE id = ?
            FOR UPDATE
        ");

        // Inserir compra na tabela purchases com status 'pending'
        $stmtInsert = $pdo->prepare("
            INSERT INTO purchases
            (
                user_id,
                events_id,
                quantity,
                total_price,
                payment_method,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        // Descontar stock
        $stmtBaixaStock = $pdo->prepare("
            UPDATE events
            SET stock = stock - ?
            WHERE id = ?
            AND stock >= ?
        ");

        $totalCompra = 0.00;
        foreach ($itensCompra as $item) {
            $event_id   = (int)$item['events_id'];
            $quantidade = (int)$item['quantity'];

            if ($quantidade < 1) {
                throw new Exception("Foi encontrada uma quantidade inválida no carrinho.");
            }

            // PASSO 1: BLOQUEAR A LINHA DO EVENTO
            $stmtStock->execute([$event_id]);
            $evento = $stmtStock->fetch(PDO::FETCH_ASSOC);
            if (!$evento) {
                throw new Exception("O evento selecionado já não se encontra disponível.");
            }

            // PASSO 2: VERIFICAR O STOCK DISPONÍVEL
            $stockDisponivel = (int)$evento['stock'];
            if ($stockDisponivel < $quantidade) {
                throw new Exception(
                    "Stock insuficiente para o evento \"" .
                    $evento['title'] .
                    "\". Solicitaste " .
                    $quantidade .
                    " bilhete(s), mas apenas existem " .
                    $stockDisponivel .
                    " disponível(eis)."
                );
            }

            // PASSO 3: CALCULAR O PREÇO REAL DO BANCO
            $precoUnitario = (float)$evento['price'];
            $subtotalItem = round($precoUnitario * $quantidade, 2);

            // PASSO 4: REGISTAR A COMPRA (STATUS 'pending')
            $stmtInsert->execute([
                $user_id,
                $event_id,
                $quantidade,
                $subtotalItem,
                $metodo
            ]);

            // PASSO 5: DESCONTAR O STOCK
            $stmtBaixaStock->execute([
                $quantidade,
                $event_id,
                $quantidade
            ]);

            if ($stmtBaixaStock->rowCount() !== 1) {
                throw new Exception(
                    "Não foi possível reservar os bilhetes de \"" .
                    $evento['title'] .
                    "\". Por favor, tenta novamente."
                );
            }

            $totalCompra += $subtotalItem;
        }

        // LIMPAR O CARRINHO
        $stmtClear = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmtClear->execute([$user_id]);

        // CONFIRMAR A TRANSAÇÃO
        $pdo->commit();

        // Redireciona para index.php com mensagem de sucesso
        $_SESSION['compra_sucesso'] = "A sua compra de bilhetes foi registada com sucesso! O pedido encontra-se com estado Pendente de aprovação.";
        header("Location: ../index.php");
        exit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erroPagamento = $e->getMessage();
    }
}

// 5. BUSCAR OS ITENS PARA MOSTRAR NO CHECKOUT
$stmtCart = $pdo->prepare("
    SELECT
        c.id AS cart_id,
        c.quantity,
        c.events_id,
        e.title,
        e.price,
        e.date,
        e.stock
    FROM cart c
    INNER JOIN events e ON c.events_id = e.id
    WHERE c.user_id = ?
    ORDER BY c.id ASC
");
$stmtCart->execute([$user_id]);
$itens = $stmtCart->fetchAll(PDO::FETCH_ASSOC);

// Se o carrinho estiver vazio, voltar ao catálogo
if (empty($itens)) {
    header("Location: ../events/index.php");
    exit();
}

// CALCULAR O TOTAL REAL DO CARRINHO
$totalPagar = 0.00;
foreach ($itens as $item) {
    $preco = (float)$item['price'];
    $quantidade = (int)$item['quantity'];
    $totalPagar += $preco * $quantidade;
}
$totalPagar = round($totalPagar, 2);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalização de Compra | LiveEvents 2026</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff8f8 !important;
        }
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 4px;
            font-weight: 600;
        }
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body class="bg-light">

<!-- MENU SUPERIOR -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fa-solid fa-bolt me-2"></i>Live<span>Events</span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-white small d-none d-md-inline">
                <i class="fa-solid fa-shield-halved text-success me-1"></i>
                Ambiente Seguro de Pagamento
            </span>

            <a href="../cart/cart.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>
                Voltar ao Carrinho
            </a>
        </div>
    </div>
</nav>

<!-- CABEÇALHO -->
<header class="page-header py-4 bg-white border-bottom">
    <div class="container">
        <h1 class="page-title mb-1 fw-bold text-dark">
            Finalização de Compra
        </h1>
        <p class="page-breadcrumb mb-0 text-muted">
            <a href="../index.php">Início</a> /
            <a href="../cart/cart.php">Carrinho</a> /
            Checkout Seguro
        </p>
    </div>
</header>

<!-- CONTEÚDO PRINCIPAL -->
<main class="py-5">
    <div class="container">

        <!-- MENSAGEM DE ERRO DO BACKEND -->
        <?php if ($erroPagamento): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2 fs-5 align-middle"></i>
                <strong>Não foi possível concluir a compra:</strong>
                <br>
                <?= htmlspecialchars($erroPagamento, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ETAPAS DO CHECKOUT -->
        <div class="checkout-steps mb-5">
            <div class="step-item text-success">
                <span class="step-number bg-success text-white">
                    <i class="fa-solid fa-check"></i>
                </span>
                <span>1. Carrinho</span>
            </div>

            <div class="text-muted">
                <i class="fa-solid fa-chevron-right"></i>
            </div>

            <div class="step-item active">
                <span class="step-number">2</span>
                <span>2. Dados & Pagamento</span>
            </div>

            <div class="text-muted">
                <i class="fa-solid fa-chevron-right"></i>
            </div>

            <div class="step-item">
                <span class="step-number">3</span>
                <span>3. Bilhete Digital</span>
            </div>
        </div>

        <!-- FORMULÁRIO COM VALIDAÇÃO JS -->
        <form method="POST" action="checkout.php" id="formCheckout" novalidate>
            <input type="hidden" name="metodo_pagamento" id="metodoPagamento" value="mbway">

            <div class="row g-5">
                <!-- COLUNA ESQUERDA -->
                <div class="col-lg-7">
                    <!-- DADOS DO COMPRADOR -->
                    <div class="form-card mb-4 bg-white p-4 rounded-4 shadow-sm border">
                        <h4 class="fw-bold mb-3">
                            <i class="fa-solid fa-user me-2 text-danger"></i>
                            1. Dados do Comprador
                        </h4>
                        <p class="text-muted small mb-4">
                            Os bilhetes digitais e comprovativos de pagamento serão emitidos para este contacto:
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-custom fw-semibold" for="inputNome">
                                    Nome Completo *
                                </label>
                                <input type="text" name="nome" id="inputNome" class="form-control form-control-custom" placeholder="Ex: Rodrigo Pereira" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                                <div class="invalid-feedback" id="feedbackNome">
                                    Insira um nome válido (apenas letras, mínimo 3 caracteres).
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom fw-semibold" for="inputEmail">
                                    E-mail para Receber Bilhetes *
                                </label>
                                <input type="email" name="email" id="inputEmail" class="form-control form-control-custom" placeholder="rodrigo@exemplo.com" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                                <div class="invalid-feedback" id="feedbackEmail">
                                    Insira um endereço de e-mail válido.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom fw-semibold" for="inputTelemovel">
                                    Telemóvel (9 dígitos) *
                                </label>
                                <input type="tel" name="telemovel" id="inputTelemovel" class="form-control form-control-custom" placeholder="912 345 678" maxlength="9" required>
                                <div class="invalid-feedback" id="feedbackTelemovel">
                                    Insira um telemóvel válido com exatamente 9 algarismos numéricos.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom fw-semibold" for="inputNif">
                                    NIF (Opcional - 9 dígitos)
                                </label>
                                <input type="text" name="nif" id="inputNif" class="form-control form-control-custom" placeholder="999 999 990" maxlength="9">
                                <div class="invalid-feedback" id="feedbackNif">
                                    O NIF deve conter exatamente 9 algarismos numéricos.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MÉTODO DE PAGAMENTO -->
                    <div class="form-card bg-white p-4 rounded-4 shadow-sm border">
                        <h4 class="fw-bold mb-3">
                            <i class="fa-solid fa-credit-card me-2 text-danger"></i>
                            2. Método de Pagamento
                        </h4>

                        <p class="text-muted small mb-4">
                            Escolha a forma de pagamento oficial e segura:
                        </p>

                        <div class="row g-3 mb-4">
                            <!-- MB WAY -->
                            <div class="col-md-4">
                                <div class="payment-method-card active" onclick="selecionarMetodo('mbway', this)">
                                    <i class="fa-solid fa-mobile-screen fs-3 text-danger"></i>
                                    <div>
                                        <strong class="d-block text-dark">MB WAY</strong>
                                        <small class="text-muted">Aprovação imediata</small>
                                    </div>
                                </div>
                            </div>

                            <!-- MULTIBANCO -->
                            <div class="col-md-4">
                                <div class="payment-method-card" onclick="selecionarMetodo('multibanco', this)">
                                    <i class="fa-solid fa-receipt fs-3 text-danger"></i>
                                    <div>
                                        <strong class="d-block text-dark">Multibanco</strong>
                                        <small class="text-muted">Entidade e Ref.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- CARTÃO -->
                            <div class="col-md-4">
                                <div class="payment-method-card" onclick="selecionarMetodo('cartao', this)">
                                    <i class="fa-regular fa-credit-card fs-3 text-danger"></i>
                                    <div>
                                        <strong class="d-block text-dark">Cartão</strong>
                                        <small class="text-muted">Visa / Mastercard</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CAMPOS MB WAY -->
                        <div id="campoMbway" class="p-3 bg-light rounded-3 border mb-3">
                            <label class="form-label-custom fw-semibold" for="inputMbway">Número de Telemóvel MB WAY *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">+351</span>
                                <input type="tel" name="telemovel_mbway" id="inputMbway" class="form-control form-control-custom bg-white" placeholder="912 345 678" maxlength="9">
                            </div>
                            <div class="invalid-feedback" id="feedbackMbway">
                                Insira um número MB WAY válido (9 dígitos numéricos).
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="fa-solid fa-bell me-1"></i>Receberá uma notificação na aplicação MB WAY para validar o pagamento.
                            </small>
                        </div>

                        <!-- CAMPOS MULTIBANCO -->
                        <div id="campoMultibanco" class="p-3 bg-light rounded-3 border mb-3 d-none">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-solid fa-circle-info text-primary fs-3"></i>
                                <p class="small text-muted mb-0">
                                    Após clicar em pagar, serão gerados os dados de Entidade, Referência e Montante com validade de 24 horas.
                                </p>
                            </div>
                        </div>

                        <!-- CAMPOS CARTÃO -->
                        <div id="campoCartao" class="p-3 bg-light rounded-3 border mb-3 d-none">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label-custom fw-semibold" for="inputCartaoNumero">Número do Cartão *</label>
                                    <input type="text" name="numero_cartao" id="inputCartaoNumero" class="form-control form-control-custom bg-white" placeholder="0000 0000 0000 0000" maxlength="19">
                                    <div class="invalid-feedback" id="feedbackCartaoNumero">
                                        Insira um número de cartão válido com 16 algarismos.
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label-custom fw-semibold" for="inputCartaoValidade">Validade (MM/AA) *</label>
                                    <input type="text" name="validade_cartao" id="inputCartaoValidade" class="form-control form-control-custom bg-white" placeholder="MM/AA" maxlength="5">
                                    <div class="invalid-feedback" id="feedbackCartaoValidade">
                                        Data de validade inválida ou anterior à data atual.
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label-custom fw-semibold" for="inputCartaoCvv">CVV / CVC (3 dígitos) *</label>
                                    <input type="text" name="cvv_cartao" id="inputCartaoCvv" class="form-control form-control-custom bg-white" placeholder="123" maxlength="3">
                                    <div class="invalid-feedback" id="feedbackCartaoCvv">
                                        O código CVV deve ter exatamente 3 algarismos numéricos.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TERMOS -->
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="aceitoTermos" required>
                            <label class="form-check-label text-muted small" for="aceitoTermos">
                                Confirmo que os dados dos participantes estão corretos e aceito as condições gerais de venda e política de privacidade da LiveEvents.
                            </label>
                            <div class="invalid-feedback" id="feedbackTermos">
                                Deve aceitar os termos e condições para prosseguir.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: RESUMO DINÂMICO DOS ITENS DO CARRINHO -->
                <div class="col-lg-5">
                    <div class="order-summary-box position-sticky bg-white p-4 rounded-4 shadow-sm border" style="top: 90px;">
                        <h4 class="fw-bold mb-3">
                            <i class="fa-solid fa-ticket text-danger me-2"></i>Resumo da Compra
                        </h4>

                        <!-- EVENTOS REAIS DO CARRINHO -->
                        <div class="mb-3">
                            <?php foreach ($itens as $item): ?>
                                <?php
                                    $quantidade = (int)$item['quantity'];
                                    $precoUnitario = (float)$item['price'];
                                    $subtotalItem = $precoUnitario * $quantidade;
                                    $dataEvento = !empty($item['date']) ? date('d/m/Y', strtotime($item['date'])) : 'Data a anunciar';
                                ?>
                                <div class="d-flex justify-content-between align-items-start py-3 border-bottom">
                                    <div class="pe-2">
                                        <strong class="d-block text-dark">
                                            <?= $quantidade ?>x <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </strong>
                                        <small class="text-muted d-block">
                                            <i class="fa-regular fa-calendar me-1"></i><?= $dataEvento ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= formatarPreco($precoUnitario) ?> cada bilhete
                                        </small>
                                    </div>
                                    <span class="fw-bold text-dark text-nowrap">
                                        <?= formatarPreco($subtotalItem) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- TOTAL PARCIAL -->
                        <div class="summary-row d-flex justify-content-between py-2">
                            <span class="text-muted">Total Parcial</span>
                            <strong><?= formatarPreco($totalPagar) ?></strong>
                        </div>

                        <!-- CUSTOS DE EMISSÃO -->
                        <div class="summary-row d-flex justify-content-between py-2">
                            <span class="text-muted">Custos de Emissão Digital</span>
                            <span class="text-success fw-bold">Grátis (0,00 €)</span>
                        </div>

                        <!-- TOTAL FINAL -->
                        <div class="summary-total border-top pt-3 pb-3 my-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5 text-dark">Total a Pagar</span>
                            <span class="text-danger fw-bold fs-4">
                                <?= formatarPreco($totalPagar) ?>
                            </span>
                        </div>

                        <!-- BOTÃO CONFIRMAR E PAGAR -->
                        <button
                            type="submit"
                            name="confirmar_pagamento"
                            value="1"
                            class="btn btn-danger py-3 w-100 fw-bold rounded-3 shadow-sm fs-5">
                            <i class="fa-solid fa-lock me-2"></i> Pagar <?= formatarPreco($totalPagar) ?>
                        </button>

                        <div class="mt-4 text-center">
                            <small class="text-muted d-block mb-1">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>
                                Garantia de devolução em caso de adiamento ou cancelamento
                            </small>
                            <small class="text-muted">
                                <i class="fa-solid fa-headset me-1"></i>
                                Linha de Apoio: +351 210 000 000
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<!-- RODAPÉ UNIFICADO -->
<?php include '../includes/footer_checkout.php'; ?>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/checkout.js" ></script>
</body>
</html>
