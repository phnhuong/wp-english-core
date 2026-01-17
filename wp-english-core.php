<?php
/**
 * Plugin Name: WP English Core
 * Plugin URI:  http://english.phamhong.net
 * Description: Plugin cốt lõi quản lý Video, Phụ đề và Tính năng học Tiếng Anh.
 * Version:     1.2.0
 * Author:      Pham Hong Team
 * Author URI:  http://english.phamhong.net
 * Text Domain: wp-english-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. CONSTANTS
define( 'WEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 2. INCLUDES (Load đầy đủ 5 file chức năng)
require_once WEC_PLUGIN_DIR . 'includes/cpt-video.php';
require_once WEC_PLUGIN_DIR . 'includes/metabox-video.php';
require_once WEC_PLUGIN_DIR . 'includes/frontend-display.php';
require_once WEC_PLUGIN_DIR . 'includes/dictionary-ajax.php';
require_once WEC_PLUGIN_DIR . 'includes/shortcode-vocab.php'; // <--- File mới thêm

// 3. ALLOW VTT UPLOAD
function wec_allow_vtt_upload( $mimes ) {
    $mimes['vtt'] = 'text/vtt';
    return $mimes;
}
add_filter( 'upload_mimes', 'wec_allow_vtt_upload' );

// 4. ENQUEUE SCRIPTS
function wec_enqueue_scripts() {
    if ( is_singular( 'video_lesson' ) ) {
        wp_enqueue_style( 'wec-style', WEC_PLUGIN_URL . 'assets/style.css', array(), time() );
        wp_enqueue_script( 'wec-script', WEC_PLUGIN_URL . 'assets/script.js', array('jquery'), time(), true );

        wp_localize_script( 'wec-script', 'wec_params', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'is_logged_in' => is_user_logged_in(),
            'post_id'      => get_the_ID()
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'wec_enqueue_scripts' );

// 5. CREATE DATABASE TABLE
function wec_create_tables() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wec_user_vocab';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        word varchar(100) NOT NULL,
        meaning text NOT NULL,
        video_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// 6. ACTIVATE HOOK
function wec_activate_plugin() {
    wec_register_video_cpt();
    wec_register_taxonomies();
    wec_create_tables();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wec_activate_plugin' );