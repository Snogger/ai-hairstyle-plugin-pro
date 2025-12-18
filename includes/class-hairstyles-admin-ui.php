<?php
/**
 * AI Hairstyle – Admin UI Rendering
 * All metabox HTML output and repeater templates
 */

class AI_Hairstyle_Admin_UI {

    public static function render_main_gallery_metabox( $post ) {
        $gallery_ids = get_post_meta( $post->ID, 'main_gallery', true );
        $ids_string  = is_array( $gallery_ids ) ? implode( ',', $gallery_ids ) : '';
        ?>
        <p><strong>Main Gallery Images (Auto-Populated but Fully Editable)</strong></p>
        <div id="main-gallery-preview">
            <?php
            if ( ! empty( $gallery_ids ) && is_array( $gallery_ids ) ) {
                foreach ( $gallery_ids as $id ) {
                    echo wp_get_attachment_image( $id, 'medium', false, array(
                        'style' => 'width:175px;height:175px;object-fit:cover;border-radius:10px;margin:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);'
                    ) );
                }
            }
            ?>
        </div>
        <button type="button" class="button ai-edit-main-gallery">Add / Edit Images</button>
        <input type="hidden" id="main_gallery_ids" name="main_gallery" value="<?php echo esc_attr( $ids_string ); ?>" />
        <?php
    }

    public static function render_length_variants_metabox( $post ) {
        $variants = get_post_meta( $post->ID, 'length_variants', true ) ?: array();
        self::render_variant_repeater( $variants, 'length_variants', false );
    }

    public static function render_merge_variants_metabox( $post ) {
        $variants = get_post_meta( $post->ID, 'merge_variants', true ) ?: array();
        self::render_variant_repeater( $variants, 'merge_variants', true );
    }

    private static function render_variant_repeater( array $items, $field, $has_time = false ) {
        ?>
        <div class="repeatable-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:20%;">Name</th>
                        <th style="width:30%;">SEO Names</th>
                        <?php if ( $has_time ) : ?><th style="width:12%;">Est. Time (min)</th><?php endif; ?>
                        <th style="width:25%;">Images</th>
                        <th style="width:<?php echo $has_time ? '13%' : '25%'; ?>;">Actions</th>
                    </tr>
                </thead>
                <tbody class="repeater-items">
                    <?php
                    self::render_variant_row( array(), $field, 9999, $has_time, false ); // template row
                    foreach ( $items as $i => $item ) {
                        self::render_variant_row( $item, $field, $i, $has_time, true );
                    }
                    ?>
                </tbody>
            </table>
            <button type="button" class="button ai-add-row">Add Variant</button>
        </div>
        <?php
    }

    private static function render_variant_row( array $item, $field, $index, $has_time, $committed = false ) {
        $class   = $committed ? 'committed' : ( $index === 9999 ? 'template-row' : '' );
        $gallery = $item['gallery'] ?? array();
        ?>
        <tr class="<?php echo esc_attr( $class ); ?>">
            <td style="text-align:center;vertical-align:middle;">
                <input type="text" name="<?php echo esc_attr( $field ); ?>[<?php echo $index; ?>][name]"
                       value="<?php echo esc_attr( $item['name'] ?? '' ); ?>" class="regular-text" <?php disabled( $committed ); ?> />
            </td>
            <td style="text-align:center;vertical-align:middle;">
                <textarea name="<?php echo esc_attr( $field ); ?>[<?php echo $index; ?>][seo]" rows="2"><?php echo esc_textarea( $item['seo'] ?? '' ); ?></textarea>
            </td>
            <?php if ( $has_time ) : ?>
            <td style="text-align:center;vertical-align:middle;">
                <input type="number" name="<?php echo esc_attr( $field ); ?>[<?php echo $index; ?>][time]"
                       value="<?php echo esc_attr( $item['time'] ?? '' ); ?>" min="0" style="width:80px;" <?php disabled( $committed ); ?> />
            </td>
            <?php endif; ?>
            <td style="text-align:center;vertical-align:middle;">
                <div class="variant-thumb-preview">
                    <?php foreach ( (array)$gallery as $att_id ) {
                        echo wp_get_attachment_image( $att_id, array(100,100), false, array(
                            'style' => 'width:100px;height:100px;object-fit:cover;border-radius:8px;margin:6px;box-shadow:0 2px 6px rgba(0,0,0,0.1);'
                        ) );
                    } ?>
                </div>
                <button type="button" class="button ai-upload-variant-images" <?php disabled( $committed ); ?>>Add Images</button>
                <input type="hidden" class="gallery-ids" name="<?php echo esc_attr( $field ); ?>[<?php echo $index; ?>][gallery]"
                       value="<?php echo esc_attr( implode( ',', (array)$gallery ) ); ?>" />
            </td>
            <td class="actions-cell" style="text-align:center;vertical-align:middle;">
                <span class="commit-buttons" style="<?php echo $committed ? 'display:none;' : ''; ?>">
                    <button type="button" class="button button-primary ai-commit-row">Save</button>
                    <button type="button" class="button ai-cancel-row">Cancel</button>
                </span>
                <span class="committed-buttons" style="<?php echo $committed ? '' : 'display:none;'; ?>">
                    <button type="button" class="button ai-edit-row">Edit</button>
                    <button type="button" class="button ai-clone-row">Clone</button>
                    <button type="button" class="button ai-remove-row">Delete</button>
                </span>
            </td>
        </tr>
        <?php
    }

    public static function render_products_metabox( $post ) {
        $products = get_post_meta( $post->ID, '_ai_products', true ) ?: array();
        $symbol   = get_option( 'ai_currency', '£' );
        ?>
        <div class="repeatable-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price (<?php echo esc_html( $symbol ); ?>)</th>
                        <th>Image</th>
                        <th>Link</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="repeater-items">
                    <?php
                    self::render_product_row( array(), 9999, false );
                    foreach ( $products as $i => $p ) {
                        self::render_product_row( $p, $i, true );
                    }
                    ?>
                </tbody>
            </table>
            <button type="button" class="button ai-add-row">Add Product</button>
        </div>
        <?php
    }

    private static function render_product_row( array $p, $index, $committed = false ) {
        $class = $committed ? 'committed' : ( $index === 9999 ? 'template-row' : '' );
        ?>
        <tr class="<?php echo esc_attr( $class ); ?>">
            <td style="text-align:center;vertical-align:middle;">
                <input type="text" name="_ai_products[<?php echo $index; ?>][name]"
                       value="<?php echo esc_attr( $p['name'] ?? '' ); ?>" class="regular-text" <?php disabled( $committed ); ?> />
            </td>
            <td style="text-align:center;vertical-align:middle;">
                <input type="number" name="_ai_products[<?php echo $index; ?>][price]"
                       value="<?php echo esc_attr( $p['price'] ?? '' ); ?>" step="0.01" <?php disabled( $committed ); ?> />
            </td>
            <td style="text-align:center;vertical-align:middle;">
                <?php if ( ! empty( $p['image_id'] ) ) {
                    echo wp_get_attachment_image( $p['image_id'], array(100,100), false, array(
                        'class' => 'upsell-image',
                        'style' => 'width:100px;height:100px;object-fit:cover;border-radius:8px;margin:6px;box-shadow:0 2px 6px rgba(0,0,0,0.1);'
                    ) );
                } ?>
                <button type="button" class="button ai-upload-single-image" <?php disabled( $committed ); ?>>Upload Image</button>
                <input type="hidden" class="image-id-field" name="_ai_products[<?php echo $index; ?>][image_id]"
                       value="<?php echo esc_attr( $p['image_id'] ?? '' ); ?>" />
            </td>
            <td style="text-align:center;vertical-align:middle;">
                <input type="url" name="_ai_products[<?php echo $index; ?>][link]"
                       value="<?php echo esc_url( $p['link'] ?? '' ); ?>" class="regular-text" <?php disabled( $committed ); ?> />
            </td>
            <td style="text-align:center;vertical-align:middle;">
                <textarea name="_ai_products[<?php echo $index; ?>][desc]" rows="3"><?php echo esc_textarea( $p['desc'] ?? '' ); ?></textarea>
            </td>
            <td class="actions-cell" style="text-align:center;vertical-align:middle;">
                <span class="commit-buttons" style="<?php echo $committed ? 'display:none;' : ''; ?>">
                    <button type="button" class="button button-primary ai-commit-row">Save</button>
                    <button type="button" class="button ai-cancel-row">Cancel</button>
                </span>
                <span class="committed-buttons" style="<?php echo $committed ? '' : 'display:none;'; ?>">
                    <button type="button" class="button ai-edit-row">Edit</button>
                    <button type="button" class="button ai-clone-row">Clone</button>
                    <button type="button" class="button ai-remove-row">Delete</button>
                </span>
            </td>
        </tr>
        <?php
    }

    public static function render_styling_metabox( $post ) {
        $time       = get_post_meta( $post->ID, '_ai_styling_time', true );
        $difficulty = get_post_meta( $post->ID, '_ai_difficulty', true );
        ?>
        <div style="display:flex;gap:40px;align-items:center;flex-wrap:wrap;">
            <div>
                <strong>Estimated Time (minutes):</strong><br>
                <input type="number" name="_ai_styling_time" value="<?php echo esc_attr( $time ); ?>" min="0" style="width:120px;margin-top:6px;" />
            </div>
            <div>
                <strong>Difficulty Level:</strong><br>
                <select name="_ai_difficulty" style="margin-top:6px;">
                    <option value="">None</option>
                    <option value="easy" <?php selected( $difficulty, 'easy' ); ?>>Easy</option>
                    <option value="medium" <?php selected( $difficulty, 'medium' ); ?>>Medium</option>
                    <option value="hard" <?php selected( $difficulty, 'hard' ); ?>>Hard</option>
                    <option value="expert" <?php selected( $difficulty, 'expert' ); ?>>Expert</option>
                    <option value="master" <?php selected( $difficulty, 'master' ); ?>>Master</option>
                </select>
            </div>
        </div>
        <?php
    }
}