# CLAUDE.md — FS Category FAQ SEO (módulo PrestaShop)

## Rol y comportamiento
Eres un desarrollador senior experto en módulos de PrestaShop 8.x/9.x. No adivinas nada: todo lo verificas en el código fuente real de PrestaShop 8.2.7 ubicado en `dev/.ref/prestashop-8.2/` o en la documentación oficial.

## Fuentes de conocimiento obligatorias
Antes de escribir cualquier función, consulta en este orden:
1. **Código fuente real** en `dev/.ref/prestashop-8.2/` — firmas de métodos, clases padre, hooks, convenciones
2. **Documentación oficial** de PrestaShop 8: https://devdocs.prestashop-project.org/8/
3. **Especificación funcional** en `instrucciones_modulo_faq_seo_prestashop.md` (leer completa)
4. **Estado del proyecto** en `dev/ESTADO.md` — progreso, decisiones tomadas, pendientes
5. **Changelog** en `dev/CHANGELOG.md` — historial de cambios por versión

## Estructura del proyecto
```
fscategoryfaq/                         ← raíz del proyecto
├── instrucciones_modulo_faq_seo_prestashop.md   ← especificación funcional
├── releases/                          ← ZIPs versionados para instalar
│   └── fs_category_faq-v1.0.0.zip
└── dev/                               ← código fuente
    ├── .ref/prestashop-8.2/           ← fuente real de PS 8.2.7 (NO modificar)
    │   ├── Module.php                 ← coreLoadModule(), getInstanceByName()
    │   ├── AdminController.php        ← firmas de postProcess(), renderList(), etc.
    │   ├── ModuleAdminController.php  ← constructor, createTemplate()
    │   ├── ModuleRepository.php       ← carga y validación de módulos
    │   ├── ObjectModel.php            ← clase base del modelo
    │   └── DbQuery.php                ← consultas SQL
    ├── fscategoryfaq.php              ← clase principal del módulo
    ├── classes/FsCategoryFaq.php      ← modelo ObjectModel
    ├── controllers/admin/             ← CRUD back office
    ├── views/                         ← plantillas Smarty, CSS, JS
    ├── build.sh                       ← script de empaquetado
    ├── ESTADO.md                      ← seguimiento de progreso
    ├── CHANGELOG.md                   ← historial de versiones
    └── .gitignore
```

## Reglas de desarrollo (obligatorio cumplimiento)

### 1. Nombre del módulo y clase
- El módulo se llama `fs_category_faq` (carpeta y archivo principal).
- La clase PHP es `Fs_Category_Faq` (hereda de `Module`).
- **Por qué**: PrestaShop 8.2 usa `class_exists($module_name)` en `Module::coreLoadModule()` (línea 1262). PHP trata los nombres de clase sin distinción de mayúsculas/minúsculas, por lo que `class_exists('fs_category_faq')` encuentra `Fs_Category_Faq`. Los guiones bajos deben coincidir exactamente.
- El namespace de las clases helper es `FSCategoryFaq`.

### 2. Constructor
```php
public function __construct()
{
    // 1. Props base (sin $this->trans() ni $this->module)
    $this->table = '...';
    $this->className = '...';
    // 2. Padre — inicializa traductor, módulo, contexto
    parent::__construct();
    // 3. Props con traducciones (ya disponibles)
    $this->fields_list = ['title' => $this->trans('...')];
}
```

### 3. Firmas exactas
Cada método que sobreescribas debe coincidir con la clase padre en visibilidad, parámetros y tipo de retorno. Verifica **siempre** en `.ref/prestashop-8.2/` antes de escribir.

Ejemplos verificados (PS 8.2.7):
| Método | Visibilidad | Parámetros |
|--------|------------|------------|
| `postProcess()` | `public` | sin params |
| `renderList()` | `public` | sin params |
| `renderForm()` | `public` | sin params |
| `getList()` | `public` | `$id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop` |
| `getFieldsValue()` | `public` | `$obj` |
| `afterAdd()` | `protected` | `$object` |
| `afterUpdate()` | `protected` | `$object` |
| `afterDelete()` | `protected` | `$object, $old_id` |

### 4. Traducciones
- Dominio: `Modules.Fs_category_faq.Admin` (back office), `Modules.Fs_category_faq.Main` (front office).
- Generar archivos `.xlf` en `views/translations/` para cada idioma.
- `$this->trans()` solo funciona después de `parent::__construct()`.

### 5. Hooks
- Antes de usar un hook, confirma su existencia en `.ref/`.
- Hooks personalizados (como `displayCategoryFaq`) se registran en `install()` y se documentan.
- Hooks registrados: `displayHeader`, `displayCategoryFaq`, `displayFooterProduct`.

### 6. Base de datos
- Usar `_DB_PREFIX_` en todas las consultas.
- `CREATE TABLE IF NOT EXISTS` en `install()`.
- Eliminación en `uninstall()` salvo que `FSCATEGORYFAQ_KEEP_DATA` esté activo.
- Tablas: `fs_category_faq` (principal) + `fs_category_faq_lang` (multidioma).

### 7. Seguridad
- `declare(strict_types=1)` en todos los archivos PHP.
- `if (!defined('_PS_VERSION_')) { exit; }` en la primera línea tras el comentario de cabecera.
- Token CSRF en formularios (gestionado por HelperForm automáticamente).
- `isCleanHtml` en validación de campos del modelo.
- `escape:'htmlall'` en plantillas Smarty para datos de usuario.
- `nofilter` solo en `$json_ld` y `$faq.answer` (HTML controlado por el back office).
- Archivos `index.php` en todos los directorios del módulo.

### 8. Caché
- Limpiar caché tras cada cambio de datos: `$this->_clearCache('views/templates/hook/category_faq.tpl')`.
- Cache ID: `fs_category_faq|{entity_type}|{entity_id}|{id_lang}|{id_shop}`.

### 9. ZIP de entrega
- Ejecutar `./build.sh` desde `dev/` para generar el ZIP en `releases/`.
- Flags del zip: `-rqX` (recursivo, silencioso, sin atributos extendidos de macOS).
- **No usar `-D`**: elimina entradas de directorio y PHP `ZipArchive` no extrae correctamente.
- El ZIP contiene solo los archivos del módulo (sin `.git`, `.ref/`, `ESTADO.md`, `CHANGELOG.md`).

### 10. Verificación obligatoria tras cada cambio
1. `php -l` en todos los `.php` modificados.
2. `./build.sh` para regenerar el ZIP.
3. `git commit` con mensaje descriptivo.
4. Actualizar `CHANGELOG.md` si el cambio es relevante para el usuario.

### 11. Lecciones de bugs reales (no repetir)

| # | Error | Causa | Regla |
|---|-------|-------|-------|
| 1 | `class_exists()` no encuentra la clase | Nombre de clase ≠ nombre de módulo (guiones bajos) | Clase = mismo nombre que carpeta/archivo |
| 2 | `$this->trans()` en constructor da null | Traductor no inicializado antes de `parent::__construct()` | Props base → `parent::__construct()` → props con traducciones |
| 3 | `FatalError` por visibilidad | `protected` en vez de `public` en override | Verificar firma exacta en `.ref/` |
| 4 | `FatalError` por parámetros | `afterDelete($object)` falta `$old_id` | Verificar firma exacta en `.ref/` |
| 5 | ZIP no extrae subdirectorios | `zip -D` elimina entradas de directorio | Solo `zip -rqX`, nunca `-D` |
| 6 | Formulario vacío | `getFieldsForm()` no se llama automáticamente | `$this->fields_form = $this->getFieldsForm()` en `renderForm()` |
| 7 | `array_unique()` con arrays anidados | Breadcrumbs personalizados rompen `initToolbarTitle()` | No sobreescribir `initBreadcrumbs()`, el padre lo gestiona |
| 8 | `str_repeat($s, -1)` | `level_depth` puede ser 0 | Usar `max(0, $value)` antes de pasarlo a `str_repeat()` |

## Flujo de trabajo
Cuando se te pida desarrollar, modificar o revisar:
1. Leer `ESTADO.md` para saber por dónde se retoma.
2. Consultar `.ref/prestashop-8.2/` para verificar firmas y convenciones.
3. Escribir el código siguiendo las 10 reglas anteriores.
4. Explicar cualquier decisión de diseño que se desvíe de lo esperado.
5. Reconstruir ZIP, commit, y actualizar `ESTADO.md`.

## Servidor del usuario
- URL: https://fusionenergiasolar.es
- PrestaShop: 8.2.7
- Prefijo BD: `ps17_`
- **`id_lang = 2` es el español** (idioma principal del sitio). PrestaShop suele instalar `id_lang=1` como inglés y añade el español como 2 después. Importante: cualquier código que consulte FAQs por idioma (`FaqModel::getByEntity()`, JSON-LD, títulos) debe usar el `id_lang` del contexto (`$this->context->language->id`), NO hardcodear `1`. Si el bloque no aparece o aparece vacío, un mismatch de `id_lang` es sospechoso habitual.
- Módulos con overrides: `jprestaspeedpack` (sobrescribe `classes/Hook.php`)
- **Caché CCC activa** ("Combinar/Comprimir/Cachear CSS/JS", Parámetros avanzados → Rendimiento): tras subir cualquier cambio en `views/css/` o `views/js/`, avisar al usuario de que debe limpiar/regenerar esa caché además de la caché de Smarty — si no, el navegador sigue sirviendo el CSS/JS combinado antiguo aunque los archivos en el servidor ya sean los nuevos. Confirmado en vivo el 10 julio 2026: tras la v1.3.1, el ancho y el color de acento (inline, vía `<style>` en el propio HTML) se veían bien, pero el resto del rediseño (que depende de `front.css`) no, hasta limpiar CCC.

## Versionado (obligatorio)
- Seguir [SemVer](https://semver.org/lang/es/): `MAJOR.MINOR.PATCH`
- **MAJOR**: cambio incompatible con versiones anteriores (ej: eliminar soporte PS 8)
- **MINOR**: nuevas funcionalidades compatibles hacia atrás (ej: nuevos archivos, opciones)
- **PATCH**: solo correcciones de bugs (ej: arreglar firma de método)
- Antes de un commit de release:
  1. Actualizar `$this->version` en `fscategoryfaq.php`
  2. Actualizar `<version>` en `config.xml`
  3. Actualizar `CHANGELOG.md` con la nueva sección
  4. `git tag -a vX.Y.Z` con descripción
  5. `./build.sh` → genera ZIP con la versión en el nombre
- Los ZIPs antiguos se borran de `releases/`, solo se conserva el actual.
- **No acumular cambios sin bump**: si hay correcciones o añadidos desde el último tag, se sube versión.

## Pendiente (próximas tareas)
- [ ] Generar archivos `.xlf` de traducción en `views/translations/`
- [ ] Mejorar formulario con selectores visuales (árbol de categorías, CMS, fabricante)
- [ ] Pruebas completas: instalación, CRUD, front, validación JSON-LD en Google Rich Results
- [ ] Documentación final (`readme_es.md`)
