/**
 * FS Category FAQ SEO — JavaScript Front Office
 *
 * Mínimo JS: solo mejoras de accesibilidad y UX.
 * El acordeón funciona nativamente con <details> (sin JS).
 *
 * @version 1.0.0
 */

(function () {
    'use strict';

    /**
     * Si la URL contiene un hash tipo #faq-{id}, hacer scroll
     * suave hasta esa FAQ y abrir su acordeón.
     */
    function handleDeepLink() {
        var hash = window.location.hash;

        if (!hash || hash.indexOf('#faq-') !== 0) {
            return;
        }

        var target = document.getElementById(hash.substring(1));
        if (!target) {
            return;
        }

        // Abrir el <details> si es un acordeón
        if (target.tagName === 'DETAILS') {
            target.setAttribute('open', '');
        }

        // Scroll suave hasta el elemento
        setTimeout(function () {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });

            // Foco para lectores de pantalla
            var question = target.querySelector('.fs-faq-question');
            if (question) {
                question.setAttribute('tabindex', '-1');
                question.focus({ preventScroll: true });
            }
        }, 150);
    }

    /**
     * Mejora la accesibilidad: permite cerrar un <details> con la tecla Escape.
     */
    function enhanceKeyboardAccess() {
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            var focused = document.activeElement;
            if (!focused) {
                return;
            }

            // Buscar si el foco está dentro de un <details> abierto
            var details = focused.closest('details[open]');
            if (!details) {
                return;
            }

            // Cerrar el acordeón y devolver el foco al summary
            details.removeAttribute('open');
            var summary = details.querySelector('summary');
            if (summary) {
                summary.focus();
            }
        });
    }

    /**
     * Inicialización al cargar la página.
     */
    function init() {
        handleDeepLink();
        enhanceKeyboardAccess();
    }

    // DOM listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
