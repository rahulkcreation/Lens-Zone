<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Make sure WordPress functions are available
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../../');
}

if (!function_exists('add_action')) {
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

if (!function_exists('add_menu_page')) {
    require_once(ABSPATH . 'wp-admin/includes/admin.php');
}

if (!function_exists('__') || !function_exists('_e')) {
    require_once(ABSPATH . 'wp-includes/l10n.php');
}

if (!function_exists('wp_count_posts')) {
    require_once(ABSPATH . 'wp-includes/post.php');
}

if (!function_exists('settings_fields') || !function_exists('do_settings_sections')) {
    require_once(ABSPATH . 'wp-admin/includes/template.php');
}

if (!function_exists('register_setting')) {
    require_once(ABSPATH . 'wp-admin/includes/settings.php');
}

if (!function_exists('wp_nonce_field') || !function_exists('wp_verify_nonce')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

class Lens_Zone_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Add chat to order details
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_admin_order_chat' ) );
        add_action( 'wp_ajax_admin_send_order_message', array( $this, 'admin_send_order_message' ) );
        add_action( 'wp_ajax_admin_get_order_messages', array( $this, 'admin_get_order_messages' ) );
        
        // Display user name and phone in admin order details
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_lens_user_info_admin' ), 5 );
    }
    
    public function enqueue_admin_scripts( $hook ) {
        // Only load on our plugin pages
        if ( strpos( $hook, 'lens-zone' ) !== false || strpos( $hook, 'post.php' ) !== false || strpos( $hook, 'post-new.php' ) !== false ) {
            wp_enqueue_media();
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_style( 'lens-zone-admin', LZ_PLUGIN_URL . 'assets/css/admin.css', array(), LZ_PLUGIN_VERSION );
            wp_enqueue_script( 'lens-zone-admin-alerts', LZ_PLUGIN_URL . 'assets/js/admin-custom-alerts.js', array( 'jquery' ), LZ_PLUGIN_VERSION, true );
        }
    }
    
    // This function has been moved to the bottom of the class
    
    // This function has been moved to the bottom of the class

    public function admin_menu() {
        add_menu_page(
            __( 'Lens Manager', 'lens-zone' ),
            __( 'Lens Manager', 'lens-zone' ),
            'manage_options',
            'lens-zone',
            array( $this, 'dashboard_page' ),
            'dashicons-visibility',
            58
        );

        add_submenu_page(
            'lens-zone',
            __( 'Dashboard', 'lens-zone' ),
            __( 'Dashboard', 'lens-zone' ),
            'manage_options',
            'lens-zone',
            array( $this, 'dashboard_page' )
        );

        add_submenu_page(
            'lens-zone',
            __( 'Lens Category', 'lens-zone' ),
            __( 'Lens Category', 'lens-zone' ),
            'manage_options',
            'lens-zone-categories',
            array( $this, 'lens_categories_page' )
        );

        add_submenu_page(
            'lens-zone',
            __( 'Lens Sub-Category', 'lens-zone' ),
            __( 'Lens Sub-Category', 'lens-zone' ),
            'manage_options',
            'lens-zone-sub-categories',
            array( $this, 'lens_sub_categories_page' )
        );

        add_submenu_page(
            'lens-zone',
            __( 'Change Details', 'lens-zone' ),
            __( 'Change Details', 'lens-zone' ),
            'manage_options',
            'lens-zone-power-details',
            array( $this, 'power_details_page' )
        );

        add_submenu_page(
            'lens-zone',
            __( 'Database', 'lens-zone' ),
            __( 'Database', 'lens-zone' ),
            'manage_options',
            'lens-zone-database',
            array( $this, 'database_page' )
        );
        
        // Hidden submenu pages for Add/Edit
        add_submenu_page(
            null,
            __( 'Add Lens Category', 'lens-zone' ),
            __( 'Add Lens Category', 'lens-zone' ),
            'manage_options',
            'lens-zone-add-category',
            array( $this, 'add_edit_category_page' )
        );
        
        add_submenu_page(
            null,
            __( 'Edit Lens Category', 'lens-zone' ),
            __( 'Edit Lens Category', 'lens-zone' ),
            'manage_options',
            'lens-zone-edit-category',
            array( $this, 'add_edit_category_page' )
        );
        
        add_submenu_page(
            null,
            __( 'Add Lens Sub-Category', 'lens-zone' ),
            __( 'Add Lens Sub-Category', 'lens-zone' ),
            'manage_options',
            'lens-zone-add-sub-category',
            array( $this, 'add_edit_sub_category_page' )
        );
        
        add_submenu_page(
            null,
            __( 'Edit Lens Sub-Category', 'lens-zone' ),
            __( 'Edit Lens Sub-Category', 'lens-zone' ),
            'manage_options',
            'lens-zone-edit-sub-category',
            array( $this, 'add_edit_sub_category_page' )
        );
    }

    public function dashboard_page() {
        global $wpdb;
        
        $total_lens_categories = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_categories WHERE status = 'active'" );
        $total_lens_sub_categories = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_sub_categories WHERE status = 'active'" );
        
        $total_lens_categories = $total_lens_categories ? $total_lens_categories : 0;
        $total_lens_sub_categories = $total_lens_sub_categories ? $total_lens_sub_categories : 0;
        ?>
        <div class="wrap">
            <h1><?php _e( 'Dashboard', 'lens-zone' ); ?></h1>

            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="meta-box-sortables">
                            <div class="postbox">
                                <h2 class="hndle"><span><?php _e( 'Statistics', 'lens-zone' ); ?></span></h2>
                                <div class="inside">
                                    <p><?php _e( 'Total Lens Categories:', 'lens-zone' ); ?> <strong><?php echo $total_lens_categories; ?></strong></p>
                                    <p><?php _e( 'Total Lens Sub-Categories:', 'lens-zone' ); ?> <strong><?php echo $total_lens_sub_categories; ?></strong></p>
                                </div>
                            </div>
                            <div class="postbox">
                                <h2 class="hndle"><span><?php _e( 'Shortcode', 'lens-zone' ); ?></span></h2>
                                <div class="inside">
                                    <p><?php _e( 'Use the following shortcode to display the lens selection flow on a product page:', 'lens-zone' ); ?></p>
                                    <input type="text" value="[select_lens_flow]" readonly="readonly" class="large-text" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


    public function register_settings() {
        register_setting( 'lens-zone-power-details', 'lens_zone_sph_values' );
        register_setting( 'lens-zone-power-details', 'lens_zone_cyl_values' );
        register_setting( 'lens-zone-power-details', 'lens_zone_additional_power_values' );
        register_setting( 'lens-zone-power-details', 'lens_zone_additional_power_categories' );
        register_setting( 'lens-zone-power-details', 'lens_zone_support_phone' );
        
        // Frame size label settings
        register_setting( 'lens-zone-power-details', 'lens_zone_size_label' );
        register_setting( 'lens-zone-power-details', 'lens_zone_lens_width_label' );
        register_setting( 'lens-zone-power-details', 'lens_zone_lens_width_icon' );
        register_setting( 'lens-zone-power-details', 'lens_zone_bridge_width_label' );
        register_setting( 'lens-zone-power-details', 'lens_zone_bridge_width_icon' );
        register_setting( 'lens-zone-power-details', 'lens_zone_temple_length_label' );
        register_setting( 'lens-zone-power-details', 'lens_zone_temple_length_icon' );
    }

    public function power_details_page() {
        global $wpdb;
        
        // Get all active categories for dropdown
        $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE status = 'active' ORDER BY name ASC" );
        $selected_categories = get_option( 'lens_zone_additional_power_categories', array() );
        if ( ! is_array( $selected_categories ) ) {
            $selected_categories = array();
        }
        ?>
        <div class="wrap">
            <h1><?php _e( 'Change Details', 'lens-zone' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'lens-zone-power-details' );
                do_settings_sections( 'lens-zone-power-details' );
                ?>
                
                <!-- Frame Size Labels Section -->
                <h2><?php _e( 'Frame Size Labels & Icons', 'lens-zone' ); ?></h2>
                <p class="description" style="margin-bottom: 20px;"><?php _e( 'Configure the labels and icons that will be displayed on product pages for frame size specifications.', 'lens-zone' ); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="lens_zone_size_label"><?php _e( 'Size Label', 'lens-zone' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="lens_zone_size_label" name="lens_zone_size_label" value="<?php echo esc_attr( get_option( 'lens_zone_size_label', 'SIZE' ) ); ?>" class="regular-text" placeholder="SIZE">
                            <p class="description"><?php _e( 'Label shown before size name (e.g., "SIZE : Medium")', 'lens-zone' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="lens_zone_lens_width_label"><?php _e( 'Lens Width Label', 'lens-zone' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="lens_zone_lens_width_label" name="lens_zone_lens_width_label" value="<?php echo esc_attr( get_option( 'lens_zone_lens_width_label', 'LENS WIDTH' ) ); ?>" class="regular-text" placeholder="LENS WIDTH">
                            <p class="description"><?php _e( 'Label for lens width measurement', 'lens-zone' ); ?></p>
                            
                            <div style="margin-top: 15px; padding: 15px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
                                <strong style="display: block; margin-bottom: 10px; color: #1d2327; font-size: 13px;">
                                    <span class="dashicons dashicons-format-image" style="color: #2271b1; vertical-align: middle;"></span>
                                    <?php _e( 'Icon Image', 'lens-zone' ); ?>
                                </strong>
                                <div id="lens-width-icon-preview" style="margin-bottom: 10px; min-height: 60px; display: flex; align-items: center; justify-content: center; background: white; border: 1px dashed #c3c4c7; border-radius: 4px; padding: 10px;">
                                    <?php 
                                    $lens_width_icon = get_option( 'lens_zone_lens_width_icon' );
                                    if ( $lens_width_icon ): 
                                    ?>
                                        <?php echo wp_get_attachment_image( $lens_width_icon, 'thumbnail', false, array( 'style' => 'max-width: 120px; max-height: 60px;' ) ); ?>
                                    <?php else: ?>
                                        <span style="color: #8c8f94; font-size: 13px;"><?php _e( 'No icon uploaded', 'lens-zone' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="lens_zone_lens_width_icon" name="lens_zone_lens_width_icon" value="<?php echo esc_attr( $lens_width_icon ); ?>">
                                <button type="button" class="button upload-frame-icon-btn" data-target="lens_zone_lens_width_icon" style="margin-right: 8px;">
                                    <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                                    <?php _e( 'Upload Icon', 'lens-zone' ); ?>
                                </button>
                                <button type="button" class="button remove-frame-icon-btn" data-target="lens_zone_lens_width_icon">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                    <?php _e( 'Remove Icon', 'lens-zone' ); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="lens_zone_bridge_width_label"><?php _e( 'Bridge Width Label', 'lens-zone' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="lens_zone_bridge_width_label" name="lens_zone_bridge_width_label" value="<?php echo esc_attr( get_option( 'lens_zone_bridge_width_label', 'BRIDGE WIDTH' ) ); ?>" class="regular-text" placeholder="BRIDGE WIDTH">
                            <p class="description"><?php _e( 'Label for bridge width measurement', 'lens-zone' ); ?></p>
                            
                            <div style="margin-top: 15px; padding: 15px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
                                <strong style="display: block; margin-bottom: 10px; color: #1d2327; font-size: 13px;">
                                    <span class="dashicons dashicons-format-image" style="color: #2271b1; vertical-align: middle;"></span>
                                    <?php _e( 'Icon Image', 'lens-zone' ); ?>
                                </strong>
                                <div id="bridge-width-icon-preview" style="margin-bottom: 10px; min-height: 60px; display: flex; align-items: center; justify-content: center; background: white; border: 1px dashed #c3c4c7; border-radius: 4px; padding: 10px;">
                                    <?php 
                                    $bridge_width_icon = get_option( 'lens_zone_bridge_width_icon' );
                                    if ( $bridge_width_icon ): 
                                    ?>
                                        <?php echo wp_get_attachment_image( $bridge_width_icon, 'thumbnail', false, array( 'style' => 'max-width: 120px; max-height: 60px;' ) ); ?>
                                    <?php else: ?>
                                        <span style="color: #8c8f94; font-size: 13px;"><?php _e( 'No icon uploaded', 'lens-zone' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="lens_zone_bridge_width_icon" name="lens_zone_bridge_width_icon" value="<?php echo esc_attr( $bridge_width_icon ); ?>">
                                <button type="button" class="button upload-frame-icon-btn" data-target="lens_zone_bridge_width_icon" style="margin-right: 8px;">
                                    <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                                    <?php _e( 'Upload Icon', 'lens-zone' ); ?>
                                </button>
                                <button type="button" class="button remove-frame-icon-btn" data-target="lens_zone_bridge_width_icon">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                    <?php _e( 'Remove Icon', 'lens-zone' ); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="lens_zone_temple_length_label"><?php _e( 'Temple Length Label', 'lens-zone' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="lens_zone_temple_length_label" name="lens_zone_temple_length_label" value="<?php echo esc_attr( get_option( 'lens_zone_temple_length_label', 'TEMPLE LENGTH' ) ); ?>" class="regular-text" placeholder="TEMPLE LENGTH">
                            <p class="description"><?php _e( 'Label for temple length measurement', 'lens-zone' ); ?></p>
                            
                            <div style="margin-top: 15px; padding: 15px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
                                <strong style="display: block; margin-bottom: 10px; color: #1d2327; font-size: 13px;">
                                    <span class="dashicons dashicons-format-image" style="color: #2271b1; vertical-align: middle;"></span>
                                    <?php _e( 'Icon Image', 'lens-zone' ); ?>
                                </strong>
                                <div id="temple-length-icon-preview" style="margin-bottom: 10px; min-height: 60px; display: flex; align-items: center; justify-content: center; background: white; border: 1px dashed #c3c4c7; border-radius: 4px; padding: 10px;">
                                    <?php 
                                    $temple_length_icon = get_option( 'lens_zone_temple_length_icon' );
                                    if ( $temple_length_icon ): 
                                    ?>
                                        <?php echo wp_get_attachment_image( $temple_length_icon, 'thumbnail', false, array( 'style' => 'max-width: 120px; max-height: 60px;' ) ); ?>
                                    <?php else: ?>
                                        <span style="color: #8c8f94; font-size: 13px;"><?php _e( 'No icon uploaded', 'lens-zone' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="lens_zone_temple_length_icon" name="lens_zone_temple_length_icon" value="<?php echo esc_attr( $temple_length_icon ); ?>">
                                <button type="button" class="button upload-frame-icon-btn" data-target="lens_zone_temple_length_icon" style="margin-right: 8px;">
                                    <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                                    <?php _e( 'Upload Icon', 'lens-zone' ); ?>
                                </button>
                                <button type="button" class="button remove-frame-icon-btn" data-target="lens_zone_temple_length_icon">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                    <?php _e( 'Remove Icon', 'lens-zone' ); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <hr style="margin: 40px 0;">
                
                <h2><?php _e( 'SPH Values', 'lens-zone' ); ?></h2>
                <textarea name="lens_zone_sph_values" rows="10" cols="50"><?php echo esc_textarea( get_option( 'lens_zone_sph_values' ) ); ?></textarea>
                <p class="description"><?php _e( 'Enter each SPH value on a new line.', 'lens-zone' ); ?></p>

                <h2><?php _e( 'CYL Values', 'lens-zone' ); ?></h2>
                <textarea name="lens_zone_cyl_values" rows="10" cols="50"><?php echo esc_textarea( get_option( 'lens_zone_cyl_values' ) ); ?></textarea>
                <p class="description"><?php _e( 'Enter each CYL value on a new line.', 'lens-zone' ); ?></p>

                <h2><?php _e( 'Additional Power Values', 'lens-zone' ); ?></h2>
                <textarea name="lens_zone_additional_power_values" rows="10" cols="50"><?php echo esc_textarea( get_option( 'lens_zone_additional_power_values' ) ); ?></textarea>
                <p class="description"><?php _e( 'Enter each Additional Power value on a new line.', 'lens-zone' ); ?></p>

                <h2><?php _e( 'Assign Additional Power to Categories', 'lens-zone' ); ?></h2>
                <p class="description" style="margin-bottom: 10px;"><?php _e( 'Select categories where Additional Power field should be shown in manual power entry form.', 'lens-zone' ); ?></p>
                <?php if ( ! empty( $categories ) ): ?>
                    <?php foreach ( $categories as $category ): ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="lens_zone_additional_power_categories[]" value="<?php echo $category->id; ?>" <?php checked( in_array( $category->id, $selected_categories ) ); ?>>
                            <?php echo esc_html( $category->name ); ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;"><?php _e( 'No categories available. Please create lens categories first.', 'lens-zone' ); ?></p>
                <?php endif; ?>

                <h2 style="margin-top: 30px;"><?php _e( 'Support Phone Number', 'lens-zone' ); ?></h2>
                <input type="text" name="lens_zone_support_phone" value="<?php echo esc_attr( get_option( 'lens_zone_support_phone' ) ); ?>" />

                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // Upload icon
            $('.upload-frame-icon-btn').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var targetId = $button.data('target');
                var $input = $('#' + targetId);
                var $preview = $input.prev('div');
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e( 'Choose Icon', 'lens-zone' ); ?>',
                    button: {
                        text: '<?php _e( 'Use this icon', 'lens-zone' ); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    $preview.html('<img src="' + attachment.url + '" style="max-width: 120px; max-height: 60px;">');
                });
                
                mediaUploader.open();
            });
            
            // Remove icon
            $('.remove-frame-icon-btn').on('click', function(e) {
                e.preventDefault();
                var targetId = $(this).data('target');
                var $input = $('#' + targetId);
                var $preview = $input.prev('div');
                
                $input.val('');
                $preview.html('');
            });
        });
        </script>
        <?php
    }

    public function database_page() {
        ?>  
        <div class="wrap">
            <h1><?php _e( 'Database', 'lens-zone' ); ?></h1>
            
            <div class="card">
                <h2><?php _e( 'Create Database Tables', 'lens-zone' ); ?></h2>
                <p><?php _e( 'Click the button below to create the required database tables for the plugin.', 'lens-zone' ); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'lens_zone_create_tables', 'lens_zone_create_tables_nonce' ); ?>
                    <input type="hidden" name="lens_zone_action" value="create_tables">
                    <button type="submit" class="button button-primary"><?php _e( 'Create DB Tables', 'lens-zone' ); ?></button>
                </form>
                <form method="post" action="" style="margin-top: 10px;" id="recreate-tables-form">
                    <?php wp_nonce_field( 'lens_zone_recreate_tables', 'lens_zone_recreate_tables_nonce' ); ?>
                    <input type="hidden" name="lens_zone_action" value="recreate_tables">
                    <button type="button" class="button button-secondary" id="recreate-tables-btn"><?php _e( 'Drop & Recreate Tables', 'lens-zone' ); ?></button>
                </form>
                <script>
                jQuery(document).ready(function($) {
                    $('#recreate-tables-btn').on('click', function(e) {
                        e.preventDefault();
                        lensZoneConfirm(
                            '<?php _e( 'This will delete all existing data and recreate tables. Are you sure?', 'lens-zone' ); ?>',
                            function(confirmed) {
                                if (confirmed) {
                                    $('#recreate-tables-form').submit();
                                }
                            },
                            '<?php _e( 'Confirm Action', 'lens-zone' ); ?>'
                        );
                    });
                });
                </script>
                <?php 
                if ( isset( $_POST['lens_zone_action'] ) && $_POST['lens_zone_action'] === 'create_tables' ) {
                    if ( ! isset( $_POST['lens_zone_create_tables_nonce'] ) || ! wp_verify_nonce( $_POST['lens_zone_create_tables_nonce'], 'lens_zone_create_tables' ) ) {
                        echo '<div class="notice notice-error"><p>' . __( 'Security check failed.', 'lens-zone' ) . '</p></div>';
                    } else {
                        $db = new Lens_Zone_DB();
                        $result = $db->create_tables();
                        echo '<div class="notice notice-success"><p>' . __( 'Database tables created successfully.', 'lens-zone' ) . '</p></div>';
                    }
                }
                
                if ( isset( $_POST['lens_zone_action'] ) && $_POST['lens_zone_action'] === 'recreate_tables' ) {
                    if ( ! isset( $_POST['lens_zone_recreate_tables_nonce'] ) || ! wp_verify_nonce( $_POST['lens_zone_recreate_tables_nonce'], 'lens_zone_recreate_tables' ) ) {
                        echo '<div class="notice notice-error"><p>' . __( 'Security check failed.', 'lens-zone' ) . '</p></div>';
                    } else {
                        $db = new Lens_Zone_DB();
                        $db->drop_tables();
                        $db->create_tables();
                        echo '<div class="notice notice-success"><p>' . __( 'Database tables recreated successfully.', 'lens-zone' ) . '</p></div>';
                    }
                }
                ?>
            </div>
            
            <div class="card">
                <h2><?php _e( 'Check Database Tables', 'lens-zone' ); ?></h2>
                <p><?php _e( 'Click the button below to check if the required database tables exist.', 'lens-zone' ); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field( 'lens_zone_check_tables', 'lens_zone_check_tables_nonce' ); ?>
                    <input type="hidden" name="lens_zone_action" value="check_tables">
                    <button type="submit" class="button button-secondary"><?php _e( 'Check DB Tables', 'lens-zone' ); ?></button>
                </form>
                <?php 
                if ( isset( $_POST['lens_zone_action'] ) && $_POST['lens_zone_action'] === 'check_tables' ) {
                    if ( ! isset( $_POST['lens_zone_check_tables_nonce'] ) || ! wp_verify_nonce( $_POST['lens_zone_check_tables_nonce'], 'lens_zone_check_tables' ) ) {
                        echo '<div class="notice notice-error"><p>' . __( 'Security check failed.', 'lens-zone' ) . '</p></div>';
                    } else {
                        $db = new Lens_Zone_DB();
                        $tables_exist = $db->check_tables_exist();
                        
                        if ( $tables_exist ) {
                            echo '<div class="notice notice-success"><p>' . __( 'Status: Already Available', 'lens-zone' ) . '</p></div>';
                        } else {
                            echo '<div class="notice notice-warning"><p>' . __( 'Status: Not Available. Please create it.', 'lens-zone' ) . '</p></div>';
                        }
                    }
                }
                ?>
            </div>
            
            <div class="card">
                <h2><?php _e( 'Database Information', 'lens-zone' ); ?></h2>
                <?php
                $db = new Lens_Zone_DB();
                $db_size = $db->get_db_size();
                ?>
                <p><?php _e( 'Plugin DB Size:', 'lens-zone' ); ?> <strong><?php echo $db_size; ?></strong></p>
            </div>
        </div>
        <?php
    }
    
    public function lens_categories_page() {
        global $wpdb;
        
        // Handle actions
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            $id = intval( $_GET['id'] );
            $wpdb->update( 
                $wpdb->prefix . 'lens_categories', 
                array( 'status' => 'trash' ), 
                array( 'id' => $id ) 
            );
            echo '<div class="notice notice-success"><p>' . __( 'Category moved to trash.', 'lens-zone' ) . '</p></div>';
        }
        
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'empty_trash' ) {
            $wpdb->delete( $wpdb->prefix . 'lens_categories', array( 'status' => 'trash' ) );
            echo '<div class="notice notice-success"><p>' . __( 'Trash emptied.', 'lens-zone' ) . '</p></div>';
        }
        
        // Get filter
        $status = isset( $_GET['status'] ) ? $_GET['status'] : 'active';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Pagination
        $per_page = 10;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $page - 1 ) * $per_page;
        
        // Query
        $where = "WHERE status = '$status'";
        if ( $search ) {
            $where .= $wpdb->prepare( " AND name LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
        }
        
        $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_categories $where" );
        $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lens_categories $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset" );
        
        $total_pages = ceil( $total_items / $per_page );
        
        // Count for tabs
        $active_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_categories WHERE status = 'active'" );
        $trash_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_categories WHERE status = 'trash'" );
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Lens Categories', 'lens-zone' ); ?></h1>
            <a href="<?php echo admin_url( 'admin.php?page=lens-zone-add-category' ); ?>" class="page-title-action"><?php _e( 'Add New', 'lens-zone' ); ?></a>
            
            <form method="get">
                <input type="hidden" name="page" value="lens-zone-categories">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php _e( 'Search categories...', 'lens-zone' ); ?>">
                    <input type="submit" class="button" value="<?php _e( 'Search', 'lens-zone' ); ?>">
                </p>
            </form>
            
            <ul class="subsubsub">
                <li><a href="?page=lens-zone-categories&status=active" <?php echo $status === 'active' ? 'class="current"' : ''; ?>><?php _e( 'All', 'lens-zone' ); ?> (<?php echo $active_count; ?>)</a> |</li>
                <li><a href="?page=lens-zone-categories&status=trash" <?php echo $status === 'trash' ? 'class="current"' : ''; ?>><?php _e( 'Trash', 'lens-zone' ); ?> (<?php echo $trash_count; ?>)</a></li>
            </ul>
            
            <?php if ( $status === 'trash' && $trash_count > 0 ): ?>
                <a href="#" class="button" id="empty-trash-categories-btn" data-url="?page=lens-zone-categories&action=empty_trash"><?php _e( 'Empty Trash', 'lens-zone' ); ?></a>
                <script>
                jQuery(document).ready(function($) {
                    $('#empty-trash-categories-btn').on('click', function(e) {
                        e.preventDefault();
                        var url = $(this).data('url');
                        lensZoneConfirm(
                            '<?php _e( 'Are you sure you want to permanently delete all items in trash?', 'lens-zone' ); ?>',
                            function(confirmed) {
                                if (confirmed) {
                                    window.location.href = url;
                                }
                            },
                            '<?php _e( 'Empty Trash', 'lens-zone' ); ?>'
                        );
                    });
                });
                </script>
            <?php endif; ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Image', 'lens-zone' ); ?></th>
                        <th><?php _e( 'Name', 'lens-zone' ); ?></th>
                        <th><?php _e( 'Date', 'lens-zone' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $categories ): ?>
                        <?php foreach ( $categories as $category ): ?>
                            <tr>
                                <td>
                                    <?php if ( $category->image_id ): ?>
                                        <?php echo wp_get_attachment_image( $category->image_id, array( 50, 50 ) ); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-format-image" style="font-size: 50px; color: #ddd;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $category->name ); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="<?php echo admin_url( 'admin.php?page=lens-zone-edit-category&id=' . $category->id ); ?>"><?php _e( 'Edit', 'lens-zone' ); ?></a> | </span>
                                        <span class="trash"><a href="#" class="trash-category-link" data-url="<?php echo admin_url( 'admin.php?page=lens-zone-categories&action=delete&id=' . $category->id ); ?>"><?php _e( 'Trash', 'lens-zone' ); ?></a></span>
                                    </div>
                                </td>
                                <td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $category->created_at ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3"><?php _e( 'No categories found.', 'lens-zone' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ( $total_pages > 1 ): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base' => add_query_arg( 'paged', '%#%' ),
                            'format' => '',
                            'prev_text' => __( '&laquo;' ),
                            'next_text' => __( '&raquo;' ),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle trash category links
            $('.trash-category-link').on('click', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                lensZoneConfirm(
                    '<?php _e( 'Are you sure you want to move this category to trash?', 'lens-zone' ); ?>',
                    function(confirmed) {
                        if (confirmed) {
                            window.location.href = url;
                        }
                    },
                    '<?php _e( 'Move to Trash', 'lens-zone' ); ?>'
                );
            });
        });
        </script>
        <?php
    }
    
    public function add_edit_category_page() {
        global $wpdb;
        
        $is_edit = isset( $_GET['id'] );
        $category = null;
        
        if ( $is_edit ) {
            $id = intval( $_GET['id'] );
            $category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE id = %d", $id ) );
        }
        
        // Handle form submission
        if ( isset( $_POST['lens_zone_save_category'] ) ) {
            if ( ! isset( $_POST['lens_zone_category_nonce'] ) || ! wp_verify_nonce( $_POST['lens_zone_category_nonce'], 'lens_zone_save_category' ) ) {
                wp_die( __( 'Security check failed.', 'lens-zone' ) );
            }
            
            $name = sanitize_text_field( $_POST['category_name'] );
            $description = wp_kses_post( $_POST['category_description'] );
            $image_id = intval( $_POST['category_image_id'] );
            
            $data = array(
                'name' => $name,
                'description' => $description,
                'image_id' => $image_id,
            );
            
            if ( $is_edit ) {
                $result = $wpdb->update( $wpdb->prefix . 'lens_categories', $data, array( 'id' => $id ) );
                if ( $result === false ) {
                    echo '<div class="notice notice-error"><p>' . __( 'Error updating category: ', 'lens-zone' ) . $wpdb->last_error . '</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>' . __( 'Category updated successfully.', 'lens-zone' ) . '</p></div>';
                    // Reload fresh data from database
                    $category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE id = %d", $id ) );
                }
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $data['status'] = 'active';
                $result = $wpdb->insert( $wpdb->prefix . 'lens_categories', $data );
                if ( $result === false ) {
                    echo '<div class="notice notice-error"><p>' . __( 'Error adding category: ', 'lens-zone' ) . $wpdb->last_error . '</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>' . __( 'Category added successfully.', 'lens-zone' ) . '</p></div>';
                    $id = $wpdb->insert_id;
                    $category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE id = %d", $id ) );
                    $is_edit = true;
                }
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __( 'Edit Lens Category', 'lens-zone' ) : __( 'Add New Lens Category', 'lens-zone' ); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'lens_zone_save_category', 'lens_zone_category_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="category_name"><?php _e( 'Name', 'lens-zone' ); ?> *</label></th>
                        <td><input type="text" id="category_name" name="category_name" value="<?php echo $category ? esc_attr( $category->name ) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="category_description"><?php _e( 'Description', 'lens-zone' ); ?></label></th>
                        <td>
                            <?php
                            wp_editor( 
                                $category ? $category->description : '', 
                                'category_description',
                                array( 'textarea_rows' => 10 )
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Image', 'lens-zone' ); ?></label></th>
                        <td>
                            <div id="category-image-preview">
                                <?php if ( $category && $category->image_id ): ?>
                                    <?php echo wp_get_attachment_image( $category->image_id, 'medium' ); ?>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" id="category_image_id" name="category_image_id" value="<?php echo $category ? esc_attr( $category->image_id ) : ''; ?>">
                            <button type="button" class="button" id="upload_category_image_button"><?php _e( 'Upload Image', 'lens-zone' ); ?></button>
                            <button type="button" class="button" id="remove_category_image_button"><?php _e( 'Remove Image', 'lens-zone' ); ?></button>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="lens_zone_save_category" class="button button-primary" value="<?php echo $is_edit ? __( 'Update', 'lens-zone' ) : __( 'Save', 'lens-zone' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=lens-zone-categories' ); ?>" class="button"><?php _e( 'Cancel', 'lens-zone' ); ?></a>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#upload_category_image_button').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e( 'Choose Image', 'lens-zone' ); ?>',
                    button: {
                        text: '<?php _e( 'Use this image', 'lens-zone' ); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#category_image_id').val(attachment.id);
                    $('#category-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px;">');
                });
                
                mediaUploader.open();
            });
            
            $('#remove_category_image_button').click(function(e) {
                e.preventDefault();
                $('#category_image_id').val('');
                $('#category-image-preview').html('');
            });
        });
        </script>
        <?php
    }
    
    public function add_edit_sub_category_page() {
        global $wpdb;
        
        $is_edit = isset( $_GET['id'] );
        $sub_category = null;
        $points = array();
        
        if ( $is_edit ) {
            $id = intval( $_GET['id'] );
            $sub_category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_sub_categories WHERE id = %d", $id ) );
            $points = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_sub_category_points WHERE sub_category_id = %d", $id ) );
        }
        
        // Handle form submission
        if ( isset( $_POST['lens_zone_save_sub_category'] ) ) {
            if ( ! isset( $_POST['lens_zone_sub_category_nonce'] ) || ! wp_verify_nonce( $_POST['lens_zone_sub_category_nonce'], 'lens_zone_save_sub_category' ) ) {
                wp_die( __( 'Security check failed.', 'lens-zone' ) );
            }
            
            $name = sanitize_text_field( $_POST['sub_category_name'] );
            $category_id = intval( $_POST['parent_category_id'] );
            $image_id = intval( $_POST['sub_category_image_id'] );
            $price = floatval( $_POST['sub_category_price'] );
            $point_texts = isset( $_POST['points'] ) ? $_POST['points'] : array();
            
            $data = array(
                'name' => $name,
                'category_id' => $category_id,
                'image_id' => $image_id,
                'price' => $price,
            );
            
            if ( $is_edit ) {
                $wpdb->update( $wpdb->prefix . 'lens_sub_categories', $data, array( 'id' => $id ) );
                // Delete old points
                $wpdb->delete( $wpdb->prefix . 'lens_sub_category_points', array( 'sub_category_id' => $id ) );
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $data['status'] = 'active';
                $wpdb->insert( $wpdb->prefix . 'lens_sub_categories', $data );
                $id = $wpdb->insert_id;
            }
            
            // Insert points
            foreach ( $point_texts as $point_text ) {
                if ( ! empty( $point_text ) ) {
                    $wpdb->insert( 
                        $wpdb->prefix . 'lens_sub_category_points',
                        array(
                            'sub_category_id' => $id,
                            'point_text' => sanitize_text_field( $point_text )
                        )
                    );
                }
            }
            
            echo '<div class="notice notice-success"><p>' . ( $is_edit ? __( 'Sub-category updated successfully.', 'lens-zone' ) : __( 'Sub-category added successfully.', 'lens-zone' ) ) . '</p></div>';
            
            if ( ! $is_edit ) {
                $sub_category = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_sub_categories WHERE id = %d", $id ) );
                $points = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lens_sub_category_points WHERE sub_category_id = %d", $id ) );
                $is_edit = true;
            }
        }
        
        // Get all categories for dropdown
        $categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lens_categories WHERE status = 'active' ORDER BY name ASC" );
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __( 'Edit Lens Sub-Category', 'lens-zone' ) : __( 'Add New Lens Sub-Category', 'lens-zone' ); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'lens_zone_save_sub_category', 'lens_zone_sub_category_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="sub_category_name"><?php _e( 'Name', 'lens-zone' ); ?> *</label></th>
                        <td><input type="text" id="sub_category_name" name="sub_category_name" value="<?php echo $sub_category ? esc_attr( $sub_category->name ) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="parent_category_id"><?php _e( 'Parent Lens Category', 'lens-zone' ); ?> *</label></th>
                        <td>
                            <select id="parent_category_id" name="parent_category_id" required>
                                <option value=""><?php _e( 'Select a Category', 'lens-zone' ); ?></option>
                                <?php foreach ( $categories as $cat ): ?>
                                    <option value="<?php echo $cat->id; ?>" <?php selected( $sub_category ? $sub_category->category_id : '', $cat->id ); ?>><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Image', 'lens-zone' ); ?></label></th>
                        <td>
                            <div id="sub-category-image-preview">
                                <?php if ( $sub_category && $sub_category->image_id ): ?>
                                    <?php echo wp_get_attachment_image( $sub_category->image_id, 'medium' ); ?>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" id="sub_category_image_id" name="sub_category_image_id" value="<?php echo $sub_category ? esc_attr( $sub_category->image_id ) : ''; ?>">
                            <button type="button" class="button" id="upload_sub_category_image_button"><?php _e( 'Upload Image', 'lens-zone' ); ?></button>
                            <button type="button" class="button" id="remove_sub_category_image_button"><?php _e( 'Remove Image', 'lens-zone' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Points', 'lens-zone' ); ?></label></th>
                        <td>
                            <div id="points-container">
                                <?php if ( $points ): ?>
                                    <?php foreach ( $points as $point ): ?>
                                        <div class="point-row" style="margin-bottom: 10px;">
                                            <input type="text" name="points[]" value="<?php echo esc_attr( $point->point_text ); ?>" class="regular-text">
                                            <button type="button" class="button remove-point"><?php _e( 'Remove', 'lens-zone' ); ?></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="point-row" style="margin-bottom: 10px;">
                                        <input type="text" name="points[]" value="" class="regular-text">
                                        <button type="button" class="button remove-point"><?php _e( 'Remove', 'lens-zone' ); ?></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button" id="add-more-points"><?php _e( 'Add More', 'lens-zone' ); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sub_category_price"><?php _e( 'Price', 'lens-zone' ); ?> *</label></th>
                        <td><input type="number" step="0.01" id="sub_category_price" name="sub_category_price" value="<?php echo $sub_category ? esc_attr( $sub_category->price ) : ''; ?>" class="regular-text" required></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="lens_zone_save_sub_category" class="button button-primary" value="<?php echo $is_edit ? __( 'Update', 'lens-zone' ) : __( 'Save', 'lens-zone' ); ?>">
                    <a href="<?php echo admin_url( 'admin.php?page=lens-zone-sub-categories' ); ?>" class="button"><?php _e( 'Cancel', 'lens-zone' ); ?></a>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Media uploader
            var mediaUploader;
            
            $('#upload_sub_category_image_button').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e( 'Choose Image', 'lens-zone' ); ?>',
                    button: {
                        text: '<?php _e( 'Use this image', 'lens-zone' ); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#sub_category_image_id').val(attachment.id);
                    $('#sub-category-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px;">');
                });
                
                mediaUploader.open();
            });
            
            $('#remove_sub_category_image_button').click(function(e) {
                e.preventDefault();
                $('#sub_category_image_id').val('');
                $('#sub-category-image-preview').html('');
            });
            
            // Add more points
            $('#add-more-points').click(function(e) {
                e.preventDefault();
                $('#points-container').append('<div class="point-row" style="margin-bottom: 10px;"><input type="text" name="points[]" value="" class="regular-text"><button type="button" class="button remove-point"><?php _e( 'Remove', 'lens-zone' ); ?></button></div>');
            });
            
            // Remove point
            $(document).on('click', '.remove-point', function(e) {
                e.preventDefault();
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }
    
    public function lens_sub_categories_page() {
        global $wpdb;
        
        // Handle actions
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            $id = intval( $_GET['id'] );
            $wpdb->update( 
                $wpdb->prefix . 'lens_sub_categories', 
                array( 'status' => 'trash' ), 
                array( 'id' => $id ) 
            );
            echo '<div class="notice notice-success"><p>' . __( 'Sub-category moved to trash.', 'lens-zone' ) . '</p></div>';
        }
        
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'empty_trash' ) {
            $wpdb->delete( $wpdb->prefix . 'lens_sub_categories', array( 'status' => 'trash' ) );
            echo '<div class="notice notice-success"><p>' . __( 'Trash emptied.', 'lens-zone' ) . '</p></div>';
        }
        
        // Get filter
        $status = isset( $_GET['status'] ) ? $_GET['status'] : 'active';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Pagination
        $per_page = 10;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $page - 1 ) * $per_page;
        
        // Query
        $where = "WHERE status = '$status'";
        if ( $search ) {
            $where .= $wpdb->prepare( " AND name LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
        }
        
        $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_sub_categories $where" );
        $sub_categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lens_sub_categories $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset" );
        
        $total_pages = ceil( $total_items / $per_page );
        
        // Count for tabs
        $active_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_sub_categories WHERE status = 'active'" );
        $trash_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lens_sub_categories WHERE status = 'trash'" );
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Lens Sub-Categories', 'lens-zone' ); ?></h1>
            <a href="<?php echo admin_url( 'admin.php?page=lens-zone-add-sub-category' ); ?>" class="page-title-action"><?php _e( 'Add New', 'lens-zone' ); ?></a>
            
            <form method="get">
                <input type="hidden" name="page" value="lens-zone-sub-categories">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php _e( 'Search sub-categories...', 'lens-zone' ); ?>">
                    <input type="submit" class="button" value="<?php _e( 'Search', 'lens-zone' ); ?>">
                </p>
            </form>
            
            <ul class="subsubsub">
                <li><a href="?page=lens-zone-sub-categories&status=active" <?php echo $status === 'active' ? 'class="current"' : ''; ?>><?php _e( 'All', 'lens-zone' ); ?> (<?php echo $active_count; ?>)</a> |</li>
                <li><a href="?page=lens-zone-sub-categories&status=trash" <?php echo $status === 'trash' ? 'class="current"' : ''; ?>><?php _e( 'Trash', 'lens-zone' ); ?> (<?php echo $trash_count; ?>)</a></li>
            </ul>
            
            <?php if ( $status === 'trash' && $trash_count > 0 ): ?>
                <a href="#" class="button" id="empty-trash-subcategories-btn" data-url="?page=lens-zone-sub-categories&action=empty_trash"><?php _e( 'Empty Trash', 'lens-zone' ); ?></a>
                <script>
                jQuery(document).ready(function($) {
                    $('#empty-trash-subcategories-btn').on('click', function(e) {
                        e.preventDefault();
                        var url = $(this).data('url');
                        lensZoneConfirm(
                            '<?php _e( 'Are you sure you want to permanently delete all items in trash?', 'lens-zone' ); ?>',
                            function(confirmed) {
                                if (confirmed) {
                                    window.location.href = url;
                                }
                            },
                            '<?php _e( 'Empty Trash', 'lens-zone' ); ?>'
                        );
                    });
                });
                </script>
            <?php endif; ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Image', 'lens-zone' ); ?></th>
                        <th><?php _e( 'Name', 'lens-zone' ); ?></th>
                        <th><?php _e( 'Parent Category', 'lens-zone' ); ?></th>
                        <th><?php _e( 'Date', 'lens-zone' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $sub_categories ): ?>
                        <?php foreach ( $sub_categories as $sub_category ): ?>
                            <?php
                            // Get parent category name
                            $parent_category = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}lens_categories WHERE id = %d", $sub_category->category_id ) );
                            $parent_name = $parent_category ? $parent_category->name : '-';
                            ?>
                            <tr>
                                <td>
                                    <?php if ( $sub_category->image_id ): ?>
                                        <?php echo wp_get_attachment_image( $sub_category->image_id, array( 50, 50 ) ); ?>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-format-image" style="font-size: 50px; color: #ddd;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( $sub_category->name ); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="<?php echo admin_url( 'admin.php?page=lens-zone-edit-sub-category&id=' . $sub_category->id ); ?>"><?php _e( 'Edit', 'lens-zone' ); ?></a> | </span>
                                        <span class="trash"><a href="#" class="trash-subcategory-link" data-url="<?php echo admin_url( 'admin.php?page=lens-zone-sub-categories&action=delete&id=' . $sub_category->id ); ?>"><?php _e( 'Trash', 'lens-zone' ); ?></a></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $parent_name ); ?></td>
                                <td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $sub_category->created_at ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php _e( 'No sub-categories found.', 'lens-zone' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ( $total_pages > 1 ): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base' => add_query_arg( 'paged', '%#%' ),
                            'format' => '',
                            'prev_text' => __( '&laquo;' ),
                            'next_text' => __( '&raquo;' ),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle trash subcategory links
            $('.trash-subcategory-link').on('click', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                lensZoneConfirm(
                    '<?php _e( 'Are you sure you want to move this sub-category to trash?', 'lens-zone' ); ?>',
                    function(confirmed) {
                        if (confirmed) {
                            window.location.href = url;
                        }
                    },
                    '<?php _e( 'Move to Trash', 'lens-zone' ); ?>'
                );
            });
        });
        </script>
        <?php
    }
    
    // Add admin chat to order details
    public function add_admin_order_chat( $order ) {
        // Only show for processing and on-hold orders
        if ( ! in_array( $order->get_status(), array( 'processing', 'on-hold' ) ) ) {
            return;
        }
        
        $order_id = $order->get_id();
        ?>
        <div class="lens-zone-admin-chat" style="margin-top: 20px;">
            <h3><?php _e( 'Customer Communication', 'lens-zone' ); ?></h3>
            <div class="chat-messages" id="admin-chat-messages-<?php echo $order_id; ?>" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin-bottom: 15px;">
                <?php $this->display_admin_order_messages( $order_id ); ?>
            </div>
            <div class="chat-input-area">
                <textarea id="admin-chat-message-<?php echo $order_id; ?>" placeholder="<?php _e( 'Type your message...', 'lens-zone' ); ?>" style="width: 100%; min-height: 80px; padding: 10px; margin-bottom: 10px;"></textarea>
                <div class="chat-actions">
                    <button class="button admin-send-message-btn" data-order-id="<?php echo $order_id; ?>">
                        <?php _e( 'Send Message', 'lens-zone' ); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var orderId = <?php echo $order_id; ?>;
            
            // Send message
            $('.admin-send-message-btn[data-order-id="' + orderId + '"]').on('click', function() {
                var message = $('#admin-chat-message-' + orderId).val().trim();
                
                if (!message) {
                    lensZoneAlert('<?php _e( 'Please enter a message', 'lens-zone' ); ?>', 'warning', '<?php _e( 'Message Required', 'lens-zone' ); ?>');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'admin_send_order_message',
                        order_id: orderId,
                        message: message
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#admin-chat-message-' + orderId).val('');
                            loadMessages();
                        }
                    }
                });
            });
            
            // Load messages
            function loadMessages() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'admin_get_order_messages',
                        order_id: orderId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#admin-chat-messages-' + orderId).html(response.data);
                        }
                    }
                });
            }
            
            // Auto-refresh messages every 10 seconds
            setInterval(loadMessages, 10000);
        });
        </script>
        
        <style>
        .lens-zone-admin-chat .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .lens-zone-admin-chat .customer-message {
            background: #e3f2fd;
            margin-right: 20%;
        }
        .lens-zone-admin-chat .admin-message {
            background: #f5f5f5;
            margin-left: 20%;
        }
        .lens-zone-admin-chat .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .lens-zone-admin-chat .message-text {
            font-size: 14px;
            line-height: 1.5;
        }
        .lens-zone-admin-chat .message-file {
            margin-top: 5px;
        }
        .lens-zone-admin-chat .message-file a {
            color: #0073aa;
            text-decoration: none;
        }
        </style>
        <?php
    }
    
    // Display admin order messages
    private function display_admin_order_messages( $order_id ) {
        $messages = get_post_meta( $order_id, '_lens_zone_chat_messages', true );
        if ( ! is_array( $messages ) ) {
            $messages = array();
        }
        
        if ( empty( $messages ) ) {
            echo '<p style="text-align: center; color: #999;">' . __( 'No messages yet.', 'lens-zone' ) . '</p>';
            return;
        }
        
        foreach ( $messages as $msg ) {
            $sender_class = $msg['sender'] === 'customer' ? 'customer-message' : 'admin-message';
            ?>
            <div class="chat-message <?php echo $sender_class; ?>">
                <div class="message-header">
                    <strong><?php echo $msg['sender'] === 'customer' ? __( 'Customer', 'lens-zone' ) : __( 'Admin', 'lens-zone' ); ?></strong>
                    <span class="message-time"><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $msg['timestamp'] ); ?></span>
                </div>
                <?php if ( ! empty( $msg['message'] ) ): ?>
                    <div class="message-text"><?php echo esc_html( $msg['message'] ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $msg['file_url'] ) ): ?>
                    <div class="message-file">
                        <a href="<?php echo esc_url( $msg['file_url'] ); ?>" target="_blank">
                            📎 <?php _e( 'View Attachment', 'lens-zone' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    // Admin send order message
    public function admin_send_order_message() {
        $order_id = intval( $_POST['order_id'] );
        $message = sanitize_textarea_field( $_POST['message'] );
        
        $messages = get_post_meta( $order_id, '_lens_zone_chat_messages', true );
        if ( ! is_array( $messages ) ) {
            $messages = array();
        }
        
        $messages[] = array(
            'sender' => 'admin',
            'message' => $message,
            'file_url' => '',
            'timestamp' => current_time( 'timestamp' )
        );
        
        update_post_meta( $order_id, '_lens_zone_chat_messages', $messages );
        
        wp_send_json_success();
    }
    
    // Admin get order messages
    public function admin_get_order_messages() {
        $order_id = intval( $_POST['order_id'] );
        
        ob_start();
        $this->display_admin_order_messages( $order_id );
        $html = ob_get_clean();
        
        wp_send_json_success( $html );
    }
    
    // Display lens user info in admin order details
    public function display_lens_user_info_admin( $order ) {
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
        <div class="order_data_column" style="clear: both; margin-top: 20px;">
            <h3 style="margin-bottom: 10px;"><?php _e( 'Customer Contacts', 'lens-zone' ); ?></h3>
            <div class="address">
                <?php if ( $user_name ): ?>
                    <p><strong><?php _e( 'Name:', 'lens-zone' ); ?></strong> <?php echo esc_html( $user_name ); ?></p>
                <?php endif; ?>
                <?php if ( $user_phone ): ?>
                    <p><strong><?php _e( 'Phone:', 'lens-zone' ); ?></strong> <a href="tel:<?php echo esc_attr( $user_phone ); ?>"><?php echo esc_html( $user_phone ); ?></a></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}