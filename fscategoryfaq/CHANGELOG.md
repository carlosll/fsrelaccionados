# Changelog

Todas las versiones notables de FS Category FAQ SEO están documentadas aquí.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y el versionado sigue [SemVer](https://semver.org/lang/es/).

---

## [1.7.8] — 2026-07-21

### Añadido
- **Margen superior e inferior configurables**: dos nuevos campos de texto en el panel "Diseño y colores" permiten definir la separación del bloque de FAQs con el contenido anterior (`FSCATEGORYFAQ_MARGIN_TOP`, default: `0`) y posterior (`FSCATEGORYFAQ_MARGIN_BOTTOM`, default: `2rem`). Los valores se sanitizan con regex (solo se permiten valores CSS seguros: `0`, `1rem`, `20px`, `auto`…) y se aplican como variables CSS `--fs-faq-margin-top` / `--fs-faq-margin-bottom` en el contenedor `.fs-category-faq-block`. Así el bloque no se pega a los módulos adyacentes y se adapta a cualquier tema sin tocar código.

---

## [1.7.7] — 2026-07-20

### Corregido
- **Delete duplicado en importación**: al usar "Reemplazar FAQs existentes", una FAQ con múltiples traducciones se intentaba borrar dos veces. Se añadió guard `seenFaqIds` para deducir por `id_faq`.
- **Filtro `javascript:`/`data:` ampliado a `src`**: el filtro de URIs peligrosas solo cubría `href`. Ahora también bloquea `javascript:` y `data:` en `src` (XSS en `<img>`, `<iframe>`, etc.).
- **`Tools::clearSmartyCache()` eliminado de `clearFaqCache()`**: invalidaba toda la caché de plantillas de la tienda en cada cambio de FAQ. `_clearCache()` del módulo es suficiente.
- **Fallbacks en `Configuration::get()`**: las 25 claves de configuración ahora tienen valor por defecto como 5º parámetro. Evita selectores vacíos en instalaciones que actualizaron desde versiones anteriores sin esas claves.

---

## [1.7.6] — 2026-07-17

### Añadido
- **Hooks `displayHome` y `displayHomeBottom` para la página de inicio**: ahora puedes elegir entre 4 hooks distintos para posicionar las FAQs en la home. Los nuevos hooks `displayHome` (contenido central, máxima visibilidad) y `displayHomeBottom` (zona inferior de la home) se suman a los ya existentes `displayFooter` y `displayFooterProduct`. El nuevo default para instalaciones nuevas es `displayHome` (recomendado).

### Cambiado
- **Hook por defecto para inicio**: ahora es `displayHome` (antes `displayFooter`). Las FAQs en la home aparecerán en la zona central de contenido en vez de dentro del footer. El upgrade script migra automáticamente las instalaciones que tenían el default anterior; si el usuario ya había elegido otro hook manualmente, se respeta su elección.

---

## [1.7.5] — 2026-07-15

### Corregido
- **Mensajes de feedback en importación**: al entrar a la página de importación ya no aparece el error "No se ha seleccionado ningún archivo" de ejecuciones anteriores. El redirect post-importación ahora usa `$this->redirect_after` (flujo estándar de PS) en lugar de `Tools::redirectAdmin()` manual, lo que permite que PrestaShop persista y muestre correctamente los mensajes de confirmación/error.
- **Texto de ayuda del campo answer**: cambiado de `&lt;p&gt;, &lt;strong&gt;...` a texto plano (`p, strong, em...`) para evitar el doble escape de entidades HTML que mostraba `&amp;lt;p&amp;gt;` ilegible en el formulario.

---

## [1.7.4] — 2026-07-15

### Añadido
- **Importación inteligente en modo "Actualizar"**: `duplicate_update` ahora compara la respuesta y pregunta existentes con las del JSON antes de tocar la BD. Si son idénticas, se salta la FAQ (contador "sin cambios"). Solo se actualizan las FAQs whose answer o pregunta realmente cambiaron.

---

## [1.7.3] — 2026-07-15

### Corregido
- **Sanitización HTML demasiado restrictiva**: la función `sanitizeAnswer()` usaba `strip_tags()` con solo 7 etiquetas permitidas (`<p><strong><em><ul><ol><li><a><br>`), eliminando tablas, encabezados, imágenes y otros elementos al guardar. Se amplió la whitelist a 31 etiquetas: párrafos/formato/listas + tablas (`<table>`, `<thead>`, `<tbody>`, `<tfoot>`, `<tr>`, `<th>`, `<td>`, `<caption>`, `<colgroup>`, `<col>`) + formato extendido (`<b>`, `<i>`, `<u>`, `<s>`, `<hr>`, `<h1>`–`<h6>`) + estructura (`<img>`, `<span>`, `<div>`, `<blockquote>`, `<pre>`, `<code>`, `<sub>`, `<sup>`).

### Cambiado
- Actualizado el texto de ayuda del campo `answer` en el formulario de FAQs para reflejar las nuevas etiquetas permitidas.

---

## [1.7.2] — 2026-07-13

### Añadido
- **Nuevos hooks para fabricantes**: `displayFullWidthCategoryFooter` (full_width_bottom_container, justo bajo los productos) y `displayWrapperBottom` (wrapper_bottom_container). Ambos posicionan las FAQs antes del footer, mejor que `displayFooter`. Añadidos también como opción para categorías.
- **Nuevos métodos**: `hookDisplayFullWidthCategoryFooter()` y `hookDisplayWrapperBottom()`.

### Cambiado
- **Hook por defecto para fabricantes**: ahora `displayFullWidthCategoryFooter` (antes `displayFooter`). Las FAQs aparecen bajo el contenedor principal en vez de dentro del footer.

---

## [1.7.1] — 2026-07-13

### Eliminado
- **Retirado todo el código de diagnóstico temporal**: `logDebug()`, `renderDebugPanel()`, marcadores HTML `<!-- FSFAQ ... -->`, logs a `ps_log` y `error_log()`. El diagnóstico de v1.6.7–v1.7.0 cumplió su propósito.

### Mantenido de v1.7.0
- **Respaldo universal `displayFooter`**: el bloque de FAQs ahora también se pinta desde `hookDisplayFooter` con guard `faqRendered` para evitar duplicados. Si el hook principal de Panda (`displayCategoryFooter`) falla por cualquier motivo, `displayFooter` garantiza que las FAQs se muestren igualmente.

---

## [1.7.0] — 2026-07-13

### Corregido
- **Respaldo universal en `displayFooter`**: el bloque de categoría ahora también se pinta por el hook `displayFooter` con guard `faqRendered` para evitar duplicados. No depende exclusivamente de `displayCategoryFooter` (hook del tema Panda).

### Añadido (TEMPORAL — retirado en v1.7.1)
- Marcadores de diagnóstico `<!-- FSFAQ ... -->` en `hookDisplayHeader` y `hookDisplayFooter`.
- `logDebug()` con canal dual (`ps_log` + `error_log()`).
- `renderDebugPanel()` en la página de configuración del módulo.

---

## [1.6.8-debug] — 2026-07-13

### Cambiado (TEMPORAL — se retira en la próxima versión)
- **Canal de logs cambiado a la tabla de PrestaShop**: en v1.6.7 los `[FSFAQ DEBUG]` iban solo a `error_log()` de PHP, que en este hosting no dejó rastro visible. Ahora `logDebug()` escribe además en la tabla `ps_log` vía `PrestaShopLogger::addLog()`, visible directamente en **Back Office → Parámetros avanzados → Logs** (busca "FSFAQ"). Ya no hace falta acceso SSH ni buscar el log de PHP.

### Añadido (TEMPORAL — se retira en la próxima versión)
- **Panel de diagnóstico en la página de configuración del módulo**: al abrir la configuración se muestra un recuadro que, corriendo en el back office (sin caché de página del front), informa del estado real: si el módulo está activado, qué hooks están registrados y cuáles faltan, cuántas FAQs de categoría hay en la BD, y por cada una su `id_shop`, si está activa y si tiene pregunta/respuesta no vacías para el idioma actual (`id_lang`). Esto separa de forma definitiva un problema de datos/idioma de un problema de que los hooks no se ejecuten en el front (caché de página de `jprestaspeedpack`).

Sin cambio funcional del front. Objetivo: cerrar el diagnóstico del bug de FAQs no visibles.

---

## [1.6.7-debug] — 2026-07-13

### Añadido (TEMPORAL — se retira en la próxima versión)
- **Logs de diagnóstico `[FSFAQ DEBUG]`**: se añade un helper `logDebug()` que emite líneas al log de errores de PHP con el prefijo `[FSFAQ DEBUG]`. Cubre los 5 puntos de decisión del flujo: cada hook (`displayHeader`, `displayCategoryFaq`, `displayCategoryFooter`, `displayFooterProduct`, `displayFooter`), `renderFaqBlock()` con todas sus salidas tempranas + el número de bytes que devuelve la plantilla, y `detectCurrentEntity()` con `php_self` + `controller_class` + la rama del switch que sigue (o el motivo por el que devuelve null).

Sin cambio funcional, solo instrumentación. Objetivo: identificar por qué el bloque de FAQs no aparece en el front de las páginas de categoría del sitio en producción (bug persistente que ni v1.6.5 ni v1.6.6 resolvieron). Para leer los logs en el servidor tras abrir una categoría:

```
tail -n 200 /var/log/php_errors.log | grep 'FSFAQ DEBUG'
```

(La ruta exacta del log depende del hosting; en algunos casos está en `/home/USUARIO/logs/` o accesible vía cPanel → Errores de PHP.)

---

## [1.6.6] — 2026-07-13

### Corregido
- **FAQs no visibles en categorías**: la sintaxis `{$faq@first}` usada en la plantilla Smarty no es compatible con Smarty 4/5 que usa PrestaShop 8.2, provocando que la plantilla fallara al compilar y el bloque entero de FAQs desapareciera del front. Se reemplazó por `{$smarty.foreach.faqLoop.first}`, la sintaxis estándar compatible con todas las versiones de Smarty.

---

## [1.6.1] — 2026-07-13

### Cambiado
- **Modo de apertura de FAQs como selector**: el antiguo interruptor ON/OFF "FAQs abiertas por defecto" se reemplaza por un selector con 3 opciones: "Todas cerradas", "Primera abierta — resto cerradas (recomendado)" y "Todas abiertas". El valor por defecto es "Primera abierta".
- **Selector "seleccionar todas" forzado**: ahora se fuerza `force_show_bulk_actions=true` en el HelperList mediante `setHelperDisplay()` para garantizar que la columna de checkboxes aparece en el listado incluso con pocas FAQs.

### Corregido
- **Menú "FAQs" en Configuración**: el upgrade script de la v1.6.0 no lograba recolocar la pestaña porque `installTabs()` fallaba. La v1.6.1 usa un enfoque directo (busca el padre de la sección Configurar vía SQL y actualiza el tab existente).

---

## [1.6.0] — 2026-07-13

### Añadido
- **Tamaño del título independiente**: nuevo selector en el panel "Diseño y colores" que controla solo el tamaño del título del bloque de FAQs (5 tamaños: S 1.25rem → XXL 2.75rem). La escala general de texto ahora aplica solo a pregunta y respuesta, permitiendo títulos más grandes sin inflar el resto del texto.
- **Checkbox "seleccionar todas" en el listado**: ahora aparece la casilla en la cabecera del listado para marcar/desmarcar todas las FAQs a la vez y aplicar acciones en lote (eliminar).

### Cambiado
- **Menú "FAQs" bajo Configuración**: la pestaña del módulo se ha movido de la sección SEO a la sección **Configurar** (bajo Parámetros de la tienda), con el nombre "FAQs". En nuevas instalaciones aparece automáticamente; en instalaciones existentes, el script `upgrade-1.6.0.php` la recoloca.

---

## [1.5.1] — 2026-07-12

### Añadido
- **Control de duplicados al importar**: nuevo selector "Si la pregunta ya existe en esa página" con 3 opciones:
  - *Añadir de todas formas* — comportamiento anterior, puede crear duplicados
  - *Saltar pregunta* — si la misma pregunta ya existe en esa página, no la importa
  - *Actualizar respuesta* — si la pregunta ya existe, sobrescribe su respuesta y estado con los datos del archivo
- **`FsCategoryFaq::findByQuestion()`**: nuevo método estático para buscar una FAQ por `entity_type` + `entity_id` + texto exacto de la pregunta + idioma + tienda. Devuelve el ID si existe, 0 si no.
- **Resumen detallado de la importación**: ahora desglosa "X añadidas, Y actualizadas, Z saltadas (ya existían), N errores" en vez de solo "X FAQs importadas correctamente, Y errores."

### Corregido
- **La opción "Reemplazar FAQs existentes" borraba toda la tienda**: al importar un JSON, marcar la casilla de reemplazo eliminaba **todas** las FAQs de la tienda sin discriminar por página/categoría. Ahora solo borra las FAQs que pertenecen a las mismas entidades (`entity_type` + `entity_id`) que vienen en el archivo. Si importas FAQs de "Generadores" y de "Inicio", solo se eliminan las FAQs actuales de esas dos páginas; el resto se conservan intactas.

### Cambiado
- Texto de la casilla de reemplazo en el formulario de importación: ahora explica con un ejemplo concreto qué FAQs se borrarán y cuáles no.
- Texto del resumen de importación: formato nuevo con desglose añadidas/actualizadas/saltadas/errores.

---

## [1.5.0] — 2026-07-11

### Añadido
- **Importar FAQs desde archivo JSON**: nuevo botón en el Gestor de FAQs que permite subir un archivo `.json` generado por herramientas externas (DinoRank API, etc.) para importar múltiples FAQs de una sola vez. El sistema valida el formato, sanitiza respuestas con `sanitizeAnswer()`, y muestra un resumen detallado de importaciones correctas y errores (FAQ por FAQ).
- **Exportar FAQs a JSON**: nuevo botón que descarga todas las FAQs de la tienda actual en formato JSON, incluyendo todas las traducciones disponibles. El formato es compatible con el de importación para permitir ciclos exportar → editar → importar.
- **`FsCategoryFaq::getAllWithTranslations()`**: nuevo método estático en el ObjectModel para obtener FAQs con sus traducciones, reutilizable tanto por la exportación como por futuras funcionalidades.

---

## [1.4.0] — 2026-07-10

### Añadido
- **Filtro por categoría en el listado de FAQs**: la columna "Entidad" del Gestor de FAQs ahora tiene un desplegable con el árbol de categorías (igual que el del formulario de alta/edición) para ver solo las FAQs de una categoría concreta.
- **Reordenar arrastrando filas**: se añade `FsCategoryFaq::updatePosition()`, necesario para que PrestaShop pueda guardar el nuevo orden al arrastrar una fila en el listado. El reordenamiento agrupa por categoría/página (`entity_type` + `entity_id`), así que arrastrar una FAQ nunca mezcla su posición con la de otra categoría, CMS o fabricante — funciona tanto si el listado está filtrado a una sola categoría como si muestra varias mezcladas.

### Corregido
- **El arrastrar para reordenar no funcionaba**: `FsCategoryFaq` no tenía el método `updatePosition()` que PrestaShop necesita para procesar el drag-and-drop del listado (`AdminController::processPosition()`); cualquier intento de arrastrar fallaba con "Failed to update the position". No se había detectado antes porque nadie había probado a arrastrar una fila.
- **Filtro de categoría a prueba de colisión de IDs**: al filtrar por una categoría concreta, se exige también `entity_type = 'category'` en la consulta — sin esto, una página CMS o un fabricante que por casualidad tuviera el mismo ID numérico que la categoría elegida se habría colado en el listado filtrado.

---

## [1.3.1] — 2026-07-10

### Cambiado
- **Color de acento por defecto**: de `#ff6a00` a `#1066B2` (azul de marca real del cliente, confirmado tras revisar el plan antes de darlo por definitivo). Solo afecta a instalaciones nuevas o a quien no haya tocado el campo "Color de acento" en Configuración.

### Añadido
- **Script de actualización `upgrade/upgrade-1.3.1.php`**: el módulo ya estaba instalado en producción antes de la 1.3.0. `installDefaultConfig()` solo corre en instalaciones nuevas, así que las 11 claves de `Configuration` del panel de Diseño y colores nunca se habrían escrito en la base de datos real al subir solo los archivos actualizados. PrestaShop detecta automáticamente este script al ver que la versión instalada es anterior a la de `config.xml` y ejecuta `upgrade_module_1_3_1()`, que da de alta esas 11 claves con sus valores por defecto si no existen ya.

---

## [1.3.0] — 2026-07-10

### Corregido
- **Bloque recortado / no ocupaba el ancho del contenedor**: `.fs-category-faq-block` tenía un `max-width: 800px` fijo en CSS, sin importar el ancho real de la columna donde se insertaba. Ahora usa `width: 100%` + una variable `--fs-faq-max-width` configurable (100% por defecto).

### Añadido
- **Panel de "Diseño y colores" en la configuración del módulo**: 11 nuevos controles para personalizar el bloque sin tocar código — color de acento, color de fondo de las tarjetas, color de texto de la pregunta, color de texto de la respuesta, color de bordes (selectores de color nativos de PrestaShop), redondeo de esquinas, densidad del espaciado, intensidad de la sombra, estilo del icono (flecha / más-menos / ninguno), tamaño del texto (escala título+pregunta+respuesta) y ancho del bloque (100% / 1200px / 960px / 800px).
- **Rediseño visual del bloque front**: icono de flecha (chevron) CSS puro como nuevo valor por defecto, borde de acento en el ítem abierto, elevación sutil al pasar el ratón, badge con el número de preguntas junto al título, barra de acento junto al título, transiciones con easing más suave, respeto a `prefers-reduced-motion`.
- Todos los valores de diseño se validan en servidor antes de volcarse a CSS: colores contra `^#[0-9A-Fa-f]{6}$`, el resto contra listas blancas — nunca se interpola texto libre del admin en el `<style>` generado.

### Cambiado
- El color de acento, antes gris fijo en el icono y azul fijo en los enlaces, ahora es un único valor configurable que también tiñe el borde del ítem abierto y el foco de teclado.
- Versión salta de 1.2.3 a 1.3.0 (MINOR: nueva funcionalidad compatible hacia atrás).

---

## [1.2.3] — 2026-07-10

### Corregido
- **Schema FAQPage duplicado**: eliminados los atributos `itemscope`, `itemtype` e `itemprop` del HTML visible. El schema ahora se sirve exclusivamente vía JSON-LD (`<script type="application/ld+json">`), evitando que Google vea dos FAQPage en la misma página.

---

## [1.2.2] — 2026-07-10

### Añadido
- **Pestaña "FAQs SEO" en el menú izquierdo del back office**: acceso directo al listado de FAQs desde el menú principal, sin tener que navegar por Módulos → Configurar → Gestor.

---

## [1.2.1] — 2026-07-10

### Corregido
- **Botones de toolbar no visibles en Gestor de FAQs**: movidos de `renderList()` a `initPageHeaderToolbar()`, que es donde PrestaShop los espera.
- **Título por defecto mejorado para SEO**: ahora es "Preguntas frecuentes sobre {entity_name}" en vez de solo "Preguntas frecuentes". Así cada página tiene un título único con la entidad (categoría, fabricante...).

---

## [1.2.0] — 2026-07-10

### Añadido
- **Hooks independientes por tipo de página**: categoría, inicio, CMS y fabricante tienen su propio selector de hook en Configuración. Así puedes elegir el hook que funciona en cada tipo de página sin depender de un único global.
- **Auto-registro de hooks**: al entrar en la página de Configuración, el módulo verifica y registra automáticamente cualquier hook que falte. Ya no hace falta reinstalar al añadir hooks nuevos.

### Cambiado
- **Todos los hooks renderizan sin filtro de configuración**: la bandera `$faqRendered` evita duplicados. El ajuste de hook por tipo de página es orientativo para saber dónde transplantar el módulo.
- **Versión salta de 1.1.5 a 1.2.0** por la nueva funcionalidad de hooks por tipo de página.

---

## [1.1.5] — 2026-07-10

### Añadido
- **Hook `displayCategoryFooter`**: hook nativo del tema Panda que coloca las FAQs justo debajo del listado de productos en categorías, sin necesidad de tocar plantillas.
- **Selector de hook ampliado** con 4 opciones: Panda (recomendado), Personalizado, Producto y Universal.

### Cambiado
- **Hook por defecto**: ahora es `displayCategoryFooter` (Panda) en vez de `displayCategoryFaq`. El bloque aparece automáticamente en la posición correcta sin modificar el tema.

---

## [1.1.4] — 2026-07-10

### Añadido
- **Hook `displayFooter` como respaldo universal**: si `displayCategoryFaq` (requiere editar plantilla) y `displayFooterProduct` (solo producto) no se disparan, `displayFooter` garantiza que el bloque se muestre en cualquier página sin modificar el tema.

### Corregido
- **Deduplicación**: bandera `$faqRendered` evita que el bloque se renderice dos veces cuando múltiples hooks disparan en la misma página.

---

## [1.1.3] — 2026-07-10

### Corregido
- **Validación siempre falla ("pregunta vacía"/"respuesta vacía")**: `validateFaqSubmission()` y `sanitizeSubmittedAnswers()` leían `Tools::getValue('question')`/`Tools::getValue('answer')` como arrays, pero HelperForm envía campos multidioma con sufijo `_idLang` (ej: `question_1`, `answer_1`). Corregidos ambos métodos para usar los nombres reales.

---

## [1.1.2] — 2026-07-10

### Corregido
- **Categoría no se guardaba**: `resolveEntityFromPost()` se ejecutaba después de `parent::postProcess()`, así que el objeto se guardaba sin `entity_id`. Movido antes del padre.
- **Posición inconsistente**: provocada por el mismo bug — `getNextPosition()` calculaba mal al tener `entity_id = 0`.

---

## [1.1.1] — 2026-07-10

### Corregido
- **Formulario vacío en "Añadir nueva FAQ"**: `getFieldsForm()` devolvía `['form' => [...]]` provocando doble envoltorio con `AdminController::renderForm()`. Ahora devuelve el array sin la clave `'form'`.
- **Categorías mal ordenadas en el selector**: `Category::getCategories()` agrupa por nivel. Se reconstruye el árbol `id_parent` manualmente y se aplana con `flattenCategoryTree()` para mostrar jerarquía padre→hijo→nieto.
- **TypeError al guardar FAQ**: `ObjectModel::add()` y `update()` usan `&=` que convierte `bool` a `int`. Con `strict_types=1`, el retorno `int` rompe. Se añade cast `(bool)`.
- **Filtros rotos en el listado back office**: `filter_key` usaba alias `f!`/`fl!` pero el padre genera `a`/`b`. Corregidos a `a!`/`b!`.
- **Métodos muertos `getListQuery()` y `getListTotal()`**: eliminados. El padre usa `getFromClause()`/`getJoinClause()`/`getWhereClause()` internos.
- **Falta filtro `id_shop` en listado**: añadido `$this->_where` para no mezclar FAQs entre tiendas.
- **Cabecera `@version` desincronizada** y bloque `if` vacío en `renderFaqBlock()`.

---

## [1.1.0] — 2026-07-10

### Añadido
- Archivos SQL separados (`sql/install.php`, `sql/uninstall.php`)
- Archivo `config.xml` con metadatos del módulo
- Traducciones `.xlf` con 58 cadenas en español
- Selectores de entidad en formulario: categoría (lista indentada), CMS, fabricante
- Placeholder dual `{entity_name}` / `%category_name%`
- Validaciones servidor en formulario (entidad existe, pregunta/respuesta no vacías)
- CLAUDE.md con 10 reglas de desarrollo y lecciones aprendidas
- Referencia de código PS 8.2.7 en `.ref/prestashop-8.2/`

### Corregido
- Constructor: `parent::__construct()` antes de `$this->trans()`
- Firmas de métodos: `postProcess()` public, `afterDelete($object, $old_id)`, `getList()` params
- `$this->fields_form = $this->getFieldsForm()` en `renderForm()`
- `zip -D` eliminado del build (rompía extracción en PS)
- Template: `<div>` → `<section>` (semántica HTML5)
- `initBreadcrumbs()` eliminado (rompía `array_unique()`)
- `str_repeat()` con `max(0, ...)` para evitar valor negativo

---

## [1.0.0] — 2026-07-09

### Añadido

- Clase principal `fs_category_faq.php` con instalación/desinstalación, hooks y configuración
- ObjectModel `FsCategoryFaq` con soporte multidioma, `entity_type`/`entity_id` y sanitización HTML
- Controlador back office CRUD (`AdminFsCategoryFaqController`) con HelperList/HelperForm
- Selector dinámico de entidad en formulario: categoría (lista indentada), CMS, fabricante
- Filtro por tipo de entidad en listado + breadcrumbs + validaciones servidor
- Plantilla front office `category_faq.tpl` con acordeón nativo `<details>` (cero JS) y `<section>` semántica
- Datos estructurados JSON-LD FAQPage (schema.org) con schema.org inline en el HTML
- Configuración global: activar/desactivar, título, JSON-LD, nº máximo FAQs, hook, acordeón, CSS extra
- Hook `displayCategoryFaq` (principal) + `displayFooterProduct` (fallback automático)
- Placeholder dual `{entity_name}` / `%category_name%` para compatibilidad con especificación
- CSS front con variables, responsive, modo impresión, animaciones, accesibilidad
- JS front: deep-link `#faq-{id}`, cierre con Escape, scroll suave
- JS admin: toggle dinámico de selectores de entidad
- SQL separado en `sql/install.php` y `sql/uninstall.php`
- Archivo `config.xml` con metadatos del módulo
- Traducciones `.xlf` con 58 cadenas en español
- `logo.png` 32×32
- Archivos `index.php` de seguridad en los 11 directorios del módulo
- Sistema de versionado: `CHANGELOG.md` + `build.sh` + tags git
- Referencia de código PS 8.2.7 en `.ref/prestashop-8.2/` (6 clases clave)
- Reglas de desarrollo en `CLAUDE.md`
- ZIP de distribución en `releases/fs_category_faq-v1.0.0.zip` (36 archivos, 32 KB)
- Compatible con PrestaShop 8.0.0 → 9.99.99

### Auditoría PrestaShop 9

- ObjectModel propio sin cambios ✅
- `Db`, `Configuration`, `Module` sin cambios ✅
- `ModuleAdminController` + `HelperForm` siguen funcionando (desaprobados, no eliminados) ✅
- Sin dependencias de Guzzle, SwiftMailer, anotaciones ni contenedor global ✅

### Lecciones aprendidas (errores corregidos durante el desarrollo)

1. `class_exists($module_name)` en `Module::coreLoadModule()` — la clase debe llamarse igual que carpeta/archivo
2. `parent::__construct()` antes de `$this->trans()` — el traductor se inicializa en el padre
3. Firmas exactas de métodos — verificar en `.ref/` antes de sobrescribir
4. `zip -D` rompe `ZipArchive` — usar solo `-X` (sin atributos macOS)
5. `$this->fields_form` debe asignarse explícitamente en `renderForm()` — no se llama a `getFieldsForm()` automático
6. `'type' => 'categories'` no es un tipo estándar de HelperForm en PS 8.2 — usar select con query
7. `postProcess()` es `public` en AdminController, no `protected`
8. `afterDelete()` requiere 2º parámetro `$old_id`
9. `array_unique()` en `initToolbarTitle()` rompe con breadcrumbs personalizados → no sobreescribir `initBreadcrumbs()`
10. `str_repeat()` con `level_depth` puede recibir valor negativo → usar `max(0, ...)`
