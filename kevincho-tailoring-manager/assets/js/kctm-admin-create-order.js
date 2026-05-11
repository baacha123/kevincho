(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize Select2 for customer search
        initCustomerSelect();

        // Initialize Select2 for product search
        initProductSelects();

        // Add product row
        var productIndex = 1;
        $('#kctm-add-product-row').on('click', function() {
            var $row = $(
                '<div class="kctm-product-row">' +
                '<select name="items[' + productIndex + '][product_id]" class="kctm-select2-product" style="width:300px;">' +
                '<option value="">Search for a product...</option>' +
                '</select>' +
                '<input type="number" name="items[' + productIndex + '][quantity]" value="1" min="1" style="width:70px;" placeholder="Qty">' +
                '<input type="number" name="items[' + productIndex + '][price]" step="0.01" min="0" style="width:120px;" placeholder="Price (override)">' +
                '<button type="button" class="button kctm-remove-row">&times;</button>' +
                '</div>'
            );
            $('#kctm-product-rows').append($row);
            initProductSelect($row.find('.kctm-select2-product'));
            productIndex++;
        });

        // Add custom item row
        var customIndex = 1;
        $('#kctm-add-custom-row').on('click', function() {
            var $row = $(
                '<div class="kctm-custom-row">' +
                '<input type="text" name="custom_items[' + customIndex + '][name]" style="width:300px;" placeholder="Item description">' +
                '<input type="number" name="custom_items[' + customIndex + '][quantity]" value="1" min="1" style="width:70px;" placeholder="Qty">' +
                '<input type="number" name="custom_items[' + customIndex + '][price]" step="0.01" min="0" style="width:120px;" placeholder="Price">' +
                '<button type="button" class="button kctm-remove-row">&times;</button>' +
                '</div>'
            );
            $('#kctm-custom-rows').append($row);
            customIndex++;
        });

        // Remove row
        $(document).on('click', '.kctm-remove-row', function() {
            $(this).closest('.kctm-product-row, .kctm-custom-row').remove();
        });

        // Show customer details when selected
        $(document).on('change', '#kctm-customer-select', function() {
            var customerId = $(this).val();
            if (!customerId) {
                $('#kctm-customer-info').hide();
                return;
            }

            $.post(kctm_order.ajax_url, {
                action: 'kctm_get_customer_details',
                security: kctm_order.nonce,
                customer_id: customerId
            }, function(response) {
                if (response.success) {
                    var d = response.data;
                    var html = d.name + '<br>Email: ' + d.email + '<br>Phone: ' + d.phone;
                    if (d.has_measurements) {
                        html += '<br><span style="color:green;">&#10003; Has measurements on file</span>';
                    } else {
                        html += '<br><span style="color:#d63638;">&#10007; No measurements on file</span>';
                    }
                    $('#kctm-customer-details').html(html);
                    $('#kctm-customer-info').show();
                }
            });
        });
    });

    function initCustomerSelect() {
        $('.kctm-select2-customer').select2({
            ajax: {
                url: kctm_order.ajax_url,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        action: 'kctm_search_customers',
                        security: kctm_order.nonce,
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return { results: data.data || [] };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: 'Search for a customer...'
        });
    }

    function initProductSelects() {
        $('.kctm-select2-product').each(function() {
            initProductSelect($(this));
        });
    }

    function initProductSelect($el) {
        $el.select2({
            ajax: {
                url: kctm_order.ajax_url,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        action: 'kctm_search_products',
                        security: kctm_order.nonce,
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return { results: data.data || [] };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: 'Search for a product...'
        });
    }

})(jQuery);
