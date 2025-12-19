<?php
/**
 * Class: AI_Hairstyle_Pro_Core
 * Handles shortcode registration and outputs the wizard container HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Hairstyle_Pro_Core {

    public function __construct() {
        add_shortcode( 'ai-hairstyle-tryon-pro', [ $this, 'render_wizard' ] );
    }

    /**
     * Render the main wizard container.
     */
    public function render_wizard( $atts ) {
        $atts = shortcode_atts( [], $atts, 'ai-hairstyle-tryon-pro' );

        ob_start();
        ?>
        <div id="ai-hairstyle-wizard" class="ai-hairstyle-wizard-container">
            <div class="ai-wizard-progress-bar">
                <div class="ai-progress-fill"></div>
            </div>

            <!-- Step 1: Upload Photos (new single-box design) -->
            <div class="ai-wizard-step ai-step-active" data-step="1">
                <h2 class="ai-step-title"><?php esc_html_e( 'Upload Your Photos', 'ai-hairstyle-tryon-pro' ); ?></h2>
                
                <div class="ai-upload-main-container">
                    <!-- Main preview (shows first uploaded or placeholder) -->
                    <div class="ai-upload-main-box">
                        <div class="ai-upload-placeholder">
                            <p><?php esc_html_e( 'Take Photo or Upload from Gallery', 'ai-hairstyle-tryon-pro' ); ?></p>
                            <div class="ai-upload-buttons">
                                <button type="button" class="ai-btn-camera"><?php esc_html_e( 'Take Photo', 'ai-hairstyle-tryon-pro' ); ?></button>
                                <button type="button" class="ai-btn-gallery"><?php esc_html_e( 'Upload from Gallery', 'ai-hairstyle-tryon-pro' ); ?></button>
                            </div>
                        </div>
                        <div class="ai-main-preview"></div>
                        <button type="button" class="ai-delete-main" aria-label="Delete main image">×</button>
                    </div>

                    <!-- Thumbnails row (hidden until uploads) -->
                    <div class="ai-thumbnails-row"></div>

                    <!-- Additional upload buttons (below thumbnails) -->
                    <div class="ai-additional-buttons">
                        <button type="button" class="ai-btn-camera-small"><?php esc_html_e( 'Take a new photo', 'ai-hairstyle-tryon-pro' ); ?></button>
                        <button type="button" class="ai-btn-gallery-small"><?php esc_html_e( 'Upload new photo', 'ai-hairstyle-tryon-pro' ); ?></button>
                    </div>
                </div>

                <div class="ai-wizard-navigation">
                    <button class="ai-btn-next ai-btn-primary" disabled><?php esc_html_e( 'Next', 'ai-hairstyle-tryon-pro' ); ?></button>
                </div>
            </div>

            <!-- Placeholder steps -->
            <div class="ai-wizard-step" data-step="2">
                <h2 class="ai-step-title"><?php esc_html_e( 'Choose Your Style', 'ai-hairstyle-tryon-pro' ); ?></h2>
                <div class="ai-hairstyle-grid"></div>
            </div>

            <div class="ai-wizard-step" data-step="3">
                <h2 class="ai-step-title"><?php esc_html_e( 'Generating...', 'ai-hairstyle-tryon-pro' ); ?></h2>
                <div class="ai-spinner"></div>
            </div>

            <div class="ai-wizard-step" data-step="4">
                <h2 class="ai-step-title"><?php esc_html_e( 'Your New Look', 'ai-hairstyle-tryon-pro' ); ?></h2>
                <div class="ai-results-gallery"></div>
            </div>

            <div class="ai-wizard-step" data-step="5">
                <h2 class="ai-step-title"><?php esc_html_e( 'Book Appointment', 'ai-hairstyle-tryon-pro' ); ?></h2>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}