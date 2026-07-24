# Estado del módulo FS Category FAQ SEO

**Última actualización:** 17 de julio de 2026
**Versión actual:** 1.7.7 (fixes de seguridad y calidad sobre v1.7.6)
**ZIP listo para instalar:** `releases/fs_category_faq-v1.7.7.zip`

## Fase actual: 🟢 v1.7.7 — Fixes de revisión

### ✅ Nuevo en v1.7.6 — displayHome y displayHomeBottom
La página de inicio ahora puede elegir entre 4 hooks: `displayHome` (contenido central, recomendado), `displayHomeBottom` (zona inferior de la home), `displayFooter` y `displayFooterProduct`. Antes solo estaban disponibles `displayFooter` y `displayFooterProduct`, lo que obligaba a mostrar las FAQs en el footer. Ahora pueden posicionarse en la zona central de la home (`displayHome`) donde tienen mucha más visibilidad.

El upgrade script registra automáticamente los nuevos hooks y migra el default de `displayFooter` a `displayHome` si el usuario no había cambiado manualmente la selección.

## Fase anterior: 🟢 v1.7.5 — mensajes de feedback en importación
El modo "Actualizar respuesta" de la importación ahora compara el answer y la question existentes con los del JSON. Si son idénticos, se salta la FAQ sin tocar la BD (contador "sin cambios"). Así puedes reimportar el JSON completo y solo se actualizan las FAQs cuyo HTML realmente cambió.

### ✅ Corregido (15 julio 2026) — Sanitización HTML
El campo `answer` eliminaba etiquetas `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`, `<h1>`–`<h6>`, `<img>`, `<span>`, `<div>` y otras al guardar porque `sanitizeAnswer()` usaba una whitelist de `strip_tags()` con solo 7 etiquetas. Se amplió a 31 etiquetas cubriendo tablas, formato extendido y elementos estructurales. También se actualizó el texto de ayuda en el formulario.

## Fase anterior: 🟢 FAQs visibles — mejorando posición en fabricantes (v1.7.2)

El bug original (FAQs no visibles) estaba resuelto: el módulo simplemente estaba desactivado. Las FAQs se muestran correctamente en categorías vía `displayCategoryFooter` (Panda).

### v1.7.2 — Mejora de posición en fabricantes

Las FAQs en páginas de fabricante salían en el `<footer>`, que queda mal visualmente. Se han añadido dos hooks mejor posicionados:
- **`displayFullWidthCategoryFooter`** (nuevo default para fabricantes): justo bajo `#wrapper`, en `full_width_bottom_container`. Misma zona que usa `stbanner`.
- **`displayWrapperBottom`**: en `wrapper_bottom_container`, actualmente vacío — sin otros módulos compitiendo.

Ambos disponibles también como opción para categorías desde el desplegable de configuración.

## Fase actual: 🟢 BUG RESUELTO — el módulo estaba desactivado

El hook `displayCategoryFooter` de Panda **sí existe** en `themes/panda/templates/catalog/listing/category.tpl:127`. Nuestro `hookDisplayCategoryFooter()` es correcto y siempre lo fue. **El módulo simplemente estaba desactivado** en el back office (Módulos → Gestor de módulos → FS Category FAQ SEO → Activar). Al reactivarlo, las FAQs vuelven a mostrarse en `displayCategoryFooter`.

### Lo que se aprendió (y queda documentado)
1. **`displayCategoryFooter` es un hook del tema Panda**, no de PrestaShop. Se invoca desde `catalog/listing/category.tpl:127` con `{hook h='displayCategoryFooter'}`.
2. **`jprestaspeedpack` sobreescribe `override/classes/Hook.php`**, interceptando todos los hooks. Si las FAQs dejan de verse en el futuro, comprobar: (a) que el módulo esté activo, (b) limpiar la caché de página de jprestaspeedpack, (c) desactivar temporalmente el override.
3. **El error `admin_addons_login` del dashboard** era por `psaddonsconnect` y la caché de Symfony corrupta, no por nuestro módulo.

### v1.7.1 — Limpieza
- Retirados `logDebug()`, `renderDebugPanel()` y los marcadores HTML `<!-- FSFAQ ... -->`.
- Mantenido el respaldo universal `displayFooter` con guard `faqRendered` como red de seguridad.

## (Histórico) Diagnóstico previo — FAQs no visibles en el front (categorías)

### 🔴 Pendiente crítico: FAQs no se muestran en categorías

**Síntoma:** El bloque de FAQs no aparece en las páginas de categoría. El menú admin y el CRUD funcionan correctamente, pero el front no renderiza el bloque.

**Dato del sitio:** `id_lang = 2` es el español (idioma principal). Un mismatch de idioma sería sospechoso — los logs y el panel de diagnóstico imprimen el `id_lang` en uso.

**Intentos de fix:**
1. ✅ v1.6.5 — Corregido SQL del upgrade (menú bajo Catálogo) → no resolvió el display
2. ✅ v1.6.6 — Cambiado `{$faq@first}` → `{$smarty.foreach.faqLoop.first}` (sintaxis Smarty 4/5) → no resolvió
3. 🔍 v1.6.7-debug — `error_log()` en el flujo → **el usuario no vio nada en los logs** (destino de `error_log` no accesible en este hosting, o hooks no se ejecutan)
4. 🔍 v1.6.8-debug — canal de log movido a `ps_log` (visible en BO → Parámetros avanzados → Logs) + panel de diagnóstico en la página de config del módulo (estado real sin caché del front). **Pendiente: que el usuario suba, abra una categoría y reporte (a) lo que muestra el panel de config y (b) si aparecen líneas `[FSFAQ DEBUG]` en BO → Logs.**

**Hipótesis principal tras el "nada en logs":** o `error_log` iba a un destino no visible (lo resuelve el canal `ps_log`), o los hooks del módulo no se ejecutan en el front por la **caché de página de `jprestaspeedpack`** (que ya sabemos que sobrescribe `classes/Hook.php`). El panel de config + los logs de `ps_log` distinguen ambos casos.

### ✅ DIAGNÓSTICO CERRADO (13 julio 2026, con v1.6.8-debug)

El panel de diagnóstico confirmó que **por el lado de datos todo está perfecto**: módulo activado, los 5 hooks registrados (ninguno falta), 202 FAQs de categoría, todas con `id_shop=1`, activas y con "lang OK" ✔ para `id_lang=2`. **Y aun así, al abrir una categoría en el front, el log `ps_log` sale VACÍO** (ni una línea `[FSFAQ DEBUG]`).

Como cada hook loguea en su primera línea antes de cualquier comprobación, log vacío = **los hooks del módulo NO se ejecutan en el front**. No es un problema de código, datos ni idioma. **Causa: `jprestaspeedpack` sirve la página de categoría desde su caché de página completa** (HTML estático, sin ejecutar PHP), y esa copia se generó cuando el bloque aún estaba roto (versiones previas), por eso no tiene FAQs. La caché de Smarty/CCC que limpió el usuario es distinta de la caché de página de jprestaspeedpack.

**Descartes realizados por el usuario:**
1. Categoría con `?test=123` → las FAQs **siguen sin aparecer**.
2. `jprestaspeedpack` → **caché vaciada y módulo desactivado** → sigue sin aparecer y el log sigue vacío.
3. Diff del código de render del front v1.5.0 → HEAD: **los hooks, `detectCurrentEntity()` y `renderFaqBlock()` NO cambiaron** desde la versión que funcionaba. Lo único que cambió fue admin (menú, `$this->tab`, tamaño de título, modo de apertura) y la plantilla (`$open_default` → `$open_mode`, inocuo). **Confirmado: no es una regresión de nuestro código.**

### ❌ (DESCARTADO) Hipótesis Cloudflare cacheando el HTML — ver causa raíz real arriba

El sitio está detrás de **Cloudflare**. Con la caché de página completa de Cloudflare ("Cache Everything") activa, el HTML de las categorías se sirve desde el edge de Cloudflare **sin llegar al servidor**, así que PrestaShop no ejecuta el módulo (por eso el log de `ps_log` sale vacío) y se sigue mostrando una copia vieja guardada de cuando el bloque estaba roto. Encaja con TODO: log vacío, limpiar Smarty/CCC/jprestaspeedpack (todas en el origen, por debajo de Cloudflare) no hace nada, funcionaba en 1.4/1.5 (cuando la copia en Cloudflare era buena), y `?test=123` no ayudó (la regla de Cloudflare ignora query strings).

**No es un bug del módulo — el módulo funciona.** Es infraestructura/Cloudflare.

**Fix (pendiente de que el usuario lo aplique):**
1. Confirmar: Cloudflare → Caching → Configuration → activar **Development Mode** (bypass de caché 3h) y abrir una categoría en incógnito. Si aparecen las FAQs → confirmado.
2. Arreglar: Cloudflare → Caching → **Purge Everything**. La siguiente visita regenera la página ya con las FAQs y esa es la que Cloudflare cachea.
3. A futuro (decisión del equipo web del cliente): cachear el HTML completo de una tienda dinámica con "Cache Everything" es agresivo — también retrasa precios/stock/cualquier cambio de contenido. Valorar excluir el HTML de categorías/productos o usar TTL corto + purga al actualizar.

Una vez confirmado que se ven las FAQs, hacer **v1.7.0 limpio** retirando `logDebug()` y `renderDebugPanel()`.

**Posibles causas a investigar:**
- [ ] ¿`FSCATEGORYFAQ_ENABLED` = false? (módulo desactivado sin querer)
- [ ] ¿Las FAQs existen en la BD? (verificar `ps17_fs_category_faq` y `_lang`)
- [ ] ¿El hook `displayFooter` está ejecutándose? (es el fallback universal)
- [ ] ¿`detectCurrentEntity()` devuelve null? (`php_self` !== 'category' en el theme Panda)
- [ ] ¿Error PHP silencioso? (revisar logs del servidor)
- [ ] ¿Caché de Smarty/PS sirviendo plantilla compilada antigua?
- [ ] ¿Módulo `jprestaspeedpack` cacheando páginas sin el bloque?
- [ ] ¿El `id_shop` en la query es correcto? (debería ser 1 en instancia single-shop)

**Plan de diagnóstico:** añadir `error_log()` en puntos clave de `renderFaqBlock()` y `detectCurrentEntity()` para trazar dónde se corta el flujo.

### Progreso

- [x] Lectura de instrucciones y especificación
- [x] Auditoría de requisitos
- [x] Aprobación de mejoras por el usuario
- [x] Implementación de la clase principal (`fscategoryfaq.php`)
- [x] Implementación del modelo ObjectModel (`FsCategoryFaq`)
- [x] Implementación del controlador back office (CRUD completo)
- [x] Plantilla front office con acordeón nativo y JSON-LD
- [x] CSS/JS front + admin
- [x] Archivos `index.php` de seguridad (12 directorios)
- [x] Archivos SQL separados (`sql/install.php`, `sql/uninstall.php`)
- [x] Archivo `config.xml`
- [x] Traducciones `.xlf` (español, 58 cadenas)
- [x] Sistema de versionado (CHANGELOG.md + SemVer + build.sh)
- [x] Referencia de código PS 8.2.7 en `.ref/`
- [x] Reglas de desarrollo en `CLAUDE.md`
- [x] Auditoría de compatibilidad con PrestaShop 9: **sin problemas**
- [x] Documentación final (`readme_es.md`)
- [x] Pruebas reales en PrestaShop (instalación, CRUD, front, validación JSON-LD)

### Decisiones confirmadas

| # | Decisión |
|---|----------|
| 1 | Controlador **legacy** (compatible PS 8.x y PS 9.x) |
| 2 | Acordeón con **`<details>` nativo HTML5** (cero JS, accesible) |
| 3 | **Conservar datos** al desinstalar por defecto (configurable) |
| 4 | `displayFooterProduct` como **fallback automático** |
| 5 | Namespaces modernos (`FSCategoryFaq`) en clases helper |
| 6 | `entity_type` + `entity_id` para soportar categoría, inicio, CMS y fabricante |
| 7 | FAQ → 1 sola entidad (sin multi-asociación) |
| 8 | Nombre del módulo: `fs_category_faq` → clase `Fs_Category_Faq` |
| 9 | Placeholders duales: `{entity_name}` y `%category_name%` |
| 10 | ZIP sin metadatos macOS y **con** entradas de directorio |

### Archivos implementados (39 en ZIP)

| Archivo | Descripción |
|---------|-------------|
| `fscategoryfaq.php` | Clase principal: install/uninstall, hooks, JSON-LD, config |
| `config.xml` | Metadatos del módulo |
| `logo.png` | Icono 32×32 |
| `classes/FsCategoryFaq.php` | ObjectModel con entity_type/entity_id |
| `controllers/admin/AdminFsCategoryFaqController.php` | CRUD con HelperList/HelperForm |
| `sql/install.php` | Creación de tablas |
| `sql/uninstall.php` | Eliminación de tablas |
| `upgrade/upgrade-1.3.1.php` | Da de alta en tiendas ya instaladas las claves de Configuration nuevas del panel de Diseño |
| `views/templates/hook/category_faq.tpl` | Plantilla front con acordeón nativo |
| `views/css/front.css` | Estilos front (variables CSS, responsive, print) |
| `views/css/admin.css` | Estilos admin |
| `views/js/front.js` | JS front (deep-link, Escape, scroll suave) |
| `views/js/admin.js` | JS admin (toggle selectores de entidad) |
| `views/translations/es.xlf` | 58 cadenas en español |
| `index.php` × 11 | Seguridad anti directory listing |
| `build.sh` | Script de empaquetado |
| `CLAUDE.md` | Reglas de desarrollo |
| `CHANGELOG.md` | Historial de versiones |

### Auditoría PS9 — OK sin cambios

El módulo es compatible con PrestaShop 8.0 → 9.99.99. ObjectModel propio, Db, Configuration y Module sin cambios en PS9. `ModuleAdminController` y `HelperForm` siguen funcionando (desaprobados pero no eliminados).

### v1.3.0 — Rediseño visual + panel de "Diseño y colores" (10 julio 2026)

Petición del usuario: el bloque quedaba recortado a la derecha (no usaba el 100% de su columna) y pidió poder personalizar colores/tamaños desde el propio módulo, con un diseño elegante y buena UX.

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Bug de ancho | `views/css/front.css` | `max-width: 800px` fijo → `width: 100%` + variable `--fs-faq-max-width` (configurable, 100% por defecto) |
| Panel de diseño | `fscategoryfaq.php` | 11 Configuration keys nuevas (5 colores + redondeo + densidad + sombra + icono + tamaño de texto + ancho), con `RADIUS_MAP`/`DENSITY_MAP`/`SHADOW_MAP`/`TEXT_SCALE_MAP`/`MAX_WIDTH_MAP` como listas blancas y `sanitizeHexColor()`/`sanitizeChoice()` para que nunca se interpole texto libre del admin en el CSS generado |
| Inyección de estilo | `fscategoryfaq.php` (`buildDesignStyle()`), `category_faq.tpl` | El bloque de `--fs-faq-*` validado se vuelca en un `<style>` inline scoped a `.fs-category-faq-block`, justo antes de la sección |
| Rediseño visual | `views/css/front.css`, `category_faq.tpl` | Chevron CSS puro (nuevo icono por defecto), borde de acento en el ítem abierto, elevación al hover, badge contador de preguntas, barra de acento junto al título, `color-mix()` con fallback, `prefers-reduced-motion` |
| Traducciones | `views/translations/es.xlf` | +36 cadenas nuevas (m32–m67) |

Verificado: `type => 'color'` de HelperForm confirmado contra el código fuente real de PrestaShop 8.2 (GitHub) — carga automáticamente el plugin jQuery `colorpicker`, no requiere wiring adicional en el módulo.

### v1.3.1 — Confirmación del plan + color de marca + fix de actualización (10 julio 2026)

El usuario pidió confirmar antes de implementar las decisiones creativas de la v1.3.0 (color, icono, ancho, estilo de tarjeta). Confirmado vía `AskUserQuestion`: icono flecha, ancho 100%, tarjeta suave — todos ya correctos. Único cambio: color de acento → `#1066B2` (azul real de la marca, no el naranja que había supuesto).

Al revisar esto se detectó un problema real: el módulo ya estaba instalado en producción en v1.2.3, e `installDefaultConfig()` solo corre en instalación nueva — sin más, las 11 claves nuevas de Configuration nunca se habrían escrito en la BD real al subir solo los archivos. Se añadió `upgrade/upgrade-1.3.1.php` (verificado contra `Module::runUpgradeModule()`/`loadUpgradeVersionList()` en `.ref/prestashop-8.2/Module.php:585-735`), que PrestaShop ejecuta automáticamente al detectar el salto de versión. Ahora son 12 directorios con `index.php` de seguridad (antes 11) y 39 archivos en el ZIP (antes 36).

**✅ Verificado en producción (10 julio 2026):** tras subir el ZIP, el ancho al 100% y el color de acento `#1066B2` funcionaban, pero el resto del rediseño visual (icono chevron, tarjetas redondeadas/con sombra, badge de contador, barra de acento) no se veía. Causa: la caché "Combinar/Comprimir/Cachear CSS" (CCC) de PrestaShop (Parámetros avanzados → Rendimiento) servía el `front.css` antiguo. Al desactivar/limpiar esa caché, todo el diseño se mostró correctamente. **Lección para futuros cambios de CSS/JS en este sitio: además de subir archivos y limpiar la caché de Smarty, hay que limpiar/regenerar la caché CCC — si no, los cambios de estilos no se ven aunque el archivo en el servidor sea el correcto.**

De paso se detectó y descartó como irrelevante: `front.css` se ve con mojibake en los comentarios (`DiseÃ±o` en vez de `Diseño`) al abrirlo directo en el navegador — el servidor no declara `charset=utf-8` para `.css`. No afecta a la aplicación de estilos (los comentarios no los interpreta el navegador); es un detalle de configuración del hosting, no del módulo.

### v1.4.0 — Filtro por categoría + reordenar arrastrando (10 julio 2026)

Petición directa del cliente (vía el usuario): en el Gestor de FAQs, poder filtrar el listado por categoría para ver solo esa categoría, y poder reordenar arrastrando filas en vez de escribir el número de posición a mano.

Al investigar el reordenamiento se encontró que **arrastrar no funcionaba en absoluto**: `FsCategoryFaq` no tenía `updatePosition()`, el método que PrestaShop necesita para procesar el drag-and-drop (`AdminController::processPosition()`, verificado en `.ref/prestashop-8.2/AdminController.php:1417`). Se añadió siguiendo el patrón real de `CMS::updatePosition()` del core (verificado vía fetch a GitHub, ya que `ObjectModel.php` no trae una implementación genérica — cada entidad con posición define la suya), agrupando por `entity_type` + `entity_id` + `id_shop` para que arrastrar una FAQ nunca mezcle su orden con el de otra categoría/página.

Para el filtro: se amplió la columna "Entidad" existente en `AdminFsCategoryFaqController.php` con un desplegable de categorías (reutilizando `buildCategoryOptions()`), filtrando sobre `entity_id`. Como `entity_id` es un entero que también usan CMS y fabricante (cada uno con su propio autoincremental), `getList()` exige además `entity_type = 'category'` cuando este filtro está activo, para que un `id_cms`/`id_manufacturer` que coincida numéricamente con la categoría elegida no se cuele en el listado.

**Alcance decidido con el usuario:** el filtro es solo para categorías (no se extendió a CMS/fabricante — el cliente solo pidió categoría, y es el caso de uso principal del módulo).

**✅ Confirmado en producción (10 julio 2026):** el filtro de categoría y el reordenar arrastrando funcionan correctamente en fusionenergiasolar.es.

### Para retomar

Di **"continúa"**.

### v1.5.0 — Importar/Exportar FAQs desde JSON (11 julio 2026)

Petición del usuario: la IA de SEO (DinoRank) genera FAQs con datos reales de keywords y las entrega en `.json`. Se necesita poder importarlas masivamente sin insertar una a una por el CRUD manual. De paso, poder exportar las FAQs existentes para dárselas a la IA como referencia.

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Botón "Importar FAQs" | `AdminFsCategoryFaqController.php` | Nuevo botón en toolbar → formulario de subida de archivo `.json` → validación FAQ por FAQ → inserción con `FaQModel::add()` → resumen de X importadas / Y errores |
| Botón "Exportar FAQs" | `AdminFsCategoryFaqController.php` | Descarga todas las FAQs de la tienda en `.json` con metadatos (entity_type, entity_id, entity_name, question, answer, active, position, id_lang) |
| `getAllWithTranslations()` | `classes/FsCategoryFaq.php` | Nuevo método estático para consultar FAQs con sus traducciones (left join a `_lang`), reutilizable por export y futuras funcionalidades |
| Traducciones | `views/translations/es.xlf` | +20 cadenas nuevas (a33–a52) para la UI de import/export y mensajes de error |
| Upgrade script | `upgrade/upgrade-1.5.0.php` | Placeholder (sin cambios en BD) para que PS reconozca el salto de versión |

**Formato JSON definido para la IA**: array `faqs` con objetos `{entity_type, entity_id, question, answer (HTML), active}`. El `.json` de ejemplo con 30 FAQs de Generadores está en `../textosfaqs/faqs-generadores.json`. **✅ Confirmado en producción (12 julio 2026):** import/export funciona correctamente en fusionenergiasolar.es.

### v1.5.1 — Reemplazo quirúrgico al importar (12 julio 2026)

La opción "Reemplazar FAQs existentes" de la v1.5.0 borraba **todas** las FAQs de la tienda antes de importar. Esto era peligroso: si el JSON solo trae FAQs de "Generadores", no hay motivo para borrar las de otras categorías.

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Reemplazo por entidad | `AdminFsCategoryFaqController.php` | Solo se borran las FAQs cuyo `entity_type` + `entity_id` coinciden con los del archivo. Se recopilan las entidades únicas del JSON, y solo esas se eliminan antes de importar. |
| Resumen con conteo de eliminadas | `AdminFsCategoryFaqController.php` | El mensaje de confirmación ahora incluye "Se eliminaron X FAQs existentes de las mismas páginas antes de importar." |
| Texto explicativo en UI | `AdminFsCategoryFaqController.php` | La casilla ahora dice "Reemplazar FAQs existentes en las mismas páginas" con un ejemplo concreto de qué se borra y qué no. |
| Traducciones | `views/translations/es.xlf` | Actualizadas las cadenas a53–a54, nueva a55 para el mensaje de eliminadas. |

### v1.6.0 — Título independiente + menú en Configuración + select all (13 julio 2026)

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Tamaño del título independiente | `fscategoryfaq.php` | Nuevo selector "Tamaño del título" en Diseño y colores: 5 tamaños (1.25rem a 2.75rem). La escala general ahora solo controla pregunta y respuesta. Nueva constante `TITLE_SIZE_MAP` y clave `FSCATEGORYFAQ_TITLE_SIZE`. |
| Checkbox "seleccionar todas" | `AdminFsCategoryFaqController.php` | Añadido `$this->list_id = 'fs_category_faq'` para que HelperList muestre la casilla de selección masiva en la cabecera del listado. |
| Menú bajo Configuración | `fscategoryfaq.php`, `upgrade-1.6.0.php` | La pestaña pasa de `tab='seo'` a `tab='administration'` con `parent_class_name='AdminParentPreferences'`. El upgrade recoloca la pestaña existente y añade la nueva clave de Configuration. |
| Traducciones | `views/translations/es.xlf` | +8 cadenas (a65–a72) para el control de tamaño del título. |

Próximas mejoras posibles:
- Traducciones a otros idiomas además de español
- Selector visual de categorías (tree widget) en vez de `<select>`
- Tests automatizados

### v1.6.1 — Selector modo apertura + fix menú en Configuración (13 julio 2026)

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Selector "Modo de apertura" | `fscategoryfaq.php`, `category_faq.tpl`, `upgrade-1.6.1.php` | El antiguo switch ON/OFF `FSCATEGORYFAQ_OPEN_DEFAULT` se reemplaza por `FSCATEGORYFAQ_OPEN_MODE` con 3 opciones: `all_closed`, `first_open` (default), `all_open`. Migración automática en upgrade. |
| Checkbox "seleccionar todas" | `views/js/admin.js` | JS que inyecta el checkbox en la cabecera del listado y cablea `toggleBulkBar()` para acciones masivas. |
| Menú en Configuración (reintento) | `upgrade-1.6.1.php` | Segundo intento de mover la pestaña bajo Configuración. |

### v1.6.2 — Menú bajo "Catálogo" (13 julio 2026)

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Menú bajo Catálogo | `upgrade-1.6.2.php` | Búsqueda SQL en `ps_tab_lang` del padre "Catálogo" (con fallbacks: AdminCategories → AdminProducts → AdminParentOrders). |
| `installTab()` | `upgrade-1.6.2.php` | Se usa `$module->installTab()` (API oficial PS 8.x) para crear la pestaña con permisos automáticos. |

### v1.6.3 — Fix SQL multilínea (13 julio 2026)

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| SQL en una línea | `upgrade-1.6.3.php` | MariaDB no toleraba strings SQL multilínea con `Db::getInstance()->getValue()`. Simplificado a concatenación PHP en una línea, sin `LIMIT 1`. |
| Icono `help` | `fscategoryfaq.php` | Añadido `icon => 'help'` al array `$tabs`. |

### v1.6.4 — Sincronización de upgrade scripts (13 julio 2026)

Misma query SQL corregida replicada en `upgrade-1.6.2.php` a `upgrade-1.6.4.php`. Placeholder para el salto de versión.

### v1.6.5 — Fix SQL en upgrade para "Catálogo" (13 julio 2026)

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Fix definitivo del SQL | `upgrade-1.6.5.php` | Query corregida y sincronizada. Búsqueda de "Catálogo" por nombre en `ps_tab_lang` con variantes (`Catálogo`, `Catalog`, `%Cat%logo%`). |

**✅ Confirmado en producción:** menú bajo Catálogo funciona, import/export funciona.

### v1.6.6 — Fix Smarty `{$faq@first}` → `{$smarty.foreach.faqLoop.first}` (13 julio 2026)

| Cambio | Archivo(s) | Detalle |
|---|---|---|
| Sintaxis Smarty | `category_faq.tpl` | `{$faq@first}` (Smarty 3) no compatible con Smarty 4/5 de PS 8.2. Reemplazado por `{$smarty.foreach.faqLoop.first}`. |

**❌ No resolvió el bug de display.** Las FAQs siguen sin mostrarse en el front. Ver diagnóstico pendiente arriba.

---

### 🔧 Otros pendientes

- [ ] **Checkbox "seleccionar todas"** en el listado — el JS se implementó pero no se ha verificado en producción
- [ ] **Diagnosticar FAQs no visibles** — añadir `error_log()` en `renderFaqBlock()` para trazar el flujo

### Bugs corregidos (10 julio 2026 — sesión de revisión)

| # | Bug | Archivo | Corrección |
|---|-----|---------|------------|
| 9 | `filter_key` con alias de tabla incorrectos (`f!`, `fl!` en vez de `a!`, `b!`) | `AdminFsCategoryFaqController.php` | Corregidos a `a!entity_type`, `b!question`, `a!active` |
| 10 | Métodos `getListQuery()` y `getListTotal()` nunca llamados (dead code) | `AdminFsCategoryFaqController.php` | Eliminados. Se usa `$this->_where` + `parent::getList()` |
| 11 | Faltaba filtro `id_shop` en el listado del back office | `AdminFsCategoryFaqController.php` | Añadido `$this->_where = 'AND a.id_shop = ...'` |
| 12 | Bloque `if (!$this->isCached(...)) {}` vacío | `fscategoryfaq.php` | Eliminado |
| 13 | Cabecera `@version 1.0.0` inconsistente con `$this->version = '1.1.0'` | `fscategoryfaq.php` | Corregida a `1.1.0` |
| 14 | Formulario vacío al hacer clic en "Añadir nueva FAQ" | `AdminFsCategoryFaqController.php` | `getFieldsForm()` devolvía `['form' => [...]]` pero el padre (`AdminController::renderForm()`) envuelve con otro `[['form' => ...]]` cuando `multiple_fieldsets=false`. Resultado: doble envoltorio. Corregido: devolver array sin clave `'form'`. |
| 15 | Categorías ordenadas por nivel (todos los padres, luego hijos...) | `AdminFsCategoryFaqController.php` | `Category::getCategories()` ordena por `level_depth`. Corregido: usar `Category::getNestedCategories()` + `flattenCategoryTree()` recursivo para mostrar árbol padre→hijo→nieto. |
| 16 | `TypeError: Return value must be of type bool, int returned` al guardar FAQ | `classes/FsCategoryFaq.php` | `ObjectModel::add()` usa `&=` (bitwise AND), que en PHP convierte `bool` a `int`. Nuestro `add(): bool` con `strict_types=1` no admite `int`. Corregido: `return (bool) parent::add(...)`. Mismo fix en `update()`. |
