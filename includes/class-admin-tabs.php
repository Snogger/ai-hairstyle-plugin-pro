<?php
/**
 * Admin menu with tabbed interface – Analytics, Configuration, Hairstyles, Staff only.
 * Bookings CPT is NOT included in this menu.
 */
class AI_Hairstyle_Admin_Tabs {
    private $tabs = array(
        'analytics'     => 'Analytics',
        'configuration' => 'Configuration',
        'hairstyles'    => 'Hairstyles',
        'staff'         => 'Staff',
    );

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_ai_rescan_assets', array( $this, 'ajax_handle_rescan' ) );
    }

    public function add_menu_page() {
        // Top-level menu
        add_menu_page(
            'AI Hairstyle Try-On Pro',
            'AI Hairstyle Pro',
            'manage_options',
            'ai-hairstyle-pro',
            array( $this, 'render_page' ),
            'dashicons-admin-generic',
            80
        );

        // Submenus – only the ones we want
        add_submenu_page(
            'ai-hairstyle-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'ai-hairstyle-pro', // Default tab = analytics
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'ai-hairstyle-pro',
            'Configuration',
            'Configuration',
            'manage_options',
            'ai-hairstyle-pro&tab=configuration',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'ai-hairstyle-pro',
            'Hairstyles',
            'Hairstyles',
            'manage_options',
            'ai-hairstyle-pro&tab=hairstyles',
            array( $this, 'render_page' )
        );

        add_submenu_page(
            'ai-hairstyle-pro',
            'Staff',
            'Staff',
            'manage_options',
            'ai-hairstyle-pro&tab=staff',
            array( $this, 'render_page' )
        );

        // IMPORTANT: Remove Bookings from this menu (if it was added elsewhere)
        // This ensures Bookings only appears under its own CPT menu, not here
        remove_submenu_page( 'ai-hairstyle-pro', 'edit.php?post_type=ai_booking' );
    }

    public function register_settings() {
        register_setting( 'ai_hairstyle_config', 'ai_gemini_api_key' );
        register_setting( 'ai_hairstyle_config', 'ai_primary_email' );
        register_setting( 'ai_hairstyle_config', 'ai_free_generations_limit' );
        register_setting( 'ai_hairstyle_config', 'ai_unlimited_generations' );
        register_setting( 'ai_hairstyle_config', 'ai_gender_options' );
        register_setting( 'ai_hairstyle_config', 'ai_admin_gender_filter' );
        register_setting( 'ai_hairstyle_config', 'ai_currency' );
        register_setting( 'ai_hairstyle_config', 'ai_show_styling_info' );
    }

    public function ajax_handle_rescan() {
        check_ajax_referer( 'ai_rescan_assets' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        AI_Hairstyle_Assets_Scan::scan_and_populate_hairstyles();
        wp_send_json_success( 'Rescan complete! Reloading...' );
    }

    public function render_page() {
        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'analytics';
        ?>
        <div class="wrap ai-admin-wrap">
            <h1>AI Hairstyle Try-On Pro</h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $this->tabs as $tab_key => $tab_name ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-hairstyle-pro&tab=' . $tab_key ) ); ?>"
                       class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_name ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="ai-tab-content">
                <?php
                if ( 'analytics' === $current_tab ) $this->render_analytics_tab();
                elseif ( 'configuration' === $current_tab ) $this->render_configuration_tab();
                elseif ( 'hairstyles' === $current_tab ) $this->render_hairstyles_tab();
                elseif ( 'staff' === $current_tab ) $this->render_staff_tab();
                ?>
            </div>
        </div>
        <?php
    }

    private function render_analytics_tab() {
        echo '<h2>Analytics Overview</h2>';
        echo '<p>Generations: <strong>0</strong> | Bookings: <strong>0</strong> | API Calls: <strong>0</strong></p>';
        echo '<p>Charts coming soon...</p>';
    }

    private function render_configuration_tab() {
        settings_errors( 'ai_messages' );
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'ai_hairstyle_config' ); do_settings_sections( 'ai_hairstyle_config' ); ?>

            <table class="form-table">
                <!-- Add your existing fields here (Gemini key, email, etc.) -->

                <tr>
                    <th>Currency Symbol</th>
                    <td>
                        <select name="ai_currency">
                            <option value="£" <?php selected( get_option('ai_currency', '£'), '£' ); ?>>£ GBP (UK)</option>
                            <option value="$" <?php selected( get_option('ai_currency'), '$' ); ?>>$ USD / AUD</option>
                            <option value="€" <?php selected( get_option('ai_currency'), '€' ); ?>>€ EUR</option>
                        </select>
                        <p class="description">Displayed before prices in admin and frontend.</p>
                    </td>
                </tr>

                <tr>
                    <th>Frontend Gender Options</th>
                    <td>
                        <select name="ai_gender_options">
                            <option value="both" <?php selected( get_option('ai_gender_options', 'both'), 'both' ); ?>>Both</option>
                            <option value="female" <?php selected( get_option('ai_gender_options'), 'female' ); ?>>Female only</option>
                            <option value="male" <?php selected( get_option('ai_gender_options'), 'male' ); ?>>Male only</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th>Hairstyles Tab – Gender Buttons</th>
                    <td>
                        <select name="ai_admin_gender_filter">
                            <option value="both" <?php selected( get_option('ai_admin_gender_filter', 'both'), 'both' ); ?>>Show Both (Men + Women buttons)</option>
                            <option value="male" <?php selected( get_option('ai_admin_gender_filter'), 'male' ); ?>>Men only</option>
                            <option value="female" <?php selected( get_option('ai_admin_gender_filter'), 'female' ); ?>>Women only</option>
                        </select>
                        <p class="description">Controls which gender buttons appear in Hairstyles tab.</p>
                    </td>
                </tr>

                <tr>
                    <th>Show Styling Time & Difficulty on Frontend</th>
                    <td>
                        <input type="checkbox" name="ai_show_styling_info" value="1" <?php checked( get_option('ai_show_styling_info'), 1 ); ?> />
                        <p class="description">Optional – displays styling info under hairstyle on frontend.</p>
                    </td>
                </tr>

                <tr>
                    <th>Rescan Assets Folder</th>
                    <td>
                        <button type="button" id="ai-rescan-button" class="button button-primary">Rescan Now</button>
                        <span id="ai-rescan-status" style="margin-left: 15px; font-weight: bold;"></span>
                        <p class="description">Scans assets/references/men/ and women/ and updates hairstyles.</p>
                        <script>
                        jQuery(function($) {
                            $('#ai-rescan-button').on('click', function() {
                                var btn = $(this), status = $('#ai-rescan-status');
                                btn.prop('disabled', true);
                                status.text('Scanning...').css('color', '#000');
                                $.post(ajaxurl, {
                                    action: 'ai_rescan_assets',
                                    _ajax_nonce: '<?php echo wp_create_nonce("ai_rescan_assets"); ?>'
                                }, function(res) {
                                    if (res.success) {
                                        status.html('<span style="color:green;">' + res.data + '</span>');
                                        setTimeout(() => location.reload(), 1500);
                                    } else {
                                        status.html('<span style="color:red;">Error</span>');
                                    }
                                    btn.prop('disabled', false);
                                });
                            });
                        });
                        </script>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_hairstyles_tab() {
        $filter = get_option('ai_admin_gender_filter', 'both');
        echo '<h2>Hairstyles</h2><div style="margin-bottom:20px;">';
        if ( in_array( $filter, ['both', 'male'] ) ) {
            echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=ai_hairstyle&gender=male' ) ) . '" class="button" style="margin-right:10px;">Men</a>';
        }
        if ( in_array( $filter, ['both', 'female'] ) ) {
            echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=ai_hairstyle&gender=female' ) ) . '" class="button">Women</a>';
        }
        echo '</div>';
    }

    private function render_staff_tab() {
        echo '<h2>Staff</h2>';
        echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=ai_staff' ) ) . '" class="button">View All Staff</a>';
    }
}