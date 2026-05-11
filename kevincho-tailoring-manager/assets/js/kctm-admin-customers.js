(function($) {
    'use strict';

    $(document).ready(function() {
        // Gender-based field toggle (admin measurement form)
        var $form = $('#kctm-admin-measurement-form');
        if ($form.length) {
            $form.on('change', '.kctm-gender-select', function() {
                var gender = $(this).val() || 'male';
                $form.find('[data-gender]').each(function() {
                    var $row = $(this);
                    var allowed = $row.data('gender').toString().split(',');
                    if (allowed.indexOf(gender) !== -1) {
                        $row.show();
                    } else {
                        $row.hide().find('input').val('');
                    }
                });
            }).find('.kctm-gender-select').trigger('change');
        }

        // Admin save measurements via AJAX
        $form.on('submit', function(e) {
            e.preventDefault();
            var $btn = $form.find(':submit');
            $btn.prop('disabled', true).val('Saving...');

            $.post(kctm_admin.ajax_url, {
                action: 'kctm_save_measurements',
                security: kctm_admin.nonce,
                customer_id: $form.find('[name="customer_id"]').val(),
                measurements: $form.find('[name^="measurements"]').serialize()
            }, function(response) {
                $btn.prop('disabled', false).val('Save Measurements');
                if (response.success) {
                    // Show WP admin notice
                    $form.before('<div class="notice notice-success is-dismissible"><p>' + (response.data.message || 'Saved!') + '</p></div>');
                    $('html, body').animate({ scrollTop: 0 }, 300);
                } else {
                    $form.before('<div class="notice notice-error is-dismissible"><p>' + (response.data.message || 'Error saving.') + '</p></div>');
                }
            }).fail(function() {
                $btn.prop('disabled', false).val('Save Measurements');
                alert('Connection error.');
            });
        });

        // Test WhatsApp connection button
        $('#kctm-test-whatsapp').on('click', function() {
            var $btn = $(this);
            var $result = $('#kctm-test-result');
            $btn.prop('disabled', true);
            $result.text('Testing...').removeClass('success error');

            $.post(kctm_admin.ajax_url, {
                action: 'kctm_test_whatsapp',
                security: kctm_admin.nonce
            }, function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $result.text('Connected!').addClass('success');
                } else {
                    $result.text(response.data.message || 'Connection failed.').addClass('error');
                }
            }).fail(function() {
                $btn.prop('disabled', false);
                $result.text('Request failed.').addClass('error');
            });
        });
    });

})(jQuery);
