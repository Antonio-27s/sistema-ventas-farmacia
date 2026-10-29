/**
 * ============================================================
 * API COMPARTIDA (shell) — ayuda de fetch para los modulos
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Centraliza las llamadas de los modulos al backend:
 *   - API.get(url): peticion GET que espera JSON
 *   - API.post(url, campos): POST tipo formulario con el token
 *     CSRF (del <meta name="csrf-token"> del shell) y espera JSON
 *
 * Si la respuesta no es ok o no es JSON, lanza un Error; el
 * que llama decide como mostrarlo (alert, mensaje, ...).
 * Se carga en el shell (views/partes/cabecera.php) con defer.
 */
(function (global) {
    'use strict';

    var META = document.querySelector('meta[name="csrf-token"]');
    var CSRF = META ? META.getAttribute('content') : '';

    function leerJson(r) {
        if (!r.ok) {
            throw new Error('HTTP ' + r.status);
        }
        return r.json();
    }

    global.API = {
        get: function (url) {
            return fetch(url).then(leerJson);
        },
        post: function (url, campos) {
            var data = new URLSearchParams();
            data.set('csrf', CSRF);
            for (var clave in campos) {
                if (Object.prototype.hasOwnProperty.call(campos, clave)) {
                    data.set(clave, String(campos[clave]));
                }
            }
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data.toString()
            }).then(leerJson);
        },
        csrf: function () {
            return CSRF;
        }
    };
})(window);
