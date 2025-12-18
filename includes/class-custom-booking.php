<?php
/**
 * File: includes/class-custom-booking.php
 * 
 * Custom Lightweight Booking System – Phase 3
 * 
 * - Calculates available slots from staff weekly hours + blocked periods
 * - Stores bookings as custom post type 'ai_booking'
 * - Functions for "next available" and slot checking
 * - No external dependencies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Hairstyle_Custom_Booking {

    public function __construct() {
        add_action( 'init', [ $this, 'register_booking_cpt' ] );
    }

    public function register_booking_cpt() {
        register_post_type( 'ai_booking', [
            'labels' => [
                'name' => 'Bookings',
                'singular_name' => 'Booking',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'ai-hairstyle-pro',
            'supports' => [ 'title' ],
        ] );
    }

    /**
     * Get available time slots for a staff member on a specific date
     */
    public static function get_available_slots( $staff_id, $date, $duration = 60 ) {
        $slots = [];
        $availability = get_post_meta( $staff_id, '_ai_staff_availability', true ) ?: [];
        $blocked = get_post_meta( $staff_id, '_ai_staff_blocked_periods', true ) ?: [];
        $interval = (int) get_post_meta( $staff_id, '_ai_staff_slot_interval', true ) ?: 15;

        $day_name = strtolower( date( 'l', strtotime( $date ) ) );
        if ( empty( $availability[ $day_name ]['hours'] ) || ! empty( $availability[ $day_name ]['off'] ) ) {
            return $slots;
        }

        list( $open, $close ) = explode( '-', $availability[ $day_name ]['hours'] );
        $open_time = strtotime( $date . ' ' . trim( $open ) );
        $close_time = strtotime( $date . ' ' . trim( $close ) );

        $current = $open_time;
        while ( $current + ( $duration * 60 ) <= $close_time ) {
            $slot_start = date( 'H:i', $current );
            $slot_end = date( 'H:i', $current + ( $duration * 60 ) );

            // Check blocked periods
            $blocked_today = false;
            foreach ( $blocked as $block ) {
                if ( $block['start_date'] <= $date && $block['end_date'] >= $date ) {
                    if ( $block['start_time'] <= $slot_start && $block['end_time'] >= $slot_end ) {
                        $blocked_today = true;
                        break;
                    }
                }
            }

            // Check existing bookings
            if ( ! $blocked_today && ! self::is_slot_booked( $staff_id, $date, $slot_start, $slot_end ) ) {
                $slots[] = $slot_start . ' - ' . $slot_end;
            }

            $current += $interval * 60;
        }

        return $slots;
    }

    public static function is_slot_booked( $staff_id, $date, $start, $end ) {
        // Query existing bookings – placeholder for full implementation
        return false;
    }

    /**
     * Get next available slot across all staff
     */
    public static function get_next_available( $duration = 60 ) {
        $staff = get_posts( [ 'post_type' => 'ai_staff', 'posts_per_page' => -1 ] );
        $next = null;

        foreach ( $staff as $member ) {
            for ( $i = 0; $i < 30; $i++ ) { // Check next 30 days
                $check_date = date( 'Y-m-d', strtotime( "+$i days" ) );
                $slots = self::get_available_slots( $member->ID, $check_date, $duration );
                if ( ! empty( $slots ) ) {
                    $candidate = $check_date . ' ' . $slots[0];
                    if ( ! $next || strtotime( $candidate ) < strtotime( $next ) ) {
                        $next = $candidate . ' with ' . $member->post_title;
                    }
                    break;
                }
            }
        }

        return $next ?: 'No slots in next 30 days';
    }
}

new AI_Hairstyle_Custom_Booking();