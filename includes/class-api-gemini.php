<?php
/**
 * Handles all Google Gemini API interactions.
 * Multi-angle generation, retries, error handling.
 */
class AI_Hairstyle_API_Gemini {
    private $api_key;

    public function __construct() {
        $this->api_key = get_option( 'ai_gemini_api_key', '' );
    }

    // Placeholder methods – expand with wp_remote_post to Gemini endpoint
    public function generate_images( $user_images, $reference_images, $color = '' ) {
        // TODO: 4 separate calls, use prompt from class-prompts.php, save temp files
        return array(); // Array of temp image URLs
    }
}