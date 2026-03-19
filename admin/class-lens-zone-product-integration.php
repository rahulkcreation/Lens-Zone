<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Lens_Zone_Product_Integration {

    public function __construct() {
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );
    }

    public function add_product_data_tab( $tabs ) {
        $tabs['lens_zone'] = array(
            'label'    => __( 'Lens Categories', 'lens-zone' ),
            'target'   => 'lens_zone_product_data',
            'class'    => array( 'show_if_simple', 'show_if_variable' ),
        );
        return $tabs;
    }

    public function add_product_data_panel() {
        global $post, $wpdb;

        echo '<div id="lens_zone_product_data" class="panel woocommerce_options_panel">';

        $lens_categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE status = 'active' ORDER BY name ASC" );
        
        $selected_categories = get_post_meta( $post->ID, '_selected_lens_categories', true );
        if ( ! is_array( $selected_categories ) ) {
            $selected_categories = array();
        }
        
        // Get frame sizes for this product
        $frame_sizes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_frame_sizes WHERE product_id = %d", $post->ID ) );

        ?>
        <div class="options_group">
            <p class="form-field">
                <label><?php _e( 'Select and Order Lens Categories', 'lens-zone' ); ?></label>
                <span class="description" style="display: block; margin-bottom: 10px;">
                    <?php _e( 'Select categories from the left and drag to reorder them on the right.', 'lens-zone' ); ?>
                </span>
            </p>
            
            <div style="display: flex; gap: 20px; padding: 0 20px;">
                <!-- Available Categories -->
                <div style="flex: 1;">
                    <h4><?php _e( 'Available Categories', 'lens-zone' ); ?></h4>
                    <ul id="available-lens-categories" style="border: 1px solid #ddd; padding: 10px; min-height: 200px; background: #f9f9f9; list-style: none; margin: 0;">
                        <?php foreach ( $lens_categories as $category ): ?>
                            <?php if ( ! in_array( $category->id, $selected_categories ) ): ?>
                                <li data-id="<?php echo esc_attr( $category->id ); ?>" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #ddd; cursor: move; border-radius: 4px;">
                                    <span class="dashicons dashicons-menu" style="color: #999;"></span>
                                    <?php echo esc_html( $category->name ); ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Selected Categories (Ordered) -->
                <div style="flex: 1;">
                    <h4><?php _e( 'Selected Categories (Drag to Reorder)', 'lens-zone' ); ?></h4>
                    <ul id="selected-lens-categories" style="border: 1px solid #ddd; padding: 10px; min-height: 200px; background: #f0f9ff; list-style: none; margin: 0;">
                        <?php 
                        // Get category names in order
                        $category_map = array();
                        foreach ( $lens_categories as $category ) {
                            $category_map[ $category->id ] = $category->name;
                        }
                        
                        foreach ( $selected_categories as $cat_id ): 
                            if ( isset( $category_map[ $cat_id ] ) ):
                        ?>
                            <li data-id="<?php echo esc_attr( $cat_id ); ?>" style="padding: 8px; margin-bottom: 5px; background: white; border: 1px solid #1e3a8a; cursor: move; border-radius: 4px;">
                                <span class="dashicons dashicons-menu" style="color: #1e3a8a;"></span>
                                <?php echo esc_html( $category_map[ $cat_id ] ); ?>
                                <span class="dashicons dashicons-no-alt" style="float: right; color: #dc2626; cursor: pointer;" onclick="removeLensCategory(this)"></span>
                            </li>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </ul>
                </div>
            </div>
            
            <!-- Hidden input to store ordered IDs -->
            <input type="hidden" name="_selected_lens_categories" id="_selected_lens_categories_input" value="<?php echo esc_attr( implode( ',', $selected_categories ) ); ?>">
        </div>
        
        <!-- Frame Size Specifications -->
        <div class="options_group lens-zone-frame-specs" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #23282d;"><?php _e( 'Frame Size Specifications', 'lens-zone' ); ?></h3>
                <p class="description" style="margin: 0; color: #666;">
                    <?php _e( 'Add frame size values for this product. Labels and icons are configured globally in Change Details page.', 'lens-zone' ); ?>
                </p>
            </div>
            
            <div id="frame-sizes-container">
                <?php if ( ! empty( $frame_sizes ) ): ?>
                    <?php foreach ( $frame_sizes as $index => $size ): ?>
                        <div class="frame-size-row" style="border: 1px solid #c3c4c7; padding: 20px; margin-bottom: 15px; border-radius: 4px; background: #f6f7f7; position: relative;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #ddd;">
                                <h4 style="margin: 0; font-size: 14px; font-weight: 600; color: #1d2327;">
                                    <span class="dashicons dashicons-admin-generic" style="color: #2271b1; margin-right: 5px;"></span>
                                    <?php _e( 'Size Specification', 'lens-zone' ); ?> #<?php echo $index + 1; ?>
                                </h4>
                                <button type="button" class="button remove-frame-size" style="background: #d63638; color: white; border-color: #d63638; padding: 4px 12px; height: auto; line-height: 1.4;">
                                    <span class="dashicons dashicons-trash" style="font-size: 16px; vertical-align: middle; margin-right: 4px;"></span>
                                    <?php _e( 'Remove', 'lens-zone' ); ?>
                                </button>
                            </div>
                            
                            <input type="hidden" name="frame_size_ids[]" value="<?php echo $size->id; ?>">
                            
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                                <div>
                                    <label style="display: block; margin: 0px 0px 6px 0px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php esc_attr_e( 'Size Name', 'lens-zone' ); ?>">
                                        <?php _e( 'Size Name', 'lens-zone' ); ?> <span style="color: #d63638;">*</span>
                                    </label>
                                    <input type="text" name="frame_size_names[]" value="<?php echo esc_attr( $size->size_name ); ?>" placeholder="e.g., Medium" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin: 0px 0px 6px 0px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr( get_option( 'lens_zone_lens_width_label', 'LENS WIDTH' ) ); ?>">
                                        <?php echo esc_html( get_option( 'lens_zone_lens_width_label', 'LENS WIDTH' ) ); ?> <span style="color: #d63638;">*</span>
                                    </label>
                                    <input type="text" name="frame_lens_widths[]" value="<?php echo esc_attr( $size->lens_width ); ?>" placeholder="e.g., 52mm" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin: 0px 0px 6px 0px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr( get_option( 'lens_zone_bridge_width_label', 'BRIDGE WIDTH' ) ); ?>">
                                        <?php echo esc_html( get_option( 'lens_zone_bridge_width_label', 'BRIDGE WIDTH' ) ); ?> <span style="color: #d63638;">*</span>
                                    </label>
                                    <input type="text" name="frame_bridge_widths[]" value="<?php echo esc_attr( $size->bridge_width ); ?>" placeholder="e.g., 17mm" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin: 0px 0px 6px 0px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr( get_option( 'lens_zone_temple_length_label', 'TEMPLE LENGTH' ) ); ?>">
                                        <?php echo esc_html( get_option( 'lens_zone_temple_length_label', 'TEMPLE LENGTH' ) ); ?> <span style="color: #d63638;">*</span>
                                    </label>
                                    <input type="text" name="frame_temple_lengths[]" value="<?php echo esc_attr( $size->temple_length ); ?>" placeholder="e.g., 140mm" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                <button type="button" class="button button-primary" id="add-frame-size" style="padding: 8px 16px; height: auto; line-height: 1.4;">
                    <span class="dashicons dashicons-plus-alt" style="font-size: 16px; vertical-align: middle; margin-right: 4px;"></span>
                    <?php _e( 'Add Size Specification', 'lens-zone' ); ?>
                </button>
                <p class="description" style="margin: 12px 0 0 0; color: #666; font-size: 13px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1; font-size: 16px; vertical-align: middle; margin-right: 4px;"></span>
                    <?php printf( __( 'To change labels and icons, go to <a href="%s" style="color: #2271b1; text-decoration: none;">Change Details</a> page.', 'lens-zone' ), admin_url( 'admin.php?page=lens-zone-power-details' ) ); ?>
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Make lists sortable
            $('#available-lens-categories, #selected-lens-categories').sortable({
                connectWith: '.connectedSortable',
                placeholder: 'ui-state-highlight',
                update: function(event, ui) {
                    updateSelectedCategories();
                }
            }).disableSelection();
            
            // Enable drag between lists
            $('#available-lens-categories').sortable('option', 'connectWith', '#selected-lens-categories');
            $('#selected-lens-categories').sortable('option', 'connectWith', '#available-lens-categories');
            
            // Update hidden input with ordered IDs
            function updateSelectedCategories() {
                var selectedIds = [];
                $('#selected-lens-categories li').each(function() {
                    selectedIds.push($(this).data('id'));
                });
                $('#_selected_lens_categories_input').val(selectedIds.join(','));
            }
            
            // Remove category
            window.removeLensCategory = function(element) {
                var $li = $(element).closest('li');
                var id = $li.data('id');
                var name = $li.text().trim();
                
                // Move to available list
                $li.find('.dashicons-no-alt').remove();
                $li.css('border-color', '#ddd');
                $li.find('.dashicons-menu').css('color', '#999');
                $('#available-lens-categories').append($li);
                
                updateSelectedCategories();
            };
            
            // Initial update
            updateSelectedCategories();
            
            // Frame Size Management
            var frameSizeCounter = <?php echo count( $frame_sizes ); ?>;
            
            // Add new frame size
            $('#add-frame-size').on('click', function() {
                frameSizeCounter++;
                var html = '<div class="frame-size-row" style="border: 1px solid #c3c4c7; padding: 20px; margin-bottom: 15px; border-radius: 4px; background: #f6f7f7; position: relative;">';
                html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px solid #ddd;">';
                html += '<h4 style="margin: 0; font-size: 14px; font-weight: 600; color: #1d2327;"><span class="dashicons dashicons-admin-generic" style="color: #2271b1; margin-right: 5px;"></span><?php _e( 'Size Specification', 'lens-zone' ); ?> #' + frameSizeCounter + '</h4>';
                html += '<button type="button" class="button remove-frame-size" style="background: #d63638; color: white; border-color: #d63638; padding: 4px 12px; height: auto; line-height: 1.4;"><span class="dashicons dashicons-trash" style="font-size: 16px; vertical-align: middle; margin-right: 4px;"></span><?php _e( 'Remove', 'lens-zone' ); ?></button>';
                html += '</div>';
                html += '<input type="hidden" name="frame_size_ids[]" value="0">';
                html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">';
                html += '<div><label style="display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php esc_attr_e( 'Size Name', 'lens-zone' ); ?>"><?php _e( 'Size Name', 'lens-zone' ); ?> <span style="color: #d63638;">*</span></label>';
                html += '<input type="text" name="frame_size_names[]" placeholder="e.g., Medium" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required></div>';
                html += '<div><label style="display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr( get_option( 'lens_zone_lens_width_label', 'LENS WIDTH' ) ); ?>"><?php echo esc_js( get_option( 'lens_zone_lens_width_label', 'LENS WIDTH' ) ); ?> <span style="color: #d63638;">*</span></label>';
                html += '<input type="text" name="frame_lens_widths[]" placeholder="e.g., 52mm" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required></div>';
                html += '<div><label style="display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr( get_option( 'lens_zone_bridge_width_label', 'BRIDGE WIDTH' ) ); ?>"><?php echo esc_js( get_option( 'lens_zone_bridge_width_label', 'BRIDGE WIDTH' ) ); ?> <span style="color: #d63638;">*</span></label>';
                html += '<input type="text" name="frame_bridge_widths[]" placeholder="e.g., 17mm" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required></div>';
                html += '<div><label style="display: block; margin-bottom: 6px; font-weight: 600; color: #1d2327; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo esc_attr( get_option( 'lens_zone_temple_length_label', 'TEMPLE LENGTH' ) ); ?>"><?php echo esc_js( get_option( 'lens_zone_temple_length_label', 'TEMPLE LENGTH' ) ); ?> <span style="color: #d63638;">*</span></label>';
                html += '<input type="text" name="frame_temple_lengths[]" placeholder="e.g., 140mm" style="width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px;" required></div>';
                html += '</div></div>';
                
                $('#frame-sizes-container').append(html);
            });
            
            // Remove frame size with custom confirm
            $(document).on('click', '.remove-frame-size', function() {
                var $row = $(this).closest('.frame-size-row');
                lensZoneConfirm(
                    '<?php _e( 'Are you sure you want to remove this size specification?', 'lens-zone' ); ?>',
                    function(confirmed) {
                        if (confirmed) {
                            $row.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                    },
                    '<?php _e( 'Confirm Removal', 'lens-zone' ); ?>'
                );
            });
        });
        </script>
        
        <style>
        .ui-state-highlight {
            height: 40px;
            background: #fef3c7;
            border: 2px dashed #f59e0b;
            border-radius: 4px;
        }
        #available-lens-categories li:hover,
        #selected-lens-categories li:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        </style>
        <?php

        echo '</div>';
    }

    public function save_product_data( $post_id ) {
        global $wpdb;
        
        if ( isset( $_POST['_selected_lens_categories'] ) ) {
            // Convert comma-separated string to array
            $categories = $_POST['_selected_lens_categories'];
            if ( is_string( $categories ) ) {
                $categories = array_filter( explode( ',', $categories ) );
                $categories = array_map( 'intval', $categories );
            }
            update_post_meta( $post_id, '_selected_lens_categories', $categories );
        } else {
            // If no categories selected, save empty array
            update_post_meta( $post_id, '_selected_lens_categories', array() );
        }
        
        // Save frame sizes
        if ( isset( $_POST['frame_size_ids'] ) && is_array( $_POST['frame_size_ids'] ) ) {
            $size_ids = $_POST['frame_size_ids'];
            $size_names = isset( $_POST['frame_size_names'] ) ? $_POST['frame_size_names'] : array();
            $lens_widths = isset( $_POST['frame_lens_widths'] ) ? $_POST['frame_lens_widths'] : array();
            $bridge_widths = isset( $_POST['frame_bridge_widths'] ) ? $_POST['frame_bridge_widths'] : array();
            $temple_lengths = isset( $_POST['frame_temple_lengths'] ) ? $_POST['frame_temple_lengths'] : array();
            
            // Delete existing sizes for this product
            $wpdb->delete( $wpdb->prefix . 'lens_frame_sizes', array( 'product_id' => $post_id ) );
            
            // Insert sizes (icons are stored globally, not per product)
            foreach ( $size_ids as $index => $size_id ) {
                if ( ! empty( $size_names[ $index ] ) && ! empty( $lens_widths[ $index ] ) && ! empty( $bridge_widths[ $index ] ) && ! empty( $temple_lengths[ $index ] ) ) {
                    $data = array(
                        'product_id' => $post_id,
                        'size_name' => sanitize_text_field( $size_names[ $index ] ),
                        'lens_width' => sanitize_text_field( $lens_widths[ $index ] ),
                        'bridge_width' => sanitize_text_field( $bridge_widths[ $index ] ),
                        'temple_length' => sanitize_text_field( $temple_lengths[ $index ] ),
                        'lens_width_icon' => 0, // Icons are global now
                        'bridge_width_icon' => 0,
                        'temple_length_icon' => 0,
                    );
                    
                    $wpdb->insert( $wpdb->prefix . 'lens_frame_sizes', $data );
                }
            }
        }
    }
}

new Lens_Zone_Product_Integration();