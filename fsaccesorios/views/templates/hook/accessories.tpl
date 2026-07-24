{**
 * fsaccesorios - Product Accessories Block
 *
 * Displays a compact, elegant list of product accessories
 * with checkbox selection and quantity controls.
 *
 * Available variables:
 *  - $fs_accessories   Array of formatted accessories
 *  - $fs_module_dir    Module base URL for assets
 *  - $fs_static_token  Security token
 *}
{if !empty($fs_accessories)}
<div class="fsaccesorios-block"
     id="fsaccesorios-block"
     aria-labelledby="fsaccesorios-title">

  <div class="fsaccesorios-header">
    <h3 class="fsaccesorios-title" id="fsaccesorios-title">
      {l s='Accessories' mod='fsaccesorios'}
    </h3>
    <p class="fsaccesorios-subtitle">
      {l s='Select the accessories you want to add to your order' mod='fsaccesorios'}
    </p>
  </div>

  <ul class="fsaccesorios-list" id="fsaccesorios-list" role="group" aria-label="{l s='Available accessories' mod='fsaccesorios'}">
    {foreach from=$fs_accessories item=accessory}
    <li class="fsaccesorios-item {if !$accessory.available}fsaccesorios-item--unavailable{/if}"
        data-product-id="{$accessory.id_product}"
        data-product-attribute-id="{$accessory.id_product_attribute}"
        role="listitem">

      {* --- Checkbox --- *}
      <div class="fsaccesorios-item__select">
        <label class="fsaccesorios-checkbox-label" for="fsaccesorios-chk-{$accessory.id_product}">
          <input type="checkbox"
                 class="fsaccesorios-checkbox"
                 id="fsaccesorios-chk-{$accessory.id_product}"
                 data-id-product="{$accessory.id_product}"
                 data-id-product-attribute="{$accessory.id_product_attribute}"
                 data-minimal-quantity="{$accessory.minimal_quantity}"
                 {if !$accessory.available}disabled{/if} />
          <span class="fsaccesorios-checkbox-custom"></span>
        </label>
      </div>

      {* --- Thumbnail --- *}
      <div class="fsaccesorios-item__image">
        <a href="{$accessory.link}" title="{$accessory.name|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
          <img src="{$accessory.cover}"
               alt="{$accessory.name|escape:'html':'UTF-8'}"
               loading="lazy"
               width="64"
               height="64" />
        </a>
      </div>

      {* --- Name + Price --- *}
      <div class="fsaccesorios-item__info">
        <a href="{$accessory.link}"
           class="fsaccesorios-item__name"
           title="{$accessory.name|escape:'html':'UTF-8'}"
           target="_blank"
           rel="noopener">
          {$accessory.name|escape:'html':'UTF-8'}
        </a>
        <div class="fsaccesorios-item__meta">
          <span class="fsaccesorios-item__price">{$accessory.price}</span>
          {if !$accessory.available}
            <span class="fsaccesorios-item__badge fsaccesorios-item__badge--oos">
              {l s='Out of stock' mod='fsaccesorios'}
            </span>
          {/if}
          {if $accessory.reference}
            <span class="fsaccesorios-item__ref">{l s='Ref.' mod='fsaccesorios'} {$accessory.reference|escape:'html':'UTF-8'}</span>
          {/if}
        </div>
      </div>

      {* --- Quantity (only in main row when NO combinations) --- *}
      {if !$accessory.has_combinations}
      <div class="fsaccesorios-item__qty">
        <button type="button"
                class="fsaccesorios-qty-btn fsaccesorios-qty-btn--minus"
                aria-label="{l s='Decrease quantity' mod='fsaccesorios'}"
                {if !$accessory.available}disabled{/if}>
          <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
            <rect x="0" y="4" width="10" height="2" fill="currentColor"/>
          </svg>
        </button>
        <input type="number"
               class="fsaccesorios-qty-input"
               id="fsaccesorios-qty-{$accessory.id_product}"
               value="{$accessory.minimal_quantity}"
               min="{$accessory.minimal_quantity}"
               max="{if $accessory.available}{$accessory.quantity_available}{else}0{/if}"
               step="1"
               data-product-id="{$accessory.id_product}"
               aria-label="{l s='Quantity for' mod='fsaccesorios'} {$accessory.name|escape:'html':'UTF-8'}"
               {if !$accessory.available}disabled{/if} />
        <button type="button"
                class="fsaccesorios-qty-btn fsaccesorios-qty-btn--plus"
                aria-label="{l s='Increase quantity' mod='fsaccesorios'}"
                {if !$accessory.available}disabled{/if}>
          <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
            <rect x="4" y="0" width="2" height="10" fill="currentColor"/>
            <rect x="0" y="4" width="10" height="2" fill="currentColor"/>
          </svg>
        </button>
      </div>
      {/if}

      {* --- Combination + quantity row (when combinations exist) --- *}
      {if $accessory.has_combinations}
      <div class="fsaccesorios-item__combination">
        <select class="fsaccesorios-combination-select"
                id="fsaccesorios-combo-{$accessory.id_product}"
                data-product-id="{$accessory.id_product}"
                aria-label="{l s='Select variant for' mod='fsaccesorios'} {$accessory.name|escape:'html':'UTF-8'}"
                {if !$accessory.available}disabled{/if}>
          {foreach from=$accessory.combinations item=combination}
            <option value="{$combination.id_product_attribute}"
                    data-price="{$combination.price_raw}"
                    data-quantity="{$combination.quantity}"
                    {if $combination.default_on}selected{/if}
                    {if $combination.quantity <= 0}disabled{/if}>
              {$combination.name|escape:'html':'UTF-8'} - {$combination.price}
              {if $combination.quantity <= 0} ({l s='Out of stock' mod='fsaccesorios'}){/if}
            </option>
          {/foreach}
        </select>

        <div class="fsaccesorios-item__qty fsaccesorios-item__qty--combo">
          <button type="button"
                  class="fsaccesorios-qty-btn fsaccesorios-qty-btn--minus"
                  aria-label="{l s='Decrease quantity' mod='fsaccesorios'}"
                  {if !$accessory.available}disabled{/if}>
            <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
              <rect x="0" y="4" width="10" height="2" fill="currentColor"/>
            </svg>
          </button>
          <input type="number"
                 class="fsaccesorios-qty-input"
                 id="fsaccesorios-qty-{$accessory.id_product}"
                 value="{$accessory.minimal_quantity}"
                 min="{$accessory.minimal_quantity}"
                 max="{if $accessory.available}{$accessory.quantity_available}{else}0{/if}"
                 step="1"
                 data-product-id="{$accessory.id_product}"
                 aria-label="{l s='Quantity for' mod='fsaccesorios'} {$accessory.name|escape:'html':'UTF-8'}"
                 {if !$accessory.available}disabled{/if} />
          <button type="button"
                  class="fsaccesorios-qty-btn fsaccesorios-qty-btn--plus"
                  aria-label="{l s='Increase quantity' mod='fsaccesorios'}"
                  {if !$accessory.available}disabled{/if}>
            <svg width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
              <rect x="4" y="0" width="2" height="10" fill="currentColor"/>
              <rect x="0" y="4" width="10" height="2" fill="currentColor"/>
            </svg>
          </button>
        </div>
      </div>
      {/if}

    </li>
    {/foreach}
  </ul>

</div>
{/if}
