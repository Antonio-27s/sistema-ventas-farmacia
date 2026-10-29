/**
 * ============================================================
 * VALIDACION DE FORMULARIOS (compartida) — sin recargar pagina
 * Sistema de Ventas - Farmacia
 * ============================================================
 * La BD es la fuente de las longitudes de los campos: PHP
 * pinta cada input con maxlength = longitud de la columna.
 * Este archivo hace visible esa capacidad y aplica las reglas
 * numericas en el navegador, sin recargar:
 *
 *   - Contador en vivo "n/max" bajo cada campo texto/password
 *     con maxlength (se pone rojo al llegar al limite).
 *     Exclusion optativa: data-sin-contador (ej: DNI de 8).
 *   - Campos con data-digitos="N" (DNI=8, RUC=11, ...): solo
 *     digitos y longitud exacta; error inline y bloquea el
 *     submit hasta corregir.
 *
 * El servidor revalida TODO (defensa en profundidad); esto es
 * la experiencia inmediata del usuario.
 */
(function () {
    'use strict';

    function contenedorDe(input) {
        return input.closest('.campo') || input.parentElement || document.body;
    }

    // ------------------------------------------------------------
    // Error inline (uno por campo)
    // ------------------------------------------------------------
    function quitarError(input) {
        var cont = contenedorDe(input);
        var divs = cont.querySelectorAll('.error-campo');
        for (var i = 0; i < divs.length; i++) {
            divs[i].remove();
        }
    }

    function mostrarError(input, mensaje) {
        quitarError(input);
        if (mensaje === '') {
            return;
        }
        var div = document.createElement('div');
        div.className = 'error-campo';
        div.textContent = mensaje;
        contenedorDe(input).appendChild(div);
    }

    // ------------------------------------------------------------
    // data-digitos="N": solo digitos; si no esta vacio,
    // exactamente N (comprobado al salir del campo y al enviar)
    // ------------------------------------------------------------
    function errorDigitos(input, exacto) {
        var n = parseInt(input.getAttribute('data-digitos'), 10);
        var v = input.value;
        if (v === '' || isNaN(n)) {
            return '';
        }
        if (!/^\d+$/.test(v)) {
            return 'Solo dígitos permitidos.';
        }
        if (v.length > n) {
            return 'Máximo ' + n + ' dígitos.';
        }
        if (exacto && v.length < n) {
            return 'Debe tener exactamente ' + n + ' dígitos.';
        }
        return '';
    }

    // ------------------------------------------------------------
    // Contador en vivo n/max
    // ------------------------------------------------------------
    function iniciarContador(input) {
        if (input.hasAttribute('data-sin-contador')) {
            return;
        }
        var max = parseInt(input.getAttribute('maxlength'), 10);
        if (isNaN(max) || max <= 0) {
            return;
        }
        var cont = document.createElement('div');
        cont.className = 'contador';
        cont.textContent = '0/' + max;
        contenedorDe(input).appendChild(cont);
        input._contador = cont;
        actualizarContador(input);
    }

    function actualizarContador(input) {
        if (!input._contador) {
            return;
        }
        var max = parseInt(input.getAttribute('maxlength'), 10);
        input._contador.textContent = input.value.length + '/' + max;
        input._contador.classList.toggle('limite', input.value.length >= max);
    }

    // ------------------------------------------------------------
    // Eventos delegados (cualquier form de la pagina)
    // ------------------------------------------------------------
    document.addEventListener('input', function (ev) {
        var input = ev.target;
        if (!(input instanceof HTMLInputElement)) {
            return;
        }
        if (input._contador) {
            actualizarContador(input);
        }
        if (input.hasAttribute('data-digitos')) {
            // Mientras se escribe: solo frenar no-digitos; la
            // longitud exacta se verifica al salir y al enviar.
            var v = input.value;
            if (v !== '' && !/^\d+$/.test(v)) {
                mostrarError(input, 'Solo dígitos permitidos.');
            } else {
                quitarError(input);
            }
        }
    });

    document.addEventListener('focusout', function (ev) {
        var input = ev.target;
        if (input instanceof HTMLInputElement &&
            input.hasAttribute('data-digitos')) {
            var msg = errorDigitos(input, true);
            if (msg !== '') {
                mostrarError(input, msg);
            } else {
                quitarError(input);
            }
        }
    });

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        var primero = null;
        var inputs = form.querySelectorAll('input[data-digitos]');
        for (var i = 0; i < inputs.length; i++) {
            var input = inputs[i];
            var msg = errorDigitos(input, true);
            if (msg !== '') {
                mostrarError(input, msg);
                if (!primero) {
                    primero = input;
                }
            } else {
                quitarError(input);
            }
        }
        if (primero) {
            ev.preventDefault();
            primero.focus();
        }
    });

    // ------------------------------------------------------------
    // Inicializar los contadores (texto/password con maxlength)
    // ------------------------------------------------------------
    var inputs = document.querySelectorAll('input[maxlength]');
    for (var i = 0; i < inputs.length; i++) {
        var tipo = (inputs[i].type || 'text').toLowerCase();
        if (tipo === 'text' || tipo === 'password') {
            iniciarContador(inputs[i]);
        }
    }
})();
