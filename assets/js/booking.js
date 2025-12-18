// @ts-nocheck
// This disables TypeScript validation in VS Code for this plain JS file

jQuery(function($) {
    // Load calendar on staff select or month change (integrate with wizard)
    $(document).on('click', '.ai-staff-card', function() {
        const staffId = $(this).data('staff-id');
        const month = new Date().toISOString().slice(0,7); // current month

        $.post(aiBooking.ajaxurl, {
            action: 'ai_load_calendar',
            staff_id: staffId,
            month: month,
            nonce: aiBooking.nonce
        }, function(response) {
            if (response.success) {
                $('.ai-booking-calendar-container').html(response.data.html);
            }
        });
    });

    // Time slot selection
    $(document).on('click', '.ai-time-slot', function() {
        $('.ai-time-slot').removeClass('selected');
        $(this).addClass('selected');
    });
});