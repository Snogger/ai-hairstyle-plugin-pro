<?php
/**
 * GDPR: Temp folder in uploads, auto-cleanup.
 */
class AI_Hairstyle_Security {
    public static function create_temp_folder() {
        if ( ! file_exists( AI_HAIRSTYLE_TEMP_DIR ) ) {
            wp_mkdir_p( AI_HAIRSTYLE_TEMP_DIR );
        }
    }

    public static function cleanup_temp_files( $files = array() ) {
        // TODO: Delete after use/download/email
    }
}