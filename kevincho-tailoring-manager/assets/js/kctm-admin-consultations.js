jQuery(function($) {
    'use strict';

    // Confirm cancel action
    $('.kctm-cancel-booking').on('click', function(e) {
        if (!confirm('Are you sure you want to cancel this consultation?')) {
            e.preventDefault();
        }
    });

    // Confirm complete action
    $('.kctm-complete-booking').on('click', function(e) {
        if (!confirm('Mark this consultation as completed?')) {
            e.preventDefault();
        }
    });

    // Status filter form - auto-submit on dropdown change
    $('#kctm-status-filter').on('change', function() {
        $(this).closest('form').submit();
    });

    // Resend WhatsApp notification
    $('#kctm-resend-confirmation').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var bookingId = $btn.data('booking-id');

        $btn.prop('disabled', true).text('Sending...');

        $.post(kctm_admin.ajax_url, {
            action: 'kctm_resend_consultation_notification',
            _ajax_nonce: kctm_admin.nonce,
            booking_id: bookingId
        }, function(response) {
            $btn.prop('disabled', false).text('Resend Confirmation');
            if (response.success) {
                alert('Notification sent successfully!');
            } else {
                alert('Failed to send notification: ' + (response.data ? response.data.message : 'Unknown error'));
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Resend Confirmation');
            alert('Request failed. Please try again.');
        });
    });
});
