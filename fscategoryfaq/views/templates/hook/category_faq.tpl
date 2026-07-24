{**
 * FS Category FAQ SEO — Plantilla Front Office
 *
 * Muestra el bloque de FAQs con acordeón nativo HTML5 opcional
 * e incluye los datos estructurados JSON-LD FAQPage.
 *
 * Variables disponibles:
 *   $faqs           — array de FAQs [{id_faq, question, answer, position, ...}]
 *   $block_title    — string, título del bloque
 *   $entity_name    — string, nombre de la entidad actual
 *   $show_title     — bool, mostrar u ocultar el título
 *   $use_accordion  — bool, usar acordeón <details> o mostrar todo abierto
 *   $open_mode      — string, 'all_closed' | 'first_open' | 'all_open'
 *   $json_ld        — string, script JSON-LD ya codificado (vacío si desactivado)
 *   $extra_css_class — string, clase CSS adicional para personalización
 *   $design_style   — string, propiedades --fs-faq-* ya validadas (colores, radio, sombra...)
 *   $icon_style     — string, 'chevron' | 'plusminus' | 'none'
 *   $faq_count_label — string, ej. "8 preguntas" (vacío si no hay que mostrarlo)
 *}

{* ── JSON-LD Structured Data ── *}
{if $json_ld}
<script type="application/ld+json">
{$json_ld nofilter}
</script>
{/if}

{* ── Personalización de diseño (colores/tamaños validados en PHP) ── *}
{if $design_style}
<style>.fs-category-faq-block{ldelim}{$design_style nofilter}{rdelim}</style>
{/if}

{* ── Bloque FAQ ── *}
<section class="fs-category-faq-block{if $extra_css_class} {$extra_css_class|escape:'htmlall'}{/if}">

    {* ── Título del bloque ── *}
    {if $show_title && $block_title}
        <div class="fs-faq-title-wrap">
            <span class="fs-faq-title-accent" aria-hidden="true"></span>
            <h2 class="fs-faq-title">{$block_title|escape:'htmlall'}</h2>
            {if $faq_count_label}
                <span class="fs-faq-count">{$faq_count_label|escape:'htmlall'}</span>
            {/if}
        </div>
    {/if}

    {* ── Listado de FAQs ── *}
    {if $use_accordion}
        {* ── Modo acordeón (<details> nativo HTML5, cero JS) ── *}
        <div class="fs-faq-accordion">
            {foreach from=$faqs item=faq name=faqLoop}
                <details class="fs-faq-item"
                    {if $open_mode === 'all_open' || ($open_mode === 'first_open' && $smarty.foreach.faqLoop.first)} open{/if}
                    id="faq-{$faq.id_faq|intval}">

                    <summary class="fs-faq-question">
                        <span class="fs-faq-question-text">{$faq.question|escape:'htmlall'}</span>
                        {if $icon_style != 'none'}
                            <span class="fs-faq-icon-wrap" aria-hidden="true">
                                {if $icon_style == 'plusminus'}
                                    <span class="fs-faq-icon fs-faq-icon--plusminus"></span>
                                {else}
                                    <span class="fs-faq-icon fs-faq-icon--chevron"></span>
                                {/if}
                            </span>
                        {/if}
                    </summary>

                    <div class="fs-faq-answer">
                        {$faq.answer nofilter}
                    </div>
                </details>
            {/foreach}
        </div>
    {else}
        {* ── Modo lista (todo visible, sin acordeón) ── *}
        <div class="fs-faq-list">
            {foreach from=$faqs item=faq name=faqLoop}
                <div class="fs-faq-item fs-faq-item--open"
                    id="faq-{$faq.id_faq|intval}">

                    <h3 class="fs-faq-question">
                        {$faq.question|escape:'htmlall'}
                    </h3>

                    <div class="fs-faq-answer">
                        {$faq.answer nofilter}
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}

</section>
