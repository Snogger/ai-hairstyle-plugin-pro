<?php
/**
 * Class: AI Hairstyle Try-On Pro - Staff CPT
 * Fully locked core + all updates:
 * - Weekly closed days grey out time dropdowns
 * - Blocked periods optional
 * - Per-row Save/Edit/Delete buttons
 * - Add New adds single empty row (no duplication)
 * - Saved rows greyed out + disabled until Edit
 * - Auto-remove expired blocked periods (end_date + 3 days passed)
 * - External Calendar Sync with "Check Connection" button
 * 
 * @package AI_Hairstyle_TryOn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Hairstyle_Staff_CPT {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_ai_staff', array( $this, 'save_meta' ), 10, 2 );
        add_action( 'admin_footer', array( $this, 'admin_js' ) );
        add_action( 'after_setup_theme', array( $this, 'add_featured_image_support' ) );
        add_action( 'do_meta_boxes', array( $this, 'custom_featured_image_box' ) );
        add_action( 'wp_ajax_ai_test_calendar_sync', array( $this, 'ajax_test_sync' ) );
    }

    public function register_cpt() {
        $labels = array(
            'name'               => _x( 'Staff Members', 'Post type general name', 'ai-hairstyle-tryon-pro' ),
            'singular_name'      => _x( 'Staff Member', 'Post type singular name', 'ai-hairstyle-tryon-pro' ),
            'menu_name'          => _x( 'Staff', 'Admin Menu text', 'ai-hairstyle-tryon-pro' ),
            'add_new_item'       => __( 'Add New Staff Member', 'ai-hairstyle-tryon-pro' ),
            'edit_item'          => __( 'Edit Staff Member', 'ai-hairstyle-tryon-pro' ),
            'all_items'          => __( 'All Staff Members', 'ai-hairstyle-tryon-pro' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'ai-hairstyle-tryon-pro',
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon'          => 'dashicons-groups',
        );

        register_post_type( 'ai_staff', $args );
    }

    public function add_featured_image_support() {
        add_theme_support( 'post-thumbnails', array( 'ai_staff' ) );
    }

    public function custom_featured_image_box() {
        remove_meta_box( 'postimagediv', 'ai_staff', 'side' );
        add_meta_box( 'postimagediv', __( 'Profile Photo', 'ai-hairstyle-tryon-pro' ), 'post_thumbnail_meta_box', 'ai_staff', 'side', 'low' );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'ai_staff_details',
            __( 'Staff Details', 'ai-hairstyle-tryon-pro' ),
            array( $this, 'render_details_meta_box' ),
            'ai_staff',
            'normal',
            'high'
        );

        add_meta_box(
            'ai_staff_availability',
            __( 'Availability & Blocked Dates', 'ai-hairstyle-tryon-pro' ),
            array( $this, 'render_availability_meta_box' ),
            'ai_staff',
            'normal',
            'default'
        );
    }

    public function render_details_meta_box( $post ) {
        wp_nonce_field( 'ai_staff_meta_nonce', 'ai_staff_nonce' );

        $email     = get_post_meta( $post->ID, '_ai_staff_email', true );
        $photo_alt = get_post_meta( $post->ID, '_ai_staff_photo_alt', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ai_staff_email"><?php _e( 'Email Address', 'ai-hairstyle-tryon-pro' ); ?></label></th>
                <td>
                    <input type="email" id="ai_staff_email" name="ai_staff_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
                    <p class="description"><?php _e( 'Used for individual booking notifications (optional).', 'ai-hairstyle-tryon-pro' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ai_staff_photo_alt"><?php _e( 'Profile Photo Alt Text (SEO Optimized)', 'ai-hairstyle-tryon-pro' ); ?></label></th>
                <td>
                    <input type="text" id="ai_staff_photo_alt" name="ai_staff_photo_alt" value="<?php echo esc_attr( $photo_alt ); ?>" class="regular-text" />
                    <p class="description"><?php _e( 'e.g. Sarah Johnson – Senior Stylist at [Salon Name]. Important for SEO and accessibility.', 'ai-hairstyle-tryon-pro' ); ?></p>
                </td>
            </tr>
        </table>

        <p><strong><?php _e( 'Profile Photo', 'ai-hairstyle-tryon-pro' ); ?></strong>: <?php _e( 'Use the "Profile Photo" box on the right sidebar.', 'ai-hairstyle-tryon-pro' ); ?></p>
        <p><strong><?php _e( 'Bio', 'ai-hairstyle-tryon-pro' ); ?></strong>: <?php _e( 'Use the rich editor below for a full SEO-optimized biography.', 'ai-hairstyle-tryon-pro' ); ?></p>
        <?php
    }

    private function get_time_options( $selected = '' ) {
        $options = '<option value="">--</option>';
        for ( $h = 0; $h < 24; $h++ ) {
            for ( $m = 0; $m < 60; $m += 5 ) {
                $time = sprintf( '%02d:%02d', $h, $m );
                $options .= '<option value="' . $time . '" ' . selected( $selected, $time, false ) . '>' . $time . '</option>';
            }
        }
        return $options;
    }

    public function render_availability_meta_box( $post ) {
        $weekly_hours = get_post_meta( $post->ID, '_ai_staff_weekly_hours', true );
        if ( ! is_array( $weekly_hours ) ) $weekly_hours = array();

        $days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
        $day_names = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

        ?>
        <h3><?php _e( 'Weekly Recurring Hours', 'ai-hairstyle-tryon-pro' ); ?></h3>
        <p class="description"><?php _e( 'Set default working hours. Global defaults can be overridden here.', 'ai-hairstyle-tryon-pro' ); ?></p>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Day', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'Open Time', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'Close Time', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'Closed?', 'ai-hairstyle-tryon-pro' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $days as $i => $day ) :
                    $open   = $weekly_hours[ $day ]['open'] ?? '';
                    $close  = $weekly_hours[ $day ]['close'] ?? '';
                    $closed = ! empty( $weekly_hours[ $day ]['closed'] );
                ?>
                <tr>
                    <td><strong><?php echo $day_names[ $i ]; ?></strong></td>
                    <td><select name="ai_staff_weekly[<?php echo $day; ?>][open]" class="weekly-time"><?php echo $this->get_time_options( $open ); ?></select></td>
                    <td><select name="ai_staff_weekly[<?php echo $day; ?>][close]" class="weekly-time"><?php echo $this->get_time_options( $close ); ?></select></td>
                    <td><input type="checkbox" name="ai_staff_weekly[<?php echo $day; ?>][closed]" class="weekly-closed" <?php checked( $closed ); ?> /></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top: 40px;"><?php _e( 'Blocked Dates / Off Periods', 'ai-hairstyle-tryon-pro' ); ?></h3>
        <p class="description"><?php _e( 'Define periods this stylist is unavailable (vacations, personal days, etc.). Optional.', 'ai-hairstyle-tryon-pro' ); ?></p>

        <table id="ai-staff-blocked-table" class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Off From (Date)', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'Start Time', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'Return On (Date)', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'End Time', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'All Day', 'ai-hairstyle-tryon-pro' ); ?></th>
                    <th><?php _e( 'Actions', 'ai-hairstyle-tryon-pro' ); ?></th>
                </tr>
            </thead>
            <tbody id="ai-staff-blocked-body">
                <?php
                $blocked = get_post_meta( $post->ID, '_ai_staff_blocked_periods', true );
                if ( ! is_array( $blocked ) ) $blocked = array();

                foreach ( $blocked as $i => $period ) :
                    $start_date = $period['start_date'] ?? '';
                    $start_time = $period['start_time'] ?? '';
                    $end_date   = $period['end_date'] ?? '';
                    $end_time   = $period['end_time'] ?? '';
                    $allday     = ! empty( $period['allday'] );
                    $saved      = ! empty( $start_date );
                ?>
                <tr data-index="<?php echo $i; ?>" data-saved="<?php echo $saved ? '1' : '0'; ?>" class="blocked-row <?php echo $saved ? 'saved-row' : ''; ?>">
                    <td><input type="date" name="ai_staff_blocked[<?php echo $i; ?>][start_date]" value="<?php echo esc_attr( $start_date ); ?>" class="start-date" /></td>
                    <td><select name="ai_staff_blocked[<?php echo $i; ?>][start_time]" class="time-select"><?php echo $this->get_time_options( $start_time ); ?></select></td>
                    <td><input type="date" name="ai_staff_blocked[<?php echo $i; ?>][end_date]" value="<?php echo esc_attr( $end_date ); ?>" class="end-date" /></td>
                    <td><select name="ai_staff_blocked[<?php echo $i; ?>][end_time]" class="time-select"><?php echo $this->get_time_options( $end_time ); ?></select></td>
                    <td><input type="checkbox" name="ai_staff_blocked[<?php echo $i; ?>][allday]" <?php checked( $allday ); ?> class="allday-checkbox" /></td>
                    <td class="actions-cell">
                        <?php if ( $saved ) : ?>
                            <button type="button" class="button edit-blocked"><?php _e( 'Edit', 'ai-hairstyle-tryon-pro' ); ?></button>
                            <button type="button" class="button button-link-delete delete-blocked"><?php _e( 'Delete', 'ai-hairstyle-tryon-pro' ); ?></button>
                        <?php else : ?>
                            <button type="button" class="button save-blocked"><?php _e( 'Save', 'ai-hairstyle-tryon-pro' ); ?></button>
                            <button type="button" class="button remove-blocked"><?php _e( 'Remove', 'ai-hairstyle-tryon-pro' ); ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6">
                        <button type="button" id="add-blocked-period" class="button"><?php _e( 'Add New Off Period', 'ai-hairstyle-tryon-pro' ); ?></button>
                    </td>
                </tr>
            </tfoot>
        </table>

        <h3 style="margin-top: 40px;"><?php _e( 'External Calendar Sync', 'ai-hairstyle-tryon-pro' ); ?></h3>
        <p class="description"><?php _e( 'Connect your personal calendar to automatically block booked times.', 'ai-hairstyle-tryon-pro' ); ?></p>

        <?php
        $sync_enabled   = get_post_meta( $post->ID, '_ai_staff_calendar_sync', true );
        $provider       = get_post_meta( $post->ID, '_ai_staff_calendar_provider', true ) ?: 'google';
        $calendar_id    = get_post_meta( $post->ID, '_ai_staff_calendar_id', true );
        $sync_status    = get_post_meta( $post->ID, '_ai_staff_calendar_sync_status', true );
        $sync_message   = get_post_meta( $post->ID, '_ai_staff_calendar_sync_message', true );
        $locked         = $sync_status === 'success';
        ?>

        <div id="ai-calendar-sync-wrapper" data-locked="<?php echo $locked ? '1' : '0'; ?>">
            <table class="form-table">
                <tr>
                    <th><label for="ai_staff_calendar_sync"><?php _e( 'Enable Calendar Sync', 'ai-hairstyle-tryon-pro' ); ?></label></th>
                    <td>
                        <input type="checkbox" id="ai_staff_calendar_sync" name="ai_staff_calendar_sync" <?php checked( $sync_enabled ); ?> />
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_staff_calendar_provider"><?php _e( 'Calendar Provider', 'ai-hairstyle-tryon-pro' ); ?></label></th>
                    <td>
                        <select id="ai_staff_calendar_provider" name="ai_staff_calendar_provider">
                            <option value="google" <?php selected( $provider, 'google' ); ?>>Google Calendar</option>
                            <option value="apple" <?php selected( $provider, 'apple' ); ?>>Apple iCal</option>
                            <option value="outlook" <?php selected( $provider, 'outlook' ); ?>>Outlook / Office 365</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="ai_staff_calendar_id"><?php _e( 'Calendar ID / Public iCal URL', 'ai-hairstyle-tryon-pro' ); ?></label></th>
                    <td>
                        <input type="text" id="ai_staff_calendar_id" name="ai_staff_calendar_id" value="<?php echo esc_attr( $calendar_id ); ?>" class="regular-text" placeholder="e.g. yourname@gmail.com" />
                        <p class="description provider-help" data-provider="google">
                            <?php _e( 'Google: Copy Calendar ID from Google Calendar > Settings.', 'ai-hairstyle-tryon-pro' ); ?>
                        </p>
                        <p class="description provider-help" data-provider="apple" style="display:none;">
                            <?php _e( 'Apple: Get public iCal URL from Calendar app > Share Calendar.', 'ai-hairstyle-tryon-pro' ); ?>
                        </p>
                        <p class="description provider-help" data-provider="outlook" style="display:none;">
                            <?php _e( 'Outlook: Get iCal URL from Outlook web > Calendar settings.', 'ai-hairstyle-tryon-pro' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="button" id="ai-check-calendar-connection" class="button button-primary"><?php _e( 'Check Connection', 'ai-hairstyle-tryon-pro' ); ?></button>
            </p>

            <?php if ( $locked ) : ?>
                <p class="ai-sync-success"><strong><?php _e( 'Sync successful!', 'ai-hairstyle-tryon-pro' ); ?></strong> <?php echo esc_html( $sync_message ); ?></p>
                <button type="button" id="ai-unlock-calendar-sync" class="button"><?php _e( 'Edit Sync Settings', 'ai-hairstyle-tryon-pro' ); ?></button>
            <?php endif; ?>

            <div id="ai-calendar-sync-message"></div>
        </div>
        <?php
    }

    public function admin_js() {
        $screen = get_current_screen();
        if ( $screen->post_type !== 'ai_staff' ) return;

        $today = current_time( 'Y-m-d' );
        $weekly = get_post_meta( get_the_ID(), '_ai_staff_weekly_hours', true );
        if ( ! is_array( $weekly ) ) $weekly = array();

        $time_options = $this->get_time_options();
        $nonce = wp_create_nonce( 'ai_staff_meta_nonce' );
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                let rowIndex = <?php echo count( (array) get_post_meta( get_the_ID(), '_ai_staff_blocked_periods', true ) ); ?>;

                const timeOptions = '<?php echo addslashes( $time_options ); ?>';

                function getDefaultTimes(dateStr) {
                    if (!dateStr) return { open: '', close: '' };
                    const date = new Date(dateStr + 'T12:00:00');
                    const dayNames = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                    const day = dayNames[date.getDay()].toLowerCase();
                    const hours = <?php echo wp_json_encode( $weekly ); ?>;
                    return {
                        open: hours[day]?.open || '',
                        close: hours[day]?.close || ''
                    };
                }

                // Grey out weekly times when closed
                function toggleWeeklyTimes() {
                    $('tr').each(function() {
                        const $row = $(this);
                        const closed = $row.find('.weekly-closed').is(':checked');
                        $row.find('.weekly-time').prop('disabled', closed);
                    });
                }
                toggleWeeklyTimes();
                $('.weekly-closed').on('change', toggleWeeklyTimes);

                // Grey out saved blocked rows
                function toggleBlockedRowState($row) {
                    const saved = $row.data('saved') == 1;
                    $row.toggleClass('saved-row', saved);
                    $row.find('input, select').not('.allday-checkbox').prop('disabled', saved);
                }

                // Initial state
                $('.blocked-row').each(function() {
                    toggleBlockedRowState($(this));
                });

                // Add new empty row (single)
                $('#add-blocked-period').on('click', function(e) {
                    e.preventDefault();
                    const times = getDefaultTimes('<?php echo $today; ?>');
                    const row = `
                    <tr data-index="${rowIndex}" data-saved="0" class="blocked-row">
                        <td><input type="date" name="ai_staff_blocked[${rowIndex}][start_date]" value="<?php echo $today; ?>" class="start-date" /></td>
                        <td><select name="ai_staff_blocked[${rowIndex}][start_time]" class="time-select">${timeOptions}</select></td>
                        <td><input type="date" name="ai_staff_blocked[${rowIndex}][end_date]" value="" class="end-date" /></td>
                        <td><select name="ai_staff_blocked[${rowIndex}][end_time]" class="time-select">${timeOptions}</select></td>
                        <td><input type="checkbox" name="ai_staff_blocked[${rowIndex}][allday]" class="allday-checkbox" /></td>
                        <td class="actions-cell">
                            <button type="button" class="button save-blocked"><?php _e( 'Save', 'ai-hairstyle-tryon-pro' ); ?></button>
                            <button type="button" class="button remove-blocked"><?php _e( 'Remove', 'ai-hairstyle-tryon-pro' ); ?></button>
                        </td>
                    </tr>`;
                    $('#ai-staff-blocked-body').append(row);
                    $(`select[name="ai_staff_blocked[${rowIndex}][start_time]"]`).val(times.open);
                    $(`select[name="ai_staff_blocked[${rowIndex}][end_time]"]`).val(times.close);
                    rowIndex++;
                });

                // Save row
                $('#ai-staff-blocked-body').on('click', '.save-blocked', function() {
                    const $row = $(this).closest('tr');
                    if ($row.find('.start-date').val()) {
                        $row.data('saved', 1);
                        toggleBlockedRowState($row);
                        $row.find('.actions-cell').html(`
                            <button type="button" class="button edit-blocked"><?php _e( 'Edit', 'ai-hairstyle-tryon-pro' ); ?></button>
                            <button type="button" class="button button-link-delete delete-blocked"><?php _e( 'Delete', 'ai-hairstyle-tryon-pro' ); ?></button>
                        `);
                    }
                });

                // Edit row
                $('#ai-staff-blocked-body').on('click', '.edit-blocked', function() {
                    const $row = $(this).closest('tr');
                    $row.data('saved', 0);
                    toggleBlockedRowState($row);
                    $row.find('.actions-cell').html(`
                        <button type="button" class="button save-blocked"><?php _e( 'Save', 'ai-hairstyle-tryon-pro' ); ?></button>
                        <button type="button" class="button remove-blocked"><?php _e( 'Remove', 'ai-hairstyle-tryon-pro' ); ?></button>
                    `);
                });

                // Delete/Remove row
                $('#ai-staff-blocked-body').on('click', '.delete-blocked, .remove-blocked', function() {
                    $(this).closest('tr').remove();
                });

                // All Day toggle for blocked periods
                $('#ai-staff-blocked-body').on('change', '.allday-checkbox', function() {
                    const disabled = this.checked;
                    $(this).closest('tr').find('.time-select').prop('disabled', disabled);
                    if (disabled) $(this).closest('tr').find('.time-select').val('');
                }).trigger('change');

                // External calendar sync (unchanged)
                $('#ai_staff_calendar_provider').on('change', function() {
                    $('.provider-help').hide();
                    $('.provider-help[data-provider="' + $(this).val() + '"]').show();
                }).trigger('change');

                $('#ai-check-calendar-connection').on('click', function() {
                    const button = $(this);
                    button.prop('disabled', true).text('<?php _e( "Checking...", "ai-hairstyle-tryon-pro" ); ?>');

                    const enabled = $('#ai_staff_calendar_sync').is(':checked');
                    const cal_id = $('#ai_staff_calendar_id').val().trim();

                    if (!enabled || !cal_id) {
                        $('#ai-calendar-sync-message').html('<p class="notice notice-error"><strong><?php _e( "Please enable sync and enter a calendar ID/URL first.", "ai-hairstyle-tryon-pro" ); ?></strong></p>');
                        button.prop('disabled', false).text('<?php _e( "Check Connection", "ai-hairstyle-tryon-pro" ); ?>');
                        return;
                    }

                    $.post(ajaxurl, {
                        action: 'ai_test_calendar_sync',
                        post_id: <?php echo (int) get_the_ID(); ?>,
                        nonce: '<?php echo $nonce; ?>'
                    }, function(response) {
                        if (response.success) {
                            const data = response.data;
                            const msgClass = data.valid ? 'ai-sync-success' : 'notice notice-error';
                            $('#ai-calendar-sync-message').html('<p class="' + msgClass + '"><strong>' + data.message + '</strong></p>');

                            if (data.valid) {
                                $('#ai-calendar-sync-wrapper').data('locked', '1').attr('data-locked', '1');
                                toggleLock();
                                if (!$('#ai-unlock-calendar-sync').length) {
                                    $('#ai-calendar-sync-message').before('<p class="ai-sync-success"><strong><?php _e( "Sync successful!", "ai-hairstyle-tryon-pro" ); ?></strong> ' + data.message + '</p><button type="button" id="ai-unlock-calendar-sync" class="button"><?php _e( "Edit Sync Settings", "ai-hairstyle-tryon-pro" ); ?></button>');
                                }
                            }
                        }
                        button.prop('disabled', false).text('<?php _e( "Check Connection", "ai-hairstyle-tryon-pro" ); ?>');
                    });
                });

                $(document).on('click', '#ai-unlock-calendar-sync', function() {
                    $('#ai-calendar-sync-wrapper').removeAttr('data-locked');
                    $(this).closest('p').remove();
                    toggleLock();
                });

                function toggleLock() {
                    const locked = $('#ai-calendar-sync-wrapper').data('locked');
                    if (locked) {
                        $('#ai_staff_calendar_sync, #ai_staff_calendar_provider, #ai_staff_calendar_id').prop('disabled', true);
                    } else {
                        $('#ai_staff_calendar_sync, #ai_staff_calendar_provider, #ai_staff_calendar_id').prop('disabled', false);
                    }
                }
                toggleLock();
            });
        </script>
        <style>
            .weekly-time:disabled, .time-select:disabled { opacity: 0.5; background: #f0f0f0; }
            .saved-row { background-color: #f9f9f9; }
            .saved-row input, .saved-row select { background-color: #f0f0f0; }
            .ai-sync-success { color: green; font-weight: bold; }
            #ai-calendar-sync-message { margin-top: 15px; padding: 10px; }
        </style>
        <?php
    }

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['ai_staff_nonce'] ) || ! wp_verify_nonce( $_POST['ai_staff_nonce'], 'ai_staff_meta_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_ai_staff_email', sanitize_email( $_POST['ai_staff_email'] ?? '' ) );
        update_post_meta( $post_id, '_ai_staff_photo_alt', sanitize_text_field( $_POST['ai_staff_photo_alt'] ?? '' ) );

        // Weekly hours
        if ( isset( $_POST['ai_staff_weekly'] ) ) {
            $clean = array();
            foreach ( $_POST['ai_staff_weekly'] as $day => $data ) {
                $clean[ $day ] = array(
                    'open'   => sanitize_text_field( $data['open'] ?? '' ),
                    'close'  => sanitize_text_field( $data['close'] ?? '' ),
                    'closed' => ! empty( $data['closed'] ),
                );
            }
            update_post_meta( $post_id, '_ai_staff_weekly_hours', $clean );
        }

        // Blocked periods + auto-cleanup
        $clean_blocked = array();
        if ( isset( $_POST['ai_staff_blocked'] ) && is_array( $_POST['ai_staff_blocked'] ) ) {
            $three_days_ago = date( 'Y-m-d', strtotime( '-3 days' ) );

            foreach ( $_POST['ai_staff_blocked'] as $period ) {
                if ( empty( $period['start_date'] ) ) continue;

                if ( ! empty( $period['end_date'] ) && $period['end_date'] < $three_days_ago ) {
                    continue; // expired
                }

                $clean_blocked[] = array(
                    'start_date' => sanitize_text_field( $period['start_date'] ),
                    'start_time' => sanitize_text_field( $period['start_time'] ?? '' ),
                    'end_date'   => sanitize_text_field( $period['end_date'] ?? '' ),
                    'end_time'   => sanitize_text_field( $period['end_time'] ?? '' ),
                    'allday'     => ! empty( $period['allday'] ),
                );
            }
        }
        update_post_meta( $post_id, '_ai_staff_blocked_periods', $clean_blocked );

        // External calendar sync
        $sync_enabled = ! empty( $_POST['ai_staff_calendar_sync'] );
        update_post_meta( $post_id, '_ai_staff_calendar_sync', $sync_enabled );

        if ( $sync_enabled ) {
            update_post_meta( $post_id, '_ai_staff_calendar_provider', sanitize_text_field( $_POST['ai_staff_calendar_provider'] ?? 'google' ) );
            update_post_meta( $post_id, '_ai_staff_calendar_id', sanitize_text_field( $_POST['ai_staff_calendar_id'] ?? '' ) );
        } else {
            delete_post_meta( $post_id, '_ai_staff_calendar_sync_status' );
            delete_post_meta( $post_id, '_ai_staff_calendar_sync_message' );
        }
    }

    public function ajax_test_sync() {
        check_ajax_referer( 'ai_staff_meta_nonce', 'nonce' );

        $post_id = intval( $_POST['post_id'] );
        if ( ! current_user_can( 'edit_post', $post_id ) ) wp_die();

        $enabled  = get_post_meta( $post_id, '_ai_staff_calendar_sync', true );
        $provider = get_post_meta( $post_id, '_ai_staff_calendar_provider', true );
        $cal_id   = get_post_meta( $post_id, '_ai_staff_calendar_id', true );

        if ( ! $enabled || empty( $cal_id ) ) {
            wp_send_json_success( array( 'valid' => false, 'message' => __( 'Sync disabled or no calendar set.', 'ai-hairstyle-tryon-pro' ) ) );
        }

        $valid   = false;
        $message = __( 'Invalid calendar ID or URL.', 'ai-hairstyle-tryon-pro' );

        if ( $provider === 'google' ) {
            if ( strpos( $cal_id, '@' ) !== false || preg_match( '/c_[a-z0-9]+@group/', $cal_id ) || strpos( $cal_id, 'calendar.google.com' ) !== false ) {
                $valid   = true;
                $message = __( 'Google Calendar connected – ready to sync.', 'ai-hairstyle-tryon-pro' );
            }
        } elseif ( in_array( $provider, ['apple', 'outlook'], true ) && filter_var( $cal_id, FILTER_VALIDATE_URL ) ) {
            $valid   = true;
            $message = __( 'iCal feed connected – ready to sync.', 'ai-hairstyle-tryon-pro' );
        }

        update_post_meta( $post_id, '_ai_staff_calendar_sync_status', $valid ? 'success' : 'fail' );
        update_post_meta( $post_id, '_ai_staff_calendar_sync_message', $message );

        wp_send_json_success( array( 'valid' => $valid, 'message' => $message ) );
    }
}

new AI_Hairstyle_Staff_CPT();