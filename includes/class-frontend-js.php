<?php
/**
 * Class: AI_Hairstyle_Pro_Frontend_JS
 * Enqueues wizard CSS/JS, localises data, adds upload dropzones and basic step navigation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Hairstyle_Pro_Frontend_JS {

    private $has_shortcode = false;

    public function __construct() {
        add_action( 'wp', [ $this, 'check_for_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Check if the current page has our shortcode (runs after content is loaded).
     */
    public function check_for_shortcode() {
        if ( is_singular() ) {
            global $post;
            if ( $post && has_shortcode( $post->post_content, 'ai-hairstyle-tryon-pro' ) ) {
                $this->has_shortcode = true;
            }
        }
    }

    /**
     * Enqueue frontend styles and scripts only if shortcode is present.
     */
    public function enqueue_assets() {
        if ( ! $this->has_shortcode ) {
            return;
        }

        // Wizard CSS – all customizable classes
        wp_enqueue_style(
            'ai-hairstyle-wizard-css',
            AI_HAIRSTYLE_PRO_URL . 'assets/css/wizard.css',
            [],
            AI_HAIRSTYLE_PRO_VERSION
        );

        // Wizard JS
        wp_enqueue_script(
            'ai-hairstyle-wizard-js',
            AI_HAIRSTYLE_PRO_URL . 'assets/js/wizard.js',
            [ 'jquery' ],
            AI_HAIRSTYLE_PRO_VERSION,
            true
        );

        // Localise script with useful data
        wp_localize_script( 'ai-hairstyle-wizard-js', 'aiHairstylePro', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'ai_hairstyle_pro_nonce' ),
            'tempDir'   => AI_HAIRSTYLE_PRO_TEMP_DIR,
            'pluginUrl' => AI_HAIRSTYLE_PRO_URL,
        ] );
    }
}