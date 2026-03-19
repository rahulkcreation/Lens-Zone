<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Make sure WordPress functions are available
if (!function_exists('size_format')) {
    require_once(ABSPATH . 'wp-includes/formatting.php');
}

class Lens_Zone_DB {

    /**
     * Create database tables
     * @return bool True if tables were created, false if they already exist
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'lens_categories';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            image_id BIGINT(20),
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $table_name = $wpdb->prefix . 'lens_sub_categories';
        $sql .= "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            category_id BIGINT(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            image_id BIGINT(20),
            price DECIMAL(10, 2) NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $table_name = $wpdb->prefix . 'lens_sub_category_points';
        $sql .= "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            sub_category_id BIGINT(20) NOT NULL,
            point_text TEXT NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $table_name = $wpdb->prefix . 'lens_power_options';
        $sql .= "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            type ENUM('sph', 'cyl') NOT NULL,
            value VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $table_name = $wpdb->prefix . 'lens_order_messages';
        $sql .= "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) NOT NULL,
            sender VARCHAR(20) NOT NULL,
            message TEXT,
            file_url VARCHAR(500),
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        $table_name = $wpdb->prefix . 'lens_frame_sizes';
        $sql .= "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) NOT NULL,
            size_name VARCHAR(100) NOT NULL,
            lens_width VARCHAR(50) NOT NULL,
            bridge_width VARCHAR(50) NOT NULL,
            temple_length VARCHAR(50) NOT NULL,
            lens_width_icon BIGINT(20),
            bridge_width_icon BIGINT(20),
            temple_length_icon BIGINT(20),
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        
        return true;
    }

    /**
     * Drop database tables
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'lens_frame_sizes',
            $wpdb->prefix . 'lens_order_messages',
            $wpdb->prefix . 'lens_power_options',
            $wpdb->prefix . 'lens_sub_category_points',
            $wpdb->prefix . 'lens_sub_categories',
            $wpdb->prefix . 'lens_categories',
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }
    }
    
    /**
     * Check if tables exist
     * @return bool True if all tables exist, false otherwise
     */
    public function check_tables_exist() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'lens_categories',
            $wpdb->prefix . 'lens_sub_categories',
            $wpdb->prefix . 'lens_sub_category_points',
            $wpdb->prefix . 'lens_power_options',
            $wpdb->prefix . 'lens_order_messages',
            $wpdb->prefix . 'lens_frame_sizes',
        );

        foreach ( $tables as $table ) {
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) != $table ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get database size
     * @return string Formatted size
     */
    public function get_db_size() {
        global $wpdb;

        $size = 0;
        $tables = array(
            $wpdb->prefix . 'lens_categories',
            $wpdb->prefix . 'lens_sub_categories',
            $wpdb->prefix . 'lens_sub_category_points',
            $wpdb->prefix . 'lens_power_options',
            $wpdb->prefix . 'lens_order_messages',
            $wpdb->prefix . 'lens_frame_sizes',
        );

        foreach ( $tables as $table ) {
            $table_size = $wpdb->get_var( "SELECT data_length + index_length FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "' AND table_name = '{$table}'" );
            if ($table_size) {
                $size += $table_size;
            }
        }

        return size_format( $size ? $size : 0 );  // Return 0 if no tables exist yet
    }
}