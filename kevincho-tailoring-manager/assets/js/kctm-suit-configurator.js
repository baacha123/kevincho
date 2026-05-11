/**
 * KevinCho Tailoring Manager — Suit Configurator JS
 *
 * Handles the Hockerty-style step-by-step suit configurator:
 * - Step navigation (FABRIC > STYLE > ACCENTS)
 * - Fabric selection with search/filter + SVG preview update
 * - Style/accent option selection + SVG variant toggling
 * - Live price calculation
 * - Add to Cart via AJAX
 */
(function($) {
    'use strict';

    var Configurator = {

        basePrice: 0,
        fabricModifier: 0,
        optionModifiers: {},

        init: function() {
            this.$wrap = $('#kctm-configurator');
            if (!this.$wrap.length) return;

            this.basePrice = parseFloat($('#kctm-base-price-raw').val()) || 0;

            this.bindStepNavigation();
            this.bindFabricSelection();
            this.bindFabricSearch();
            this.bindFabricFilters();
            this.bindOptionSelection();
            this.bindMonogramToggle();
            this.bindAddToCart();

            // Initialize summary from defaults
            this.updateSummary();
            this.initDefaultSelections();
        },

        /* ============================================================
           Step Navigation
           ============================================================ */

        bindStepNavigation: function() {
            var self = this;

            // Tab clicks
            this.$wrap.on('click', '.kctm-step-tab', function() {
                var step = $(this).data('step');
                self.goToStep(step);
            });

            // Next/Prev buttons
            this.$wrap.on('click', '.kctm-btn-next, .kctm-btn-prev', function() {
                var step = $(this).data('goto');
                self.goToStep(step);
            });
        },

        goToStep: function(step) {
            // Update tabs
            this.$wrap.find('.kctm-step-tab').removeClass('active');
            this.$wrap.find('.kctm-step-tab[data-step="' + step + '"]').addClass('active');

            // Mark previous steps as completed
            var steps = ['fabric', 'style', 'accents'];
            var currentIndex = steps.indexOf(step);
            this.$wrap.find('.kctm-step-tab').each(function() {
                var tabStep = $(this).data('step');
                var tabIndex = steps.indexOf(tabStep);
                if (tabIndex < currentIndex) {
                    $(this).addClass('completed');
                } else {
                    $(this).removeClass('completed');
                }
            });

            // Show panel
            this.$wrap.find('.kctm-step-panel').removeClass('active');
            this.$wrap.find('.kctm-step-panel[data-panel="' + step + '"]').addClass('active');
        },

        /* ============================================================
           Fabric Selection
           ============================================================ */

        bindFabricSelection: function() {
            var self = this;

            this.$wrap.on('click', '.kctm-fabric-swatch', function() {
                var $swatch = $(this);

                // Update selection
                self.$wrap.find('.kctm-fabric-swatch').removeClass('selected');
                $swatch.addClass('selected');

                // Get fabric data
                var color = $swatch.data('color');
                var name = $swatch.data('name');
                var pattern = $swatch.data('pattern');
                var price = parseFloat($swatch.data('price')) || 0;

                // Update preview SVG
                self.updateFabricPreview(color, pattern);

                // Update fabric info
                self.$wrap.find('.kctm-fabric-selected-name').text(name);
                $('#kctm-sum-fabric').text(name);

                // Update price
                self.fabricModifier = price;
                self.updatePrice();
            });
        },

        updateFabricPreview: function(color, pattern) {
            // Apply to both jacket and pants fabric layers
            var layers = [
                document.getElementById('kctm-suit-fabric'),
                document.getElementById('kctm-pants-fabric')
            ];

            var bgImage = 'none';
            if (pattern === 'striped') {
                bgImage = 'repeating-linear-gradient(90deg, transparent, transparent 8px, rgba(255,255,255,0.15) 8px, rgba(255,255,255,0.15) 9px)';
            } else if (pattern === 'checkered') {
                bgImage = 'repeating-linear-gradient(90deg, transparent, transparent 18px, rgba(255,255,255,0.12) 18px, rgba(255,255,255,0.12) 19px), repeating-linear-gradient(0deg, transparent, transparent 18px, rgba(255,255,255,0.12) 18px, rgba(255,255,255,0.12) 19px)';
            } else if (pattern === 'herringbone') {
                bgImage = 'repeating-linear-gradient(45deg, transparent, transparent 4px, rgba(255,255,255,0.08) 4px, rgba(255,255,255,0.08) 5px), repeating-linear-gradient(-45deg, transparent, transparent 4px, rgba(255,255,255,0.08) 4px, rgba(255,255,255,0.08) 5px)';
            } else if (pattern === 'plaid') {
                bgImage = 'repeating-linear-gradient(90deg, transparent, transparent 20px, rgba(255,255,255,0.1) 20px, rgba(255,255,255,0.1) 22px), repeating-linear-gradient(0deg, transparent, transparent 20px, rgba(255,255,255,0.1) 20px, rgba(255,255,255,0.1) 22px)';
            }

            for (var i = 0; i < layers.length; i++) {
                if (layers[i]) {
                    layers[i].style.backgroundColor = color;
                    layers[i].style.backgroundImage = bgImage;
                }
            }
        },

        /* ============================================================
           Fabric Search & Filter
           ============================================================ */

        bindFabricSearch: function() {
            var self = this;

            $('#kctm-fabric-search').on('input', function() {
                var term = $(this).val().toLowerCase();
                self.$wrap.find('.kctm-fabric-swatch').each(function() {
                    var name = $(this).data('name').toLowerCase();
                    $(this).toggleClass('hidden', term.length > 0 && name.indexOf(term) === -1);
                });
            });
        },

        bindFabricFilters: function() {
            var self = this;

            this.$wrap.on('click', '.kctm-filter-btn', function() {
                var filter = $(this).data('filter');

                self.$wrap.find('.kctm-filter-btn').removeClass('active');
                $(this).addClass('active');

                self.$wrap.find('.kctm-fabric-swatch').each(function() {
                    if (filter === 'all') {
                        $(this).removeClass('hidden');
                    } else {
                        var pattern = $(this).data('pattern');
                        $(this).toggleClass('hidden', pattern !== filter);
                    }
                });
            });
        },

        /* ============================================================
           Style & Accent Option Selection
           ============================================================ */

        bindOptionSelection: function() {
            var self = this;

            this.$wrap.on('change', '.kctm-option-card input[type="radio"]', function() {
                var group = $(this).data('group');
                var price = parseFloat($(this).data('price')) || 0;
                var optionSlug = $(this).val();

                // Update price modifier for this group
                self.optionModifiers[group] = price;
                self.updatePrice();
                self.updateSummary();

                // Update SVG preview based on group
                self.updateStylePreview(group, optionSlug);
            });
        },

        updateStylePreview: function(group, optionSlug) {
            // Style variant preview is handled by the summary panel.
            // The photorealistic suit photo shows the base silhouette;
            // individual style variations (lapels, pockets, buttons) will
            // be reflected when custom variant photos are added later.
        },

        initDefaultSelections: function() {
            // Trigger SVG updates for all default-checked options
            var self = this;
            this.$wrap.find('.kctm-option-card input[type="radio"]:checked').each(function() {
                var group = $(this).data('group');
                var optionSlug = $(this).val();
                var price = parseFloat($(this).data('price')) || 0;

                self.optionModifiers[group] = price;
                self.updateStylePreview(group, optionSlug);
            });
            this.updatePrice();
        },

        /* ============================================================
           Monogram Toggle
           ============================================================ */

        bindMonogramToggle: function() {
            this.$wrap.on('change', 'input[name="kctm_personalization[monogram]"]', function() {
                var val = $(this).val();
                var $wrap = $('#kctm-monogram-wrap');
                if (val && val !== 'none') {
                    $wrap.slideDown(200);
                } else {
                    $wrap.slideUp(200);
                    $wrap.find('input').val('');
                }
            });
        },

        /* ============================================================
           Price Calculation
           ============================================================ */

        updatePrice: function() {
            var totalModifier = this.fabricModifier;

            for (var group in this.optionModifiers) {
                if (this.optionModifiers.hasOwnProperty(group)) {
                    totalModifier += this.optionModifiers[group];
                }
            }

            var total = this.basePrice + totalModifier;

            // Update extras display
            if (totalModifier > 0) {
                $('#kctm-price-extras').show();
                $('#kctm-extras-amount').text('+' + this.formatPrice(totalModifier));
            } else {
                $('#kctm-price-extras').hide();
            }

            // Update total
            $('#kctm-total-price').text(this.formatPrice(total));
        },

        formatPrice: function(amount) {
            // Simple formatting — use WC currency data if available
            if (typeof kctm_configurator !== 'undefined' && kctm_configurator.currency_symbol) {
                return amount.toLocaleString() + ' ' + kctm_configurator.currency_symbol;
            }
            return amount.toLocaleString() + ' FCFA';
        },

        /* ============================================================
           Summary Panel
           ============================================================ */

        updateSummary: function() {
            var $items = $('#kctm-summary-items');
            var html = '';

            // Fabric
            var fabricName = this.$wrap.find('.kctm-fabric-swatch.selected').data('name') || '—';
            html += '<div class="kctm-summary-row">';
            html += '<span class="kctm-summary-label">Fabric</span>';
            html += '<span class="kctm-summary-value">' + this.escapeHtml(fabricName) + '</span>';
            html += '</div>';

            // All selected options
            this.$wrap.find('.kctm-option-card input[type="radio"]:checked').each(function() {
                var $card = $(this).closest('.kctm-option-card');
                var $group = $(this).closest('.kctm-option-group');
                var groupTitle = $group.find('.kctm-option-group-title').text();
                var optionTitle = $(this).data('title') || $card.find('.kctm-card-title').text();

                html += '<div class="kctm-summary-row">';
                html += '<span class="kctm-summary-label">' + groupTitle + '</span>';
                html += '<span class="kctm-summary-value">' + optionTitle + '</span>';
                html += '</div>';
            });

            $items.html(html);
        },

        escapeHtml: function(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        /* ============================================================
           Add to Cart
           ============================================================ */

        bindAddToCart: function() {
            var self = this;

            $('#kctm-add-to-cart').on('click', function() {
                var $btn = $(this);
                var $msg = $('#kctm-cart-message');

                // Collect data
                var productId = $('#kctm-product-id').val();
                var fabricId = self.$wrap.find('.kctm-fabric-swatch.selected').data('fabric-id');
                var personalization = {};
                var monogramText = $('#kctm-monogram-text').val() || '';

                self.$wrap.find('.kctm-option-card input[type="radio"]:checked').each(function() {
                    var group = $(this).data('group');
                    personalization[group] = $(this).val();
                });

                if (!fabricId) {
                    $msg.removeClass('success').addClass('error').text('Please select a fabric.').show();
                    return;
                }

                // Disable button and show spinner
                $btn.prop('disabled', true);
                $btn.html($btn.text() + ' <span class="kctm-spinner"></span>');
                $msg.hide().removeClass('success error');

                $.ajax({
                    url: kctm_configurator.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'kctm_add_configured_suit',
                        _ajax_nonce: kctm_configurator.nonce,
                        product_id: productId,
                        fabric_id: fabricId,
                        personalization: personalization,
                        monogram_text: monogramText
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).find('.kctm-spinner').remove();

                        if (response.success) {
                            $msg.removeClass('error').addClass('success').text(response.data.message).show();
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            }
                        } else {
                            var errMsg = response.data && response.data.message ? response.data.message : 'An error occurred.';
                            $msg.removeClass('success').addClass('error').text(errMsg).show();
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).find('.kctm-spinner').remove();
                        $msg.removeClass('success').addClass('error').text('Request failed. Please try again.').show();
                    }
                });
            });
        }
    };

    $(document).ready(function() {
        Configurator.init();
    });

})(jQuery);
