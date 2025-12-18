<?php
/**
 * AI Hairstyle – Staff External Calendar Sync Handler
 * Phase 2 placeholder – ready for Phase 3 API integration
 */

class AI_Hairstyle_Staff_Sync {

    public static function pull_busy_slots( $staff_id ) {
        // Phase 3: fetch events from external calendar and block in internal grid
    }

    public static function push_booking_event( $booking_data, $staff_id ) {
        // Phase 3: create event in external calendar
    }

    public static function get_sync_config( $staff_id ) {
        $type = get_post_meta( $staff_id, '_ai_staff_sync_type', true );
        $config = get_post_meta( $staff_id, '_ai_staff_sync_config', true );
        return array(
            'type'   => $type,
            'config' => is_array( $config ) ? $config : array(),
        );
    }
}