jQuery(function($) {
    'use strict';

    var currentYear = new Date().getFullYear();
    var currentMonth = new Date().getMonth(); // 0-indexed
    var selectedDate = '';
    var selectedTime = '';

    // Step navigation
    function showStep(stepId) {
        $('.kctm-step').hide();
        $('#' + stepId).fadeIn(300);
    }

    // Start booking
    $('#kctm-start-booking').on('click', function() {
        showStep('kctm-step-date');
        renderCalendar(currentYear, currentMonth);
    });

    // Back buttons
    $('#kctm-back-to-intro').on('click', function() { showStep('kctm-step-intro'); });
    $('#kctm-back-to-date').on('click', function() { showStep('kctm-step-date'); });
    $('#kctm-back-to-time').on('click', function() { showStep('kctm-step-time'); });
    $('#kctm-back-to-contact').on('click', function() { showStep('kctm-step-contact'); });

    // Calendar nav
    $('#kctm-prev-month').on('click', function() {
        var now = new Date();
        if (currentYear === now.getFullYear() && currentMonth <= now.getMonth()) return;
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar(currentYear, currentMonth);
    });

    $('#kctm-next-month').on('click', function() {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar(currentYear, currentMonth);
    });

    function renderCalendar(year, month) {
        var $grid = $('#kctm-calendar-grid');
        var $label = $('#kctm-calendar-month-label');
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        $label.text(monthNames[month] + ' ' + year);
        $grid.html('<div class="kctm-loading">Loading available dates...</div>');

        $.ajax({
            url: kctm_consultation.ajax_url,
            type: 'POST',
            data: {
                action: 'kctm_get_available_dates',
                _ajax_nonce: kctm_consultation.nonce,
                year: year,
                month: month + 1 // PHP months are 1-indexed
            },
            success: function(response) {
                if (response.success) {
                    buildCalendarGrid($grid, year, month, response.data.dates);
                } else {
                    $grid.html('<p class="kctm-error">Could not load available dates.</p>');
                }
            },
            error: function() {
                $grid.html('<p class="kctm-error">Connection error. Please try again.</p>');
            }
        });
    }

    function buildCalendarGrid($grid, year, month, availableDates) {
        var html = '<div class="kctm-cal-header">';
        var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        for (var d = 0; d < 7; d++) {
            html += '<div class="kctm-cal-day-name">' + days[d] + '</div>';
        }
        html += '</div><div class="kctm-cal-body">';

        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var today = new Date();
        today.setHours(0,0,0,0);

        // Empty cells for days before first
        for (var e = 0; e < firstDay; e++) {
            html += '<div class="kctm-cal-cell kctm-cal-empty"></div>';
        }

        for (var i = 1; i <= daysInMonth; i++) {
            var dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(i).padStart(2,'0');
            var cellDate = new Date(year, month, i);
            var isPast = cellDate < today;
            var isAvailable = availableDates.indexOf(dateStr) !== -1;

            var cls = 'kctm-cal-cell';
            if (isPast) cls += ' kctm-cal-past';
            else if (isAvailable) cls += ' kctm-cal-available';
            else cls += ' kctm-cal-unavailable';

            if (dateStr === selectedDate) cls += ' kctm-cal-selected';

            html += '<div class="' + cls + '" data-date="' + dateStr + '">' + i + '</div>';
        }

        html += '</div>';
        $grid.html(html);

        // Click handler for available dates
        $grid.find('.kctm-cal-available').on('click', function() {
            selectedDate = $(this).data('date');
            $('#kctm-selected-date').val(selectedDate);
            $grid.find('.kctm-cal-selected').removeClass('kctm-cal-selected');
            $(this).addClass('kctm-cal-selected');

            // Load time slots
            loadTimeSlots(selectedDate);
        });
    }

    function loadTimeSlots(date) {
        var $slots = $('#kctm-time-slots');
        var dateParts = date.split('-');
        var formatted = new Date(dateParts[0], dateParts[1]-1, dateParts[2]).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
        $('#kctm-selected-date-label').text(formatted);

        showStep('kctm-step-time');
        $slots.html('<div class="kctm-loading">Loading available times...</div>');

        $.ajax({
            url: kctm_consultation.ajax_url,
            type: 'POST',
            data: {
                action: 'kctm_get_available_times',
                _ajax_nonce: kctm_consultation.nonce,
                date: date
            },
            success: function(response) {
                if (response.success && response.data.times.length > 0) {
                    var html = '';
                    for (var t = 0; t < response.data.times.length; t++) {
                        var time = response.data.times[t];
                        var cls = 'kctm-time-slot';
                        if (time === selectedTime) cls += ' kctm-time-selected';
                        // Format time for display (e.g., "09:00" -> "9:00 AM")
                        var parts = time.split(':');
                        var h = parseInt(parts[0]);
                        var ampm = h >= 12 ? 'PM' : 'AM';
                        var h12 = h % 12 || 12;
                        var displayTime = h12 + ':' + parts[1] + ' ' + ampm;
                        html += '<button type="button" class="' + cls + '" data-time="' + time + '">' + displayTime + '</button>';
                    }
                    $slots.html(html);

                    $slots.find('.kctm-time-slot').on('click', function() {
                        selectedTime = $(this).data('time');
                        $('#kctm-selected-time').val(selectedTime);
                        $slots.find('.kctm-time-selected').removeClass('kctm-time-selected');
                        $(this).addClass('kctm-time-selected');
                        showStep('kctm-step-contact');
                    });
                } else {
                    $slots.html('<p>No available time slots for this date. Please select another date.</p>');
                }
            },
            error: function() {
                $slots.html('<p class="kctm-error">Connection error. Please try again.</p>');
            }
        });
    }

    // Continue to summary
    $('#kctm-to-summary').on('click', function() {
        var firstName = $.trim($('#kctm-first-name').val());
        var lastName = $.trim($('#kctm-last-name').val());
        var phone = $.trim($('#kctm-phone').val());

        if (!firstName || !lastName || !phone) {
            alert('Please fill in all required fields (First Name, Last Name, Phone).');
            return;
        }

        // Format selected date for display
        var dateParts = selectedDate.split('-');
        var formatted = new Date(dateParts[0], dateParts[1]-1, dateParts[2]).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
        var timeParts = selectedTime.split(':');
        var h = parseInt(timeParts[0]);
        var ampm = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12 || 12;
        var displayTime = h12 + ':' + timeParts[1] + ' ' + ampm;

        $('#kctm-summary-date').text(formatted);
        $('#kctm-summary-time').text(displayTime);
        $('#kctm-summary-name').text(firstName + ' ' + lastName);
        $('#kctm-summary-phone').text(phone);

        showStep('kctm-step-summary');
    });

    // Proceed to payment
    $('#kctm-proceed-payment').on('click', function() {
        var $btn = $(this);
        var $error = $('#kctm-booking-error');
        var $loading = $('#kctm-booking-loading');

        $btn.prop('disabled', true);
        $error.hide();
        $loading.show();

        $.ajax({
            url: kctm_consultation.ajax_url,
            type: 'POST',
            data: {
                action: 'kctm_book_consultation',
                _ajax_nonce: kctm_consultation.nonce,
                consultation_date: selectedDate,
                consultation_time: selectedTime,
                first_name: $('#kctm-first-name').val(),
                last_name: $('#kctm-last-name').val(),
                phone: $('#kctm-phone').val(),
                email: $('#kctm-email').val(),
                notes: $('#kctm-notes').val()
            },
            success: function(response) {
                $loading.hide();
                if (response.success && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    $btn.prop('disabled', false);
                    $error.text(response.data && response.data.message ? response.data.message : 'An error occurred. Please try again.').show();
                }
            },
            error: function() {
                $loading.hide();
                $btn.prop('disabled', false);
                $error.text('Connection error. Please try again.').show();
            }
        });
    });
});
