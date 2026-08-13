# Changelog

Todas las versiones notables de `fsaccesorios` están documentadas en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el versionado sigue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.15] — 2026-08-13

### Corregido
- **Regresión v1.0.13 — doble añadido con Panda:** se revierte el `defer` del script fallback de `hookDisplayHeader`. Con `defer`, el monkey-patch de `HTMLFormElement.prototype.submit` se ejecutaba después de los scripts de Panda, perdía la carrera y el envío nativo de Panda corría en paralelo con el flujo AJAX del módulo (producto duplicado en el carrito). El patch debe ejecutarse síncronamente en head, antes que cualquier otro script.

## [1.0.14] — 2026-08-13

### Corregido
- **Combinación del producto principal en temas Panda:** el JS envía ahora las selecciones `group[N]` del formulario y el controlador las resuelve con `Product::getIdProductAttributeByIdAttributes()` (igual que hace el core). Antes, en temas sin `input[name="id_product_attribute"]` (Panda), se añadía al carrito la variante por defecto en vez de la seleccionada.

## [1.0.13] — 2026-08-13

### Corregido
- **Submit programático con accesorios seleccionados:** si Panda o el tema llama a `form.submit()` sin click previo en el botón, el monkey-patch dispara ahora el flujo AJAX (antes se tragaba el submit silenciosamente: ni añadía ni redirigía). Si el AJAX ya está en curso, el submit se descarta para evitar dobles añadidos.
- **Guard `is_array`:** `validateAccessories()` rechaza entradas que no son array en el payload de accesorios sin generar warnings de PHP 8.

### Mejorado
- **CSRF alineado con el core:** la comparación del token usa `strcasecmp`, igual que `FrontController::isTokenValid()`. Comentario documentando por qué el token estático es la opción correcta (válido con full page cache).
- **`defer` en el script fallback** de `hookDisplayHeader` — no bloquea el render de la página.
- **Getter de accesorios enlazado siempre:** `_selectedAccessories` se conecta aunque el tema no tenga botón con `[data-button-action="add-to-cart"]`, de modo que el patch del prototipo sigue funcionando.

### Eliminado
- `fs_static_token` asignado a Smarty sin uso en la plantilla.

## [1.0.12] — 2026-08-13

### Corregido
- **Cantidad unificada:** el control de cantidad aparece siempre en la fila principal, también para accesorios con combinaciones. La fila de combinación solo contiene el selector, eliminando el control duplicado.
- **Nombre truncado en desktop:** se elimina el ellipsis/`nowrap` del nombre en pantallas de escritorio; solo se trunca en móvil/tablet.
- **Precio y referencia:** eliminado `white-space: nowrap`, ya no fuerzan el ancho de la fila.
- **Cache busting del CSS:** `accessories.css` se registra con `'version' => $this->version`, de modo que cada actualización del módulo invalida la caché del navegador.
- **Combinación del producto principal:** el JS envía y el controlador usa `id_product_attribute`, añadiendo al carrito la variante seleccionada por el cliente (antes se añadía siempre la variante por defecto).
- **Validación de combinaciones en servidor:** el controlador y `validateAccessories()` verifican que cada `id_product_attribute` enviado pertenece al producto correspondiente (accesorios y producto principal), rechazando peticiones manipuladas.

### Mejorado
- **Layout más compacto:** reducidos paddings, gaps y márgenes del bloque, cabecera e items.
- **Flex-wrap por defecto:** los items hacen wrap de forma natural en pantallas medias, sin depender solo del breakpoint móvil.
- **Cantidad alineada a la derecha:** `margin-left: auto` en el control de cantidad.
- **Selector de combinación:** ocupa el 100% del ancho disponible con un máximo de 16rem.

## [1.0.11] — 2026-07-24

### Seguridad
- **CSRF:** añadido token de seguridad en las peticiones AJAX de add-to-cart. El token se genera en PHP, se envía en el payload y se valida en el controlador.
- **XSS:** escapado de `$accessory.reference`, `$accessory.name` y `$combination.name` en la plantilla Smarty con `|escape:'html':'UTF-8'`.

### Corregido
- **JS cargado dos veces:** guardia `window._fsaccesorios_loaded` para evitar doble inicialización cuando ambos hooks (`actionFrontControllerSetMedia` + `displayHeader`) cargan el script.
- **innerHTML → textContent:** notificaciones toast usan `textContent` en lugar de `innerHTML` para mensajes del servidor.
- **Combinaciones "Agotado":** `getProductCombinations()` hardcodea `quantity = 999` en vez de consultar `StockAvailable`, igual que el fix de v1.0.5 a nivel producto.

### Mejorado
- **Layout con combinaciones:** la cantidad se mueve a la fila de la variante (select + qty en línea) para accesorios con combinaciones, evitando solapamiento con el precio.

## [1.0.10] — 2026-07-24

### Corregido
- **Doble protección add-to-cart:** monkey-patch del prototipo aplicado **inmediatamente** (antes que cualquier otro script) + interceptación de clicks en todos los botones add-to-cart en fase de captura. Si Panda llama a `form.submit()` o el usuario hace click, ambas vías están cubiertas.

## [1.0.9] — 2026-07-24

### Corregido
- **Add-to-cart definitivo:** monkey-patch de `HTMLFormElement.prototype.submit` a nivel global. Intercepta cualquier llamada a `.submit()` en cualquier formulario con `id_product`. Imposible de esquivar por Panda, sticky button o cualquier otro módulo.
- **Validación de stock eliminada** también de `validateAccessories()` (el controlador AJAX).
- **Nombre de producto defensivo:** maneja tanto arrays multilang como strings en `$product->name`.

## [1.0.8] — 2026-07-24

### Corregido
- **Add-to-cart real:** monkey-patch de `form.submit()` para interceptar el envío programático de Panda. El `form.submit()` nativo no dispara el evento `submit`, así que ningún `addEventListener` funcionaba. Ahora reemplazamos el método directamente.

## [1.0.7] — 2026-07-24

### Corregido
- **Add-to-cart:** ahora intercepta el clic en el botón nativo de Panda en fase de captura (`useCapture: true`). Sin botón extra, sin conflicto con el sticky button. Si hay accesorios marcados, añade producto principal + accesorios. Si no, deja que el flujo nativo funcione normalmente.

## [1.0.6] — 2026-07-24

### Corregido
- **JS no cargaba:** `jprestaspeedpack` bloqueaba `registerJavascript`. Ahora se carga vía `<script>` directo en `hookDisplayHeader`.
- **Add-to-cart funcional** con accesorios seleccionados.

### Mejorado
- **Precio** más grande y en negrita (0.94rem, 700).
- **Diseño general** con borde azul, icono en título, sin stock bloqueante, sin mensajes duplicados.

## [1.0.5] — 2026-07-24

### Mejorado
- **Stock:** accesorios siempre disponibles (la tienda no gestiona stock). Eliminado el bloqueo que ponía items en gris.
- **Texto repetitivo:** eliminado el mensaje inferior duplicado.
- **Visibilidad visual:** borde izquierdo azul en el bloque, icono en el título, resaltado más visible en fila seleccionada (fondo + borde azul), hover con borde suave.

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
