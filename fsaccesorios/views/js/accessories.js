/**
 * fsaccesorios - Product Accessories Module
 *
 * Prototype monkey-patch applied IMMEDIATELY (before any other script runs).
 * The rest of the init waits for DOMContentLoaded.
 */

(function () {
  'use strict';

  // Guard against double-loading: both actionFrontControllerSetMedia and
  // displayHeader may load this script on product pages. The displayHeader
  // fallback exists for themes/plugins (jprestaspeedpack) that strip assets
  // registered via the asset pipeline.
  if (window._fsaccesorios_loaded) { return; }
  window._fsaccesorios_loaded = true;

  // ============================================================
  // IMMEDIATE: Monkey-patch HTMLFormElement.prototype.submit
  // Must run before Panda or any other module stores a reference
  // to the original submit method.
  // ============================================================
  var nativeSubmit = HTMLFormElement.prototype.submit;
  var _selectedAccessories = null;

  HTMLFormElement.prototype.submit = function () {
    // Only intercept forms with id_product (add-to-cart forms)
    var idProductInput = this.querySelector('input[name="id_product"]');
    if (!idProductInput) {
      return nativeSubmit.call(this);
    }

    // Check if we have a pending accessories selection
    var accessories = _selectedAccessories
      ? _selectedAccessories()
      : [];

    if (accessories.length === 0) {
      return nativeSubmit.call(this);
    }

    // Accessories selected: the AJAX controller handles the cart.
    // If a click handler already started the AJAX call (isProcessing),
    // just swallow this submit to avoid a double add. Otherwise
    // (programmatic submit from Panda/theme without a button click),
    // trigger the AJAX flow here.
    if (typeof Fsaccesorios !== 'undefined'
        && Fsaccesorios.block
        && !Fsaccesorios.isProcessing) {
      var mainProductId = Fsaccesorios._getMainProductId();
      if (mainProductId > 0) {
        Fsaccesorios._executeAddToCart(
          mainProductId,
          Fsaccesorios._getMainProductAttribute(),
          Fsaccesorios._getMainQuantity(),
          accessories
        );
      }
    }
    // Never call nativeSubmit when accessories are selected.
  };

  // ============================================================
  // DOM-dependent init (waits for DOMContentLoaded)
  // ============================================================
  var Fsaccesorios = {
    controllerUrl: '',
    cartPageUrl: '',
    isProcessing: false,
    block: null,
    addToCartBtn: null,

    init: function () {
      this.controllerUrl = (window.fsaccesorios_controller || '').replace(/&amp;/g, '&');
      this.cartPageUrl = (window.fsaccesorios_cart_url || '').replace(/&amp;/g, '&');
      this.block = document.getElementById('fsaccesorios-block');

      if (!this.block) { return; }

      // Wire up the accessories getter for the prototype patch.
      // Bound even when no add-to-cart button exists, so a programmatic
      // form.submit() with accessories selected is still intercepted.
      _selectedAccessories = this._getSelectedAccessories.bind(this);

      this.addToCartBtn = document.querySelector('[data-button-action="add-to-cart"]');
      if (!this.addToCartBtn) { return; }

      this._bindAddToCartClick();
      this._bindQuantityControls();
      this._bindCombinationSelects();
      this._bindCheckboxVisual();
    },

    _getMainProductId: function () {
      var input = document.querySelector('input[name="id_product"]');
      return input ? parseInt(input.value, 10) : 0;
    },

    _getMainProductAttribute: function () {
      var input = document.querySelector('input[name="id_product_attribute"]');
      return input ? (parseInt(input.value, 10) || 0) : 0;
    },

    /**
     * Panda-style themes expose the selected combination as group[N]
     * selects/radios instead of a resolved id_product_attribute input.
     * Collect them so the controller can resolve the combination server-side.
     */
    _getMainProductGroups: function () {
      var groups = {};
      var m;
      document.querySelectorAll('select[name^="group["]').forEach(function (el) {
        m = el.name.match(/^group\[(\d+)\]$/);
        if (m) { groups[m[1]] = parseInt(el.value, 10) || 0; }
      });
      document.querySelectorAll('input[name^="group["]:checked').forEach(function (el) {
        m = el.name.match(/^group\[(\d+)\]$/);
        if (m) { groups[m[1]] = parseInt(el.value, 10) || 0; }
      });
      return groups;
    },

    _getMainQuantity: function () {
      var input = document.querySelector('input[name="qty"]');
      return input ? parseInt(input.value, 10) : 1;
    },

    _getSelectedAccessories: function () {
      if (!this.block) { return []; }
      var checkboxes = this.block.querySelectorAll(
        '.fsaccesorios-checkbox:checked:not(:disabled)'
      );
      var accessories = [];

      checkboxes.forEach(function (cb) {
        var item = cb.closest('.fsaccesorios-item');
        if (!item) { return; }

        var qtyInput = item.querySelector('.fsaccesorios-qty-input');
        var combo = item.querySelector('.fsaccesorios-combination-select');

        var idProduct = parseInt(cb.getAttribute('data-id-product'), 10);
        var idProductAttribute = combo
          ? parseInt(combo.value, 10)
          : parseInt(cb.getAttribute('data-id-product-attribute') || '0', 10);
        var quantity = qtyInput ? parseInt(qtyInput.value, 10) : 1;

        if (idProduct > 0 && quantity > 0) {
          accessories.push({
            id_product: idProduct,
            id_product_attribute: idProductAttribute,
            quantity: quantity
          });
        }
      });

      return accessories;
    },

    /**
     * Intercept clicks on the add-to-cart button.
     * When accessories are selected: stop the click, do AJAX, redirect.
     * When no accessories: let the click bubble to Panda (which calls
     * form.submit(), which goes through our prototype patch, which
     * sees no accessories and calls nativeSubmit).
     */
    _bindAddToCartClick: function () {
      var self = this;

      // Bind to ALL add-to-cart buttons (main + sticky) in capturing phase
      document.querySelectorAll('[data-button-action="add-to-cart"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          var accessories = self._getSelectedAccessories();
          if (accessories.length === 0) { return; } // let Panda handle it

          e.stopImmediatePropagation();
          e.preventDefault();

          if (self.isProcessing) { return; }

          var mainProductId = self._getMainProductId();
          var mainQuantity = self._getMainQuantity();
          if (mainProductId <= 0) { return; }

          var mainProductAttribute = self._getMainProductAttribute();
          self._executeAddToCart(mainProductId, mainProductAttribute, mainQuantity, accessories);
        }, true); // capturing phase
      });
    },

    _executeAddToCart: function (mainProductId, mainProductAttribute, mainQuantity, accessories) {
      var self = this;
      var payload = {
        id_product: mainProductId,
        id_product_attribute: mainProductAttribute,
        groups: this._getMainProductGroups(),
        quantity: mainQuantity,
        id_customization: 0,
        token: window.fsaccesorios_token || '',
        accessories: accessories
      };

      self.isProcessing = true;
      self._setLoading(true);

      var url = self.controllerUrl;
      var sep = url.indexOf('?') > -1 ? '&' : '?';
      url += sep + 'action=addMultiple&ajax=1';

      var xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.timeout = 15000;

      xhr.onload = function () {
        self.isProcessing = false;
        self._setLoading(false);

        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
              self._onSuccess(response);
            } else {
              self._onError(response.errors || ['Could not add products to cart']);
            }
          } catch (e) {
            self._onError(['Invalid server response.']);
          }
        } else {
          self._onError(['Server error (' + xhr.status + ').']);
        }
      };

      xhr.onerror = function () {
        self.isProcessing = false;
        self._setLoading(false);
        self._onError(['Network error.']);
      };

      xhr.ontimeout = function () {
        self.isProcessing = false;
        self._setLoading(false);
        self._onError(['Request timed out.']);
      };

      xhr.send(JSON.stringify(payload));
    },

    _setLoading: function (loading) {
      var buttons = document.querySelectorAll('[data-button-action="add-to-cart"]');
      buttons.forEach(function (btn) {
        if (loading) {
          btn.disabled = true;
          if (!btn.dataset.fsaOriginal) {
            btn.dataset.fsaOriginal = btn.innerHTML;
          }
          btn.innerHTML = '<span class="fsaccesorios-spinner" aria-hidden="true"></span> '
            + (window.fsaccesorios_i18n && window.fsaccesorios_i18n.adding || 'Adding...');
        } else {
          btn.disabled = false;
          if (btn.dataset.fsaOriginal) {
            btn.innerHTML = btn.dataset.fsaOriginal;
            delete btn.dataset.fsaOriginal;
          }
        }
      });
    },

    _onSuccess: function (response) {
      if (typeof prestashop !== 'undefined' && prestashop.emit) {
        prestashop.emit('updateCart', { reason: 'add', resp: response });
      }

      var i18n = window.fsaccesorios_i18n || {};
      this._showNotification(i18n.success || 'Products added to cart successfully', 'success');

      var self = this;
      setTimeout(function () {
        window.location.href = self.cartPageUrl;
      }, 800);
    },

    _onError: function (errors) {
      var msg = Array.isArray(errors) ? errors.join('\n') : errors;
      this._showNotification(msg, 'error');
      if (this.block) {
        this.block.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    },

    _showNotification: function (message, type) {
      if (typeof prestashop !== 'undefined' && prestashop.emit) {
        prestashop.emit('showNotification', { type: type, message: message });
        return;
      }

      var existing = document.querySelector('.fsaccesorios-notification');
      if (existing) { existing.remove(); }

      var toast = document.createElement('div');
      toast.className = 'fsaccesorios-notification fsaccesorios-notification--' + type;
      toast.textContent = message;
      toast.setAttribute('role', 'alert');
      document.body.appendChild(toast);

      toast.offsetHeight;
      toast.classList.add('fsaccesorios-notification--visible');

      var delay = type === 'success' ? 3000 : 5000;
      setTimeout(function () {
        toast.classList.remove('fsaccesorios-notification--visible');
        setTimeout(function () {
          if (toast.parentNode) { toast.remove(); }
        }, 300);
      }, delay);
    },

    _bindQuantityControls: function () {
      if (!this.block) { return; }
      var self = this;
      this.block.querySelectorAll('.fsaccesorios-qty-btn--minus').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var input = this.parentElement.querySelector('.fsaccesorios-qty-input');
          if (!input || input.disabled) { return; }
          var min = parseInt(input.min, 10) || 1;
          var val = parseInt(input.value, 10) || min;
          if (val > min) { input.value = val - 1; }
        });
      });
      this.block.querySelectorAll('.fsaccesorios-qty-btn--plus').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var input = this.parentElement.querySelector('.fsaccesorios-qty-input');
          if (!input || input.disabled) { return; }
          var max = parseInt(input.max, 10);
          if (isNaN(max) || max <= 0) { max = 999999; }
          var val = parseInt(input.value, 10) || 1;
          if (val < max) { input.value = val + 1; }
        });
      });
      this.block.querySelectorAll('.fsaccesorios-qty-input').forEach(function (input) {
        input.addEventListener('change', function () {
          var min = parseInt(this.min, 10) || 1;
          var max = parseInt(this.max, 10);
          if (isNaN(max) || max <= 0) { max = 999999; }
          var val = parseInt(this.value, 10);
          if (isNaN(val) || val < min) { this.value = min; }
          else if (val > max) { this.value = max; }
        });
      });
    },

    _bindCombinationSelects: function () {
      if (!this.block) { return; }
      var self = this;
      this.block.querySelectorAll('.fsaccesorios-combination-select').forEach(function (select) {
        var initial = select.options[select.selectedIndex];
        if (initial) { self._applyCombinationData(select, initial); }
        select.addEventListener('change', function () {
          var option = this.options[this.selectedIndex];
          if (option) { self._applyCombinationData(this, option); }
        });
      });
    },

    _applyCombinationData: function (select, option) {
      var item = select.closest('.fsaccesorios-item');
      if (!item) { return; }
      var idProductAttr = parseInt(option.value, 10);
      var qtyAvail = parseInt(option.getAttribute('data-quantity') || '0', 10);
      var cb = item.querySelector('.fsaccesorios-checkbox');
      if (cb) { cb.setAttribute('data-id-product-attribute', idProductAttr); }
      var qty = item.querySelector('.fsaccesorios-qty-input');
      if (qty) {
        qty.max = qtyAvail;
        if (qtyAvail <= 0) {
          qty.disabled = true;
          if (cb) { cb.disabled = true; }
        } else {
          qty.disabled = false;
          if (cb) { cb.disabled = false; }
          if (parseInt(qty.value, 10) > qtyAvail) { qty.value = qtyAvail; }
        }
      }
    },

    _bindCheckboxVisual: function () {
      if (!this.block) { return; }
      this.block.querySelectorAll('.fsaccesorios-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
          var item = this.closest('.fsaccesorios-item');
          if (!item) { return; }
          if (this.checked) {
            item.style.backgroundColor = 'rgba(4, 101, 178, 0.08)';
            item.style.borderLeftColor = 'var(--fsa-accent)';
          } else {
            item.style.backgroundColor = '';
            item.style.borderLeftColor = '';
          }
        });
      });
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { Fsaccesorios.init(); });
  } else {
    Fsaccesorios.init();
  }
})();
