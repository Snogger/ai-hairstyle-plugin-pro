<?php
/**
 * AI Hairstyle Try-On Pro – Assets Folder Scan & Populate
 * All rescan logic – safe for manual + auto variants
 * Multisite-safe, heavily commented
 */

class AI_Hairstyle_Assets_Scan {

    public static function scan_and_populate_hairstyles() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $base_path = AI_HAIRSTYLE_PLUGIN_DIR . 'assets/references/';
        $genders   = array( 'women' => 'women', 'men' => 'men' ); // Standardized to match AJAX
        $exts      = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );

        foreach ( $genders as $folder => $gender_value ) {
            $gender_path = $base_path . $folder . '/';
            if ( ! is_dir( $gender_path ) ) {
                error_log( "[AI Hairstyle Scan] Folder not found: $gender_path" );
                continue;
            }

            $style_folders = array_filter( glob( $gender_path . '*' ), 'is_dir' );

            foreach ( $style_folders as $style_path ) {
                $slug  = basename( $style_path );
                $title = ucwords( str_replace( array( '-', '_' ), ' ', $slug ) );

                $existing = get_posts( array(
                    'post_type'      => 'hairstyle', // Updated CPT slug
                    'name'           => $slug,
                    'posts_per_page' => 1,
                    'post_status'    => 'any',
                    'fields'         => 'ids',
                ) );

                $post_id = $existing ? $existing[0] : wp_insert_post( array(
                    'post_title'  => $title,
                    'post_name'   => $slug,
                    'post_type'   => 'hairstyle', // Updated
                    'post_status' => 'publish',
                ) );

                if ( ! $post_id || is_wp_error( $post_id ) ) {
                    error_log( "[AI Hairstyle Scan] Failed to create/update post for slug: $slug" );
                    continue;
                }

                update_post_meta( $post_id, 'hairstyle_gender', $gender_value ); // Updated meta key

                // Main gallery
                $main_files = self::glob_images( $style_path, $exts );
                $main_ids   = self::sideload_images( $main_files, $post_id );
                if ( ! empty( $main_ids ) ) {
                    update_post_meta( $post_id, 'main_gallery', $main_ids );
                }

                // Length variants
                $length_path = $style_path . '/length';
                if ( is_dir( $length_path ) ) {
                    $auto_names = array( 'Short', 'Medium', 'Long', 'Extra-Long' );
                    $existing   = get_post_meta( $post_id, 'length_variants', true ) ?: array();
                    $manual = array_filter( $existing, function($v) use ($auto_names) {
                        return ! in_array( ucwords( $v['name'] ?? '' ), $auto_names );
                    } );

                    $new = array();
                    foreach ( array( 'short', 'medium', 'long', 'extra-long' ) as $len ) {
                        $len_path = $length_path . '/' . $len;
                        if ( ! is_dir( $len_path ) ) continue;
                        $files = self::glob_images( $len_path, $exts );
                        $ids   = self::sideload_images( $files, $post_id );
                        if ( ! empty( $ids ) ) {
                            $new[] = array(
                                'name'    => ucwords( str_replace( '-', ' ', $len ) ),
                                'seo'     => '',
                                'gallery' => $ids
                            );
                        }
                    }
                    update_post_meta( $post_id, 'length_variants', array_merge( $new, $manual ) );
                }

                // Merge variants
                $merge_path = $style_path . '/merge';
                if ( is_dir( $merge_path ) ) {
                    $existing   = get_post_meta( $post_id, 'merge_variants', true ) ?: array();
                    $auto_names = array_map( function($sub) {
                        return ucwords( str_replace( array('-','_'), ' ', basename( $sub ) ) );
                    }, array_filter( glob( $merge_path . '/*' ), 'is_dir' ) );
                    $manual = array_filter( $existing, function($v) use ($auto_names) {
                        return ! in_array( $v['name'] ?? '', $auto_names );
                    } );

                    $new = array();
                    foreach ( array_filter( glob( $merge_path . '/*' ), 'is_dir' ) as $sub ) {
                        $name  = ucwords( str_replace( array( '-', '_' ), ' ', basename( $sub ) ) );
                        $files = self::glob_images( $sub, $exts );
                        $ids   = self::sideload_images( $files, $post_id );
                        if ( ! empty( $ids ) ) {
                            $new[] = array(
                                'name'    => $name,
                                'seo'     => '',
                                'time'    => 0,
                                'gallery' => $ids
                            );
                        }
                    }
                    update_post_meta( $post_id, 'merge_variants', array_merge( $new, $manual ) );
                }
            }
        }

        // Optional: Flush rewrite rules if needed (usually not)
        flush_rewrite_rules();
    }

    private static function glob_images( $path, $exts ) {
        $files = array();
        foreach ( $exts as $ext ) {
            $files = array_merge( $files, glob( $path . '/*.' . $ext ) );
            $files = array_merge( $files, glob( $path . '/*.' . strtoupper( $ext ) ) );
        }
        return $files;
    }

    private static function sideload_images( array $files, $post_id ) {
        $ids = array();
        foreach ( $files as $file_path ) {
            $relative    = str_replace( AI_HAIRSTYLE_PLUGIN_DIR . 'assets/references/', '', $file_path );
            $unique_name = str_replace( '/', '-', $relative );

            $existing = get_posts( array(
                'post_type'      => 'attachment',
                'post_parent'    => $post_id,
                'meta_query'     => array( array( 'key' => '_wp_attached_file', 'value' => $unique_name ) ),
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ) );

            if ( $existing ) {
                $ids[] = $existing[0];
                continue;
            }

            $file_url = AI_HAIRSTYLE_PLUGIN_URL . 'assets/references/' . $relative;
            $tmp      = download_url( $file_url );
            if ( is_wp_error( $tmp ) ) {
                error_log( "[AI Hairstyle Scan] Failed to download: $file_url" );
                continue;
            }

            $file_array = array( 'name' => $unique_name, 'tmp_name' => $tmp );
            $attach_id = media_handle_sideload( $file_array, $post_id );
            if ( is_wp_error( $attach_id ) ) {
                @unlink( $tmp );
                error_log( "[AI Hairstyle Scan] Sideload failed for $file_url: " . $attach_id->get_error_message() );
                continue;
            }
            $ids[] = $attach_id;
        }
        return $ids;
    }
}