/**
 * ============================================================
 * MODULO STOCK: dinamicas del listado
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Progressive enhancement: el HTML se renderiza en el servidor
 * (funciona sin JS); este archivo solo mejora:
 *
 *   - Busqueda/filtro: pide el listado en JSON y re-renderiza
 *     la tabla sin recargar pagina (sincroniza la URL).
 *   - Ajuste de cantidad: POST por fetch y actualiza la fila
 *     en el lugar (cantidad, estado y ultima fecha).
 *
 * Usa la ayuda compartida public/js/app/api.js (window.API).
 */
(function () {
    'use strict';

    var CUERPO = document.getElementById('stock-cuerpo');
    var CANTIDAD = document.getElementById('stock-cantidad');
    var FORM = document.getElementById('stock-busqueda');

    if (!CUERPO || !CANTIDAD || !FORM || !window.API) {
        return;
    }

    // Etiquetas de estado (deben coincidir con Stock::ESTADOS)
    var ESTADOS = {
        OK: ['ok', 'Adecuado'],
        BAJO: ['warn', 'Bajo'],
        AGOTADO: ['off', 'Agotado']
    };

    function params() {
        var data = new URLSearchParams();
        data.set('controlador', 'Stock');

        var q = document.getElementById('stock-q').value.trim();
        if (q !== '') {
            data.set('q', q);
        }

        var estado = document.getElementById('stock-estado').value;
        if (estado !== '') {
            data.set('estado', estado);
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

    function crearFila(s) {
        var tr = document.createElement('tr');

        tr.appendChild(celda(s.codigo));

        var tdNombre = document.createElement('td');
        var strong = document.createElement('strong');
        strong.textContent = s.nombre;
        tdNombre.appendChild(strong);
        tr.appendChild(tdNombre);

        tr.appendChild(celda(s.categoria));

        var tdCant = document.createElement('td');
        tdCant.className = 'celda-cantidad';
        var strongCant = document.createElement('strong');
        strongCant.textContent = String(s.cantidad);
        tdCant.appendChild(strongCant);
        tr.appendChild(tdCant);

        tr.appendChild(celda(String(s.minimo)));

        var tdEstado = document.createElement('td');
        tdEstado.className = 'celda-estado';
        tdEstado.appendChild(badge(ESTADOS[s.estado][0], ESTADOS[s.estado][1]));
        tr.appendChild(tdEstado);

        var tdFecha = document.createElement('td');
        tdFecha.className = 'celda-fecha';
        tdFecha.textContent = s.fecha || '—';
        tr.appendChild(tdFecha);

        var tdAcc = document.createElement('td');
        tdAcc.className = 'celda-acciones';

        var form = document.createElement('form');
        form.className = 'forma-linea';
        form.setAttribute('data-ajusta', '1');
        form.method = 'post';
        form.action = 'index.php?controlador=Stock&accion=ajustar&json=1';

        var hidId = document.createElement('input');
        hidId.type = 'hidden';
        hidId.name = 'id_producto';
        hidId.value = String(s.id);
        form.appendChild(hidId);

        var input = document.createElement('input');
        input.type = 'number';
        input.name = 'cantidad';
        input.className = 'ajusta-cant';
        input.min = '0';
        input.step = '1';
        input.value = String(s.cantidad);
        form.appendChild(input);

        var btn = document.createElement('button');
        btn.type = 'submit';
        btn.className = 'btn-sm';
        btn.textContent = 'Actualizar';
        form.appendChild(btn);

        tdAcc.appendChild(form);
        tr.appendChild(tdAcc);

        return tr;
    }

    function render(lista) {
        CUERPO.textContent = '';

        if (lista.length === 0) {
            var tr = document.createElement('tr');
            var td = document.createElement('td');
            td.colSpan = 8;
            td.className = 'vacio';
            td.textContent = 'No hay productos que coincidan.';
            tr.appendChild(td);
            CUERPO.appendChild(tr);
            return;
        }

        lista.forEach(function (s) {
            CUERPO.appendChild(crearFila(s));
        });
    }

    function cargar() {
        var data = params();
        data.set('json', '1');

        API.get('index.php?' + data.toString())
            .then(function (res) {
                render(res.stock || []);
                CANTIDAD.textContent = res.total + ' producto(s)';
                history.replaceState(
                    null,
                    '',
                    'index.php?' + params().toString()
                );
            })
            .catch(function (err) {
                console.error('Stock: no se pudo cargar el listado', err);
            });
    }

    FORM.addEventListener('submit', function (ev) {
        ev.preventDefault(); // sin JS el form sigue siendo un GET real
        cargar();
    });

    // ------------------------------------------------------------
    // Ajuste: fetch POST + update de la fila
    // ------------------------------------------------------------
    function actualizarFila(form, res) {
        var tr = form.closest('tr');

        var tdCant = tr.querySelector('.celda-cantidad');
        tdCant.textContent = '';
        var strong = document.createElement('strong');
        strong.textContent = String(res.cantidad);
        tdCant.appendChild(strong);

        var tdEstado = tr.querySelector('.celda-estado');
        tdEstado.textContent = '';
        tdEstado.appendChild(badge(ESTADOS[res.estado][0], ESTADOS[res.estado][1]));

        var tdFecha = tr.querySelector('.celda-fecha');
        tdFecha.textContent = res.fecha;

        form.querySelector('[name="cantidad"]').value = String(res.cantidad);
        form.querySelector('button').disabled = false;
    }

    CUERPO.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!(form instanceof HTMLFormElement) ||
            !form.hasAttribute('data-ajusta')) {
            return;
        }

        ev.preventDefault();

        var btn = form.querySelector('button');
        btn.disabled = true;

        // El form renderizado por el servidor no lleva &json=1;
        // se fuerza para que la respuesta sea JSON.
        var url = new URL(form.action, window.location.href);
        url.searchParams.set('json', '1');

        API.post(url.toString(), {
            id_producto: form.querySelector('[name="id_producto"]').value,
            cantidad: form.querySelector('[name="cantidad"]').value
        })
            .then(function (res) {
                if (res && res.ok) {
                    actualizarFila(form, res);
                } else {
                    btn.disabled = false;
                    alert((res && res.error) || 'No se pudo ajustar el stock.');
                }
            })
            .catch(function () {
                btn.disabled = false;
                alert('Error de red al ajustar el stock.');
            });
    });
})();
