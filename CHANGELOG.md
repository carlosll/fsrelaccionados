# Changelog

Todas las versiones notables de `fsaccesorios` están documentadas en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el versionado sigue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.1] — 2026-07-24

### Corregido
- Archivos `index.php` ausentes en todos los directorios del módulo (seguridad PrestaShop)
- Hook de fallback `displayFooterProduct` añadido para compatibilidad con temas sin `displayProductAdditionalInfo`

---

## [1.0.0] — 2026-07-24

### Añadido
- Bloque de accesorios en la ficha de producto vía hook `displayProductAdditionalInfo`
- Checkbox personalizado con control de cantidad (+/−) para cada accesorio
- Selector de combinaciones/variantes cuando el accesorio tiene atributos
- Añadir producto principal + accesorios seleccionados al carrito en una sola operación AJAX
- Controlador frontal para validación y adición múltiple al carrito
- Validación servidor: verifica relación accesorio-producto, stock, cantidades mínimas
- CSS responsive con custom properties (design tokens) para desktop, tablet y móvil
- Sin dependencia de jQuery: JavaScript vanilla
- Soporte de accesibilidad: `aria-label`, `role`, `focus-visible`, `prefers-reduced-motion`
- Toasts de notificación con soporte para `prestashop.emit`
- Traducciones: español, francés, portugués, alemán, italiano
- Compatible con PrestaShop 8.2 y 9.x (themes classic y hummingbird)

---

[1.0.0]: https://github.com/carlosll/fsrelaccionados/releases/tag/v1.0.0
