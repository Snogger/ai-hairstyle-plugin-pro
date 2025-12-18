<?php
/**
 * AI Hairstyle Try-On Pro – Frontend Assets
 * Enqueues JS/CSS only on pages with the shortcode
 * Localizes consistent AJAX data object: aiHairstyleData
 */
class AI_Hairstyle_Frontend_JS {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        global $post;

        // Load only if shortcode exists on the page
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ai-hairstyle-tryon-pro' ) ) {

            // Enqueue CSS
            wp_enqueue_style(
                'ai-hairstyle-wizard-css',
                AI_HAIRSTYLE_PLUGIN_URL . 'assets/css/wizard.css',
                array(),
                AI_HAIRSTYLE_VERSION
            );

            // Enqueue JS with correct handle
            wp_enqueue_script(
                'ai-hairstyle-wizard',
                AI_HAIRSTYLE_PLUGIN_URL . 'assets/js/wizard.js',
                array( 'jquery' ),
                AI_HAIRSTYLE_VERSION,
                true
            );

            // Localize the EXACT object name used in wizard.js
            wp_localize_script(
                'ai-hairstyle-wizard',
                'aiHairstyleData',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'ai_hairstyle_nonce' ),
                )
            );
        }
    }
}