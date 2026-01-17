<?php
/**
 * Plugin Name: WP English Core
 * Plugin URI:  http://english.phamhong.net
 * Description: Plugin cốt lõi quản lý Video, Phụ đề và Tính năng học Tiếng Anh.
 * Version:     1.0.0
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

// 2. INCLUDES
require_once WEC_PLUGIN_DIR . 'includes/cpt-video.php';
require_once WEC_PLUGIN_DIR . 'includes/metabox-video.php';
require_once WEC_PLUGIN_DIR . 'includes/frontend-display.php';
require_once WEC_PLUGIN_DIR . 'includes/dictionary-ajax.php'; // <--- MỚI THÊM

// 3. ALLOW VTT UPLOAD
function wec_allow_vtt_upload( $mimes ) {
    $mimes['vtt'] = 'text/vtt';
    return $mimes;
}
add_filter( 'upload_mimes', 'wec_allow_vtt_upload' );

// 4. ENQUEUE SCRIPTS & LOCALIZE AJAX
function wec_enqueue_scripts() {
    if ( is_singular( 'video_lesson' ) ) {
        wp_enqueue_style( 'wec-style', WEC_PLUGIN_URL . 'assets/style.css', array(), time() );
        wp_enqueue_script( 'wec-script', WEC_PLUGIN_URL . 'assets/script.js', array('jquery'), time(), true );

        // TRUYỀN BIẾN XUỐNG JS ĐỂ GỌI AJAX (QUAN TRỌNG)
        wp_localize_script( 'wec-script', 'wec_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'wec_enqueue_scripts' );

// 5. ACTIVATE
function wec_activate_plugin() {
    wec_register_video_cpt();
    wec_register_taxonomies();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wec_activate_plugin' );