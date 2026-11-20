<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Eventos e Festivais 2026 | LiveEvents</title>
    <meta name="description" content="Pesquise eventos por título, data, categoria ou cidade. Encontre festivais de música, conferências tech e espetáculos em Portugal.">
    <meta name="keywords" content="pesquisa de eventos, agenda cultural, festivais lisboa, eventos porto, bilhetes festivais">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../assets/images/icons8-flash-64.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Estilos Tradicionais (Sem var()) -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include '../includes/header_event.php'; ?>


    <!-- Conteúdo Principal -->
    <main class="py-4">
        <div class="container">
            <div class="row g-4">
                <!-- Barra Lateral de Filtros -->
                <aside class="col-lg-3">
                    <form action="index.php" id="formFiltros" method="GET">
                        <div class="filter-sidebar-box mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><i class="fa-solid fa-sliders me-2 text-danger"></i>Filtros</h5>
                                <a href="index.php" class="small text-danger text-decoration-none">Limpar</a>
                            </div>

                            <!-- Filtro: Pesquisa por Título -->
                            <div class="mb-3">
                                <label class="form-label-custom fw-semibold mb-1">Pesquisar por Título</label>
                                <div class="input-group">
                                    <input type="text" name="titulo" id="filtroTitulo" class="form-control form-control-custom" placeholder="Ex: Electric Pulse...">
                                    <button class="btn btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                                </div>
                            </div>

                            <!-- Filtro: Intervalo de Datas -->
                            <div class="mb-4">
                                <label class="form-label-custom fw-semibold mb-1">
                                    <i class="fa-regular fa-calendar-days text-danger me-1"></i>Datas dos Eventos
                                </label>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <small class="text-muted d-block mb-1">A partir de:</small>
                                        <input type="date" name="data_inicio" id="filtroDataInicio" class="form-control form-control-custom form-control-sm" placeholder="dd/mm/aaaa">
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block mb-1">Até à data (opcional):</small>
                                        <input type="date" name="data_fim" id="filtroDataFim" class="form-control form-control-custom form-control-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Filtro: Categoria -->
                            <div class="mb-4">
                                        <p class="filter-title">Categoria</p>
                                <div class="form-check mb-2">
                                        <input class="form-check-input filtro-categoria" type="checkbox" name="categoria[]" value="Festival" id="catFest">
                                        <label class="form-check-label small" for="catFest">Festivais de Música</label>
                                </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input filtro-categoria" type="checkbox" name="categoria[]" value="Tecnologia" id="catTech">
                                        <label class="form-check-label small" for="catTech">Tecnologia & IA</label>
                                    </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input filtro-categoria" type="checkbox" name="categoria[]" value="Rock" id="catRock">
                                    <label class="form-check-label small" for="catRock">Rock & Alternativo</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input filtro-categoria" type="checkbox" name="categoria[]" value="Jazz" id="catJazz">
                                    <label class="form-check-label small" for="catJazz">Jazz, Soul & Clássica</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input filtro-categoria" type="checkbox" name="categoria[]" value="Comédia" id="catTeatro">
                                    <label class="form-check-label small" for="catTeatro">Teatro & Stand-Up</label>
                                </div>
                            </div>

                            <!-- Filtro: Cidade / Região -->
                            <div class="mb-4">
                                <p class="filter-title">Localidade</p>
                                <select id="filtroLocalidade" name="cidade" class="form-select form-select-custom">
                                    <option value="" selected>Todas as Localidades</option>
                                    <option value="lisboa">Lisboa & Vale do Tejo</option>
                                    <option value="porto">Porto & Norte</option>
                                    <option value="coimbra">Coimbra & Centro</option>
                                    <option value="algarve">Algarve (Faro / Albufeira)</option>
                                </select>
                            </div>

                            <!-- Filtro: Faixa de Preço -->
                            <div class="mb-4">
                                <p class="filter-title">Preço Máximo: <span id="precoLabel">150 €</span></p>
<input type="range" id="filtroPreco" name="preco_max" class="form-range" min="0" max="250" step="5" value="250" oninput="document.getElementById('precoLabel').innerText = this.value + ' €'">
                            </div>

                            <button type="submit" class="btn btn-dark w-100 fw-bold py-2">
                                Aplicar Filtros
                            </button>
                        </div>
                    </form>
                </aside>

                <!-- Grade de Eventos -->
                <section class="col-lg-9">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <p class="text-muted mb-0">A mostrar <strong id="totalEventos">6 eventos</strong> disponíveis</p>
                    </div>
                    <div class="row g-4" id="cardsEventos">
    <!-- O refresh_event.js vai preencher aqui automaticamente com os cards do search.php -->
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="text-muted mt-2">A carregar eventos...</p>
    </div>
</div>
                </section>
            </div>
        </div>
    </main>

    <!-- Rodapé Unificado -->
<?php include '../includes/footer_event.php'; ?>
    <!-- Bootstrap 5.3 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
<script src="../assets/js/refresh_event.js"></script>
</html>
