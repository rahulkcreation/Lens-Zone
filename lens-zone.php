<?php
/**
 * Plugin Name: Lens Zone
 * Description: A custom plugin for WooCommerce to select lenses and prescriptions for eyeglass frames.
 * Version: 7.4
 * Author: Art-Tech Fuzion
 * Author URI: http://arttechfuzion.com/
 * Text Domain: lens-zone
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Make sure WordPress functions are available
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

if (!function_exists('add_action')) {
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

if (!function_exists('plugin_dir_path')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!function_exists('is_admin')) {
    require_once(ABSPATH . 'wp-includes/functions.php');
}

if (!function_exists('load_plugin_textdomain')) {
    require_once(ABSPATH . 'wp-includes/l10n.php');
}

if (!function_exists('plugin_basename')) {
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

/**
 * The main plugin class.
 */
final class Lens_Zone {

    /**
     * The single instance of the class.
     *
     * @var Lens_Zone
     */
    private static $_instance = null;

    /**
     * Main Lens_Zone Instance.
     *
     * Ensures only one instance of Lens_Zone is loaded or can be loaded.
     *
     * @static
     * @return Lens_Zone - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Lens_Zone Constructor.
     */
    private function __construct() {
        // Silence is golden.
    }

    /**
     * Initialize the plugin.
     */
    public function init() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        define( 'LZ_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
        define( 'LZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'LZ_PLUGIN_VERSION', '4.4' );
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        require_once LZ_PLUGIN_PATH . 'includes/class-lens-zone-db.php';
        require_once LZ_PLUGIN_PATH . 'public/class-lens-zone-public.php';

        if ( is_admin() ) {
            require_once LZ_PLUGIN_PATH . 'admin/class-lens-zone-admin.php';
            require_once LZ_PLUGIN_PATH . 'admin/class-lens-zone-product-integration.php';
        }
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'instantiate_classes' ) );
    }

    /**
     * Instantiate classes.
     */
    public function instantiate_classes() {
        // Always instantiate public class for frontend (singleton pattern)
        Lens_Zone_Public::get_instance();
        
        // Instantiate admin class only in admin
        if ( is_admin() ) {
            new Lens_Zone_Admin();
        }
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'lens-zone', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function lens_zone_init() {
    Lens_Zone::instance()->init();
}
add_action( 'plugins_loaded', 'lens_zone_init' );

/**
 * Add settings link on plugin page
 */
function lens_zone_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=lens-zone">' . __( 'Settings', 'lens-zone' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lens_zone_settings_link' );