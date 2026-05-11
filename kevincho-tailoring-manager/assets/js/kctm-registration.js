/**
 * KevinCho Tailoring Manager — Registration Form JS
 *
 * Handles the "Do you have your measurements?" toggle
 * and the collapsible measurement guide.
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        var $yesSection = $('#kctm-reg-measurements-form');
        var $noSection  = $('#kctm-reg-no-measurements');
        var $guide      = $('#kctm-measurement-guide');
        var $guideLink  = $('#kctm-toggle-guide');
        var $radios     = $('input[name="kctm_has_measurements"]');

        /* ---- Toggle measurements sections ---- */
        $radios.on('change', function () {
            var val = $(this).val();

            if (val === 'yes') {
                $noSection.slideUp(200);
                $yesSection.slideDown(300);
            } else if (val === 'no') {
                $yesSection.slideUp(200);
                $noSection.slideDown(300);
            }
        });

        /* Show correct section if value is already set (e.g. after validation error) */
        var checked = $('input[name="kctm_has_measurements"]:checked').val();
        if (checked === 'yes') {
            $noSection.hide();
            $yesSection.show();
        } else if (checked === 'no') {
            $yesSection.hide();
            $noSection.show();
        }

        /* ---- Collapsible measurement guide ---- */
        $guideLink.on('click', function (e) {
            e.preventDefault();
            var $arrow = $(this).find('.kctm-guide-arrow');

            $guide.slideToggle(250, function () {
                if ($guide.is(':visible')) {
                    $arrow.css('transform', 'rotate(180deg)');
                } else {
                    $arrow.css('transform', 'rotate(0deg)');
                }
            });
        });

        /* ---- Highlight active toggle button ---- */
        $radios.on('change', function () {
            $('.kctm-reg-toggle-label').removeClass('active');
            $(this).closest('.kctm-reg-toggle-label').addClass('active');
        });

        /* Set active class on load */
        $('input[name="kctm_has_measurements"]:checked')
            .closest('.kctm-reg-toggle-label')
            .addClass('active');
    });

})(jQuery);
