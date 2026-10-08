/*
 * JS vanilla da aplicação (sem bundler). Carregado no <head> sem defer: o listener de erro
 * de imagem já existe quando as <img> começam a carregar. O resto roda no DOMContentLoaded.
 */
(function () {
    'use strict';

    /* ---------- Fallback de imagem: mostra o placeholder do Ui:Picture ---------- */
    document.addEventListener('error', function (event) {
        var img = event.target;
        if (!(img instanceof HTMLImageElement) || !img.classList.contains('app-picture__img')) {
            return;
        }
        var picture = img.closest('.app-picture');
        if (picture && !picture.classList.contains('is-broken')) {
            picture.classList.add('is-broken');
            picture.setAttribute('role', 'img');
            picture.setAttribute('aria-label', img.alt);
        }
    }, true);

    /* ---------- Máscara de R$: dígitos tratados como centavos ("123456" -> "1.234,56") ---------- */
    var moneyFormatter = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function maskMoney(input) {
        var digits = input.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 10);
        input.value = digits === '' ? '' : moneyFormatter.format(parseInt(digits, 10) / 100);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-money-mask]').forEach(function (input) {
            input.addEventListener('input', function () { maskMoney(input); });
            if (input.value !== '') {
                maskMoney(input);
            }
        });

        /* ---------- Select2 (pillbox) nos selects marcados com [data-pillbox] ----------
         * Não usar data-select2: o Select2 lê $(el).data('select2') como instância já criada. */
        if (window.jQuery && window.jQuery.fn.select2) {
            document.querySelectorAll('select[data-pillbox]').forEach(function (select) {
                window.jQuery(select).select2({
                    theme: 'bootstrap-5',
                    language: 'pt-BR',
                    width: '100%',
                    placeholder: select.dataset.placeholder || '',
                    closeOnSelect: !select.multiple,
                    selectionCssClass: select.classList.contains('is-invalid') ? 'is-invalid' : ''
                });
            });
        }
    });
})();
