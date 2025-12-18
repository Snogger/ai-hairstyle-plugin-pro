<?php
/**
 * AI Hairstyle Try-On Pro – Core Plugin Class
 * Handles shortcode rendering, wizard HTML structure, and AJAX handlers
 * Multisite-safe, heavily commented
 */
class AI_Hairstyle_Core {

    public function __construct() {
        // Register shortcode
        add_shortcode('ai-hairstyle-tryon-pro', array($this, 'render_wizard'));

        // Register AJAX handlers for hairstyle loading
        add_action('wp_ajax_load_hairstyles', array($this, 'load_hairstyles_callback'));
        add_action('wp_ajax_nopriv_load_hairstyles', array($this, 'load_hairstyles_callback'));
    }

    /**
     * Render the frontend wizard via shortcode [ai-hairstyle-tryon-pro]
     */
    public function render_wizard($atts) {
        ob_start();
        ?>
        <div id="ai-hairstyle-tryon-pro" class="ai-hairstyle-wizard">

            <!-- Step 1: Upload Photos & Select Hairstyle -->
            <div id="step-1" class="wizard-step active">
                <h2>Step 1: Upload Your Photos & Choose a Style</h2>

                <div class="step-1-layout">
                    <!-- Left: Hairstyles List -->
                    <div class="hairstyles-column">
                        <h3 id="hairstyles-title">Women's Hairstyles</h3>
                        <div id="hairstyle-grid" class="hairstyles-grid">
                            <p class="loading">Loading styles...</p>
                        </div>
                    </div>

                    <!-- Center: Upload Container -->
                    <div class="upload-column">
                        <div id="upload-container" class="upload-container">
                            <div class="upload-placeholder">
                                <p>Upload 1-4 photos (front, side, back)</p>
                                <div class="upload-buttons">
                                    <button type="button" id="main-upload-btn" class="upload-btn">↑ Upload Image</button>
                                    <button type="button" id="camera-btn" class="upload-btn camera">Take a Photo</button>
                                </div>
                                <p class="upload-note">.png, .jpg, .jpeg, .webp</p>
                            </div>
                            <div id="uploaded-images" class="uploaded-images-grid"></div>
                        </div>
                        <div class="upload-actions">
                            <button type="button" id="secondary-upload-btn" class="upload-btn secondary hidden">Upload More Images</button>
                            <button type="button" id="camera-btn-secondary" class="upload-btn camera hidden">Take Another Photo</button>
                        </div>
                    </div>
                </div>

                <!-- Book Now Button -->
                <div class="wizard-actions">
                    <button type="button" id="book-now-btn" class="book-now-btn" disabled>Book Now →</button>
                </div>
            </div>

            <!-- Future steps will go here -->
        </div>

        <!-- Hidden input for selected hairstyle ID -->
        <input type="hidden" id="selected_hairstyle_id" name="selected_hairstyle_id" value="">

        <?php
        // Safety net: Force enqueue and localize if not already done by class-frontend-js.php
        if ( ! wp_script_is( 'ai-hairstyle-wizard', 'enqueued' ) ) {
            wp_enqueue_script(
                'ai-hairstyle-wizard',
                AI_HAIRSTYLE_PLUGIN_URL . 'assets/js/wizard.js',
                array( 'jquery' ),
                AI_HAIRSTYLE_VERSION,
                true
            );

            wp_localize_script(
                'ai-hairstyle-wizard',
                'aiHairstyleData',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'ai_hairstyle_nonce' ),
                )
            );
        }

        return ob_get_clean();
    }

    /**
     * AJAX callback: Load hairstyles by gender
     */
    public function load_hairstyles_callback() {
        // Security check
        check_ajax_referer('ai_hairstyle_nonce', 'nonce');

        // Get gender from request (default to 'women')
        $gender = sanitize_text_field($_POST['gender'] ?? 'women');

        // Prepare query
        $args = array(
            'post_type'      => 'hairstyle', // Matches CPT slug from spec
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => 'hairstyle_gender',
                    'value'   => $gender,
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query($args);
        $hairstyles = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                $hairstyles[] = array(
                    'id'    => get_the_ID(),
                    'title' => get_the_title(),
                    'thumb' => $thumb ? $thumb : 'https://via.placeholder.com/150?text=' . urlencode(get_the_title()),
                );
            }
            wp_reset_postdata();
        }

        // Send response
        wp_send_json_success(array('hairstyles' => $hairstyles));
    }
}

// Instantiate the class (if not already done in main plugin file)
new AI_Hairstyle_Core();