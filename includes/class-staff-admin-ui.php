<?php
/**
 * AI Hairstyle – Staff Admin UI
 * Renders all metaboxes for ai_staff CPT
 */

class AI_Hairstyle_Staff_Admin_UI {

    public static function render_details_metabox( $post ) {
        wp_nonce_field( 'ai_staff_save', 'ai_staff_nonce' );

        $email     = get_post_meta( $post->ID, '_ai_staff_email', true );
        $photo_id  = get_post_meta( $post->ID, '_ai_staff_photo_id', true );
        $photo_alt = get_post_meta( $post->ID, '_ai_staff_photo_alt', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ai_staff_email">Email Address</label></th>
                <td>
                    <input type="email" name="ai_staff_email" id="ai_staff_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
                    <p class="description">Used for individual booking notifications (optional).</p>
                </td>
            </tr>
            <tr>
                <th><label>Staff Photo (SEO Optimized)</label></th>
                <td>
                    <div id="ai-staff-photo-preview">
                        <?php if ( $photo_id ) : ?>
                            <?php echo wp_get_attachment_image( $photo_id, 'medium', false, array( 'style' => 'max-width:200px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);' ) ); ?>
                        <?php endif; ?>
                    </div>
                    <p>
                        <button type="button" class="button ai-upload-staff-photo">Upload / Select Photo</button>
                        <button type="button" class="button ai-remove-staff-photo" <?php echo $photo_id ? '' : 'style="display:none;"'; ?>>Remove Photo</button>
                    </p>
                    <input type="hidden" name="ai_staff_photo_id" id="ai_staff_photo_id" value="<?php echo esc_attr( $photo_id ); ?>" />
                    <p>
                        <label for="ai_staff_photo_alt"><strong>Photo Alt Text (SEO)</strong></label><br>
                        <input type="text" name="ai_staff_photo_alt" id="ai_staff_photo_alt" value="<?php echo esc_attr( $photo_alt ); ?>" class="regular-text" placeholder="e.g. Sarah Johnson - Senior Stylist at [Salon Name]" />
                        <span class="description">Important for SEO and accessibility.</span>
                    </p>
                </td>
            </tr>
        </table>

        <p><strong>Bio:</strong> Use the rich editor below for a full SEO-optimized biography.</p>

        <script>
        jQuery(function($){
            var staffPhotoFrame;
            $(document).on('click', '.ai-upload-staff-photo', function(e){
                e.preventDefault();
                if ( staffPhotoFrame ) {
                    staffPhotoFrame.open();
                    return;
                }
                staffPhotoFrame = wp.media({
                    title: 'Select Staff Photo',
                    button: { text: 'Use this photo' },
                    multiple: false,
                    library: { type: 'image' }
                });
                staffPhotoFrame.on('select', function(){
                    var attachment = staffPhotoFrame.state().get('selection').first().toJSON();
                    $('#ai-staff-photo-preview').html('<img src="' + attachment.sizes.medium.url + '" style="max-width:200px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">');
                    $('#ai_staff_photo_id').val(attachment.id);
                    $('.ai-remove-staff-photo').show();
                });
                staffPhotoFrame.open();
            });

            $(document).on('click', '.ai-remove-staff-photo', function(e){
                e.preventDefault();
                $('#ai-staff-photo-preview').empty();
                $('#ai_staff_photo_id').val('');
                $('#ai_staff_photo_alt').val('');
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public static function render_availability_metabox( $post ) {
        $weekly_hours   = get_post_meta( $post->ID, '_ai_staff_weekly_hours', true ) ?: array();
        $blocked_slots  = get_post_meta( $post->ID, '_ai_staff_blocked_slots', true ) ?: array();
        $slot_interval  = get_post_meta( $post->ID, '_ai_staff_slot_interval', true ) ?: 15;

        ?>
        <div class="ai-availability-section">

            <h3>Weekly Recurring Hours</h3>
            <p class="description">Set default working hours. Global defaults can be overridden here.</p>

            <table class="form-table ai-weekly-hours-table">
                <thead>
                    <tr>
                        <th style="width:18%;">Day</th>
                        <th style="width:28%;">Open Time</th>
                        <th style="width:28%;">Close Time</th>
                        <th style="width:26%;">Closed?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                    foreach ( $days as $day ) :
                        $open   = $weekly_hours[$day]['open'] ?? '';
                        $close  = $weekly_hours[$day]['close'] ?? '';
                        $closed = ! empty( $weekly_hours[$day]['closed'] );
                    ?>
                    <tr>
                        <th style="padding-top:14px;"><?php echo ucfirst( $day ); ?></th>
                        <td>
                            <select name="ai_weekly_hours[<?php echo $day; ?>][open]" class="ai-time-select" <?php disabled( $closed ); ?>>
                                <option value="">--</option>
                                <?php self::render_time_options( $open ); ?>
                            </select>
                        </td>
                        <td>
                            <select name="ai_weekly_hours[<?php echo $day; ?>][close]" class="ai-time-select" <?php disabled( $closed ); ?>>
                                <option value="">--</option>
                                <?php self::render_time_options( $close ); ?>
                            </select>
                        </td>
                        <td style="padding-top:18px;">
                            <label>
                                <input type="checkbox" name="ai_weekly_hours[<?php echo $day; ?>][closed]" value="1"
                                       <?php checked( $closed ); ?> class="ai-day-closed" />
                                Closed
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Booking Slot Interval (Granularity)</h3>
            <p class="description">How finely customers can book appointments.</p>
            <select name="ai_slot_interval" style="width:200px;">
                <option value="15" <?php selected( $slot_interval, 15 ); ?>>15 minutes</option>
                <option value="30" <?php selected( $slot_interval, 30 ); ?>>30 minutes</option>
                <option value="60" <?php selected( $slot_interval, 60 ); ?>>60 minutes</option>
            </select>

            <h3>Blocked Dates / Time Off</h3>
            <p class="description">Add periods when this staff member is unavailable (single day, week, month, etc.).</p>

            <div class="repeatable-wrapper">
                <table class="ai-blocked-slots-table">
                    <thead>
                        <tr>
                            <th style="width:40%;">Start Date & Time</th>
                            <th style="width:40%;">End Date & Time</th>
                            <th style="width:20%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="repeater-items">
                        <?php
                        // Template row
                        self::render_blocked_slot_row( array(), 'template', false );

                        // Existing slots
                        if ( ! empty( $blocked_slots ) ) {
                            foreach ( $blocked_slots as $index => $slot ) {
                                self::render_blocked_slot_row( $slot, $index, true );
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <button type="button" class="button ai-add-blocked-slot">Add Time Off</button>
            </div>

            <h3>External Calendar Sync (Coming Soon)</h3>
            <p class="description">Two-way sync with Google Calendar, Calendly, Microsoft 365, or iCal feed.</p>
            <p><em>Full integration in Phase 2.</em></p>

        </div>

        <script>
        jQuery(function($){

            // Disable time selects when "Closed" checked
            $(document).on('change', '.ai-day-closed', function(){
                var $row = $(this).closest('tr');
                var disabled = this.checked;
                $row.find('.ai-time-select').prop('disabled', disabled);
            });

            // Date picker for blocked slots
            function initDatePicker($el) {
                $el.datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    changeMonth: true,
                    changeYear: true
                });
            }

            // Time dropdown (15-min intervals, 24h)
            function initTimeSelect($el, selected = '') {
                $el.html('<option value="">-- Select Time --</option>');
                for (let h = 0; h < 24; h++) {
                    for (let m = 0; m < 60; m += 15) {
                        let time = ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
                        let opt = '<option value="' + time + '" ' + (time === selected ? 'selected' : '') + '>' + time + '</option>';
                        $el.append(opt);
                    }
                }
            }

            // Init existing
            $('.ai-blocked-date').each(function(){ initDatePicker($(this)); });
            $('.ai-blocked-time').each(function(){ initTimeSelect($(this), $(this).data('selected')); });

            // Add new blocked slot
            $(document).on('click', '.ai-add-blocked-slot', function(e){
                e.preventDefault();
                var $tbody = $('.ai-blocked-slots-table tbody.repeater-items');
                var $template = $tbody.find('tr.template-row').first().clone();
                var index = $tbody.find('tr').not('.template-row').length;

                $template.removeClass('template-row').addClass('new-manual');
                $template.find('input, select').prop('disabled', false).val('');
                $template.find('.ai-blocked-date-start').attr('name', 'ai_blocked_slots[' + index + '][date_start]');
                $template.find('.ai-blocked-time-start').attr('name', 'ai_blocked_slots[' + index + '][time_start]');
                $template.find('.ai-blocked-date-end').attr('name', 'ai_blocked_slots[' + index + '][date_end]');
                $template.find('.ai-blocked-time-end').attr('name', 'ai_blocked_slots[' + index + '][time_end]');
                $template.find('.commit-buttons').show();
                $template.find('.committed-buttons').hide();

                $tbody.append($template);

                // Init pickers on new row
                $template.find('.ai-blocked-date-start, .ai-blocked-date-end').each(function(){ initDatePicker($(this)); });
                $template.find('.ai-blocked-time-start, .ai-blocked-time-end').each(function(){ initTimeSelect($(this)); });
            });

            // Save blocked slot
            $(document).on('click', '.ai-commit-blocked', function(e){
                e.preventDefault();
                var $row = $(this).closest('tr');
                var dateStart = $row.find('.ai-blocked-date-start').val();
                var timeStart = $row.find('.ai-blocked-time-start').val();
                var dateEnd = $row.find('.ai-blocked-date-end').val();
                var timeEnd = $row.find('.ai-blocked-time-end').val();

                if (!dateStart || !timeStart || !dateEnd || !timeEnd) {
                    alert('Please fill in all date and time fields.');
                    return;
                }

                var start = dateStart + ' ' + timeStart;
                var end = dateEnd + ' ' + timeEnd;

                if (new Date(end) <= new Date(start)) {
                    alert('End date/time must be after start date/time.');
                    return;
                }

                if (confirm('Save this blocked period?')) {
                    $row.removeClass('new-manual').addClass('committed');
                    $row.find('input, select').prop('disabled', true);
                    $row.find('.commit-buttons').hide();
                    $row.find('.committed-buttons').show();
                }
            });

            // Cancel, Edit, Delete
            $(document).on('click', '.ai-cancel-blocked', function(e){
                e.preventDefault();
                $(this).closest('tr').remove();
            });

            $(document).on('click', '.ai-edit-blocked', function(e){
                e.preventDefault();
                var $row = $(this).closest('tr');
                $row.removeClass('committed');
                $row.find('input, select').prop('disabled', false);
                $row.find('.commit-buttons').show();
                $row.find('.committed-buttons').hide();
            });

            $(document).on('click', '.ai-remove-blocked', function(e){
                e.preventDefault();
                if (confirm('Delete this blocked period?')) {
                    $(this).closest('tr').remove();
                }
            });
        });
        </script>
        <?php
    }

    private static function render_time_options( $selected = '' ) {
        for ( $h = 0; $h < 24; $h++ ) {
            for ( $m = 0; $m < 60; $m += 15 ) {
                $time = sprintf( '%02d:%02d', $h, $m );
                echo '<option value="' . $time . '" ' . selected( $selected, $time, false ) . '>' . $time . '</option>';
            }
        }
    }

    private static function render_blocked_slot_row( $slot, $index, $committed = false ) {
        $class = $committed ? 'committed' : ( $index === 'template' ? 'template-row' : '' );
        $date_start = $slot['date_start'] ?? '';
        $time_start = $slot['time_start'] ?? '';
        $date_end   = $slot['date_end'] ?? '';
        $time_end   = $slot['time_end'] ?? '';
        ?>
        <tr class="<?php echo esc_attr( $class ); ?>">
            <td>
                <input type="text" name="ai_blocked_slots[<?php echo $index; ?>][date_start]"
                       value="<?php echo esc_attr( $date_start ); ?>" class="ai-blocked-date-start regular-text" placeholder="yyyy-mm-dd" <?php disabled( $committed ); ?> />
                <select name="ai_blocked_slots[<?php echo $index; ?>][time_start]" class="ai-blocked-time-start regular-text" <?php disabled( $committed ); ?> data-selected="<?php echo esc_attr( $time_start ); ?>">
                    <option value="">-- Select Time --</option>
                </select>
            </td>
            <td>
                <input type="text" name="ai_blocked_slots[<?php echo $index; ?>][date_end]"
                       value="<?php echo esc_attr( $date_end ); ?>" class="ai-blocked-date-end regular-text" placeholder="yyyy-mm-dd" <?php disabled( $committed ); ?> />
                <select name="ai_blocked_slots[<?php echo $index; ?>][time_end]" class="ai-blocked-time-end regular-text" <?php disabled( $committed ); ?> data-selected="<?php echo esc_attr( $time_end ); ?>">
                    <option value="">-- Select Time --</option>
                </select>
            </td>
            <td class="actions-cell">
                <span class="commit-buttons" style="<?php echo $committed ? 'display:none;' : ''; ?>">
                    <button type="button" class="button button-primary ai-commit-blocked">Save</button>
                    <button type="button" class="button ai-cancel-blocked">Cancel</button>
                </span>
                <span class="committed-buttons" style="<?php echo $committed ? '' : 'display:none;'; ?>">
                    <button type="button" class="button ai-edit-blocked">Edit</button>
                    <button type="button" class="button ai-remove-blocked">Delete</button>
                </span>
            </td>
        </tr>
        <?php
    }

    public static function render_sync_metabox( $post ) {
        $sync_type   = get_post_meta( $post->ID, '_ai_staff_sync_type', true );
        $sync_config = get_post_meta( $post->ID, '_ai_staff_sync_config', true );
        $config      = is_array( $sync_config ) ? $sync_config : array();
        ?>
        <h3>External Calendar Sync</h3>
        <p class="description">Connect this staff member's calendar for two-way sync (pull busy slots + push bookings).</p>

        <table class="form-table">
            <tr>
                <th><label for="ai_staff_sync_type">Calendar Type</label></th>
                <td>
                    <select name="ai_staff_sync_type" id="ai_staff_sync_type">
                        <option value="">None</option>
                        <option value="google" <?php selected( $sync_type, 'google' ); ?>>Google Calendar</option>
                        <option value="calendly" <?php selected( $sync_type, 'calendly' ); ?>>Calendly</option>
                        <option value="microsoft" <?php selected( $sync_type, 'microsoft' ); ?>>Microsoft 365 / Outlook</option>
                        <option value="ical" <?php selected( $sync_type, 'ical' ); ?>>iCal Feed (read-only)</option>
                    </select>
                </td>
            </tr>

            <tr class="ai-sync-config ai-sync-google" style="display:<?php echo $sync_type === 'google' ? 'table-row' : 'none'; ?>;">
                <th><label for="ai_sync_google_calendar_id">Calendar ID</label></th>
                <td>
                    <input type="text" name="ai_staff_sync_config[google][calendar_id]" value="<?php echo esc_attr( $config['google']['calendar_id'] ?? '' ); ?>" class="regular-text" placeholder="your.email@gmail.com" />
                    <p class="description">The Calendar ID or email address of the calendar to sync.</p>
                </td>
            </tr>

            <tr class="ai-sync-config ai-sync-calendly" style="display:<?php echo $sync_type === 'calendly' ? 'table-row' : 'none'; ?>;">
                <th><label for="ai_sync_calendly_url">Calendly Scheduling URL</label></th>
                <td>
                    <input type="url" name="ai_staff_sync_config[calendly][url]" value="<?php echo esc_url( $config['calendly']['url'] ?? '' ); ?>" class="regular-text" placeholder="https://calendly.com/your-name" />
                    <p class="description">Your personal Calendly scheduling page URL.</p>
                </td>
            </tr>

            <tr class="ai-sync-config ai-sync-microsoft" style="display:<?php echo $sync_type === 'microsoft' ? 'table-row' : 'none'; ?>;">
                <th><label for="ai_sync_microsoft_email">Microsoft Email</label></th>
                <td>
                    <input type="email" name="ai_staff_sync_config[microsoft][email]" value="<?php echo esc_attr( $config['microsoft']['email'] ?? '' ); ?>" class="regular-text" placeholder="your.name@company.com" />
                    <p class="description">Microsoft 365 email address (full Graph API setup in Phase 3).</p>
                </td>
            </tr>

            <tr class="ai-sync-config ai-sync-ical" style="display:<?php echo $sync_type === 'ical' ? 'table-row' : 'none'; ?>;">
                <th><label for="ai_sync_ical_url">iCal Feed URL</label></th>
                <td>
                    <input type="url" name="ai_staff_sync_config[ical][url]" value="<?php echo esc_url( $config['ical']['url'] ?? '' ); ?>" class="regular-text" placeholder="https://example.com/calendar.ics" />
                    <p class="description">Public iCal feed URL (read-only sync).</p>
                </td>
            </tr>
        </table>

        <p><strong>Note:</strong> Full two-way sync will be implemented in Phase 3. For now, this stores the connection details.</p>

        <script>
        jQuery(function($){
            function toggleSyncFields() {
                var type = $('#ai_staff_sync_type').val();
                $('.ai-sync-config').hide();
                if (type) {
                    $('.ai-sync-' + type).show();
                }
            }
            $('#ai_staff_sync_type').on('change', toggleSyncFields);
            toggleSyncFields();
        });
        </script>
        <?php
    }

    public static function save_availability( $post_id ) {
        // Weekly hours
        if ( isset( $_POST['ai_weekly_hours'] ) && is_array( $_POST['ai_weekly_hours'] ) ) {
            $clean = array();
            foreach ( $_POST['ai_weekly_hours'] as $day => $data ) {
                $clean[$day] = array(
                    'open'   => sanitize_text_field( $data['open'] ?? '' ),
                    'close'  => sanitize_text_field( $data['close'] ?? '' ),
                    'closed' => ! empty( $data['closed'] ),
                );
            }
            update_post_meta( $post_id, '_ai_staff_weekly_hours', $clean );
        }

        // Slot interval
        if ( isset( $_POST['ai_slot_interval'] ) ) {
            $interval = in_array( $_POST['ai_slot_interval'], array(15,30,60) ) ? absint( $_POST['ai_slot_interval'] ) : 15;
            update_post_meta( $post_id, '_ai_staff_slot_interval', $interval );
        }

        // Blocked slots
        if ( isset( $_POST['ai_blocked_slots'] ) && is_array( $_POST['ai_blocked_slots'] ) ) {
            $clean = array();
            foreach ( $_POST['ai_blocked_slots'] as $slot ) {
                $date_start = sanitize_text_field( $slot['date_start'] ?? '' );
                $time_start = sanitize_text_field( $slot['time_start'] ?? '' );
                $date_end   = sanitize_text_field( $slot['date_end'] ?? '' );
                $time_end   = sanitize_text_field( $slot['time_end'] ?? '' );

                if ( $date_start && $time_start && $date_end && $time_end ) {
                    $start = $date_start . ' ' . $time_start;
                    $end   = $date_end . ' ' . $time_end;
                    if ( strtotime( $end ) > strtotime( $start ) ) {
                        $clean[] = array(
                            'date_start' => $date_start,
                            'time_start' => $time_start,
                            'date_end'   => $date_end,
                            'time_end'   => $time_end,
                        );
                    }
                }
            }
            update_post_meta( $post_id, '_ai_staff_blocked_slots', $clean );
        } else {
            delete_post_meta( $post_id, '_ai_staff_blocked_slots' );
        }
    }
}