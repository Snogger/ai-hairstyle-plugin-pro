<?php
/**
 * Plugin Name: AI Hairstyle Try-On Pro
 * Plugin URI:  https://github.com/Snogger/ai-hairstyle-plugin-pro
 * Description: Standalone AI hairstyle try-on with built-in booking calendar for salons. Multisite compatible, GDPR compliant.
 * Version:     1.0.0
 * Author:      Snogger
 * Author URI:  https://snogger.com
 * License:     GPL-2.0+
 * Text Domain: ai-hairstyle-tryon-pro
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define constants
define( 'AI_HAIRSTYLE_PRO_VERSION', '1.0.0' );
define( 'AI_HAIRSTYLE_PRO_FILE', __FILE__ );
define( 'AI_HAIRSTYLE_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_HAIRSTYLE_PRO_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_HAIRSTYLE_PRO_TEMP_DIR', WP_CONTENT_DIR . '/uploads/ai-temp/' );
define( 'AI_HAIRSTYLE_PRO_ASSETS_DIR', AI_HAIRSTYLE_PRO_DIR . 'assets/references/' );

// Ensure temp directory exists on load
if ( ! file_exists( AI_HAIRSTYLE_PRO_TEMP_DIR ) ) {
    wp_mkdir_p( AI_HAIRSTYLE_PRO_TEMP_DIR );
}

/**
 * Autoload classes using our exact naming convention:
 * Class AI_Hairstyle_Pro_Core           → includes/class-core.php
 * Class AI_Hairstyle_Pro_Frontend_JS    → includes/class-frontend-js.php
 * Class AI_Hairstyle_Pro_Hairstyles_CPT → includes/class-hairstyles-cpt.php
 * etc.
 */
spl_autoload_register( function( $class ) {
    $prefix   = 'AI_Hairstyle_Pro_';
    $base_dir = AI_HAIRSTYLE_PRO_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file           = $base_dir . 'class-' . str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Initialise the plugin – instantiate classes that exist.
 */
function ai_hairstyle_pro_init() {
    // Core (wizard HTML container + shortcode)
    if ( class_exists( 'AI_Hairstyle_Pro_Core' ) ) {
        new AI_Hairstyle_Pro_Core();
    }

    // Frontend assets + upload/step logic
    if ( class_exists( 'AI_Hairstyle_Pro_Frontend_JS' ) ) {
        new AI_Hairstyle_Pro_Frontend_JS();
    }

    // Future classes will be added here one by one as we create them
}
add_action( 'plugins_loaded', 'ai_hairstyle_pro_init' );

/**
 * Activation hook
 */
function ai_hairstyle_pro_activate() {
    if ( ! file_exists( AI_HAIRSTYLE_PRO_TEMP_DIR ) ) {
        wp_mkdir_p( AI_HAIRSTYLE_PRO_TEMP_DIR );
    }

    if ( ! wp_next_scheduled( 'ai_hairstyle_pro_cleanup_temp' ) ) {
        wp_schedule_event( time(), 'daily', 'ai_hairstyle_pro_cleanup_temp' );
    }

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ai_hairstyle_pro_activate' );

/**
 * Deactivation hook
 */
function ai_hairstyle_pro_deactivate() {
    wp_clear_scheduled_hook( 'ai_hairstyle_pro_cleanup_temp' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ai_hairstyle_pro_deactivate' );