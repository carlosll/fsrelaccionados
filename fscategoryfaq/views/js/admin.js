/**
 * FS Category FAQ SEO — JavaScript Back Office
 *
 * Gestiona la visibilidad dinámica de los selectores de entidad
 * en el formulario de creación/edición de FAQs.
 *
 * @version 1.0.0
 */

(function () {
    'use strict';

    /**
     * IDs de los campos que se muestran/ocultan según el tipo de entidad.
     */
    var ENTITY_FIELDS = {
        category: ['fs-faq-entity-category'],
        cms: ['fs-faq-entity-cms'],
        manufacturer: ['fs-faq-entity-manufacturer'],
        home: [],
    };

    /**
     * Oculta todos los campos de entidad.
     */
    function hideAllEntityFields() {
        Object.values(ENTITY_FIELDS).forEach(function (ids) {
            ids.forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    // Subir hasta el .form-group contenedor
                    var group = el.closest('.form-group');
                    if (group) {
                        group.style.display = 'none';
                    } else {
                        el.style.display = 'none';
                    }
                }
            });
        });
    }

    /**
     * Muestra los campos correspondientes al tipo de entidad seleccionado.
     *
     * @param {string} entityType
     */
    function showEntityFields(entityType) {
        var ids = ENTITY_FIELDS[entityType];
        if (!ids) {
            return;
        }

        ids.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                var group = el.closest('.form-group');
                if (group) {
                    group.style.display = 'block';
                } else {
                    el.style.display = 'block';
                }
            }
        });
    }

    /**
     * Callback cuando cambia el selector de tipo de entidad.
     */
    function onEntityTypeChange() {
        var select = document.getElementById('fs-faq-entity-type');
        if (!select) {
            return;
        }

        var entityType = select.value;
        hideAllEntityFields();
        showEntityFields(entityType);
    }

    /**
     * Inicializa el comportamiento dinámico.
     */
    function init() {
        var select = document.getElementById('fs-faq-entity-type');

        if (!select) {
            // No estamos en el formulario ADD/EDIT
            return;
        }

        // Estado inicial
        hideAllEntityFields();
        showEntityFields(select.value);

        // Escuchar cambios
        select.addEventListener('change', onEntityTypeChange);
    }

    /**
     * Inyecta checkboxes de selección masiva en el listado de FAQs.
     *
     * En PS 8.2, el HelperList renderiza un <th class="text-center">--</th>
     * como placeholder de la columna bulk, pero sin checkbox real.
     * Este script reemplaza el placeholder por checkboxes funcionales:
     * - Cabecera: "seleccionar todas"
     * - Cada fila: un checkbox con el ID de la FAQ
     * - Barra de acciones con botón "Eliminar seleccionados"
     */
    function injectBulkCheckboxes() {
        // Buscar la tabla del listado (HelperList de PS)
        var table = document.querySelector('table.table');
        if (!table) {
            return;
        }

        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        // ── Comprobar si los checkboxes de fila YA existen ──
        // (PS 8.2.7 los renderiza en cada <td class="row-selector text-center">)
        var rowCheckboxes = tbody.querySelectorAll('input[type="checkbox"][name="fs_category_faqBox[]"]');
        if (rowCheckboxes.length === 0) {
            return;
        }

        // ── Cabecera: añadir "seleccionar todas" en el <th> vacío ──
        // La cabecera principal es el PRIMER <tr> del thead (sin clase "filter")
        var headerRows = table.querySelectorAll('thead tr');
        var mainHeaderRow = null;
        for (var i = 0; i < headerRows.length; i++) {
            if (!headerRows[i].classList.contains('filter')) {
                mainHeaderRow = headerRows[i];
                break;
            }
        }
        if (!mainHeaderRow) {
            return;
        }

        // El primer <th> de la cabecera principal está vacío (placeholder)
        var firstTh = mainHeaderRow.querySelector('th');
        if (!firstTh || firstTh.querySelector('input[type="checkbox"]')) {
            return; // Ya tiene checkbox o no existe
        }

        var selectAllCheckbox = document.createElement('input');
        selectAllCheckbox.type = 'checkbox';
        selectAllCheckbox.title = 'Seleccionar / deseleccionar todas';
        selectAllCheckbox.style.cssText = 'margin:0;cursor:pointer;vertical-align:middle;';
        selectAllCheckbox.addEventListener('change', function () {
            for (var j = 0; j < rowCheckboxes.length; j++) {
                rowCheckboxes[j].checked = selectAllCheckbox.checked;
            }
            toggleBulkBar();
        });
        firstTh.appendChild(selectAllCheckbox);

        // ── Vincular los checkboxes de fila existentes a la barra de acciones ──
        // Los checkboxes ya los renderiza HelperList con name="fs_category_faqBox[]"
        for (var k = 0; k < rowCheckboxes.length; k++) {
            rowCheckboxes[k].addEventListener('change', toggleBulkBar);
        }

        // ── Botón de acción en lote ──
        var bulkBar = document.createElement('div');
        bulkBar.id = 'fs-faq-bulk-bar';
        bulkBar.style.cssText = 'margin:8px 0;padding:8px 12px;background:#f8f9fa;border:1px solid #ddd;border-radius:4px;display:none;';
        bulkBar.innerHTML = '<span style="margin-right:12px;font-weight:600;">Con seleccionados:</span>';

        var deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-danger btn-sm';
        deleteBtn.innerHTML = '<i class="icon-trash"></i> Eliminar seleccionados';
        deleteBtn.addEventListener('click', function () {
            var checked = tbody.querySelectorAll('input[name="fs_category_faqBox[]"]:checked');
            if (checked.length === 0) {
                return;
            }
            if (!confirm('¿Eliminar las ' + checked.length + ' FAQs seleccionadas?')) {
                return;
            }
            var ids = [];
            for (var c = 0; c < checked.length; c++) {
                ids.push(checked[c].value);
            }
            if (ids.length > 0) {
                var currentUrl = window.location.href.split('?')[0];
                var params = new URLSearchParams(window.location.search);
                var token = params.get('token') || '';
                window.location.href = currentUrl + '?controller=AdminFsCategoryFaq&token=' + token
                    + '&submitBulkdeletefs_category_faq=1&fs_category_faqBox=' + ids.join(',');
            }
        });
        bulkBar.appendChild(deleteBtn);
        table.parentNode.insertBefore(bulkBar, table);

        function toggleBulkBar() {
            var anyChecked = tbody.querySelectorAll('input[name="fs_category_faqBox[]"]:checked').length > 0;
            bulkBar.style.display = anyChecked ? 'block' : 'none';
        }
    }

    // DOM listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
            injectBulkCheckboxes();
        });
    } else {
        init();
        injectBulkCheckboxes();
    }
})();
