<?php
/**
 * AI Hairstyle Try-On Pro – Hairstyles CPT Core
 * Registers CPT, metaboxes, save logic, gender filter, and manual rescan
 */
require_once __DIR__ . '/class-hairstyles-admin-ui.php';
require_once __DIR__ . '/class-hairstyles-assets-scan.php';
require_once __DIR__ . '/class-hairstyles-admin-assets.php';

class AI_Hairstyle_Hairstyles_CPT {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
        add_action( 'save_post_hairstyle', array( $this, 'save_metaboxes' ) );
        add_action( 'restrict_manage_posts', array( $this, 'gender_filter_dropdown' ) );
        add_filter( 'parse_query', array( $this, 'gender_filter_query' ) );
        add_action( 'admin_init', array( $this, 'handle_manual_rescan' ) );
    }

    public function register_cpt() {
        register_post_type( 'hairstyle', array(
            'labels'       => array(
                'name'          => 'Hairstyles',
                'singular_name' => 'Hairstyle',
                'add_new_item'  => 'Add New Hairstyle',
                'edit_item'     => 'Edit Hairstyle',
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'supports'     => array( 'title', 'editor', 'thumbnail' ), // Added for thumbs
        ) );
    }

    public function add_metaboxes() {
        add_meta_box( 'ai_settings', 'Hairstyle Settings', array( $this, 'settings_metabox' ), 'hairstyle', 'normal', 'high' );
        add_meta_box( 'ai_gallery', 'Main Gallery (Editable)', array( $this, 'gallery_metabox' ), 'hairstyle', 'normal', 'high' );
        add_meta_box( 'ai_length', 'Length Variants', array( $this, 'length_variants_metabox' ), 'hairstyle', 'normal', 'default' );
        add_meta_box( 'ai_merge', 'Merge Variants', array( $this, 'merge_variants_metabox' ), 'hairstyle', 'normal', 'default' );
        add_meta_box( 'ai_products', 'Recommended Products (Upsell)', array( $this, 'products_metabox' ), 'hairstyle', 'normal', 'default' );
        add_meta_box( 'ai_styling', 'Styling Info', array( $this, 'styling_metabox' ), 'hairstyle', 'side', 'default' );
    }

    public function settings_metabox( $post ) {
        wp_nonce_field( 'ai_save', 'ai_nonce' );
        $enabled   = get_post_meta( $post->ID, '_ai_enabled', true );
        $featured  = get_post_meta( $post->ID, '_ai_featured', true );
        $price     = get_post_meta( $post->ID, '_ai_price', true );
        $symbol    = get_option( 'ai_currency', '£' );

        // Assigned stylists
        $assigned  = get_post_meta( $post->ID, '_ai_assigned_staff', true );
        $all_staff = $assigned === 'all';
        $selected  = is_array( $assigned ) ? $assigned : array();

        // Get all published staff (updated CPT slug)
        $staff_posts = get_posts( array(
            'post_type'      => 'staff',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <div style="display:flex;gap:40px;align-items:start;flex-wrap:wrap;">
            <div>
                <strong>Enabled</strong><br>
                <label><input type="checkbox" name="ai_enabled" value="1" <?php checked( $enabled !== '0' ); ?> /> This hairstyle is active</label>
            </div>
            <div>
                <strong>Featured</strong><br>
                <label><input type="checkbox" name="ai_featured" value="1" <?php checked( $featured, '1' ); ?> /> Show as featured</label>
            </div>
            <div>
                <strong>Price</strong><br>
                <input type="number" name="ai_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" style="width:120px;" /> <?php echo esc_html( $symbol ); ?>
            </div>
        </div>

        <hr style="margin:30px 0;border:none;border-top:1px solid #ddd;">

        <div>
            <strong>Assign Stylists</strong><br>
            <label style="display:block;margin:12px 0;">
                <input type="checkbox" name="ai_assigned_staff_all" value="1" <?php checked( $all_staff ); ?> class="ai-assign-all" />
                <strong>All Stylists</strong> (select to assign this hairstyle to every stylist)
            </label>

            <div class="ai-individual-staff" style="<?php echo $all_staff ? 'opacity:0.5;pointer-events:none;' : ''; ?>">
                <?php foreach ( $staff_posts as $staff ) : ?>
                <label style="display:block;margin:6px 0;">
                    <input type="checkbox" name="ai_assigned_staff[]" value="<?php echo esc_attr( $staff->ID ); ?>"
                           <?php checked( in_array( $staff->ID, $selected ) || $all_staff ); ?>
                           <?php disabled( $all_staff ); ?> />
                    <?php echo esc_html( get_the_title( $staff ) ); ?>
                </label>
                <?php endforeach; ?>
                <?php if ( empty( $staff_posts ) ) : ?>
                <p><em>No staff members have been added yet. Create them in the Staff tab first.</em></p>
                <?php endif; ?>
            </div>
        </div>

        <script>
        jQuery(function($){
            $(document).on('change', '.ai-assign-all', function(){
                var checked = this.checked;
                $('.ai-individual-staff').css({
                    'opacity': checked ? '0.5' : '1',
                    'pointer-events': checked ? 'none' : 'auto'
                });
                $('.ai-individual-staff input[type="checkbox"]').prop('checked', checked).prop('disabled', checked);
            });
        });
        </script>
        <?php
    }

    public function gallery_metabox( $post ) {
        AI_Hairstyle_Admin_UI::render_main_gallery_metabox( $post );
    }

    public function length_variants_metabox( $post ) {
        AI_Hairstyle_Admin_UI::render_length_variants_metabox( $post );
    }

    public function merge_variants_metabox( $post ) {
        AI_Hairstyle_Admin_UI::render_merge_variants_metabox( $post );
    }

    public function products_metabox( $post ) {
        AI_Hairstyle_Admin_UI::render_products_metabox( $post );
    }

    public function styling_metabox( $post ) {
        AI_Hairstyle_Admin_UI::render_styling_metabox( $post );
    }

    public function save_metaboxes( $post_id ) {
        if ( ! isset( $_POST['ai_nonce'] ) || ! wp_verify_nonce( $_POST['ai_nonce'], 'ai_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        update_post_meta( $post_id, '_ai_enabled', isset( $_POST['ai_enabled'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_ai_featured', isset( $_POST['ai_featured'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_ai_price', sanitize_text_field( $_POST['ai_price'] ?? '' ) );
        update_post_meta( $post_id, '_ai_styling_time', absint( $_POST['_ai_styling_time'] ?? 0 ) );
        update_post_meta( $post_id, '_ai_difficulty', sanitize_text_field( $_POST['_ai_difficulty'] ?? '' ) );

        // Stylist assignment save logic
        if ( isset( $_POST['ai_assigned_staff_all'] ) && $_POST['ai_assigned_staff_all'] == '1' ) {
            update_post_meta( $post_id, '_ai_assigned_staff', 'all' );
        } elseif ( ! empty( $_POST['ai_assigned_staff'] ) && is_array( $_POST['ai_assigned_staff'] ) ) {
            $clean_staff = array_map( 'absint', $_POST['ai_assigned_staff'] );
            update_post_meta( $post_id, '_ai_assigned_staff', $clean_staff );
        } else {
            update_post_meta( $post_id, '_ai_assigned_staff', array() ); // none selected
        }

        // Main gallery
        if ( isset( $_POST['main_gallery'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $_POST['main_gallery'] ) ) );
            update_post_meta( $post_id, 'main_gallery', $ids );
        }

        // Products
        if ( isset( $_POST['_ai_products'] ) && is_array( $_POST['_ai_products'] ) ) {
            $clean = array();
            foreach ( $_POST['_ai_products'] as $p ) {
                if ( empty( $p['name'] ) ) continue;
                $clean[] = array(
                    'name'     => sanitize_text_field( $p['name'] ),
                    'price'    => sanitize_text_field( $p['price'] ?? '' ),
                    'image_id' => absint( $p['image_id'] ?? 0 ),
                    'link'     => esc_url_raw( $p['link'] ?? '' ),
                    'desc'     => sanitize_textarea_field( $p['desc'] ?? '' ),
                );
            }
            update_post_meta( $post_id, '_ai_products', $clean );
        }

        // Variants (length + merge)
        foreach ( array( 'length_variants' => false, 'merge_variants' => true ) as $field => $has_time ) {
            if ( isset( $_POST[$field] ) && is_array( $_POST[$field] ) ) {
                $clean = array();
                foreach ( $_POST[$field] as $v ) {
                    if ( empty( $v['name'] ) ) continue;
                    $gallery = array_filter( array_map( 'absint', explode( ',', $v['gallery'] ?? '' ) ) );
                    $item = array(
                        'name'    => sanitize_text_field( $v['name'] ),
                        'seo'     => sanitize_textarea_field( $v['seo'] ?? '' ),
                        'gallery' => $gallery,
                    );
                    if ( $has_time ) $item['time'] = absint( $v['time'] ?? 0 );
                    $clean[] = $item;
                }
                update_post_meta( $post_id, $field, $clean );
            }
        }
    }

    public function gender_filter_dropdown( $post_type ) {
        if ( $post_type !== 'hairstyle' ) return;
        $current = isset( $_GET['gender'] ) ? sanitize_text_field( $_GET['gender'] ) : '';
        ?>
        <select name="gender">
            <option value="">All Genders</option>
            <option value="men" <?php selected( $current, 'men' ); ?>>Men</option>
            <option value="women" <?php selected( $current, 'women' ); ?>>Women</option>
        </select>
        <?php
    }

    public function gender_filter_query( $query ) {
        global $pagenow;
        if ( is_admin() && $pagenow === 'edit.php' && $query->get( 'post_type' ) === 'hairstyle' && ! empty( $_GET['gender'] ) ) {
            $meta_query = (array) $query->get( 'meta_query' );
            $meta_query[] = array(
                'key'     => 'hairstyle_gender',
                'value'   => sanitize_text_field( $_GET['gender'] ),
                'compare' => '='
            );
            $query->set( 'meta_query', $meta_query );
        }
    }

    public function handle_manual_rescan() {
        if ( isset( $_GET['rescan_hairstyles'] ) && current_user_can( 'manage_options' ) ) {
            AI_Hairstyle_Assets_Scan::scan_and_populate_hairstyles();
            wp_redirect( remove_query_arg( 'rescan_hairstyles' ) );
            exit;
        }
    }
}

new AI_Hairstyle_Hairstyles_CPT();