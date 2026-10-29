/**
 * ============================================================
 * MODULO PRODUCTO: dinamicas del catalogo
 * Sistema de Ventas - Farmacia
 * ============================================================
 * Progressive enhancement: el HTML se renderiza en el
 * servidor (funciona sin JS); este archivo solo mejora:
 *
 *   - Busqueda/filtro: pide el listado en JSON (accion listar
 *     con &json=1) y re-renderiza la tabla sin recargar pagina.
 *   - Activar/Inactivar: POST por fetch (accion toggle_estado
 *     con &json=1) y actualiza la fila en el lugar.
 *
 * Usa la ayuda compartida public/js/app/api.js (window.API),
 * que lleva el token CSRF del <meta name="csrf-token">.
 * Regla de seguridad: ningun dato de la BD se intercala en
 * innerHTML; todo se construye con textContent/createElement.
 */
(function () {
    'use strict';

    var CUERPO = document.getElementById('catalogo-cuerpo');
    var CANTIDAD = document.getElementById('catalogo-cantidad');
    var FORM = document.getElementById('catalogo-busqueda');

    // Solo la vista del catalogo tiene estos elementos
    if (!CUERPO || !CANTIDAD || !FORM || !window.API) {
        return;
    }

    // ------------------------------------------------------------
    // Parametros de la busqueda actual (del formulario)
    // ------------------------------------------------------------
    function params() {
        var data = new URLSearchParams();
        data.set('controlador', 'Producto');

        var q = document.getElementById('catalogo-q').value.trim();
        if (q !== '') {
            data.set('q', q);
        }

        var cat = document.getElementById('catalogo-categoria').value;
        if (cat !== '0') {
            data.set('id_categoria', cat);
        }

        return data;
    }

    // ------------------------------------------------------------
    // Construccion de filas (solo con textContent/createElement)
    // ------------------------------------------------------------
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

    function crearFila(p) {
        var tr = document.createElement('tr');

        // Codigo
        tr.appendChild(celda(p.codigo));

        // Nombre (+ principio activo como sublinea)
        var tdNombre = document.createElement('td');
        var strong = document.createElement('strong');
        strong.textContent = p.nombre;
        tdNombre.appendChild(strong);
        if (p.principio_activo) {
            var sub = document.createElement('span');
            sub.className = 'sub-celda';
            sub.textContent = p.principio_activo;
            tdNombre.appendChild(sub);
        }
        tr.appendChild(tdNombre);

        // Categoria y laboratorio
        tr.appendChild(celda(p.categoria));
        tr.appendChild(celda(p.laboratorio || '—'));

        // Tipo (+ badge de receta)
        var tdTipo = document.createElement('td');
        tdTipo.appendChild(document.createTextNode(p.tipo_label));
        if (p.requiere_receta) {
            tdTipo.appendChild(document.createTextNode(' '));
            tdTipo.appendChild(badge('off', 'receta'));
        }
        tr.appendChild(tdTipo);

        // Precio de venta
        var tdPrecio = document.createElement('td');
        tdPrecio.textContent = 'S/ ' + p.precio_venta.toLocaleString(
            'en-US',
            { minimumFractionDigits: 2, maximumFractionDigits: 2 }
        );
        tr.appendChild(tdPrecio);

        // Stock
        var tdStock = document.createElement('td');
        if (p.stock === 0) {
            tdStock.appendChild(badge('off', '0'));
        } else {
            var strongStock = document.createElement('strong');
            strongStock.textContent = String(p.stock);
            tdStock.appendChild(strongStock);
        }
        tr.appendChild(tdStock);

        // Estado
        var tdEstado = document.createElement('td');
        tdEstado.className = 'celda-estado';
        tdEstado.appendChild(badge(
            p.activo ? 'ok' : 'off',
            p.activo ? 'Activo' : 'Inactivo'
        ));
        tr.appendChild(tdEstado);

        // Acciones: editar (link) + activar/inactivar (form)
        var tdAcc = document.createElement('td');
        tdAcc.className = 'celda-acciones';

        var link = document.createElement('a');
        link.className = 'btn-sm';
        link.href = 'index.php?controlador=Producto&accion=editar&id=' + p.id;
        link.textContent = 'Editar';
        tdAcc.appendChild(link);

        var form = document.createElement('form');
        form.className = 'forma-linea';
        form.setAttribute('data-togla', '1');
        form.method = 'post';
        form.action = 'index.php?controlador=Producto&accion=toggle_estado&json=1';

        var hidId = document.createElement('input');
        hidId.type = 'hidden';
        hidId.name = 'producto_id';
        hidId.value = String(p.id);
        form.appendChild(hidId);

        var btn = document.createElement('button');
        btn.type = 'submit';
        btn.className = 'btn-sm' + (p.activo ? ' peligro' : '');
        btn.textContent = p.activo ? 'Inactivar' : 'Activar';
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
            td.colSpan = 9;
            td.className = 'vacio';
            td.textContent = 'No hay productos que coincidan. ';
            var a = document.createElement('a');
            a.href = 'index.php?controlador=Producto&accion=nuevo';
            a.textContent = 'Crear el primero';
            td.appendChild(a);
            tr.appendChild(td);
            CUERPO.appendChild(tr);
            return;
        }

        lista.forEach(function (p) {
            CUERPO.appendChild(crearFila(p));
        });
    }

    // ------------------------------------------------------------
    // Busqueda/filtro: fetch del listado JSON + re-render
    // ------------------------------------------------------------
    function cargar() {
        var data = params();
        data.set('json', '1');

        API.get('index.php?' + data.toString())
            .then(function (res) {
                render(res.productos || []);
                CANTIDAD.textContent = res.total + ' producto(s)';

                // La URL refleja el filtro actual (compartible,
                // y un F5 reproduce la misma vista)
                history.replaceState(
                    null,
                    '',
                    'index.php?' + params().toString()
                );
            })
            .catch(function (err) {
                console.error('Catalogo: no se pudo cargar el listado', err);
            });
    }

    FORM.addEventListener('submit', function (ev) {
        ev.preventDefault(); // sin JS el form sigue siendo un GET real
        cargar();
    });

    // ------------------------------------------------------------
    // Activar/Inactivar: fetch POST + update de la fila
    // ------------------------------------------------------------
    function actualizarFila(form, activo) {
        var tr = form.closest('tr');
        var tdEstado = tr.querySelector('.celda-estado');
        tdEstado.textContent = '';
        tdEstado.appendChild(badge(
            activo ? 'ok' : 'off',
            activo ? 'Activo' : 'Inactivo'
        ));

        var btn = form.querySelector('button');
        btn.className = 'btn-sm' + (activo ? ' peligro' : '');
        btn.textContent = activo ? 'Inactivar' : 'Activar';
    }

    CUERPO.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!(form instanceof HTMLFormElement) ||
            !form.hasAttribute('data-togla')) {
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
            producto_id: form.querySelector('[name="producto_id"]').value
        })
            .then(function (res) {
                if (res && res.ok) {
                    actualizarFila(form, res.activo);
                } else {
                    btn.disabled = false;
                    alert((res && res.error) || 'No se pudo actualizar el estado.');
                }
            })
            .catch(function () {
                btn.disabled = false;
                alert('Error de red al actualizar el estado.');
            });
    });
})();
