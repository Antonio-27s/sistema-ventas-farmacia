/**
 * ============================================================
 * PANEL DE SESION (shell de la aplicacion)
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Abre y cierra el panel lateral de sesion (vista temporal).
 *
 * Disparadores:
 *   - botones con clase .js-abre-sesion (header y dashboard)
 *   - boton de cierre del panel (#panel-cerrar)
 *   - clic sobre el fondo oscuro (#panel-fondo)
 *   - tecla Escape
 *
 * El estado inicial (abierto/cerrado) lo fija el servidor en
 * el HTML (el fallback sin JS ?accion=sesion lo abre).
 */
(function () {
    'use strict';

    var panel = document.getElementById('panel-sesion');
    var fondo = document.getElementById('panel-fondo');

    // Las vistas sin shell (login) no tienen el panel
    if (!panel || !fondo) {
        return;
    }

    function abrir(abierto) {
        panel.classList.toggle('abierto', abierto);
        fondo.classList.toggle('visible', abierto);
    }

    var botones = document.querySelectorAll('.js-abre-sesion');
    for (var i = 0; i < botones.length; i++) {
        botones[i].addEventListener('click', function () {
            abrir(!panel.classList.contains('abierto'));
        });
    }

    var cerrar = document.getElementById('panel-cerrar');
    if (cerrar) {
        cerrar.addEventListener('click', function () {
            abrir(false);
        });
    }

    fondo.addEventListener('click', function () {
        abrir(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            abrir(false);
        }
    });
})();
