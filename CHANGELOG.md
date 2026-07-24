# Changelog

Todas las versiones notables de `fsaccesorios` están documentadas en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el versionado sigue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.4] — 2026-07-24

### Mejorado
- **Integración visual con el tema:** los tokens de diseño ahora heredan de las variables CSS del tema (`--fes-*`) con fallbacks corporativos. El acento cambia de cyan a azul corporativo (#0465b2).
- **Anillos de foco** corregidos al azul de la marca.
- **Resaltado de fila seleccionada** en JS ajustado al azul corporativo.

## [1.0.3] — 2026-07-24

### Corregido
- **Fatal error en página de producto**: `ImageType::getFormatedName()` no existe en PS 8.x. Corregido a `getFormattedName()` (doble t). Causaba HTTP 500 en todas las páginas de producto.

## [1.0.2] — 2026-07-24

### Corregido
- Hashes MD5 reales en los 5 archivos de traducción (antes usaban placeholders `XXXXX`/`YYYYY` que impedían que `$this->l()` encontrara las traducciones)

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
