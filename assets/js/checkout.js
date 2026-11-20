
// ALTERNAR MÉTODO DE PAGAMENTO
function selecionarMetodo(metodo, elemento) {
    document.getElementById('metodoPagamento').value = metodo;

    var cards = document.querySelectorAll('.payment-method-card');
    cards.forEach(function(card) {
        card.classList.remove('active');
    });

    elemento.classList.add('active');

    document.getElementById('campoMbway').classList.add('d-none');
    document.getElementById('campoMultibanco').classList.add('d-none');
    document.getElementById('campoCartao').classList.add('d-none');

    if (metodo === 'mbway') {
        document.getElementById('campoMbway').classList.remove('d-none');
    } else if (metodo === 'multibanco') {
        document.getElementById('campoMultibanco').classList.remove('d-none');
    } else if (metodo === 'cartao') {
        document.getElementById('campoCartao').classList.remove('d-none');
    }
}

// ========================================================
// VALIDAÇÃO ESTRITA DE FORMULÁRIO COM JAVASCRIPT
// ========================================================
document.addEventListener('DOMContentLoaded', function() {
    var formCheckout = document.getElementById('formCheckout');
    var inputNome = document.getElementById('inputNome');
    var inputEmail = document.getElementById('inputEmail');
    var inputTelemovel = document.getElementById('inputTelemovel');
    var inputNif = document.getElementById('inputNif');
    var inputMbway = document.getElementById('inputMbway');
    var inputCartaoNumero = document.getElementById('inputCartaoNumero');
    var inputCartaoValidade = document.getElementById('inputCartaoValidade');
    var inputCartaoCvv = document.getElementById('inputCartaoCvv');
    var aceitoTermos = document.getElementById('aceitoTermos');

    // 1. FILTRAGEM EM TEMPO REAL (NÃO PERMITE MISTURAR LETRAS E NÚMEROS)

    // Nome: Apenas letras (com acentos) e espaços
    if (inputNome) {
        inputNome.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, '');
            validarCampo(inputNome, e.target.value.trim().length >= 3);
        });
    }

    // Telemóvel Comprador: Apenas dígitos, máximo 9
    if (inputTelemovel) {
        inputTelemovel.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 9);
            validarCampo(inputTelemovel, /^[0-9]{9}$/.test(e.target.value));
        });
    }

    // NIF: Apenas dígitos, máximo 9
    if (inputNif) {
        inputNif.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 9);
            if (e.target.value.length > 0) {
                validarCampo(inputNif, /^[0-9]{9}$/.test(e.target.value));
            } else {
                limparErro(inputNif);
            }
        });
    }

    // Telemóvel MB WAY: Apenas dígitos, máximo 9
    if (inputMbway) {
        inputMbway.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 9);
            validarCampo(inputMbway, /^[0-9]{9}$/.test(e.target.value));
        });
    }

    // Cartão de Crédito - Número: Formatação automática 0000 0000 0000 0000 (16 dígitos)
    if (inputCartaoNumero) {
        inputCartaoNumero.addEventListener('input', function(e) {
            var raw = e.target.value.replace(/\D/g, '').substring(0, 16);
            var parts = raw.match(/.{1,4}/g);
            e.target.value = parts ? parts.join(' ') : '';
            validarCampo(inputCartaoNumero, raw.length === 16);
        });
    }

    // Cartão de Crédito - Validade: Formatação automática MM/AA
    if (inputCartaoValidade) {
        inputCartaoValidade.addEventListener('input', function(e) {
            var raw = e.target.value.replace(/\D/g, '').substring(0, 4);
            if (raw.length >= 2) {
                e.target.value = raw.substring(0, 2) + '/' + raw.substring(2, 4);
            } else {
                e.target.value = raw;
            }
            validarDataValidadeCartao(inputCartaoValidade);
        });
    }

    // Cartão de Crédito - CVV: Apenas dígitos, máximo 3
    if (inputCartaoCvv) {
        inputCartaoCvv.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
            validarCampo(inputCartaoCvv, /^[0-9]{3}$/.test(e.target.value));
        });
    }

    // Helper: Validar e aplicar classes de erro
    function validarCampo(input, condicaoValida) {
        if (!input) return true;
        if (condicaoValida) {
            input.classList.remove('is-invalid');
            return true;
        } else {
            input.classList.add('is-invalid');
            return false;
        }
    }

    function limparErro(input) {
        if (input) input.classList.remove('is-invalid');
    }

    // Helper: Validar data de expiração do cartão (não pode ser anterior à data de hoje)
    function validarDataValidadeCartao(input) {
        if (!input) return true;
        var valor = input.value.trim();
        if (!/^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(valor)) {
            input.classList.add('is-invalid');
            return false;
        }

        var partes = valor.split('/');
        var mesValidade = parseInt(partes[0], 10);
        var anoValidade = parseInt('20' + partes[1], 10);

        var dataAtual = new Date();
        var mesAtual = dataAtual.getMonth() + 1;
        var anoAtual = dataAtual.getFullYear();

        // Se o ano for menor, ou se for o mesmo ano e o mês for menor que o atual, rejeita
        if (anoValidade < anoAtual || (anoValidade === anoAtual && mesValidade < mesAtual)) {
            input.classList.add('is-invalid');
            return false;
        }

        input.classList.remove('is-invalid');
        return true;
    }

    // 2. VALIDAÇÃO GLOBAL NO SUBMIT DO FORMULÁRIO
    if (formCheckout) {
        formCheckout.addEventListener('submit', function(e) {
            var isValid = true;
            var primeiroErro = null;

            // Validar Nome Completo
            var nomeVal = inputNome.value.trim();
            if (nomeVal.length < 3 || !/^[a-zA-ZÀ-ÿ\s]+$/.test(nomeVal)) {
                validarCampo(inputNome, false);
                isValid = false;
                if (!primeiroErro) primeiroErro = inputNome;
            } else {
                validarCampo(inputNome, true);
            }

            // Validar E-mail
            var emailVal = inputEmail.value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                validarCampo(inputEmail, false);
                isValid = false;
                if (!primeiroErro) primeiroErro = inputEmail;
            } else {
                validarCampo(inputEmail, true);
            }

            // Validar Telemóvel
            var telVal = inputTelemovel.value.replace(/\D/g, '');
            if (telVal.length !== 9) {
                validarCampo(inputTelemovel, false);
                isValid = false;
                if (!primeiroErro) primeiroErro = inputTelemovel;
            } else {
                validarCampo(inputTelemovel, true);
            }

            // Validar NIF (se preenchido, deve ter 9 dígitos)
            var nifVal = inputNif.value.trim();
            if (nifVal.length > 0 && !/^[0-9]{9}$/.test(nifVal)) {
                validarCampo(inputNif, false);
                isValid = false;
                if (!primeiroErro) primeiroErro = inputNif;
            } else {
                limparErro(inputNif);
            }

            // Validar Campos Consoante o Método Escolhido
            var metodo = document.getElementById('metodoPagamento').value;

            if (metodo === 'mbway') {
                var mbwayVal = inputMbway ? inputMbway.value.replace(/\D/g, '') : '';
                if (mbwayVal.length !== 9) {
                    validarCampo(inputMbway, false);
                    isValid = false;
                    if (!primeiroErro) primeiroErro = inputMbway;
                } else {
                    validarCampo(inputMbway, true);
                }
            } else if (metodo === 'cartao') {
                // Número do cartão: exatamente 16 dígitos
                var cartaoRaw = inputCartaoNumero ? inputCartaoNumero.value.replace(/\D/g, '') : '';
                if (cartaoRaw.length !== 16) {
                    validarCampo(inputCartaoNumero, false);
                    isValid = false;
                    if (!primeiroErro) primeiroErro = inputCartaoNumero;
                } else {
                    validarCampo(inputCartaoNumero, true);
                }

                // Validade: não pode ser anterior a hoje
                if (!validarDataValidadeCartao(inputCartaoValidade)) {
                    isValid = false;
                    if (!primeiroErro) primeiroErro = inputCartaoValidade;
                }

                // CVV: exatamente 3 dígitos
                var cvvVal = inputCartaoCvv ? inputCartaoCvv.value.replace(/\D/g, '') : '';
                if (cvvVal.length !== 3) {
                    validarCampo(inputCartaoCvv, false);
                    isValid = false;
                    if (!primeiroErro) primeiroErro = inputCartaoCvv;
                } else {
                    validarCampo(inputCartaoCvv, true);
                }
            }

            // Validar Termos
            if (aceitoTermos && !aceitoTermos.checked) {
                validarCampo(aceitoTermos, false);
                isValid = false;
                if (!primeiroErro) primeiroErro = aceitoTermos;
            } else if (aceitoTermos) {
                validarCampo(aceitoTermos, true);
            }

            // Se algum campo falhou, trava o envio e foca no primeiro campo com erro
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                if (primeiroErro) {
                    primeiroErro.focus();
                    primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});
