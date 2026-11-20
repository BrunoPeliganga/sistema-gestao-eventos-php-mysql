<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 1. Verificação de autenticação
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['id'];
$user_role = $_SESSION['role'] ?? 'user';
$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($purchase_id <= 0) {
    echo "Identificador de bilhete inválido.";
    exit();
}

// 2. Buscar exclusivamente os dados deste bilhete específico
$stmt = $pdo->prepare("
    SELECT 
        p.*, 
        e.title, 
        e.description,
        e.date, 
        e.time, 
        e.location, 
        e.category, 
        e.price AS unit_price,
        e.image,
        u.username,
        u.email
    FROM purchases p 
    INNER JOIN events e ON p.events_id = e.id 
    INNER JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND (p.user_id = ? OR ? = 'admin')
");
$stmt->execute([$purchase_id, $user_id, $user_role]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "Bilhete não encontrado ou não tem permissão para aceder a este documento.";
    exit();
}

$status = strtolower($ticket['status'] ?? 'pending');
$isCompleted = ($status === 'completed' || $status === 'pago');
$ticketRef = '#LEV-' . str_pad((string)$ticket['id'], 5, '0', STR_PAD_LEFT);
$eventImage = getCategoryEventImage($ticket['category'] ?? 'Festival', $ticket['image'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilhete Oficial <?= $ticketRef ?> - <?= htmlspecialchars($ticket['title']) ?> | LiveEvents</title>
    <link rel="icon" href="../assets/images/icons8-flash-64.png">

    <!-- Bootstrap 5, FontAwesome e Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Open Sans', sans-serif;
            color: #1e293b;
            padding: 30px 15px;
        }

        /* Barra de Ações Superior (Não sai na impressão) */
        .action-toolbar {
            max-width: 820px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        /* Contentor do Bilhete Oficial */
        .ticket-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(0,0,0,0.08);
            border: 2px solid #e2e8f0;
            position: relative;
        }

        /* Banner Topo do Bilhete */
        .ticket-header-banner {
            background: linear-gradient(135deg, #111827 0%, #1e293b 100%);
            color: #ffffff;
            padding: 24px 30px;
            border-bottom: 4px solid #ff4757;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .ticket-logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .ticket-logo span {
            color: #ff4757;
        }

        .ticket-badge-ref {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-family: monospace;
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 1px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        /* Corpo do Bilhete */
        .ticket-body-content {
            padding: 30px;
        }

        .ticket-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        /* Caixa de Destaque da Data e Recinto */
        .event-highlight-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #ff4757;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 25px;
        }

        /* Grelha de Informações */
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .info-cell {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
        }

        .info-cell .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            display: block;
            margin-bottom: 3px;
        }

        .info-cell .value {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Área de Validação e QR Code */
        .qr-section {
            background: #fafbfc;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 25px;
        }

        .qr-code-box {
            background: #ffffff;
            padding: 12px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            margin-bottom: 10px;
        }

        /* Rodapé de Segurança */
        .ticket-security-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* ESTILOS DE IMPRESSÃO LIMPOS */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .action-toolbar {
                display: none !important;
            }
            .ticket-sheet {
                box-shadow: none !important;
                border: 2px solid #000000 !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
            }
            .ticket-header-banner {
                background: #111827 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA DE AÇÕES SUPERIOR -->
    <div class="action-toolbar">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-ticket text-danger fs-5"></i>
            <span class="fw-bold">Visualização do Bilhete Digital Oficial</span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-danger btn-sm fw-bold px-3 rounded-pill shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Imprimir Bilhete
            </button>
            <button onclick="window.close()" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                <i class="fa-solid fa-xmark me-1"></i> Fechar Aba
            </button>
        </div>
    </div>

    <!-- FOLHA DO BILHETE DIGITAL OFICIAL -->
    <article class="ticket-sheet">
        <!-- Banner Topo -->
        <div class="ticket-header-banner">
            <div>
                <div class="ticket-logo">
                    <i class="fa-solid fa-bolt me-1"></i>Live<span>Events</span>
                </div>
                <small class="text-white-50">Comprovativo Oficial de Entrada Digital</small>
            </div>
            <div class="text-end">
                <span class="ticket-badge-ref"><?= $ticketRef ?></span>
                <div class="mt-2">
                    <?php if ($isCompleted): ?>
                        <span class="badge bg-success px-3 py-2 text-uppercase fw-bold">
                            <i class="fa-solid fa-circle-check me-1"></i> Bilhete Válido & Confirmado
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark px-3 py-2 text-uppercase fw-bold">
                            <i class="fa-solid fa-clock me-1"></i> Pagamento Pendente
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Conteúdo do Bilhete -->
        <div class="ticket-body-content">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-danger text-uppercase px-3 py-2"><?= htmlspecialchars($ticket['category'] ?? 'Festival') ?></span>
                <span class="badge bg-dark text-uppercase px-3 py-2">Bilheteira Oficial 2026</span>
            </div>

            <h1 class="ticket-title">
                <?= htmlspecialchars($ticket['title']) ?>
            </h1>

            <!-- Destaque: Data, Hora e Local -->
            <div class="event-highlight-box">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">
                            <i class="fa-regular fa-calendar-days text-danger me-1"></i> Data e Horário
                        </small>
                        <strong class="fs-5 text-dark">
                            <?= date('d/m/Y', strtotime($ticket['date'])) ?> às <?= date('H:i', strtotime($ticket['time'])) ?>
                        </strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-bold d-block mb-1">
                            <i class="fa-solid fa-location-dot text-danger me-1"></i> Recinto / Localização
                        </small>
                        <strong class="fs-5 text-dark">
                            <?= htmlspecialchars($ticket['location']) ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Grelha de Informações Detalhadas do Comprador e Pagamento -->
            <div class="ticket-grid">
                <div class="info-cell">
                    <span class="label">Titular do Bilhete</span>
                    <span class="value"><?= htmlspecialchars($ticket['username']) ?></span>
                </div>
                <div class="info-cell">
                    <span class="label">E-mail Registado</span>
                    <span class="value"><?= htmlspecialchars($ticket['email']) ?></span>
                </div>
                <div class="info-cell">
                    <span class="label">Quantidade de Entradas</span>
                    <span class="value"><?= (int)$ticket['quantity'] ?> Bilhete(s)</span>
                </div>
                <div class="info-cell">
                    <span class="label">Valor Total Pago</span>
                    <span class="value text-danger"><?= number_format((float)$ticket['total_price'], 2, ',', '.') ?> €</span>
                </div>
                <div class="info-cell">
                    <span class="label">Método de Pagamento</span>
                    <span class="value"><?= htmlspecialchars($ticket['payment_method'] ?? 'MB WAY') ?></span>
                </div>
                <div class="info-cell">
                    <span class="label">Data de Emissão</span>
                    <span class="value"><?= date('d/m/Y \à\s H:i', strtotime($ticket['purchase_date'])) ?></span>
                </div>
            </div>

            <!-- Validação com QR Code de Alta Resolução -->
            <div class="qr-section">
                <div class="qr-code-box">
                    <svg width="150" height="150" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <rect width="100" height="100" fill="#ffffff" />
                        <rect x="5" y="5" width="28" height="28" fill="#111827" />
                        <rect x="9" y="9" width="20" height="20" fill="#ffffff" />
                        <rect x="13" y="13" width="12" height="12" fill="#111827" />

                        <rect x="67" y="5" width="28" height="28" fill="#111827" />
                        <rect x="71" y="9" width="20" height="20" fill="#ffffff" />
                        <rect x="75" y="13" width="12" height="12" fill="#111827" />

                        <rect x="5" y="67" width="28" height="28" fill="#111827" />
                        <rect x="9" y="71" width="20" height="20" fill="#ffffff" />
                        <rect x="13" y="75" width="12" height="12" fill="#111827" />

                        <rect x="38" y="10" width="8" height="8" fill="#111827" />
                        <rect x="50" y="10" width="10" height="8" fill="#111827" />
                        <rect x="38" y="24" width="22" height="8" fill="#111827" />
                        <rect x="10" y="38" width="22" height="8" fill="#111827" />
                        <rect x="38" y="38" width="10" height="10" fill="#ff4757" />
                        <rect x="54" y="38" width="12" height="10" fill="#111827" />
                        <rect x="72" y="38" width="18" height="8" fill="#111827" />
                        <rect x="38" y="54" width="22" height="8" fill="#111827" />
                        <rect x="66" y="54" width="14" height="8" fill="#111827" />
                        <rect x="38" y="68" width="8" height="22" fill="#111827" />
                        <rect x="52" y="68" width="18" height="10" fill="#111827" />
                        <rect x="76" y="68" width="14" height="22" fill="#111827" />
                        <rect x="52" y="82" width="18" height="8" fill="#111827" />
                    </svg>
                </div>
                <div class="fw-bold font-monospace text-dark fs-6"><?= $ticketRef ?></div>
                <small class="text-muted d-block mt-1">Apresente este código QR à entrada do recinto para validação eletrónica de acesso.</small>
            </div>

            <!-- Avisos e Informações de Segurança -->
            <div class="ticket-security-footer">
                <div class="row g-2 align-items-center">
                    <div class="col-sm-8">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i>
                        <strong>Documento Oficial e Pessoal:</strong> Entrada sujeita às regras de admissão da sala/recinto. É obrigatória a apresentação de documento de identificação com foto.
                    </div>
                    <div class="col-sm-4 text-sm-end text-muted font-monospace small">
                        SEC-HASH: <?= strtoupper(md5($ticket['id'] . $ticket['purchase_date'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </article>

</body>
</html>
