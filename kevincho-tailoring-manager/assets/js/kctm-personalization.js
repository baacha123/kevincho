(function($) {
    'use strict';

    var Personalizer = {
        init: function() {
            this.$wrap = $('#kctm-personalizer');
            if (!this.$wrap.length) return;

            this.$summary = this.$wrap.find('.kctm-pz-summary-list');
            this.$totalEl = this.$wrap.find('.kctm-pz-total-amount');
            this.$totalInput = this.$wrap.find('input[name="kctm_personalization_total"]');

            this.bindEvents();
            this.updateSummary();
        },

        bindEvents: function() {
            var self = this;

            // Option selection change
            this.$wrap.on('change', 'input[type="radio"]', function() {
                self.updateSummary();
            });

            // Monogram text field toggle
            this.$wrap.on('change', 'input[name="kctm_personalization[monogram]"]', function() {
                var val = $(this).val();
                var $textField = self.$wrap.find('.kctm-pz-monogram-text');
                if (val && val !== 'none') {
                    $textField.slideDown(200);
                } else {
                    $textField.slideUp(200);
                    $textField.find('input').val('');
                }
            });
        },

        updateSummary: function() {
            var self = this;
            var items = [];
            var totalModifier = 0;

            this.$wrap.find('.kctm-pz-group').each(function() {
                var $group = $(this);
                var groupTitle = $group.find('.kctm-pz-group-title').text();
                var $selected = $group.find('input[type="radio"]:checked');

                if ($selected.length) {
                    var $card = $selected.closest('.kctm-pz-option-card');
                    var optionTitle = $card.find('.kctm-pz-option-title').text();
                    var priceModifier = parseFloat($selected.data('price-modifier')) || 0;

                    items.push({
                        group: groupTitle,
                        option: optionTitle,
                        price: priceModifier
                    });

                    totalModifier += priceModifier;
                }
            });

            // Update summary list
            var html = '';
            items.forEach(function(item) {
                html += '<li>';
                html += '<span class="kctm-pz-sum-label">' + item.group + '</span>';
                html += '<span class="kctm-pz-sum-value">' + item.option;
                if (item.price > 0) {
                    html += ' <small>(+' + self.formatPrice(item.price) + ')</small>';
                }
                html += '</span>';
                html += '</li>';
            });
            this.$summary.html(html);

            // Update total
            if (totalModifier > 0) {
                this.$totalEl.text('+' + this.formatPrice(totalModifier));
                this.$wrap.find('.kctm-pz-summary-total').show();
            } else {
                this.$wrap.find('.kctm-pz-summary-total').hide();
            }

            // Update hidden input
            this.$totalInput.val(totalModifier);
        },

        formatPrice: function(amount) {
            // Use WooCommerce currency format if available
            if (typeof woocommerce_params !== 'undefined') {
                return amount.toLocaleString() + ' ' + (woocommerce_params.currency_symbol || '');
            }
            return amount.toLocaleString() + ' FCFA';
        }
    };

    $(document).ready(function() {
        Personalizer.init();
    });

})(jQuery);
