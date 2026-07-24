/**
 * fsaccesorios - Product Accessories Module
 *
 * Vanilla JavaScript (no jQuery dependency).
 * Compatible with PrestaShop 8.2 and 9.x, Panda and classic themes.
 *
 * Intercepts the native add-to-cart button click (capturing phase, before
 * Panda's sticky handler) when accessories are selected. Single-button UX:
 * same button adds main product + accessories or just the main product.
 */

(function () {
  'use strict';

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

      // Find the add-to-cart button (works for classic, Panda, sticky)
      this.addToCartBtn = document.querySelector('[data-button-action="add-to-cart"]');
      if (!this.addToCartBtn) { return; }

      this._bindAddToCart();
      this._bindQuantityControls();
      this._bindCombinationSelects();
      this._bindCheckboxVisual();
    },

    _getMainProductId: function () {
      var input = document.querySelector('input[name="id_product"]');
      return input ? parseInt(input.value, 10) : 0;
    },

    _getMainQuantity: function () {
      var input = document.querySelector('input[name="qty"]');
      return input ? parseInt(input.value, 10) : 1;
    },

    _getSelectedAccessories: function () {
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
     * Intercept the add-to-cart button click in the CAPTURING phase.
     * This fires BEFORE Panda's sticky button handler, so we can
     * stop propagation when accessories are selected.
     */
    _bindAddToCart: function () {
      var self = this;

      // Use capturing phase (true) to beat Panda's handler
      this.addToCartBtn.addEventListener('click', function (e) {
        if (self.isProcessing) {
          e.stopImmediatePropagation();
          e.preventDefault();
          return;
        }

        var accessories = self._getSelectedAccessories();

        // No accessories selected: let the native flow work
        if (accessories.length === 0) {
          return;
        }

        // Accessories selected: we handle it
        e.stopImmediatePropagation();
        e.preventDefault();

        var mainProductId = self._getMainProductId();
        var mainQuantity = self._getMainQuantity();

        if (mainProductId <= 0) { return; }

        self._executeAddToCart(mainProductId, mainQuantity, accessories);
      }, true); // <-- capturing phase
    },

    _executeAddToCart: function (mainProductId, mainQuantity, accessories) {
      var self = this;
      var payload = {
        id_product: mainProductId,
        quantity: mainQuantity,
        id_customization: 0,
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
        self._onError(['Network error. Please check your connection.']);
      };

      xhr.ontimeout = function () {
        self.isProcessing = false;
        self._setLoading(false);
        self._onError(['Request timed out.']);
      };

      xhr.send(JSON.stringify(payload));
    },

    _setLoading: function (loading) {
      var btn = this.addToCartBtn;
      if (!btn) { return; }

      if (loading) {
        btn.disabled = true;
        if (!btn.dataset.fsaOriginal) {
          btn.dataset.fsaOriginal = btn.innerHTML;
        }
        btn.innerHTML = '<span class="fsaccesorios-spinner" aria-hidden="true"></span> Añadiendo...';
      } else {
        btn.disabled = false;
        if (btn.dataset.fsaOriginal) {
          btn.innerHTML = btn.dataset.fsaOriginal;
          delete btn.dataset.fsaOriginal;
        }
      }
    },

    _onSuccess: function (response) {
      if (typeof prestashop !== 'undefined'
          && prestashop.emit
          && typeof prestashop.emit === 'function') {
        prestashop.emit('updateCart', {
          reason: 'add',
          resp: response
        });
      }

      this._showNotification(
        'Productos añadidos al carrito correctamente',
        'success'
      );

      var self = this;
      setTimeout(function () {
        window.location.href = self.cartPageUrl;
      }, 800);
    },

    _onError: function (errors) {
      var msg = Array.isArray(errors) ? errors.join('<br>') : errors;
      this._showNotification(msg, 'error');
      if (this.block) {
        this.block.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    },

    _showNotification: function (message, type) {
      if (typeof prestashop !== 'undefined'
          && prestashop.emit
          && typeof prestashop.emit === 'function') {
        prestashop.emit('showNotification', {
          type: type,
          message: message
        });
        return;
      }

      var existing = document.querySelector('.fsaccesorios-notification');
      if (existing) { existing.remove(); }

      var toast = document.createElement('div');
      toast.className = 'fsaccesorios-notification fsaccesorios-notification--' + type;
      toast.innerHTML = message;
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
