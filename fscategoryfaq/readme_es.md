# FS Category FAQ SEO — Módulo para PrestaShop

> Añade bloques de preguntas frecuentes con datos estructurados JSON-LD FAQPage en páginas de categoría, inicio, CMS y fabricante.

**Versión:** 1.5.0  
**Compatibilidad:** PrestaShop 8.0 → 9.99.99  
**PHP:** 8.1+  
**Licencia:** Non-Commercial — Uso bajo licencia

---

## ✨ Características

- ✅ **CRUD completo** de FAQs desde el back office
- ✅ **4 tipos de página**: categoría, inicio, CMS y fabricante
- ✅ **Datos estructurados JSON-LD** `FAQPage` (schema.org) generados automáticamente
- ✅ **Acordeón HTML5 nativo** (`<details>`) — cero JavaScript, totalmente accesible
- ✅ **Multidioma**: pregunta y respuesta en todos los idiomas de la tienda
- ✅ **Multitienda**: cada FAQ se asocia a una tienda concreta
- ✅ **Configuración global**: título, hook, acordeón, número máximo, JSON-LD...
- ✅ **HTML seguro**: sanitización de etiquetas peligrosas (scripts, eventos, iframes)
- 📥 **Importar FAQs desde JSON**: subida masiva de FAQs desde archivo `.json` generado por DinoRank API u otras herramientas
- 📤 **Exportar FAQs a JSON**: descarga todas las FAQs en formato JSON para editarlas externamente o dárselas a tu IA de SEO como referencia
- ✅ **Caché integrado**: se limpia automáticamente tras cada cambio
- 🎨 **Panel de Diseño y colores**: 11 controles visuales para personalizar colores, tipografía, espaciado, sombras e iconos desde el back office sin tocar código
- 📋 **Filtro por categoría** en el listado de FAQs con desplegable del árbol de categorías
- ↕️ **Reordenar arrastrando** filas en el listado (drag & drop nativo de PrestaShop)
- ✅ **CSS responsive** con variables personalizables y modo impresión
- ✅ **Placeholders** en títulos: `{entity_name}` y `%category_name%`

---

## 📋 Requisitos

| Componente | Mínimo |
|------------|--------|
| PrestaShop | 8.0.0 |
| PHP        | 8.1 |
| MySQL      | 5.7+ (o MariaDB 10.2+) |

---

## 🚀 Instalación

### Instalación desde ZIP

1. Descarga el archivo `fs_category_faq-v1.5.0.zip`.
2. En tu back office de PrestaShop, ve a **Módulos → Gestor de módulos → Subir un módulo**.
3. Arrastra el ZIP o selecciónalo desde tu equipo.
4. Haz clic en **Instalar**.

### Instalación manual (FTP)

1. Descomprime el ZIP.
2. Sube la carpeta `fs_category_faq` a `/modules/` de tu PrestaShop.
3. Ve a **Módulos → Gestor de módulos**, busca "FS Category FAQ SEO" y haz clic en **Instalar**.

### Hook personalizado (opcional, recomendado)

Si tu tema no tiene el hook `displayCategoryFaq`, añádelo en la plantilla de categoría para controlar la posición exacta del bloque:

```smarty
{hook h='displayCategoryFaq' id_category=$category.id}
```

Ubicaciones sugeridas:
- `themes/tu-tema/templates/catalog/product-list.tpl` (debajo del listado de productos)
- `themes/tu-tema/templates/catalog/category.tpl` (al final de la página)

---

## ⚙️ Configuración

Accede desde **Módulos → FS Category FAQ SEO → Configurar**.

| Opción | Descripción | Valor por defecto |
|--------|-------------|-------------------|
| Módulo activo | Activa/desactiva el módulo globalmente | ✅ Sí |
| Mostrar título del bloque | Muestra u oculta el título sobre las FAQs | ✅ Sí |
| Título por defecto | Texto del título. Usa `{entity_name}` o `%category_name%` para insertar el nombre de la página | "Preguntas frecuentes" |
| Nº máximo de FAQs visibles | Límite de FAQs mostradas por página (1-50) | 10 |
| Usar acordeón | Acordeón colapsable con `<details>` nativo | ✅ Sí |
| FAQs abiertas por defecto | Solo aplica si el acordeón está activo | ❌ No |
| Clase CSS adicional | Clase extra para adaptar estilos a tu tema | *(vacío)* |
| Hook de visualización | `displayCategoryFaq` (personalizado) o `displayFooterProduct` (automático) | displayCategoryFaq |
| Activar JSON-LD | Genera schema FAQPage para Google | ✅ Sí |
| Conservar datos al desinstalar | Si se activa, las FAQs se conservan al desinstalar el módulo | ✅ Sí |

### Panel "Diseño y colores"

Además de las opciones anteriores, el módulo incluye un panel de personalización visual con **11 controles** para adaptar el bloque a la identidad de tu marca sin escribir CSS:

| Control | Descripción | Valor por defecto |
|---------|-------------|-------------------|
| Color de acento | Color principal para iconos, bordes activos y foco de teclado | `#1066B2` |
| Color de fondo | Fondo de cada tarjeta de pregunta | `#ffffff` |
| Color del texto de la pregunta | Color del texto de la pregunta | `#1a1a2e` |
| Color del texto de la respuesta | Color del contenido de la respuesta | `#4b5563` |
| Color de bordes | Color de los separadores entre preguntas | `#e5e7eb` |
| Redondeo de esquinas | Radio de borde de las tarjetas (sin redondeo / suave 12px / redondeado 24px) | Suave |
| Densidad del espaciado | Separación entre preguntas (compacto / cómodo / amplio) | Cómodo |
| Intensidad de la sombra | Elevación de las tarjetas (sin sombra / sutil / marcada) | Sutil |
| Estilo del icono | Icono del acordeón (flecha / más-menos / ninguno) | Flecha |
| Escala de texto | Tamaño relativo de título, pregunta y respuesta (compacto / normal / grande) | Normal |
| Ancho del bloque | Ancho máximo del bloque (100% / 1200px / 960px / 800px) | 100% |

Todos los valores se validan en servidor antes de generar el CSS. Los colores pasan por una validación estricta de formato hexadecimal (`#RRGGBB`), y el resto de opciones se contrastan contra listas blancas — **nunca** se interpola texto libre del administrador en el `<style>` generado.

---

## 📝 Gestión de FAQs

Accede desde **Módulos → FS Category FAQ SEO → Configurar → Gestor de FAQs**, o directamente desde el menú superior: **FAQs SEO → Gestor de FAQs**.

### Añadir una FAQ

1. Haz clic en **Añadir nueva FAQ**.
2. Selecciona el **tipo de página** (categoría, inicio, CMS o fabricante).
3. Elige la **entidad concreta** (ej: la categoría "Baterías solares").
4. Escribe la **pregunta** (máximo 255 caracteres).
5. Escribe la **respuesta** (HTML básico permitido).
6. Configura **activo** y **posición**.
7. Haz clic en **Guardar**.

### HTML permitido en respuestas

```html
<p>, <strong>, <em>, <ul>, <ol>, <li>, <a>, <br>
```

Las etiquetas `<script>`, `on*`, `<iframe>` y `javascript:` son **eliminadas automáticamente**.

### Listado

- Filtra por **tipo de página** (categoría, inicio, CMS, fabricante).
- **Filtra por categoría**: la columna "Entidad" incluye un desplegable con el árbol de categorías para ver solo las FAQs de una categoría concreta.
- Activa/desactiva FAQs con un clic desde el icono de estado.
- **Reordena arrastrando**: arrastra cualquier fila a la posición deseada (drag & drop nativo de PrestaShop). El orden se guarda por grupo (categoría/página), sin mezclar FAQs de distintas entidades.
- Elimina FAQs individualmente o en lote.

---

## 🖼️ Hooks disponibles

El módulo registra **5 hooks** y permite elegir un hook distinto para cada tipo de página (categoría, inicio, CMS, fabricante).

| Hook | Descripción | Cuándo se usa |
|------|-------------|---------------|
| `displayCategoryFooter` | Hook nativo del tema Panda. Coloca las FAQs justo debajo del listado de productos en categorías, sin tocar plantillas. | **Hook por defecto para categorías** |
| `displayCategoryFaq` | Hook personalizado. Debes insertarlo manualmente en tu plantilla para controlar la posición exacta. | Si necesitas una ubicación específica |
| `displayFooter` | Hook universal presente en todos los temas. Garantiza visibilidad sin editar plantillas. | Respaldo automático para inicio, CMS y fabricante |
| `displayFooterProduct` | Zona inferior de la página de producto. | Solo en páginas de producto |
| `displayHeader` | Carga CSS y JS solo en páginas con FAQs activas. | Automático |

### Configuración de hook por tipo de página

Cada tipo de página (categoría, inicio, CMS, fabricante) tiene su propio selector de hook en la configuración. Esto permite, por ejemplo:
- Usar `displayCategoryFooter` (Panda) en categorías — el bloque aparece automáticamente bajo los productos.
- Usar `displayFooter` en páginas CMS y de fabricante — visible sin tocar plantillas.
- Usar `displayCategoryFaq` en la home si quieres controlar la posición exacta.

El módulo incluye **deduplicación automática**: si varios hooks se disparan en la misma página, el bloque solo se renderiza una vez.

### Personalizar posición en el tema Panda

El hook por defecto (`displayCategoryFooter`) suele ser suficiente. Si necesitas una posición distinta, inserta este código en la plantilla de categoría de tu tema hijo:

```smarty
{* En themes/tu-tema-hijo/templates/catalog/product-list.tpl *}
{hook h='displayCategoryFaq' id_category=$category.id}
```

Y en la configuración del módulo selecciona **"Hook personalizado (displayCategoryFaq)"** para categorías.

---

## 🔍 Datos estructurados JSON-LD

El módulo genera automáticamente schema `FAQPage` de [schema.org](https://schema.org/FAQPage).

### Requisitos para que se genere

- Módulo activo globalmente.
- JSON-LD activado en la configuración.
- Al menos una FAQ **activa** y **visible** en la página.
- La pregunta y la respuesta **no están vacías**.

### Validación

Una vez instalado, valida tus URLs con:

- [Rich Results Test de Google](https://search.google.com/test/rich-results)
- [Schema Markup Validator](https://validator.schema.org/)

### Ejemplo de salida

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "¿Qué batería solar necesito para una vivienda aislada?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "La batería adecuada depende del consumo diario, la potencia del inversor y los días de autonomía que quieras cubrir."
      }
    }
  ]
}
```

> ⚠️ **Importante:** Las FAQs incluidas en el JSON-LD deben ser **visibles en la página**. No se incluyen FAQs ocultas, inactivas ni vacías. Google exige que el contenido del schema coincida exactamente con lo que el usuario ve.

---

## 🎨 Personalización visual

### Desde el back office (recomendado)

El **panel "Diseño y colores"** (en la página de Configuración del módulo) permite personalizar colores, tipografía, espaciado, sombras e iconos con 11 controles visuales, sin tocar ni una línea de código. Ver la [sección de configuración](#panel-diseño-y-colores) para el detalle de cada opción.

Los valores se validan en servidor y se inyectan como variables CSS en un `<style>` inline en la página, por lo que los cambios se ven inmediatamente al limpiar la caché.

### Mediante CSS (avanzado)

Si necesitas un control aún más fino, el bloque expone **variables CSS** que puedes sobrescribir desde tu tema:

```css
.fs-category-faq-block {
    --fs-faq-accent: #1066B2;
    --fs-faq-item-radius: 12px;
    --fs-faq-question-bg: #f7fafc;
    --fs-faq-question-color: #1a202c;
    /* ... y muchas más */
}
```

Todas las variables están documentadas en `views/css/front.css`.

---

## 🧹 Desinstalación

1. Ve a **Módulos → Gestor de módulos**.
2. Busca "FS Category FAQ SEO".
3. Haz clic en la flecha y selecciona **Desinstalar**.

### Conservar datos

Si la opción **"Conservar datos al desinstalar"** está activada (por defecto), las tablas y las FAQs se conservan al desinstalar. Si la desactivas, **todo el contenido se borrará permanentemente**.

---

## 🗃️ Estructura de archivos

```text
fs_category_faq/
├── fs_category_faq.php                      ← Clase principal del módulo
├── config.xml                               ← Metadatos
├── logo.png                                 ← Icono 32×32
├── classes/
│   └── FsCategoryFaq.php                    ← ObjectModel
├── controllers/
│   └── admin/
│       └── AdminFsCategoryFaqController.php ← CRUD back office
├── upgrade/
│   └── upgrade-1.3.1.php                    ← Script de actualización automática
├── sql/
│   ├── install.php                          ← Creación de tablas
│   └── uninstall.php                        ← Eliminación de tablas
├── views/
│   ├── css/
│   │   ├── front.css                        ← Estilos front (variables CSS)
│   │   └── admin.css                        ← Estilos back office
│   ├── js/
│   │   ├── front.js                         ← Deep-link + Escape + scroll
│   │   └── admin.js                         ← Toggle selectores de entidad
│   ├── templates/
│   │   └── hook/
│   │       └── category_faq.tpl             ← Plantilla front office
│   └── translations/
│       └── es.xlf                           ← 58 cadenas en español
└── index.php × 12                           ← Seguridad anti directory listing
```

---

## ❓ Solución de problemas

### Las FAQs no aparecen en el front

1. Verifica que el módulo esté **activo** en la configuración.
2. Comprueba que la FAQ esté **activa** y tenga **pregunta y respuesta** rellenas.
3. Revisa que la FAQ esté asociada a la **categoría/página correcta** y al **idioma actual**.
4. Si usas `displayCategoryFaq`, asegúrate de haber **insertado el hook** en tu plantilla.
5. Limpia la **caché de PrestaShop** (Parámetros avanzados → Rendimiento).

### Los cambios de estilos no se ven tras actualizar

Si has subido una nueva versión del módulo y los colores, iconos, sombras u otros estilos no se reflejan, **no basta con limpiar la caché de Smarty**. PrestaShop tiene un sistema de **caché CCC** (Combinar, Comprimir, Cachear) en **Parámetros avanzados → Rendimiento** que sirve versiones combinadas y cacheadas de los archivos CSS/JS. Para que los cambios en `front.css` o `admin.css` se vean:

1. Ve a **Parámetros avanzados → Rendimiento**.
2. En la sección **CCC (Combinar, Comprimir y Cachear)**, desactiva temporalmente la compresión de CSS (o limpia la caché CCC si tu versión de PrestaShop lo permite).
3. Vuelve a activarla tras verificar que los cambios se aplican.

> 💡 Los cambios hechos desde el panel "Diseño y colores" (color de acento, ancho, etc.) **sí** se ven inmediatamente porque se inyectan en un `<style>` inline en el propio HTML, no en el archivo CSS cacheado.

### El JSON-LD no aparece o no es válido

1. Verifica que **JSON-LD esté activado** en la configuración.
2. Asegúrate de que las FAQs **no estén vacías** (pregunta + respuesta).
3. Comprueba que la FAQ esté **activa y visible** en la página donde esperas ver el schema.
4. Valida la URL con [Rich Results Test](https://search.google.com/test/rich-results).

### El módulo no se instala

1. Verifica la versión de **PHP** (mínimo 8.1).
2. Verifica la versión de **PrestaShop** (mínimo 8.0.0).
3. Revisa los **permisos de escritura** en la carpeta `/modules/`.
4. Activa el **modo debug** de PrestaShop para ver el error específico.

### El acordeón no funciona en algún navegador

El acordeón usa `<details>` y `<summary>`, elementos nativos de HTML5 soportados por el **99.9% de navegadores** (Chrome, Firefox, Safari, Edge). Si no funciona, comprueba que no haya conflictos con el **CSS de tu tema** que oculten el contenido.

---

## 📊 Buenas prácticas SEO

- ✅ Escribe preguntas reales de clientes (compatibilidad, potencia, instalación, garantía...).
- ✅ Respuestas entre 50 y 120 palabras.
- ✅ Contenido original por categoría (nunca duplicado).
- ✅ Usa HTML básico para estructurar la respuesta.
- ❌ No inventes datos técnicos ni prometas compatibilidades no confirmadas.
- ❌ No crees FAQs genéricas tipo "¿Por qué comprar en nuestra tienda?".
- ❌ No escondas FAQs solo para el schema de Google.

---

## 🏷️ Versionado

Este módulo sigue [SemVer](https://semver.org/lang/es/): `MAJOR.MINOR.PATCH`.

| Versión | Fecha | Novedades |
|---------|-------|-----------|
| 1.5.0 | 2026-07-11 | Importar FAQs desde archivo JSON (subida masiva con validación) + Exportar FAQs a JSON (descarga para editar o compartir con IA de SEO) |
| 1.4.0 | 2026-07-10 | Filtro por categoría en el listado de FAQs + reordenar arrastrando filas (drag & drop) |
| 1.3.1 | 2026-07-10 | Color de acento `#1066B2` (azul de marca); script de actualización automática para instalaciones existentes |
| 1.3.0 | 2026-07-10 | Panel "Diseño y colores" con 11 controles visuales; rediseño del bloque front (icono, tarjetas, sombra, badge) |
| 1.2.3 | 2026-07-10 | Elimina atributos de microdatos del HTML visible; el schema FAQPage se sirve solo vía JSON-LD (evita duplicado) |
| 1.2.2 | 2026-07-10 | Pestaña "FAQs SEO" en el menú lateral del back office |
| 1.2.1 | 2026-07-10 | Toolbar del gestor corregido; título por defecto con `{entity_name}` para SEO |
| 1.2.0 | 2026-07-10 | Hook independiente por tipo de página (categoría/inicio/CMS/fabricante) con auto-registro |
| 1.1.5 | 2026-07-10 | Hook `displayCategoryFooter` (Panda) como posición por defecto |
| 1.1.4 | 2026-07-10 | Hook `displayFooter` como respaldo universal + deduplicación de bloques |
| 1.1.3 | 2026-07-10 | Corrige validación de formularios multidioma (`question_{idLang}`) |
| 1.1.2 | 2026-07-10 | Corrige guardado de entidad y cálculo de posición |
| 1.1.1 | 2026-07-10 | Corrige formulario vacío, TypeError al guardar, filtros y orden de categorías en el back office |
| 1.1.0 | 2026-07-10 | SQL separado, config.xml, traducciones .xlf, validaciones servidor, placeholders duales, CLAUDE.md, referencia PS 8.2.7 |
| 1.0.0 | 2026-07-09 | Versión inicial: CRUD, 4 tipos de entidad, acordeón nativo, JSON-LD, multidioma, multitienda |

---

## 👨‍💻 Desarrollo

Para continuar el desarrollo, lee `CLAUDE.md` (reglas de desarrollo) y `ESTADO.md` (progreso).

```bash
# Empaquetar una nueva versión
cd dev/
./build.sh [versión]

# El ZIP se genera en releases/
```

### Mantener este documento actualizado

Cada vez que se publique una nueva versión, este `readme_es.md` debe actualizarse con:
- El número de versión en la cabecera y en el nombre del ZIP de ejemplo
- Las nuevas funcionalidades en la sección de características
- Las nuevas opciones de configuración (si las hay)
- La tabla de versionado con la nueva entrada
- Cualquier cambio en hooks, estructura de archivos o solución de problemas

La documentación desactualizada es peor que no tener documentación: quien la lea asumirá que el módulo hace menos de lo que realmente hace.

---

**¿Preguntas o sugerencias?** Contacta con el desarrollador del módulo.
