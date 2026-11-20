document.addEventListener('DOMContentLoaded', function () {
    const formFiltros = document.getElementById('formFiltros');
    const cardsEventos = document.getElementById('cardsEventos');
    const totalEventos = document.getElementById('totalEventos');

    // Função que envia os filtros para o search.php via Fetch
    function aplicarFiltrosAjax() {
        if (!formFiltros || !cardsEventos) return;

        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);

        // Feedback visual de carregamento
        cardsEventos.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-danger" role="status"></div>
                <p class="text-muted mt-2">A filtrar eventos...</p>
            </div>
        `;

        // Chama o search.php sem recarregar a tela
        fetch(`search.php?${params.toString()}`)
            .then(res => res.text())
            .then(html => {
                cardsEventos.innerHTML = html;
                
                // Atualiza o contador de eventos encontrados
                const totalCards = cardsEventos.querySelectorAll('.event-card').length;
                if (totalEventos) {
                    totalEventos.innerText = totalCards + (totalCards === 1 ? ' evento' : ' eventos');
                }
            })
            .catch(err => {
                console.error('Erro nos filtros:', err);
                cardsEventos.innerHTML = `<div class="col-12 text-center text-danger py-4">Erro ao carregar eventos. Tente novamente.</div>`;
            });
    }

    // 1. Ao clicar em "Aplicar Filtros" ou submeter:
    if (formFiltros) {
        formFiltros.addEventListener('submit', function (e) {
            e.preventDefault();
            aplicarFiltrosAjax();
        });
    }

    // 2. Atualizar automaticamente ao mudar Datas ou Localidade:
    const filtroDataInicio = document.getElementById('filtroDataInicio');
    const filtroDataFim = document.getElementById('filtroDataFim');
    const filtroData = document.getElementById('filtroData');
    const filtroLocalidade = document.getElementById('filtroLocalidade');

    if (filtroDataInicio) filtroDataInicio.addEventListener('change', aplicarFiltrosAjax);
    if (filtroDataFim) filtroDataFim.addEventListener('change', aplicarFiltrosAjax);
    if (filtroData) filtroData.addEventListener('change', aplicarFiltrosAjax);
    if (filtroLocalidade) filtroLocalidade.addEventListener('change', aplicarFiltrosAjax);

    // 3. Atualizar automaticamente ao marcar/desmarcar Categorias:
    document.querySelectorAll('.filtro-categoria').forEach(cb => {
        cb.addEventListener('change', aplicarFiltrosAjax);
    });

    // 4. Atualizar ao soltar o seletor de Preço:
    const filtroPreco = document.getElementById('filtroPreco');
    if (filtroPreco) filtroPreco.addEventListener('change', aplicarFiltrosAjax);

    // 5. Atualizar ao digitar o Título (com delay de 350ms):
    const filtroTitulo = document.getElementById('filtroTitulo');
    let timer;
    if (filtroTitulo) {
        filtroTitulo.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(aplicarFiltrosAjax, 350);
        });
    }

    // Carregar os eventos iniciais logo ao abrir a página
    aplicarFiltrosAjax();
});

// Adiciona o bilhete ao carrinho via AJAX sem mudar de página
function adicionarAoCarrinhoAjax(eventoId, btnElement) {
    var originalHtml = '';
    if (btnElement) {
        originalHtml = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }

    fetch('../cart/add.php?id=' + eventoId + '&ajax=1')
        .then(res => res.json())
        .then(data => {
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = originalHtml;
            }

            if (data.sucesso) {
                // Atualiza o número de itens na badge do cabeçalho
                var badges = document.querySelectorAll('.btn-nav-cart .badge');
                badges.forEach(function(badge) {
                    badge.innerText = data.totalCarrinho;
                });

                // Toast ou feedback visual
                mostrarNotificacaoCarrinho('Bilhete adicionado ao carrinho! Total: ' + data.totalCarrinho);
            } else if (data.redirecionar) {
                window.location.href = data.redirecionar;
            } else if (data.mensagem) {
                alert(data.mensagem);
            }
        })
        .catch(err => {
            console.error('Erro ao adicionar:', err);
            if (btnElement) {
                btnElement.disabled = false;
                btnElement.innerHTML = originalHtml;
            }
            window.location.href = '../cart/add.php?id=' + eventoId;
        });
}

// Notificação flutuante suave (Toast)
function mostrarNotificacaoCarrinho(mensagem) {
    var toast = document.getElementById('toastCarrinho');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastCarrinho';
        toast.style.position = 'fixed';
        toast.style.bottom = '25px';
        toast.style.right = '25px';
        toast.style.backgroundColor = '#111827';
        toast.style.color = '#ffffff';
        toast.style.padding = '14px 22px';
        toast.style.borderRadius = '30px';
        toast.style.boxShadow = '0 6px 20px rgba(0,0,0,0.3)';
        toast.style.zIndex = '9999';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '10px';
        toast.style.fontSize = '14px';
        toast.style.fontWeight = '600';
        toast.style.transition = 'all 0.3s ease';
        document.body.appendChild(toast);
    }
    toast.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #2ed573; font-size: 18px;"></i> ' + mensagem;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 3000);
}
