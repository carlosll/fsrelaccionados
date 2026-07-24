/**
 * fsaccesorios - Product Accessories Module
 *
 * Vanilla JavaScript (no jQuery dependency).
 * Compatible with PrestaShop 8.2 and 9.x.
 *
 * Responsibilities:
 *  - Intercept native add-to-cart form submission
 *  - Collect selected accessories with quantities
 *  - POST all products (main + accessories) to custom controller
 *  - Handle success/error feedback
 *  - Quantity +/- controls
 *  - Combination selectors
 */

(function () {
  'use strict';

  /**
   * Fsaccesorios namespace.
   */
  var Fsaccesorios = {
    /** @type {string} URL to the module's cart controller */
    controllerUrl: '',

    /** @type {string} URL to the cart page for redirect */
    cartPageUrl: '',

    /** @type {boolean} Prevents concurrent submissions */
    isProcessing: false,

    /** @type {HTMLElement|null} The accessories block container */
    block: null,

    /** @type {HTMLElement|null} The native add-to-cart button */
    addToCartButton: null,

    /**
     * Initialize the module.
     * Called on DOMContentLoaded.
     */
    init: function () {
      // Read JS configuration injected by Media::addJsDef
      this.controllerUrl = (window.fsaccesorios_controller || '').replace(/&amp;/g, '&');
      this.cartPageUrl = (window.fsaccesorios_cart_url || '').replace(/&amp;/g, '&');
      this.block = document.getElementById('fsaccesorios-block');

      // If no accessories block on this page, nothing to do
      if (!this.block) {
        return;
      }

      // Find the native add-to-cart form
      var form = this._getAddToCartForm();
      if (!form) {
        return;
      }

      // Cache the button reference
      this.addToCartButton = form.querySelector('[data-button-action="add-to-cart"]');

      // Bind all interactions
      this._bindAddToCart(form);
      this._bindQuantityControls();
      this._bindCombinationSelects();
      this._bindCheckboxVisual();
    },

    /**
     * Locate the native PrestaShop add-to-cart form.
     * Works across classic theme and hummingbird.
     *
     * @return {HTMLFormElement|null}
     */
    _getAddToCartForm: function () {
      // Classic theme form
      var form = document.getElementById('add-to-cart-or-refresh');
      if (form) {
        return form;
      }

      // Hummingbird / alternative themes: find form containing the button
      var btn = document.querySelector('[data-button-action="add-to-cart"]');
      if (btn) {
        return btn.closest('form');
      }

      return null;
    },

    /**
     * Intercept the native add-to-cart form submission.
     * If accessories are selected, prevent default and use our controller.
     * If no accessories selected, let the native flow handle it.
     *
     * @param {HTMLFormElement} form
     */
    _bindAddToCart: function (form) {
      var self = this;

      form.addEventListener('submit', function (e) {
        // Guard against concurrent processing
        if (self.isProcessing) {
          e.preventDefault();
          return;
        }

        var selectedAccessories = self._getSelectedAccessories();

        // No accessories selected: let the native form flow through
        if (selectedAccessories.length === 0) {
          return;
        }

        // We have accessories: handle with our controller
        e.preventDefault();
        self._handleAddToCart(form, selectedAccessories);
      });
    },

    /**
     * Collect selected accessories from the DOM.
     *
     * @return {Array<{id_product: number, id_product_attribute: number, quantity: number}>}
     */
    _getSelectedAccessories: function () {
      var checkboxes = this.block.querySelectorAll(
        '.fsaccesorios-checkbox:checked:not(:disabled)'
      );
      var accessories = [];

      checkboxes.forEach(function (checkbox) {
        var item = checkbox.closest('.fsaccesorios-item');
        if (!item) {
          return;
        }

        var qtyInput = item.querySelector('.fsaccesorios-qty-input');
        var combinationSelect = item.querySelector('.fsaccesorios-combination-select');

        var idProduct = parseInt(checkbox.getAttribute('data-id-product'), 10);
        var idProductAttribute = combinationSelect
          ? parseInt(combinationSelect.value, 10)
          : parseInt(checkbox.getAttribute('data-id-product-attribute') || '0', 10);
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
     * Execute the multi-product add-to-cart via AJAX.
     *
     * @param {HTMLFormElement} form
     * @param {Array} accessories
     */
    _handleAddToCart: function (form, accessories) {
      var self = this;
      var formData = new FormData(form);

      // Read main product data from the form
      var mainProductId = parseInt(formData.get('id_product'), 10);
      var mainQuantity = parseInt(formData.get('qty') || '1', 10);

      // Also check for id_customization
      var idCustomizationRaw = formData.get('id_customization');
      var mainCustomization = idCustomizationRaw ? parseInt(idCustomizationRaw, 10) : 0;

      var payload = {
        id_product: mainProductId,
        quantity: mainQuantity,
        id_customization: mainCustomization,
        accessories: accessories
      };

      // Enter processing state
      self.isProcessing = true;
      self._setLoading(true);

      // Build URL with params
      var url = self.controllerUrl;
      var separator = url.indexOf('?') > -1 ? '&' : '?';
      url += separator + 'action=addMultiple&ajax=1';

      var xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

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
            self._onError(['Invalid server response. Please try again.']);
          }
        } else {
          self._onError(['Server error (' + xhr.status + '). Please try again.']);
        }
      };

      xhr.onerror = function () {
        self.isProcessing = false;
        self._setLoading(false);
        self._onError(['Network error. Please check your connection and try again.']);
      };

      xhr.ontimeout = function () {
        self.isProcessing = false;
        self._setLoading(false);
        self._onError(['The request timed out. Please try again.']);
      };

      xhr.timeout = 15000; // 15 seconds timeout
      xhr.send(JSON.stringify(payload));
    },

    /**
     * Toggle the add-to-cart button loading state.
     *
     * @param {boolean} loading
     */
    _setLoading: function (loading) {
      var btn = this.addToCartButton;
      if (!btn) {
        return;
      }

      if (loading) {
        btn.disabled = true;
        // Store original content for restoration
        if (!btn.dataset.fsaOriginalText) {
          btn.dataset.fsaOriginalText = btn.innerHTML;
        }
        btn.innerHTML =
          '<span class="fsaccesorios-spinner" aria-hidden="true"></span>' +
          this._getI18n('adding', 'Adding...');
      } else {
        btn.disabled = false;
        if (btn.dataset.fsaOriginalText) {
          btn.innerHTML = btn.dataset.fsaOriginalText;
          delete btn.dataset.fsaOriginalText;
        }
      }
    },

    /**
     * Handle successful cart addition.
     *
     * @param {Object} response
     */
    _onSuccess: function (response) {
      // Notify PrestaShop to refresh the cart widget in header
      if (window.prestashop && typeof window.prestashop.emit === 'function') {
        window.prestashop.emit('updateCart', {
          reason: 'add',
          resp: response
        });
      }

      // Show success notification
      this._showNotification(
        this._getI18n('success', 'Products added to cart successfully'),
        'success'
      );

      // Redirect to cart page after a short delay
      var self = this;
      setTimeout(function () {
        window.location.href = self.cartPageUrl;
      }, 800);
    },

    /**
     * Handle errors during cart addition.
     *
     * @param {string[]} errors
     */
    _onError: function (errors) {
      var message = '';
      if (Array.isArray(errors) && errors.length > 0) {
        message = errors.join('<br>');
      } else {
        message = this._getI18n('error', 'An error occurred. Please try again.');
      }

      this._showNotification(message, 'error');

      // Scroll to the accessories block so the user can see what failed
      if (this.block) {
        this.block.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    },

    /**
     * Show a toast notification.
     * Prefers PrestaShop's native notification system, falls back to custom.
     *
     * @param {string} message HTML message
     * @param {string} type    'success' | 'error'
     */
    _showNotification: function (message, type) {
      // Try PrestaShop's native notification system first
      if (window.prestashop && typeof window.prestashop.emit === 'function') {
        window.prestashop.emit('showNotifications', {
          type: type,
          message: message
        });
        return;
      }

      // Fallback: custom toast
      var existing = document.querySelector('.fsaccesorios-notification');
      if (existing) {
        existing.remove();
      }

      var toast = document.createElement('div');
      toast.className =
        'fsaccesorios-notification fsaccesorios-notification--' + type;
      toast.innerHTML = message;
      toast.setAttribute('role', 'alert');
      document.body.appendChild(toast);

      // Trigger reflow for transition
      toast.offsetHeight;
      toast.classList.add('fsaccesorios-notification--visible');

      // Auto-dismiss
      var dismissTimeout = type === 'success' ? 3000 : 5000;
      setTimeout(function () {
        toast.classList.remove('fsaccesorios-notification--visible');
        setTimeout(function () {
          if (toast.parentNode) {
            toast.remove();
          }
        }, 300);
      }, dismissTimeout);
    },

    /**
     * Get a translated string or fallback.
     *
     * @param {string} key
     * @param {string} fallback
     * @return {string}
     */
    _getI18n: function (key, fallback) {
      if (window.fsaccesorios_i18n && window.fsaccesorios_i18n[key]) {
        return window.fsaccesorios_i18n[key];
      }
      return fallback;
    },

    /**
     * Bind +/- quantity buttons.
     */
    _bindQuantityControls: function () {
      var self = this;

      if (!this.block) {
        return;
      }

      // Decrease buttons
      this.block.querySelectorAll('.fsaccesorios-qty-btn--minus').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var container = this.parentElement;
          var input = container.querySelector('.fsaccesorios-qty-input');
          if (!input || input.disabled) {
            return;
          }

          var min = parseInt(input.min, 10) || 1;
          var val = parseInt(input.value, 10) || min;
          if (val > min) {
            input.value = val - 1;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      });

      // Increase buttons
      this.block.querySelectorAll('.fsaccesorios-qty-btn--plus').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var container = this.parentElement;
          var input = container.querySelector('.fsaccesorios-qty-input');
          if (!input || input.disabled) {
            return;
          }

          var max = parseInt(input.max, 10);
          if (isNaN(max) || max <= 0) {
            max = 999999;
          }
          var val = parseInt(input.value, 10) || 1;
          if (val < max) {
            input.value = val + 1;
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      });

      // Validate direct input changes
      this.block.querySelectorAll('.fsaccesorios-qty-input').forEach(function (input) {
        input.addEventListener('change', function () {
          var min = parseInt(this.min, 10) || 1;
          var max = parseInt(this.max, 10);
          if (isNaN(max) || max <= 0) {
            max = 999999;
          }

          var val = parseInt(this.value, 10);
          if (isNaN(val) || val < min) {
            this.value = min;
          } else if (val > max) {
            this.value = max;
          }
        });
      });
    },

    /**
     * Bind combination/attribute selectors.
     * Updates price, thumb, id_product_attribute, and max quantity when changed.
     */
    _bindCombinationSelects: function () {
      if (!this.block) {
        return;
      }

      var self = this;

      this.block.querySelectorAll('.fsaccesorios-combination-select').forEach(function (select) {
        var initialOption = select.options[select.selectedIndex];
        if (initialOption) {
          self._applyCombinationData(select, initialOption);
        }

        select.addEventListener('change', function () {
          var option = this.options[this.selectedIndex];
          if (option) {
            self._applyCombinationData(this, option);
          }
        });
      });
    },

    /**
     * Apply combination data to the item when a variant is selected.
     *
     * @param {HTMLSelectElement} select
     * @param {HTMLOptionElement} option
     */
    _applyCombinationData: function (select, option) {
      var item = select.closest('.fsaccesorios-item');
      if (!item) {
        return;
      }

      var idProductAttr = parseInt(option.value, 10);
      var qtyAvailable = parseInt(option.getAttribute('data-quantity') || '0', 10);
      var price = option.getAttribute('data-price');

      // Update the checkbox data attribute
      var checkbox = item.querySelector('.fsaccesorios-checkbox');
      if (checkbox) {
        checkbox.setAttribute('data-id-product-attribute', idProductAttr);
      }

      // Update max quantity on the input
      var qtyInput = item.querySelector('.fsaccesorios-qty-input');
      if (qtyInput) {
        qtyInput.max = qtyAvailable;
        if (qtyAvailable <= 0) {
          qtyInput.disabled = true;
          checkbox.disabled = true;
        } else {
          qtyInput.disabled = false;
          checkbox.disabled = false;
          // Clamp current value
          var currentVal = parseInt(qtyInput.value, 10);
          if (currentVal > qtyAvailable) {
            qtyInput.value = qtyAvailable;
          }
        }
      }
    },

    /**
     * Bind visual feedback when checking/unchecking checkboxes.
     * Adds a subtle highlight to the selected row.
     */
    _bindCheckboxVisual: function () {
      if (!this.block) {
        return;
      }

      this.block.querySelectorAll('.fsaccesorios-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
          var item = this.closest('.fsaccesorios-item');
          if (!item) {
            return;
          }

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

  // --- Bootstrap ---
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      Fsaccesorios.init();
    });
  } else {
    Fsaccesorios.init();
  }
})();
