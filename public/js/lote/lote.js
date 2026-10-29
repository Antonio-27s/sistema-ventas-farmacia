/**
 * ============================================================
 * MODULO LOTE: dinamicas del listado FEFO
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Progressive enhancement: el HTML se renderiza en el servidor
 * (funciona sin JS); este archivo solo mejora:
 *
 *   - Busqueda: pide el listado en JSON y re-renderiza la tabla
 *     sin recargar pagina (sincroniza la URL).
 *
 * Usa la ayuda compartida public/js/app/api.js (window.API).
 */
(function () {
    'use strict';

    var CUERPO = document.getElementById('lote-cuerpo');
    var CANTIDAD = document.getElementById('lote-cantidad');
    var FORM = document.getElementById('lote-busqueda');

    if (!CUERPO || !CANTIDAD || !FORM || !window.API) {
        return;
    }

    // Etiquetas de estado (deben coincidir con Lote::ESTADOS)
    var ESTADOS = {
        VENCIDO: ['off', 'Vencido'],
        PRONTO: ['warn', 'Vence pronto'],
        VIGENTE: ['ok', 'Vigente']
    };

    function params() {
        var data = new URLSearchParams();
        data.set('controlador', 'Lote');

        var q = document.getElementById('lote-q').value.trim();
        if (q !== '') {
            data.set('q', q);
        }

        return data;
    }

    function celda(texto) {
        var td = document.createElement('td');
        td.textContent = texto;
        return td;
    }

    function badge(clase, texto) {
        var span = document.createElement('span');
        span.className = 'badge ' + clase;
        span.textContent = texto;
        return span;
    }

    function crearFila(l) {
        var tr = document.createElement('tr');

        var tdProd = document.createElement('td');
        var strong = document.createElement('strong');
        strong.textContent = l.producto;
        tdProd.appendChild(strong);
        var sub = document.createElement('span');
        sub.className = 'sub-celda';
        sub.textContent = l.codigo_producto;
        tdProd.appendChild(sub);
        tr.appendChild(tdProd);

        tr.appendChild(celda(l.numero_lote));
        tr.appendChild(celda(String(l.cantidad)));
        tr.appendChild(celda(l.fecha_fabricacion || '—'));

        var tdVenc = document.createElement('td');
        var strongVenc = document.createElement('strong');
        strongVenc.textContent = l.fecha_vencimiento;
        tdVenc.appendChild(strongVenc);
        tr.appendChild(tdVenc);

        var tdEstado = document.createElement('td');
        tdEstado.appendChild(badge(ESTADOS[l.estado][0], ESTADOS[l.estado][1]));
        tr.appendChild(tdEstado);

        return tr;
    }

    function render(lista) {
        CUERPO.textContent = '';

        if (lista.length === 0) {
            var tr = document.createElement('tr');
            var td = document.createElement('td');
            td.colSpan = 6;
            td.className = 'vacio';
            td.textContent = 'No hay lotes registrados. ';
            var a = document.createElement('a');
            a.href = 'index.php?controlador=Lote&accion=nuevo';
            a.textContent = 'Registrar el primero';
            td.appendChild(a);
            tr.appendChild(td);
            CUERPO.appendChild(tr);
            return;
        }

        lista.forEach(function (l) {
            CUERPO.appendChild(crearFila(l));
        });
    }

    function cargar() {
        var data = params();
        data.set('json', '1');

        API.get('index.php?' + data.toString())
            .then(function (res) {
                render(res.lotes || []);
                CANTIDAD.textContent = res.total + ' lote(s)';
                history.replaceState(
                    null,
                    '',
                    'index.php?' + params().toString()
                );
            })
            .catch(function (err) {
                console.error('Lote: no se pudo cargar el listado', err);
            });
    }

    FORM.addEventListener('submit', function (ev) {
        ev.preventDefault(); // sin JS el form sigue siendo un GET real
        cargar();
    });
})();
