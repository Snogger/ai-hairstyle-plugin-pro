<?php
/**
 * Plugin Name:       AI Hairstyle Try-On Pro
 * Description:       Virtual hairstyle try-on with Gemini AI, custom booking, auto-populated hairstyles from assets.
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPL-2.0+
 * Text Domain:       ai-hairstyle-tryon-pro
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'AI_HAIRSTYLE_VERSION', '1.0.0' );
define( 'AI_HAIRSTYLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_HAIRSTYLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_HAIRSTYLE_TEMP_DIR', wp_upload_dir()['basedir'] . '/ai-temp/' );

// Hard-coded Banana.dev API Key (as requested)
define( 'AI_HAIRSTYLE_BANANA_API_KEY', 'AIzaSyChmhiS8fKJN9Fx2iO0M5xev76GSw7CSAE' );

// Include all modular class files
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-core.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-frontend-js.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-api-gemini.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-prompts.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-custom-booking.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-elementor-widget.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-hairstyles-cpt.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-staff-cpt.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-admin-tabs.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-analytics.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-emails.php';
require_once AI_HAIRSTYLE_PLUGIN_DIR . 'includes/class-security.php';

// Activation hooks: Create temp folder + scan hairstyles from assets
register_activation_hook( __FILE__, array( 'AI_Security', 'create_temp_folder' ) );
register_activation_hook( __FILE__, array( 'AI_Hairstyle_Hairstyles_CPT', 'scan_and_populate_hairstyles' ) );

// Initialize all core classes on plugins_loaded
add_action( 'plugins_loaded', function() {
    new AI_Hairstyle_Core();
    new AI_Hairstyle_Frontend_JS();
    new AI_Hairstyle_Admin_Tabs();
    new AI_Hairstyle_Hairstyles_CPT();   // FIXED: correct name
    new AI_Hairstyle_Staff_CPT();        // FIXED: correct name
    // Other classes initialize inside their constructors or hooks
} );