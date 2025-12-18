<?php
/**
 * File: includes/class-google-calendar-sync.php
 * 
 * Optional Two-Way Google Calendar Synchronization
 * 
 * - Staff member connects their own Google account via OAuth
 * - Pulls busy events → adds to blocked periods for availability calculation
 * - Pushes new bookings → creates event in staff's primary calendar
 * - Refresh tokens encrypted and stored per-staff (post meta)
 * - Feature globally toggled in Configuration tab
 * - Fully optional – "Coming Soon" until enabled
 * 
 * @package AI Hairstyle Try-On Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Hairstyle_Google_Calendar_Sync {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only load if globally enabled in Configuration
        if ( ! get_option( 'ai_hairstyle_google_sync_enabled', false ) ) {
            return;
        }

        add_action( 'admin_init', [ $this, 'handle_oauth_callback' ] );
        add_action( 'ai_hairstyle_booking_saved', [ $this, 'push_booking_to_google' ], 10, 2 );
        add_action( 'ai_hairstyle_daily_google_sync', [ $this, 'sync_all_staff_busy_times' ] );

        if ( ! wp_next_scheduled( 'ai_hairstyle_daily_google_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'ai_hairstyle_daily_google_sync' );
        }
    }

    /** Generate OAuth URL for a staff member */
    public function get_auth_url( $staff_id ) {
        $client_id = get_option( 'ai_hairstyle_google_client_id' );
        if ( ! $client_id ) {
            return '#';
        }

        $redirect_uri = admin_url( 'admin-ajax.php?action=ai_google_oauth_callback' );

        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => rawurlencode( $redirect_uri ),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce( 'google_sync_' . $staff_id ) . '|' . $staff_id,
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
    }

    /** Handle OAuth callback via admin-ajax (more reliable than query var) */
    public function handle_oauth_callback() {
        if ( ! isset( $_GET['action'] ) || 'ai_google_oauth_callback' !== $_GET['action'] ) {
            return;
        }

        if ( ! isset( $_GET['code'], $_GET['state'] ) ) {
            wp_die( 'Google Calendar sync error: missing code or state.' );
        }

        list( $nonce, $staff_id ) = explode( '|', sanitize_text_field( wp_unslash( $_GET['state'] ) ) );
        if ( ! wp_verify_nonce( $nonce, 'google_sync_' . $staff_id ) ) {
            wp_die( 'Security check failed.' );
        }

        $staff_id = (int) $staff_id;
        if ( get_post_type( $staff_id ) !== 'ai_staff' ) {
            wp_die( 'Invalid staff member.' );
        }

        $client_id     = get_option( 'ai_hairstyle_google_client_id' );
        $client_secret = get_option( 'ai_hairstyle_google_client_secret' );
        $redirect_uri  = admin_url( 'admin-ajax.php?action=ai_google_oauth_callback' );

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ),
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code',
            ],
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            wp_die( 'Failed to retrieve Google tokens.' );
        }

        $tokens = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $tokens['refresh_token'] ) ) {
            wp_die( 'No refresh token received. Try reconnecting.' );
        }

        update_post_meta( $staff_id, '_ai_google_refresh_token', $this->encrypt( $tokens['refresh_token'] ) );
        update_post_meta( $staff_id, '_ai_google_access_token', $this->encrypt( $tokens['access_token'] ) );
        update_post_meta( $staff_id, '_ai_google_token_expires', time() + $tokens['expires_in'] );

        wp_redirect( admin_url( 'post.php?post=' . $staff_id . '&action=edit&message=google_sync_success' ) );
        exit;
    }

    /** Refresh or return valid access token */
    private function get_access_token( $staff_id ) {
        $encrypted_refresh = get_post_meta( $staff_id, '_ai_google_refresh_token', true );
        if ( ! $encrypted_refresh ) {
            return false;
        }

        $refresh_token = $this->decrypt( $encrypted_refresh );

        $client_id     = get_option( 'ai_hairstyle_google_client_id' );
        $client_secret = get_option( 'ai_hairstyle_google_client_secret' );

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token',
            ],
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        update_post_meta( $staff_id, '_ai_google_access_token', $this->encrypt( $data['access_token'] ) );
        update_post_meta( $staff_id, '_ai_google_token_expires', time() + $data['expires_in'] );

        return $data['access_token'];
    }

    /** Improved encryption using openssl + WP salt */
    private function encrypt( $text ) {
        $key = substr( wp_hash( wp_salt( 'auth' ) ), 0, 32 );
        $iv  = random_bytes( 16 );
        $encrypted = openssl_encrypt( $text, 'aes-256-cbc', $key, 0, $iv );
        return base64_encode( $iv . $encrypted );
    }

    private function decrypt( $encrypted ) {
        $data = base64_decode( $encrypted );
        if ( strlen( $data ) < 17 ) {
            return false;
        }
        $iv  = substr( $data, 0, 16 );
        $ciphertext = substr( $data, 16 );
        $key = substr( wp_hash( wp_salt( 'auth' ) ), 0, 32 );
        return openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, 0, $iv );
    }

    /** Pull busy slots and cache */
    public function sync_staff_busy_times( $staff_id ) {
        $access_token = $this->get_access_token( $staff_id );
        if ( ! $access_token ) {
            return;
        }

        $time_min = gmdate( 'Y-m-d\T00:00:00\Z' );
        $time_max = gmdate( 'Y-m-d\T00:00:00\Z', strtotime( '+60 days' ) );

        $response = wp_remote_post( 'https://www.googleapis.com/calendar/v3/freeBusy', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'timeMin' => $time_min,
                'timeMax' => $time_max,
                'items'   => [ [ 'id' => 'primary' ] ],
            ] ),
        ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $busy = $body['calendars']['primary']['busy'] ?? [];

        $blocked = [];
        foreach ( $busy as $slot ) {
            $date = substr( $slot['start'], 0, 10 );
            $blocked[ $date ][] = [
                'start' => date( 'H:i', strtotime( $slot['start'] ) ),
                'end'   => date( 'H:i', strtotime( $slot['end'] ) ),
            ];
        }

        set_transient( 'ai_google_busy_' . $staff_id, $blocked, DAY_IN_SECONDS * 2 );
    }

    public function sync_all_staff_busy_times() {
        $staff = get_posts( [
            'post_type'      => 'ai_staff',
            'posts_per_page' => -1,
            'meta_query'     => [ [ 'key' => '_ai_google_refresh_token', 'compare' => 'EXISTS' ] ],
        ] );

        foreach ( $staff as $post ) {
            $this->sync_staff_busy_times( $post->ID );
        }
    }

    /** Push booking to Google Calendar */
    public function push_booking_to_google( $booking_id, $booking_data ) {
        $staff_id = $booking_data['staff_id'] ?? 0;
        if ( ! $staff_id ) {
            return;
        }

        $access_token = $this->get_access_token( $staff_id );
        if ( ! $access_token ) {
            return;
        }

        $start = $booking_data['date'] . 'T' . $booking_data['time'] . ':00';
        $end   = date( 'Y-m-d\TH:i:00', strtotime( $start . ' +' . ( $booking_data['duration'] ?? 60 ) . ' minutes' ) );

        $event = [
            'summary'     => 'Booking: ' . ( $booking_data['hairstyle'] ?? 'Appointment' ),
            'description' => sprintf(
                "Client: %s\nEmail: %s\nPhone: %s\nNotes: %s",
                $booking_data['client_name'] ?? '',
                $booking_data['client_email'] ?? '',
                $booking_data['client_phone'] ?? '',
                $booking_data['notes'] ?? ''
            ),
            'start'       => [ 'dateTime' => $start, 'timeZone' => wp_timezone_string() ],
            'end'         => [ 'dateTime' => $end, 'timeZone' => wp_timezone_string() ],
        ];

        wp_remote_post( 'https://www.googleapis.com/calendar/v3/calendars/primary/events', [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $event ),
        ] );
    }

    /** Get Google-blocked times for a date (used in availability logic) */
    public function get_google_blocked_times( $staff_id, $date ) {
        $blocked = get_transient( 'ai_google_busy_' . $staff_id );
        return $blocked[ $date ] ?? [];
    }
}

AI_Hairstyle_Google_Calendar_Sync::get_instance();