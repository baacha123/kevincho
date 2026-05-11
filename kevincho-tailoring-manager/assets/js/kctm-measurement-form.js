(function($) {
    'use strict';

    var MeasurementForm = {
        init: function() {
            this.$form = $('#kctm-measurement-form');
            if (!this.$form.length) return;

            this.$message = this.$form.find('.kctm-message');
            this.$saveBtn = this.$form.find('.kctm-save-btn');

            this.bindEvents();
            this.toggleGenderFields();
        },

        bindEvents: function() {
            var self = this;

            // Gender change — show/hide relevant fields
            this.$form.on('change', 'input[name="measurements[gender]"], select[name="measurements[gender]"], .kctm-gender-select', function() {
                self.toggleGenderFields();
            });

            // AJAX form submit
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.saveForm();
            });
        },

        toggleGenderFields: function() {
            var gender = this.$form.find('input[name="measurements[gender]"]:checked, select[name="measurements[gender]"]').val() || 'male';

            this.$form.find('[data-gender]').each(function() {
                var $el = $(this);
                var allowedGenders = $el.data('gender').toString().split(',');

                if (allowedGenders.indexOf(gender) !== -1) {
                    $el.removeClass('kctm-hidden').show();
                } else {
                    $el.addClass('kctm-hidden').hide();
                    $el.find('input').val(''); // Clear hidden field values
                }
            });
        },

        saveForm: function() {
            var self = this;
            var formData = this.$form.serialize();

            this.$saveBtn.prop('disabled', true);
            this.$message.removeClass('kctm-success kctm-error').hide();

            // Add spinner
            this.$saveBtn.after('<span class="kctm-spinner"></span>');

            $.ajax({
                url: kctm_measurements.ajax_url,
                type: 'POST',
                data: formData + '&action=kctm_save_my_measurements&security=' + kctm_measurements.nonce,
                success: function(response) {
                    self.$form.find('.kctm-spinner').remove();
                    self.$saveBtn.prop('disabled', false);

                    if (response.success) {
                        self.$message.text(response.data.message || 'Measurements saved successfully!').addClass('kctm-success').show();
                        // Scroll to message
                        $('html, body').animate({ scrollTop: self.$message.offset().top - 100 }, 300);
                    } else {
                        var errors = response.data.errors || ['An error occurred.'];
                        self.$message.html(errors.join('<br>')).addClass('kctm-error').show();
                    }
                },
                error: function() {
                    self.$form.find('.kctm-spinner').remove();
                    self.$saveBtn.prop('disabled', false);
                    self.$message.text('Connection error. Please try again.').addClass('kctm-error').show();
                }
            });
        }
    };

    $(document).ready(function() {
        MeasurementForm.init();
    });

})(jQuery);
