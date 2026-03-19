<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Lens_Zone_Public {

    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_shortcode( 'select_lens_flow', array( $this, 'render_shortcode' ) );

        // AJAX actions
        add_action( 'wp_ajax_load_lens_categories', array( $this, 'load_lens_categories' ) );
        add_action( 'wp_ajax_nopriv_load_lens_categories', array( $this, 'load_lens_categories' ) );
        add_action( 'wp_ajax_load_lens_sub_categories', array( $this, 'load_lens_sub_categories' ) );
        add_action( 'wp_ajax_nopriv_load_lens_sub_categories', array( $this, 'load_lens_sub_categories' ) );
        add_action( 'wp_ajax_load_power_options', array( $this, 'load_power_options' ) );
        add_action( 'wp_ajax_nopriv_load_power_options', array( $this, 'load_power_options' ) );
        add_action( 'wp_ajax_load_manual_power_form', array( $this, 'load_manual_power_form' ) );
        add_action( 'wp_ajax_nopriv_load_manual_power_form', array( $this, 'load_manual_power_form' ) );
        add_action( 'wp_ajax_load_upload_form', array( $this, 'load_upload_form' ) );
        add_action( 'wp_ajax_nopriv_load_upload_form', array( $this, 'load_upload_form' ) );
        add_action( 'wp_ajax_upload_prescription_file', array( $this, 'upload_prescription_file' ) );
        add_action( 'wp_ajax_nopriv_upload_prescription_file', array( $this, 'upload_prescription_file' ) );
        add_action( 'wp_ajax_add_lens_to_cart', array( $this, 'add_lens_to_cart' ) );
        add_action( 'wp_ajax_nopriv_add_lens_to_cart', array( $this, 'add_lens_to_cart' ) );
        add_action( 'wp_ajax_add_to_cart', array( $this, 'add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_add_to_cart', array( $this, 'add_to_cart' ) );

        add_filter( 'woocommerce_get_item_data', array( $this, 'display_lens_data_in_cart' ), 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'add_lens_price_to_cart' ) );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_lens_data_to_order_items' ), 10, 4 );
        
        // Add modal container to cart and checkout pages
        add_action( 'wp_footer', array( $this, 'add_modal_container' ) );
        
        // Add chat functionality to My Account orders
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'add_order_chat_section' ) );
        add_action( 'wp_ajax_send_order_message', array( $this, 'send_order_message' ) );
        add_action( 'wp_ajax_nopriv_send_order_message', array( $this, 'send_order_message' ) );
        add_action( 'wp_ajax_get_order_messages', array( $this, 'get_order_messages' ) );
        add_action( 'wp_ajax_nopriv_get_order_messages', array( $this, 'get_order_messages' ) );
        add_action( 'wp_ajax_upload_order_file', array( $this, 'upload_order_file' ) );
        add_action( 'wp_ajax_nopriv_upload_order_file', array( $this, 'upload_order_file' ) );
        
        // Display user name and phone in order details
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_lens_user_info' ), 5 );
    }

    public function enqueue_scripts() {
        // Enqueue dashicons for the button icon
        wp_enqueue_style( 'dashicons' );
        
        // Enqueue plugin styles
        wp_enqueue_style( 'lens-zone-public', LZ_PLUGIN_URL . 'assets/css/public.css', array(), LZ_PLUGIN_VERSION );
        
        // Enqueue jQuery and plugin script
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'lens-zone-custom-alerts', LZ_PLUGIN_URL . 'assets/js/admin-custom-alerts.js', array( 'jquery' ), LZ_PLUGIN_VERSION, true );
        wp_enqueue_script( 'lens-zone-public', LZ_PLUGIN_URL . 'assets/js/public.js', array( 'jquery', 'lens-zone-custom-alerts' ), LZ_PLUGIN_VERSION, true );

        // Localize script with AJAX parameters
        wp_localize_script( 'lens-zone-public', 'lens_zone_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'lens-zone-nonce' ),
            'cart_url' => wc_get_cart_url(),
            'is_cart' => is_cart(),
            'is_checkout' => is_checkout(),
            'is_account' => is_account_page(),
        ) );
    }

    public function render_shortcode() {
        global $product, $wpdb;
        if ( ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        // Check if product has lens categories assigned
        $selected_categories = get_post_meta( $product->get_id(), '_selected_lens_categories', true );
        if ( ! is_array( $selected_categories ) || empty( $selected_categories ) ) {
            return; // Don't show button if no categories assigned
        }
        
        // Get frame sizes for this product
        $frame_sizes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_frame_sizes WHERE product_id = %d", $product->get_id() ) );
        
        // Get global labels and icons
        $size_label = get_option( 'lens_zone_size_label', 'SIZE' );
        $lens_width_label = get_option( 'lens_zone_lens_width_label', 'LENS WIDTH' );
        $lens_width_icon = get_option( 'lens_zone_lens_width_icon' );
        $bridge_width_label = get_option( 'lens_zone_bridge_width_label', 'BRIDGE WIDTH' );
        $bridge_width_icon = get_option( 'lens_zone_bridge_width_icon' );
        $temple_length_label = get_option( 'lens_zone_temple_length_label', 'TEMPLE LENGTH' );
        $temple_length_icon = get_option( 'lens_zone_temple_length_icon' );

        ob_start();
        ?>
        <!-- Frame Size Specifications -->
        <?php if ( ! empty( $frame_sizes ) ): ?>
        <div class="lens-zone-frame-sizes" style="margin-bottom: 24px; padding: 20px 0;">
            <?php foreach ( $frame_sizes as $size ): ?>
            <div class="frame-size-section" style="margin-bottom: 20px;">
                <h2 class="frame-size-title" style="text-align: center; font-size: 22px; font-weight: 700; color: #1d2327; margin-bottom: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
                    <?php echo esc_html( $size_label ); ?> : <?php echo esc_html( $size->size_name ); ?>
                </h2>
                
                <div class="frame-size-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 1000px; margin: 0 auto; padding: 0 15px;">
                    <!-- Lens Width Card -->
                    <div class="frame-size-card" style="background: #f6f7f9; border-radius: 8px; padding: 24px 16px; text-align: center;">
                        <div class="frame-icon-container" style="height: 70px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                            <?php if ( $lens_width_icon ): ?>
                                <?php echo wp_get_attachment_image( $lens_width_icon, 'thumbnail', false, array( 'style' => 'max-height: 70px; max-width: 100%; width: auto; height: auto; object-fit: contain;' ) ); ?>
                            <?php else: ?>
                                <div style="width: 80px; height: 70px; background: #e5e7eb; border-radius: 6px;"></div>
                            <?php endif; ?>
                        </div>
                        <p class="frame-label" style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; line-height: 1.3;">
                            <?php echo esc_html( $lens_width_label ); ?>
                        </p>
                        <p class="frame-value" style="font-size: 22px; font-weight: 700; color: #1d2327; margin: 0; line-height: 1;">
                            <?php echo esc_html( $size->lens_width ); ?>
                        </p>
                    </div>
                    
                    <!-- Bridge Width Card -->
                    <div class="frame-size-card" style="background: #f6f7f9; border-radius: 8px; padding: 24px 16px; text-align: center;">
                        <div class="frame-icon-container" style="height: 70px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                            <?php if ( $bridge_width_icon ): ?>
                                <?php echo wp_get_attachment_image( $bridge_width_icon, 'thumbnail', false, array( 'style' => 'max-height: 70px; max-width: 100%; width: auto; height: auto; object-fit: contain;' ) ); ?>
                            <?php else: ?>
                                <div style="width: 80px; height: 70px; background: #e5e7eb; border-radius: 6px;"></div>
                            <?php endif; ?>
                        </div>
                        <p class="frame-label" style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; line-height: 1.3;">
                            <?php echo esc_html( $bridge_width_label ); ?>
                        </p>
                        <p class="frame-value" style="font-size: 22px; font-weight: 700; color: #1d2327; margin: 0; line-height: 1;">
                            <?php echo esc_html( $size->bridge_width ); ?>
                        </p>
                    </div>
                    
                    <!-- Temple Length Card -->
                    <div class="frame-size-card" style="background: #f6f7f9; border-radius: 8px; padding: 24px 16px; text-align: center;">
                        <div class="frame-icon-container" style="height: 70px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                            <?php if ( $temple_length_icon ): ?>
                                <?php echo wp_get_attachment_image( $temple_length_icon, 'thumbnail', false, array( 'style' => 'max-height: 70px; max-width: 100%; width: auto; height: auto; object-fit: contain;' ) ); ?>
                            <?php else: ?>
                                <div style="width: 80px; height: 70px; background: #e5e7eb; border-radius: 6px;"></div>
                            <?php endif; ?>
                        </div>
                        <p class="frame-label" style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; line-height: 1.3;">
                            <?php echo esc_html( $temple_length_label ); ?>
                        </p>
                        <p class="frame-value" style="font-size: 22px; font-weight: 700; color: #1d2327; margin: 0; line-height: 1;">
                            <?php echo esc_html( $size->temple_length ); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="lens-zone-buttons-container" style="display: flex; align-items: center; gap: 16px; max-width: 100%; margin: 20px 0;">
            <button id="select-lens-button" class="button lens-zone-action-btn" data-product-id="<?php echo $product->get_id(); ?>" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px 24px; font-size: 16px; font-weight: 600; border-radius: 8px; background: #1e3a8a; color: white; border: none; cursor: pointer; transition: all 0.3s ease;">
                <span class="dashicons dashicons-visibility" style="font-size: 20px;"></span> <?php _e( 'SELECT LENS', 'lens-zone' ); ?>
            </button>
            
            <span style="font-size: 18px; font-weight: 700; color: #6b7280; flex-shrink: 0;">OR</span>
            
            <button id="buy-only-frame-button" class="button lens-zone-action-btn" data-product-id="<?php echo $product->get_id(); ?>" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px 24px; font-size: 16px; font-weight: 600; border-radius: 8px; background: #10b981; color: white; border: none; cursor: pointer; transition: all 0.3s ease;">
                <?php _e( 'BUY ONLY FRAME', 'lens-zone' ); ?>
            </button>
        </div>

        <div id="lens-selection-modal">
            <div class="modal-content">
                <div id="modal-body"></div>
            </div>
        </div>
        
        <style>
        /* Button hover effects */
        #select-lens-button:hover {
            background: #1e40af !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        
        #buy-only-frame-button:hover {
            background: #059669 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        #select-lens-button:disabled,
        #buy-only-frame-button:disabled {
            background: #d1d5db !important;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Frame Size Responsive Design - Based on provided images */
        
        /* Desktop - Default (above 768px) */
        .lens-zone-frame-sizes {
            width: 100%;
            max-width: 100%;
        }
        
        .frame-size-grid {
            width: 100%;
        }
        
        /* Tablet - Medium devices (max-width: 768px) */
        @media (max-width: 768px) {
            .lens-zone-frame-sizes {
                padding: 16px 0 !important;
            }
            
            .frame-size-title {
                font-size: 22px !important;
                margin-bottom: 16px !important;
            }
            
            .frame-size-grid {
                gap: 12px !important;
                padding: 0 10px !important;
            }
            
            .frame-size-card {
                padding: 16px 10px !important;
                border-radius: 6px !important;
            }
            
            .frame-icon-container {
                height: 50px !important;
                margin-bottom: 8px !important;
            }
            
            .frame-icon-container img {
                max-height: 50px !important;
            }
            
            .frame-icon-container > div {
                width: 60px !important;
                height: 50px !important;
            }
            
            .frame-label {
                font-size: 9px !important;
                margin-bottom: 6px !important;
                letter-spacing: 0.03em !important;
            }
            
            .frame-value {
                font-size: 24px !important;
            }
            
            /* Buttons responsive */
            .lens-zone-buttons-container {
                flex-direction: column !important;
                gap: 12px !important;
            }
            
            .lens-zone-buttons-container span {
                display: none !important;
            }
            
            .lens-zone-buttons-container button {
                width: 100% !important;
            }
        }
        
        /* Mobile - Small devices (max-width: 480px) */
        @media (max-width: 480px) {
            .lens-zone-frame-sizes {
                padding: 12px 0 !important;
            }
            
            .frame-size-title {
                font-size: 18px !important;
                margin-bottom: 12px !important;
            }
            
            .frame-size-grid {
                gap: 8px !important;
                padding: 0 8px !important;
            }
            
            .frame-size-card {
                padding: 12px 6px !important;
                border-radius: 6px !important;
            }
            
            .frame-icon-container {
                height: 40px !important;
                margin-bottom: 6px !important;
            }
            
            .frame-icon-container img {
                max-height: 40px !important;
            }
            
            .frame-icon-container > div {
                width: 50px !important;
                height: 40px !important;
            }
            
            .frame-label {
                font-size: 8px !important;
                margin-bottom: 4px !important;
                letter-spacing: 0.02em !important;
                line-height: 1.2 !important;
            }
            
            .frame-value {
                font-size: 20px !important;
            }
        }
        
        /* Extra Small Mobile (max-width: 360px) */
        @media (max-width: 360px) {
            .frame-size-title {
                font-size: 16px !important;
                margin-bottom: 10px !important;
            }
            
            .frame-size-grid {
                gap: 6px !important;
                padding: 0 5px !important;
            }
            
            .frame-size-card {
                padding: 10px 4px !important;
            }
            
            .frame-icon-container {
                height: 35px !important;
                margin-bottom: 5px !important;
            }
            
            .frame-icon-container img {
                max-height: 35px !important;
            }
            
            .frame-icon-container > div {
                width: 45px !important;
                height: 35px !important;
            }
            
            .frame-label {
                font-size: 7px !important;
                margin-bottom: 3px !important;
            }
            
            .frame-value {
                font-size: 18px !important;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    public function load_lens_categories() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        global $wpdb;
        $product_id = intval( $_POST['product_id'] );
        
        // Get selected categories for this product (in order)
        $selected_cats = get_post_meta( $product_id, '_selected_lens_categories', true );
        if ( ! is_array( $selected_cats ) ) {
            $selected_cats = array();
        }
        
        // Get categories maintaining the order
        $categories = array();
        if ( ! empty( $selected_cats ) ) {
            foreach ( $selected_cats as $cat_id ) {
                $category = $wpdb->get_row( $wpdb->prepare( 
                    "SELECT * FROM {$wpdb->prefix}lens_categories WHERE id = %d AND status = 'active'", 
                    $cat_id 
                ) );
                if ( $category ) {
                    $categories[] = $category;
                }
            }
        }
        
        ob_start();
        ?>
        <div class="modal-header lens-type-modal-header">
            <h2 class="lens-type-modal-title"><?php _e( 'Select Lens Type', 'lens-zone' ); ?></h2>
            <span class="close lens-type-modal-close">&times;</span>
        </div>
        <div class="modal-body lens-type-modal-body">
            <?php if ( empty( $categories ) ): ?>
                <p class="lens-type-empty-message" style="text-align: center; padding: 40px; color: #6b7280;">
                    <?php _e( 'No lens categories available for this product.', 'lens-zone' ); ?>
                </p>
            <?php else: ?>
                <?php foreach ( $categories as $category ): ?>
                    <div class="lens-card lens-category-card lens-category-<?php echo $category->id; ?>" data-category-id="<?php echo $category->id; ?>">
                        <?php if ( $category->image_id ): ?>
                            <div class="lens-card-image lens-category-image lens-category-image-<?php echo $category->id; ?>">
                                <?php echo wp_get_attachment_image( $category->image_id, 'medium' ); ?>
                            </div>
                        <?php endif; ?>
                        <div class="lens-card-content lens-category-content">
                            <h3 class="lens-card-title lens-category-title"><?php echo esc_html( $category->name ); ?></h3>
                            <?php if ( $category->description ): ?>
                                <div class="lens-card-description lens-category-description"><?php echo wp_kses_post( $category->description ); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="modal-footer lens-type-modal-footer">
            <div class="subtotal lens-type-subtotal">
                <?php 
                $product = wc_get_product( $product_id );
                $display_price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
                _e( 'Subtotal (Frame):', 'lens-zone' ); 
                ?> <strong>₹<?php echo number_format( $display_price, 0 ); ?></strong>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }
    
    public function load_lens_sub_categories() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        global $wpdb;
        $category_id = intval( $_POST['category_id'] );
        
        $category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE id = %d", $category_id ) );
        $sub_categories = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_sub_categories WHERE category_id = %d AND status = 'active' ORDER BY price ASC, name ASC", $category_id ) );
        
        // If no sub-categories, return flag to skip to power options
        if ( empty( $sub_categories ) ) {
            wp_send_json_success( array(
                'skip_to_power' => true,
                'category_id' => $category_id
            ) );
            return;
        }
        
        ob_start();
        ?>
        <div class="modal-header lens-selection-modal-header">
            <a href="#" class="back-button lens-selection-back-btn">&lt; <?php _e( 'Back', 'lens-zone' ); ?></a>
            <h2 class="lens-selection-modal-title"><?php echo esc_html( $category->name ); ?></h2>
            <span class="close lens-selection-modal-close">&times;</span>
        </div>
        <div class="modal-body lens-selection-modal-body">
            <?php foreach ( $sub_categories as $sub_cat ): ?>
                <?php
                $points = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_sub_category_points WHERE sub_category_id = %d", $sub_cat->id ) );
                ?>
                <div class="lens-card lens-subcategory-card lens-subcategory-<?php echo $sub_cat->id; ?>" data-subcategory-id="<?php echo $sub_cat->id; ?>" data-price="<?php echo $sub_cat->price; ?>">
                    <?php if ( $sub_cat->image_id ): ?>
                        <div class="lens-card-image lens-subcategory-image lens-subcategory-image-<?php echo $sub_cat->id; ?>">
                            <?php echo wp_get_attachment_image( $sub_cat->image_id, 'medium' ); ?>
                        </div>
                    <?php endif; ?>
                    <div class="lens-card-content lens-subcategory-content">
                        <h3 class="lens-card-title lens-subcategory-title"><?php echo esc_html( $sub_cat->name ); ?></h3>
                        <?php if ( $points ): ?>
                            <ul class="lens-card-points lens-subcategory-points">
                                <?php foreach ( $points as $index => $point ): ?>
                                    <li class="lens-subcategory-point-item" <?php echo $index >= 3 ? 'style="display:none;"' : ''; ?>><?php echo esc_html( $point->point_text ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ( count( $points ) > 3 ): ?>
                                <button class="view-more-btn lens-subcategory-view-more"><?php _e( 'View More', 'lens-zone' ); ?></button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="lens-card-price lens-subcategory-price">
                        <span class="current-price lens-subcategory-current-price">₹<?php echo number_format( $sub_cat->price, 0 ); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer lens-selection-modal-footer">
            <div class="subtotal lens-selection-subtotal">
                <?php _e( 'Subtotal:', 'lens-zone' ); ?> <strong>₹0</strong>
            </div>
            <button class="continue-button continue-from-subcategory lens-selection-continue-btn" disabled><?php _e( 'Continue', 'lens-zone' ); ?></button>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }
    
    public function load_power_options() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        $support_phone = get_option( 'lens_zone_support_phone', '' );
        
        ob_start();
        ?>
        <div class="modal-header power-options-modal-header">
            <a href="#" class="back-button power-options-back-btn">&lt; <?php _e( 'Back', 'lens-zone' ); ?></a>
            <h2 class="power-options-modal-title"><?php _e( 'Add Lens Details', 'lens-zone' ); ?></h2>
            <span class="close power-options-modal-close">&times;</span>
        </div>
        <div class="modal-body power-options-modal-body">
            <h3 class="power-options-section-title"><?php _e( "I don't know my power", 'lens-zone' ); ?></h3>
            <div class="power-option-card power-option-submit-later" data-option="submit-later">
                <div class="power-option-icon power-option-submit-later-icon">🕐</div>
                <div class="power-option-content power-option-submit-later-content">
                    <h3><?php _e( 'Submit Power Later', 'lens-zone' ); ?></h3>
                    <p><?php _e( 'Add to cart now, submit power within 7 days.', 'lens-zone' ); ?></p>
                </div>
            </div>
            
            <?php if ( $support_phone ): ?>
                <div class="support-text-power power-options-support-text">
                    <?php printf( __( "Don't know power? Contact here: %s", 'lens-zone' ), '<a href="tel:' . esc_attr( $support_phone ) . '">' . esc_html( $support_phone ) . '</a>' ); ?>
                </div>
            <?php endif; ?>
            
            <h3 class="power-options-section-title" style="margin-top: 24px;"><?php _e( 'I know my power', 'lens-zone' ); ?></h3>
            <div class="power-option-card power-option-manual" data-option="manual">
                <div class="power-option-icon power-option-manual-icon">✏️</div>
                <div class="power-option-content power-option-manual-content">
                    <h3><?php _e( 'Enter Power Manually', 'lens-zone' ); ?></h3>
                    <p><?php _e( 'Input your latest eye prescription', 'lens-zone' ); ?></p>
                </div>
            </div>
            
            <div class="power-option-card power-option-upload" data-option="upload">
                <div class="power-option-icon power-option-upload-icon">📤</div>
                <div class="power-option-content power-option-upload-content">
                    <h3><?php _e( 'Upload Prescription', 'lens-zone' ); ?></h3>
                    <p><?php _e( 'Just upload your power prescription', 'lens-zone' ); ?></p>
                </div>
            </div>
            
            <div class="power-form-container power-options-form-container"></div>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }



    public function load_manual_power_form() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );

        $sph_values = explode( "\n", get_option( 'lens_zone_sph_values', '' ) );
        $cyl_values = explode( "\n", get_option( 'lens_zone_cyl_values', '' ) );
        $additional_power_values = explode( "\n", get_option( 'lens_zone_additional_power_values', '' ) );
        $sph_values = array_filter( array_map( 'trim', $sph_values ) );
        $cyl_values = array_filter( array_map( 'trim', $cyl_values ) );
        $additional_power_values = array_filter( array_map( 'trim', $additional_power_values ) );
        $support_phone = get_option( 'lens_zone_support_phone', '' );
        
        // Check if additional power should be shown for current category
        $category_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;
        $show_additional_power = false;
        if ( $category_id > 0 ) {
            $assigned_categories = get_option( 'lens_zone_additional_power_categories', array() );
            if ( ! is_array( $assigned_categories ) ) {
                $assigned_categories = array();
            }
            $show_additional_power = in_array( $category_id, array_map( 'intval', $assigned_categories ) );
        }

        ob_start();
        ?>
        <div class="modal-header manual-power-modal-header">
            <a href="#" class="back-button manual-power-back-btn">&lt; <?php _e( 'Back', 'lens-zone' ); ?></a>
            <h2 class="manual-power-modal-title"><?php _e( 'Submit Eye Power', 'lens-zone' ); ?></h2>
            <span class="close manual-power-modal-close">&times;</span>
        </div>
        <div class="modal-body manual-power-modal-body">
            <h3 class="manual-power-section-title"><?php _e( 'Enter power manually', 'lens-zone' ); ?></h3>
            
            <?php if ( ! is_user_logged_in() ): ?>
                <div class="login-notice manual-power-login-notice">
                    <div class="login-notice-text manual-power-login-text">
                        <?php _e( "It seems you are not logged in. Please register or login.", 'lens-zone' ); ?>
                    </div>
                    <a href="https://lens.arttechfuzion.com/my-account/" class="login-button manual-power-login-btn"><?php _e( 'REGISTER', 'lens-zone' ); ?></a>
                </div>
            <?php else: ?>
            
            <div class="power-form-checkbox manual-power-checkbox">
                <input type="checkbox" id="same-power-checkbox" class="manual-power-same-checkbox">
                <label for="same-power-checkbox" class="manual-power-same-label"><?php _e( 'I have same power for both eyes', 'lens-zone' ); ?></label>
            </div>
            
            <div class="power-form-grid two-columns manual-power-grid">
                <div class="power-form-column left-eye-column manual-power-left-column">
                    <h4 class="manual-power-eye-title"><?php _e( 'LEFT (OS)', 'lens-zone' ); ?></h4>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'SPH', 'lens-zone' ); ?></label>
                        <select name="left_sph" class="power-select manual-power-select manual-power-left-sph">
                            <option value=""><?php _e( 'Select', 'lens-zone' ); ?></option>
                            <?php foreach ( $sph_values as $value ): ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'CYL', 'lens-zone' ); ?></label>
                        <select name="left_cyl" class="power-select manual-power-select manual-power-left-cyl">
                            <option value=""><?php _e( 'Select', 'lens-zone' ); ?></option>
                            <?php foreach ( $cyl_values as $value ): ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'Axis', 'lens-zone' ); ?></label>
                        <input type="number" name="left_axis" min="0" max="180" placeholder="Axis" class="power-input manual-power-input manual-power-left-axis">
                    </div>
                    <?php if ( $show_additional_power && ! empty( $additional_power_values ) ): ?>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'Addi. Power', 'lens-zone' ); ?></label>
                        <select name="left_additional" class="power-select manual-power-select manual-power-left-additional">
                            <option value=""><?php _e( 'Select', 'lens-zone' ); ?></option>
                            <?php foreach ( $additional_power_values as $value ): ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="power-form-column right-eye-column manual-power-right-column">
                    <h4 class="manual-power-eye-title"><?php _e( 'RIGHT (OD)', 'lens-zone' ); ?></h4>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'SPH', 'lens-zone' ); ?></label>
                        <select name="right_sph" class="power-select manual-power-select manual-power-right-sph">
                            <option value=""><?php _e( 'Select', 'lens-zone' ); ?></option>
                            <?php foreach ( $sph_values as $value ): ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'CYL', 'lens-zone' ); ?></label>
                        <select name="right_cyl" class="power-select manual-power-select manual-power-right-cyl">
                            <option value=""><?php _e( 'Select', 'lens-zone' ); ?></option>
                            <?php foreach ( $cyl_values as $value ): ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'Axis', 'lens-zone' ); ?></label>
                        <input type="number" name="right_axis" min="0" max="180" placeholder="Axis" class="power-input manual-power-input manual-power-right-axis">
                    </div>
                    <?php if ( $show_additional_power && ! empty( $additional_power_values ) ): ?>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'Addi. Power', 'lens-zone' ); ?></label>
                        <select name="right_additional" class="power-select manual-power-select manual-power-right-additional">
                            <option value=""><?php _e( 'Select', 'lens-zone' ); ?></option>
                            <?php foreach ( $additional_power_values as $value ): ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="user-info-fields manual-power-user-info" style="margin-top: 24px;">
                <h4 class="manual-power-user-info-title" style="margin-bottom: 16px; font-size: 16px; font-weight: 600; color: #111827;"><?php _e( 'Your Information', 'lens-zone' ); ?></h4>
                <div class="power-form-grid two-columns manual-power-user-grid">
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'Name', 'lens-zone' ); ?> *</label>
                        <input type="text" name="user_name" class="power-input manual-power-input manual-power-user-name" required placeholder="<?php _e( 'Enter your name', 'lens-zone' ); ?>">
                    </div>
                    <div class="power-form-field manual-power-field">
                        <label class="manual-power-label"><?php _e( 'Phone Number', 'lens-zone' ); ?> *</label>
                        <input type="tel" name="user_phone" class="power-input manual-power-input manual-power-user-phone" required placeholder="<?php _e( 'Enter your phone number', 'lens-zone' ); ?>">
                    </div>
                </div>
            </div>
            
            <?php if ( $support_phone ): ?>
                <div class="support-text manual-power-support-text">
                    <?php printf( __( "Can't find your power, Call %s", 'lens-zone' ), '<a href="tel:' . esc_attr( $support_phone ) . '">' . esc_html( $support_phone ) . '</a>' ); ?>
                </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
        <div class="modal-footer manual-power-modal-footer">
            <?php if ( is_user_logged_in() ): ?>
                <button class="continue-button save-proceed-button manual-power-save-btn"><?php _e( 'Save & Proceed', 'lens-zone' ); ?></button>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }
    
    public function load_upload_form() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        ob_start();
        ?>
        <div class="modal-header upload-prescription-modal-header">
            <a href="#" class="back-button upload-prescription-back-btn">&lt; <?php _e( 'Back', 'lens-zone' ); ?></a>
            <h2 class="upload-prescription-modal-title"><?php _e( 'Submit Eye Power', 'lens-zone' ); ?></h2>
            <span class="close upload-prescription-modal-close">&times;</span>
        </div>
        <div class="modal-body upload-prescription-modal-body">
            <h3 class="upload-prescription-section-title"><?php _e( 'Upload Prescription', 'lens-zone' ); ?></h3>
            
            <?php if ( ! is_user_logged_in() ): ?>
                <div class="login-notice upload-prescription-login-notice">
                    <div class="login-notice-text upload-prescription-login-text">
                        <?php _e( "It seems you are not logged in. Please register or login.", 'lens-zone' ); ?>
                    </div>
                    <a href="<?php echo wp_registration_url(); ?>" class="login-button upload-prescription-login-btn"><?php _e( 'REGISTER', 'lens-zone' ); ?></a>
                </div>
            <?php else: ?>
            
            <ul class="upload-prescription-instructions" style="color: #6b7280; font-size: 14px; margin: 16px 0;">
                <li><?php _e( 'PDF, JPEG, PNG formats accepted', 'lens-zone' ); ?></li>
                <li><?php _e( 'Make sure your file size under 5 MB', 'lens-zone' ); ?></li>
                <li><?php _e( 'Please upload only one file', 'lens-zone' ); ?></li>
            </ul>
            
            <div class="upload-area upload-prescription-area" id="prescription-upload-area">
                <div class="upload-area-icon upload-prescription-icon">📄</div>
                <div class="upload-area-text upload-prescription-text"><?php _e( 'Tap here to upload prescription image', 'lens-zone' ); ?></div>
                <div class="upload-area-subtext upload-prescription-subtext"><?php _e( '(Max. size: 5MB)', 'lens-zone' ); ?></div>
            </div>
            <input type="file" id="prescription-file-input" class="upload-prescription-file-input" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
            
            <div id="upload-preview" class="upload-prescription-preview" style="display: none; margin-top: 16px; text-align: center;">
                <p class="upload-prescription-success-msg" style="color: #10b981; font-weight: 600;"><?php _e( 'File uploaded successfully!', 'lens-zone' ); ?></p>
                <p id="uploaded-filename" class="upload-prescription-filename" style="color: #6b7280; font-size: 14px;"></p>
            </div>
            
            <div class="user-info-fields upload-prescription-user-info" style="margin-top: 24px;">
                <h4 class="upload-prescription-user-info-title" style="margin-bottom: 16px; font-size: 16px; font-weight: 600; color: #111827;"><?php _e( 'Your Information', 'lens-zone' ); ?></h4>
                <div class="power-form-grid two-columns upload-prescription-user-grid">
                    <div class="power-form-field upload-prescription-field">
                        <label class="upload-prescription-label"><?php _e( 'Name', 'lens-zone' ); ?> *</label>
                        <input type="text" name="upload_user_name" id="upload_user_name" class="power-input upload-prescription-input upload-prescription-user-name" required placeholder="<?php _e( 'Enter your name', 'lens-zone' ); ?>">
                    </div>
                    <div class="power-form-field upload-prescription-field">
                        <label class="upload-prescription-label"><?php _e( 'Phone Number', 'lens-zone' ); ?> *</label>
                        <input type="tel" name="upload_user_phone" id="upload_user_phone" class="power-input upload-prescription-input upload-prescription-user-phone" required placeholder="<?php _e( 'Enter your phone number', 'lens-zone' ); ?>">
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
        <div class="modal-footer upload-prescription-modal-footer">
            <?php if ( is_user_logged_in() ): ?>
                <button class="continue-button upload-proceed-button upload-prescription-continue-btn"><?php _e( 'Continue', 'lens-zone' ); ?></button>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }
    
    public function upload_prescription_file() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        
        $uploadedfile = $_FILES['prescription_file'];
        $upload_overrides = array( 'test_form' => false );
        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
        
        if ( $movefile && ! isset( $movefile['error'] ) ) {
            wp_send_json_success( array( 'url' => $movefile['url'] ) );
        } else {
            wp_send_json_error( $movefile['error'] );
        }
    }
    
    public function add_lens_to_cart() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        $product_id = intval( $_POST['product_id'] );
        $lens_data = isset( $_POST['lens_data'] ) ? $_POST['lens_data'] : array();
        
        global $wpdb;
        
        // Get category name
        $category_id = isset( $lens_data['category_id'] ) ? intval( $lens_data['category_id'] ) : 0;
        $category_name = '';
        if ( $category_id > 0 ) {
            $category = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}lens_categories WHERE id = %d", $category_id ) );
            $category_name = $category ? $category->name : '';
        }
        
        // Get sub-category name and price
        $sub_category_id = isset( $lens_data['subcategory_id'] ) ? intval( $lens_data['subcategory_id'] ) : 0;
        $sub_category_name = '';
        $lens_price = 0;
        
        if ( $sub_category_id > 0 ) {
            $sub_category = $wpdb->get_row( $wpdb->prepare( "SELECT name, price FROM {$wpdb->prefix}lens_sub_categories WHERE id = %d", $sub_category_id ) );
            if ( $sub_category ) {
                $sub_category_name = $sub_category->name;
                $lens_price = floatval( $sub_category->price );
            }
        }
        
        // Add names to lens data
        $lens_data['category_name'] = $category_name;
        $lens_data['subcategory_name'] = $sub_category_name;
        
        // Prepare cart item data
        $cart_item_data = array(
            'lens_zone_data' => $lens_data,
            'lens_zone_price' => $lens_price,
        );
        
        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );
        
        if ( $cart_item_key ) {
            wp_send_json_success( array( 
                'cart_item_key' => $cart_item_key,
                'lens_price' => $lens_price,
                'message' => 'Added to cart successfully'
            ) );
        } else {
            wp_send_json_error( __( 'Failed to add to cart', 'lens-zone' ) );
        }
    }
    
    public function add_modal_container() {
        if ( is_cart() || is_checkout() ) {
            ?>
            <div id="lens-selection-modal">
                <div class="modal-content">
                    <div id="modal-body"></div>
                </div>
            </div>
            <?php
        }
    }
    
    public function display_lens_data_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['lens_zone_data'] ) ) {
            $lens_data = $cart_item['lens_zone_data'];
            $power_type = isset( $lens_data['power_type'] ) ? $lens_data['power_type'] : 'submit-later';
            
            // Lens Category
            if ( ! empty( $lens_data['category_name'] ) ) {
                $item_data[] = array(
                    'key'     => __( 'Lens Type', 'lens-zone' ),
                    'value'   => $lens_data['category_name'],
                    'display' => '',
                );
            }
            
            // Lens Type (Sub-Category)
            if ( ! empty( $lens_data['subcategory_name'] ) ) {
                $item_data[] = array(
                    'key'     => __( 'Lens Name', 'lens-zone' ),
                    'value'   => $lens_data['subcategory_name'],
                    'display' => '',
                );
            }
            
            // Method Used
            $item_data[] = array(
                'key'     => __( 'Method Used', 'lens-zone' ),
                'value'   => ucfirst( str_replace( '-', ' ', $power_type ) ),
                'display' => '',
            );
            
            // Power Details for Manual
            if ( $power_type === 'manual' && isset( $lens_data['left_sph'] ) ) {
                $power_details = sprintf(
                    'Left: SPH %s, CYL %s, Axis %s%s | Right: SPH %s, CYL %s, Axis %s%s',
                    $lens_data['left_sph'] ?? '-',
                    $lens_data['left_cyl'] ?? '-',
                    $lens_data['left_axis'] ?? '-',
                    ! empty( $lens_data['left_additional'] ) ? ', Add: ' . $lens_data['left_additional'] : '',
                    $lens_data['right_sph'] ?? '-',
                    $lens_data['right_cyl'] ?? '-',
                    $lens_data['right_axis'] ?? '-',
                    ! empty( $lens_data['right_additional'] ) ? ', Add: ' . $lens_data['right_additional'] : ''
                );
                
                $item_data[] = array(
                    'key'     => __( 'Power Details', 'lens-zone' ),
                    'value'   => $power_details,
                    'display' => '',
                );
            }
            
            // Prescription URL for Upload
            if ( $power_type === 'upload' && isset( $lens_data['prescription_url'] ) ) {
                $item_data[] = array(
                    'key'     => __( 'Prescription URL', 'lens-zone' ),
                    'value'   => '<a href="' . esc_url( $lens_data['prescription_url'] ) . '" target="_blank" style="color: #1e3a8a; text-decoration: underline;">' . esc_url( $lens_data['prescription_url'] ) . '</a>',
                    'display' => '',
                );
            }
            
            // NOTE: User name and phone are NOT shown in cart/checkout
            // They are only visible in order details (My Account and Admin)
        }
        
        return $item_data;
    }
    
    public function add_lens_price_to_cart( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }
        
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
            return;
        }
        
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['lens_zone_price'] ) && $cart_item['lens_zone_price'] > 0 ) {
                // Use sale price if available, otherwise use regular price
                $product = $cart_item['data'];
                $base_price = $product->get_sale_price() ? floatval( $product->get_sale_price() ) : floatval( $product->get_regular_price() );
                $lens_price = floatval( $cart_item['lens_zone_price'] );
                $new_price = $base_price + $lens_price;
                
                $cart_item['data']->set_price( $new_price );
            }
        }
    }
    
    public function add_lens_data_to_order_items( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['lens_zone_data'] ) ) {
            $lens_data = $values['lens_zone_data'];
            
            // Add Lens Category
            if ( ! empty( $lens_data['category_name'] ) ) {
                $item->add_meta_data( __( 'Lens Type', 'lens-zone' ), $lens_data['category_name'] );
            }
            
            // Add Lens Type (Sub-Category)
            if ( ! empty( $lens_data['subcategory_name'] ) ) {
                $item->add_meta_data( __( 'Lens Name', 'lens-zone' ), $lens_data['subcategory_name'] );
            }
            
            // Add Method Used
            $item->add_meta_data( __( 'Method Used', 'lens-zone' ), ucfirst( str_replace( '-', ' ', $lens_data['power_type'] ) ) );
            
            // Add Power Details for Manual
            if ( $lens_data['power_type'] === 'manual' ) {
                if ( isset( $lens_data['left_sph'] ) ) {
                    $power_details = sprintf(
                        'Left: SPH %s, CYL %s, Axis %s%s | Right: SPH %s, CYL %s, Axis %s%s',
                        $lens_data['left_sph'],
                        $lens_data['left_cyl'],
                        $lens_data['left_axis'],
                        ! empty( $lens_data['left_additional'] ) ? ', Add: ' . $lens_data['left_additional'] : '',
                        $lens_data['right_sph'],
                        $lens_data['right_cyl'],
                        $lens_data['right_axis'],
                        ! empty( $lens_data['right_additional'] ) ? ', Add: ' . $lens_data['right_additional'] : ''
                    );
                    $item->add_meta_data( __( 'Power Details', 'lens-zone' ), $power_details );
                }
            }
            
            // Add Prescription URL for Upload
            if ( $lens_data['power_type'] === 'upload' && isset( $lens_data['prescription_url'] ) ) {
                $item->add_meta_data( __( 'Prescription URL', 'lens-zone' ), $lens_data['prescription_url'] );
            }
            
            // Add User Name and Phone (hidden meta - only visible in order details)
            if ( ! empty( $lens_data['user_name'] ) ) {
                $item->add_meta_data( '_lens_user_name', $lens_data['user_name'] );
            }
            if ( ! empty( $lens_data['user_phone'] ) ) {
                $item->add_meta_data( '_lens_user_phone', $lens_data['user_phone'] );
            }
        }
    }
    
    // Add chat section to order details page
    public function add_order_chat_section( $order ) {
        // Only show for processing and on-hold orders
        if ( ! in_array( $order->get_status(), array( 'processing', 'on-hold' ) ) ) {
            return;
        }
        
        $order_id = $order->get_id();
        ?>
        <div class="lens-zone-order-chat">
            <h2><?php _e( 'Order Communication', 'lens-zone' ); ?></h2>
            <div class="chat-messages" id="chat-messages-<?php echo $order_id; ?>">
                <?php $this->display_order_messages( $order_id ); ?>
            </div>
            <div class="chat-input-area">
                <textarea id="chat-message-<?php echo $order_id; ?>" placeholder="<?php _e( 'Type your message...', 'lens-zone' ); ?>"></textarea>
                <div class="chat-actions">
                    <button class="upload-file-btn" data-order-id="<?php echo $order_id; ?>">
                        <span class="dashicons dashicons-paperclip"></span> <?php _e( 'Attach File', 'lens-zone' ); ?>
                    </button>
                    <input type="file" id="chat-file-<?php echo $order_id; ?>" style="display: none;" accept=".pdf,.jpg,.jpeg,.png">
                    <button class="send-message-btn" data-order-id="<?php echo $order_id; ?>">
                        <?php _e( 'Send', 'lens-zone' ); ?>
                    </button>
                </div>
                <div id="file-preview-<?php echo $order_id; ?>" class="file-preview" style="display: none;"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var orderId = <?php echo $order_id; ?>;
            var uploadedFileUrl = '';
            
            // Upload file button
            $('.upload-file-btn[data-order-id="' + orderId + '"]').on('click', function(e) {
                e.preventDefault();
                $('#chat-file-' + orderId).click();
            });
            
            // File selection
            $('#chat-file-' + orderId).on('change', function() {
                var file = this.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        lensZoneAlert('<?php _e( 'File size must be under 5MB', 'lens-zone' ); ?>', 'warning', '<?php _e( 'File Too Large', 'lens-zone' ); ?>');
                        return;
                    }
                    
                    var formData = new FormData();
                    formData.append('action', 'upload_order_file');
                    formData.append('nonce', lens_zone_params.nonce);
                    formData.append('order_id', orderId);
                    formData.append('file', file);
                    
                    $.ajax({
                        url: lens_zone_params.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                uploadedFileUrl = response.data.url;
                                $('#file-preview-' + orderId).html('<span>📎 ' + file.name + ' <a href="#" class="remove-file">✕</a></span>').show();
                            } else {
                                lensZoneAlert('Upload failed: ' + response.data, 'error', '<?php _e( 'Upload Error', 'lens-zone' ); ?>');
                            }
                        }
                    });
                }
            });
            
            // Remove file
            $(document).on('click', '.remove-file', function(e) {
                e.preventDefault();
                uploadedFileUrl = '';
                $('#file-preview-' + orderId).hide().html('');
                $('#chat-file-' + orderId).val('');
            });
            
            // Send message
            $('.send-message-btn[data-order-id="' + orderId + '"]').on('click', function() {
                var message = $('#chat-message-' + orderId).val().trim();
                
                if (!message && !uploadedFileUrl) {
                    lensZoneAlert('<?php _e( 'Please enter a message or attach a file', 'lens-zone' ); ?>', 'warning', '<?php _e( 'Message Required', 'lens-zone' ); ?>');
                    return;
                }
                
                $.ajax({
                    url: lens_zone_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'send_order_message',
                        nonce: lens_zone_params.nonce,
                        order_id: orderId,
                        message: message,
                        file_url: uploadedFileUrl
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#chat-message-' + orderId).val('');
                            uploadedFileUrl = '';
                            $('#file-preview-' + orderId).hide().html('');
                            $('#chat-file-' + orderId).val('');
                            loadMessages();
                        }
                    }
                });
            });
            
            // Load messages
            function loadMessages() {
                $.ajax({
                    url: lens_zone_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_order_messages',
                        nonce: lens_zone_params.nonce,
                        order_id: orderId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#chat-messages-' + orderId).html(response.data);
                        }
                    }
                });
            }
            
            // Auto-refresh messages every 10 seconds
            setInterval(loadMessages, 10000);
        });
        </script>
        <?php
    }
    
    // Display order messages
    private function display_order_messages( $order_id ) {
        $messages = get_post_meta( $order_id, '_lens_zone_chat_messages', true );
        if ( ! is_array( $messages ) ) {
            $messages = array();
        }
        
        if ( empty( $messages ) ) {
            echo '<p class="no-messages">' . __( 'No messages yet. Start a conversation!', 'lens-zone' ) . '</p>';
            return;
        }
        
        foreach ( $messages as $msg ) {
            $sender_class = $msg['sender'] === 'customer' ? 'customer-message' : 'admin-message';
            ?>
            <div class="chat-message <?php echo $sender_class; ?>">
                <div class="message-header">
                    <strong><?php echo $msg['sender'] === 'customer' ? __( 'You', 'lens-zone' ) : __( 'Admin', 'lens-zone' ); ?></strong>
                    <span class="message-time"><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $msg['timestamp'] ); ?></span>
                </div>
                <?php if ( ! empty( $msg['message'] ) ): ?>
                    <div class="message-text"><?php echo esc_html( $msg['message'] ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $msg['file_url'] ) ): ?>
                    <div class="message-file">
                        <a href="<?php echo esc_url( $msg['file_url'] ); ?>" target="_blank">
                            <span class="dashicons dashicons-media-default"></span> <?php _e( 'View Attachment', 'lens-zone' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    // Send order message
    public function send_order_message() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        $order_id = intval( $_POST['order_id'] );
        $message = sanitize_textarea_field( $_POST['message'] );
        $file_url = isset( $_POST['file_url'] ) ? esc_url_raw( $_POST['file_url'] ) : '';
        
        $messages = get_post_meta( $order_id, '_lens_zone_chat_messages', true );
        if ( ! is_array( $messages ) ) {
            $messages = array();
        }
        
        $messages[] = array(
            'sender' => 'customer',
            'message' => $message,
            'file_url' => $file_url,
            'timestamp' => current_time( 'timestamp' )
        );
        
        update_post_meta( $order_id, '_lens_zone_chat_messages', $messages );
        
        wp_send_json_success();
    }
    
    // Get order messages
    public function get_order_messages() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        $order_id = intval( $_POST['order_id'] );
        
        ob_start();
        $this->display_order_messages( $order_id );
        $html = ob_get_clean();
        
        wp_send_json_success( $html );
    }
    
    // Upload order file
    public function upload_order_file() {
        check_ajax_referer( 'lens-zone-nonce', 'nonce' );
        
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        
        $uploadedfile = $_FILES['file'];
        $upload_overrides = array( 'test_form' => false );
        $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
        
        if ( $movefile && ! isset( $movefile['error'] ) ) {
            wp_send_json_success( array( 'url' => $movefile['url'] ) );
        } else {
            wp_send_json_error( $movefile['error'] );
        }
    }
    
    // Display lens user info in order details (My Account)
    public function display_lens_user_info( $order ) {
        $items = $order->get_items();
        $has_lens_data = false;
        $user_name = '';
        $user_phone = '';
        
        foreach ( $items as $item ) {
            $name = $item->get_meta( '_lens_user_name' );
            $phone = $item->get_meta( '_lens_user_phone' );
            
            if ( $name || $phone ) {
                $has_lens_data = true;
                $user_name = $name;
                $user_phone = $phone;
                break;
            }
        }
        
        if ( ! $has_lens_data ) {
            return;
        }
        ?>
        <section class="woocommerce-lens-user-info" style="margin-top: 20px; padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
            <h2 style="margin: 0 0 15px 0; font-size: 18px; font-weight: 600; color: #111827;"><?php _e( 'Customer Contact Information', 'lens-zone' ); ?></h2>
            <table class="woocommerce-table woocommerce-table--lens-info shop_table lens_info" style="width: 100%; border: none;">
                <tbody>
                    <?php if ( $user_name ): ?>
                    <tr>
                        <th style="text-align: left; padding: 8px 0; font-weight: 600; color: #374151; width: 30%;"><?php _e( 'Name:', 'lens-zone' ); ?></th>
                        <td style="padding: 8px 0; color: #111827;"><?php echo esc_html( $user_name ); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ( $user_phone ): ?>
                    <tr>
                        <th style="text-align: left; padding: 8px 0; font-weight: 600; color: #374151; width: 30%;"><?php _e( 'Phone Number:', 'lens-zone' ); ?></th>
                        <td style="padding: 8px 0; color: #111827;"><a href="tel:<?php echo esc_attr( $user_phone ); ?>" style="color: #1e3a8a; text-decoration: none;"><?php echo esc_html( $user_phone ); ?></a></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        <?php
    }
}

// Initialize the public class as singleton
Lens_Zone_Public::get_instance();