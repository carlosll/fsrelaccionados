# DESIGN.md — Recomendaciones de diseño para módulos FS

Guía visual compartida por todos los módulos del ecosistema Fusión Energía Solar.
Son recomendaciones, no reglas estrictas. Cada módulo puede apartarse cuando su
función lo requiera, pero debe hacerlo con intención, no por omisión.

El objetivo: que cualquier módulo FS instalado en la tienda parezca parte natural
de ella, no un injerto.

---

## 1. Paleta de variables

La tienda expone variables CSS corporativas. Úsalas siempre que puedas. Si no
están disponibles (el módulo se prueba fuera de la tienda, o el tema cambió),
los valores de respaldo evitan que el módulo se rompa visualmente.

| Variable | Propósito | Cuándo usarla |
|---|---|---|
| `--fes-orange` | Acción principal | Botón de añadir al carrito, enviar formulario, CTA, confirmación |
| `--fes-orange-hover` | Hover del naranja | `:hover` del botón principal |
| `--fes-blue` | Acción secundaria, enlace | Links, iconos interactivos, bordes de foco, botones secundarios |
| `--fes-blue-hover` | Hover del azul | `:hover` de enlaces y botones secundarios |
| `--fes-blue-soft` | Fondo informativo suave | Avisos, bloques destacados, banners informativos |
| `--fes-blue-border` | Borde azul claro | Campos activos, bloques informativos |
| `--fes-text` | Texto principal | Títulos, cuerpo, contenido |
| `--fes-text-secondary` | Texto secundario | Descripciones, metadatos, fechas, notas |
| `--fes-text-light` | Texto terciario | Información menos relevante, placeholders, elementos desactivados |
| `--fes-border` | Borde estándar | Separadores, bordes de campo, contenedores |
| `--fes-border-soft` | Borde suave | Separación entre filas, tarjetas, bloques ligeros |
| `--fes-surface-soft` | Fondo secundario | Áreas de fondo sutiles, rows alternas |
| `--fes-white` | Fondo principal | Tarjetas, formularios, contenedores |
| `--fes-radius` | Radio estándar | Tarjetas, botones, campos, modales |
| `--fes-shadow-sm` | Sombra ligera | Tarjetas, bloques |
| `--fes-shadow-md` | Sombra media | Desplegables, modales, tooltips |
| `--fes-transition` | Transición estándar | Hover, focus, cambios de estado |

### Jerarquía de color por tipo de elemento

| Elemento | Color |
|---|---|
| Botón principal (comprar, enviar, confirmar) | Naranja |
| Botón secundario (cancelar, volver, más info) | Azul |
| Enlace de navegación / informativo | Azul |
| Enlace con función comercial / CTA | Naranja |
| Texto de aviso / error | Rojo (temático, no corporativo) |
| Texto de éxito | Verde (temático, no corporativo) |

La diferencia clave: **naranja = acción que convierte**. Si el clic no lleva a
una compra, un presupuesto o un lead, probablemente debería ser azul.

---

## 2. Encapsulado

Cada módulo vive dentro de su propio contenedor y usa un prefijo único.

```html
<div class="fs-nombre-modulo">
  <!-- todo el módulo aquí dentro -->
</div>
```

**Reglas:**

- Todo selector CSS empieza por `.fs-nombre-modulo`. Sin excepciones.
- No uses reglas globales (`button`, `input`, `.card`, `.row`, `.title_block`).
- La tipografía se hereda del tema: `font-family: inherit`.
- No cargues Bootstrap, jQuery ni librerías que ya trae PrestaShop o Panda.
- No uses `!important` salvo para sobrescribir estilos inline de terceros.
- Si necesitas la grid de Bootstrap, usa las clases del tema (`row`, `col-lg-6`)
  pero solo dentro de tu contenedor. No redefinas `.container` ni `.row`.

---

## 3. Componentes visuales

Patrones recomendados para los elementos más comunes. No son obligatorios, pero
dan coherencia entre módulos.

### Tarjeta / bloque

```css
.fs-nombre-modulo .fs-modulo-card {
    border: 1px solid var(--fes-border-soft, #edf1f4);
    border-radius: var(--fes-radius, 8px);
    background: var(--fes-white, #fff);
    box-shadow: var(--fes-shadow-sm);
}
```

Sombras suaves. Sin bordes excesivamente marcados ni efectos de elevación grandes.

### Botón principal (naranja)

```css
.fs-nombre-modulo .fs-modulo-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 10px 18px;
    border: 1px solid var(--fes-orange, #fe5000);
    border-radius: var(--fes-radius, 8px);
    background: var(--fes-orange, #fe5000);
    color: var(--fes-white, #fff);
    font-weight: 600;
    cursor: pointer;
    transition: background-color var(--fes-transition, .18s ease),
                border-color var(--fes-transition, .18s ease);
}

.fs-nombre-modulo .fs-modulo-btn-primary:hover {
    border-color: var(--fes-orange-hover, #d84300);
    background: var(--fes-orange-hover, #d84300);
}

.fs-nombre-modulo .fs-modulo-btn-primary:focus-visible {
    outline: 2px solid var(--fes-orange, #fe5000);
    outline-offset: 2px;
}

.fs-nombre-modulo .fs-modulo-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

### Botón secundario (azul)

```css
.fs-nombre-modulo .fs-modulo-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 10px 18px;
    border: 1px solid var(--fes-blue, #0465b2);
    border-radius: var(--fes-radius, 8px);
    background: var(--fes-white, #fff);
    color: var(--fes-blue, #0465b2);
    font-weight: 600;
    cursor: pointer;
    transition: background-color var(--fes-transition, .18s ease),
                color var(--fes-transition, .18s ease);
}

.fs-nombre-modulo .fs-modulo-btn-secondary:hover {
    background: var(--fes-blue, #0465b2);
    color: var(--fes-white, #fff);
}
```

### Enlace

```css
.fs-nombre-modulo a {
    color: var(--fes-blue, #0465b2);
    text-decoration: none;
    transition: color var(--fes-transition, .18s ease);
}

.fs-nombre-modulo a:hover {
    color: var(--fes-blue-hover);
    text-decoration: underline;
}
```

### Campo de formulario

```css
.fs-nombre-modulo .fs-modulo-input {
    width: 100%;
    min-height: 42px;
    padding: 8px 12px;
    border: 1px solid var(--fes-border, #dfe6eb);
    border-radius: var(--fes-radius, 8px);
    background: var(--fes-white, #fff);
    color: var(--fes-text, #2b3137);
    font-family: inherit;
    font-size: inherit;
    transition: border-color var(--fes-transition, .18s ease),
                box-shadow var(--fes-transition, .18s ease);
}

.fs-nombre-modulo .fs-modulo-input:focus {
    border-color: var(--fes-blue, #0465b2);
    box-shadow: 0 0 0 3px rgba(4, 101, 178, .12);
    outline: none;
}

.fs-nombre-modulo .fs-modulo-input.is-error {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, .12);
}
```

---

## 4. Estados visuales que todo módulo debería cubrir

| Estado | ¿Qué esperar? |
|---|---|
| **Hover** | Transición suave en botones y enlaces interactivos |
| **Focus** | Anillo visible (2-3px) en el color del elemento |
| **Disabled** | Opacidad reducida + `cursor: not-allowed` |
| **Loading** | Indicador de carga (spinner o skeleton) en lugar del contenido |
| **Empty** | Mensaje o ilustración cuando no hay datos que mostrar |
| **Error** | Mensaje junto al elemento afectado, borde rojo si es un campo |
| **Success** | Confirmación visual clara tras una acción completada |

Los estados vacío y error son los que más se olvidan y los que más nota el usuario.

---

## 5. Responsive

Dos breakpoints cubren todo:

| Breakpoint | ¿Para qué? |
|---|---|
| `@media (max-width: 768px)` | Tableta: reducir espaciados, imágenes más pequeñas |
| `@media (max-width: 575px)` | Móvil: grid a 1 columna, formularios apilados, full width |

Reglas mínimas:

- Sin anchos fijos en píxeles que desborden. Usa `max-width`, `%`, `fr`.
- Imágenes fluidas: `img { max-width: 100%; height: auto; }`.
- Área táctil mínima ~44px en móvil para botones y enlaces interactivos.
- No depender de `:hover` para funcionalidad esencial en móvil.
- Las tablas deben tener contenedor con `overflow-x: auto` o adaptarse.
- Sin scroll horizontal bajo ningún breakpoint.

---

## 6. Accesibilidad

Seis reglas que cuestan poco y marcan diferencia:

1. **HTML semántico.** Usa `<button>` para acciones, `<a>` para navegación, `<label>` para etiquetas de campo.
2. **Contraste suficiente.** Texto sobre fondo debe ser legible. Las variables del tema ya lo garantizan si las usas.
3. **Navegación por teclado.** Todo elemento interactivo debe ser focusable y accionable con teclado (`Tab`, `Enter`, `Space`).
4. **Foco visible.** `:focus-visible` en todos los elementos interactivos. Nunca hagas `outline: none` sin remplazarlo.
5. **Etiquetas y descripciones.**
   - Todo `<input>` / `<select>` tiene un `<label>` asociado (no solo placeholder).
   - Todo `<img>` informativo tiene `alt` descriptivo.
   - Todo botón solo-icono tiene `aria-label`.
6. **Movimiento reducido.** Respeta la preferencia del sistema:

```css
@media (prefers-reduced-motion: reduce) {
    .fs-nombre-modulo *,
    .fs-nombre-modulo *::before,
    .fs-nombre-modulo *::after {
        scroll-behavior: auto !important;
        transition-duration: 0s !important;
        animation-duration: 0s !important;
    }
}
```

---

## 7. Imágenes e iconos

- **No deformar.** Usa `object-fit: cover` para imágenes editoriales, `object-fit: contain` para productos.
- **Dimensiones.** Pon `width` y `height` en el HTML para evitar layout shift durante la carga.
- **Carga diferida.** `loading="lazy"` en imágenes fuera del viewport inicial.
- **Iconos.** Usa los que ya trae el tema Panda. No cargues una librería de iconos de 2 MB para usar dos flechas. Si necesitas un icono que no existe en Panda, inline SVG pesa menos que cualquier librería.

---

## 8. Carga de assets

Cada módulo debe cargar su CSS y JS **solo en las páginas donde aparece**, no en
toda la tienda. En PrestaShop esto se hace registrando los assets en el hook
adecuado y verificando el controlador actual antes de registrarlos.

**No hacer:**
- Cargar CSS/JS en `displayHeader` sin comprobar la página
- Usar `<link>` o `<script>` inline en el hook del módulo
- Cargar fuentes externas que no usa la tienda

---

## 9. JavaScript

Pautas mínimas para que el JS del módulo sea buen vecino:

- Encapsula todo en una IIFE o namespace. Nada en el scope global.
- Inicializa solo si el contenedor del módulo existe en el DOM.
- No asumas que jQuery está disponible — Panda lo carga, pero otros temas no.
- Protege contra reinicialización tras actualizaciones AJAX de PrestaShop.
- El módulo debería funcionar (aunque sea de forma degradada) si JS falla.
