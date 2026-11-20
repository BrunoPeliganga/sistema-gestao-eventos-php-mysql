        function mudarQtdItem(inputId, subId, preco, delta) {
            var input = document.getElementById(inputId);
            var qtd = parseInt(input.value) + delta;
            if (qtd < 1) qtd = 1;
            if (qtd > 10) qtd = 10;
            input.value = qtd;
            var subtotal = (qtd * preco).toFixed(2);
            document.getElementById(subId).innerText = subtotal + ' €';
            recalcularTotalCarrinho();
        }

        function recalcularTotalCarrinho() {
            var s1 = document.getElementById('sub1') ? parseFloat(document.getElementById('sub1').innerText) : 0;
            var s2 = document.getElementById('sub2') ? parseFloat(document.getElementById('sub2').innerText) : 0;
            var total = (s1 + s2).toFixed(2);
            document.getElementById('resumoSubtotal').innerText = total + ' €';
            document.getElementById('resumoTotal').innerText = total + ' €';
        }