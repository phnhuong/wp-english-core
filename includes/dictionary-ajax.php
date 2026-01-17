<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Hàm dịch (Google Proxy)
 */
function wec_google_translate_word( $word ) {
    $word = urlencode( trim( $word ) );
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q={$word}";
    $response = wp_remote_get( $url );
    if ( is_wp_error( $response ) ) return 'Lỗi kết nối';
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( isset( $data[0][0][0] ) ) return $data[0][0][0];
    return 'Không tìm thấy nghĩa';
}

function wec_handle_dictionary_lookup() {
    if ( ! isset( $_GET['word'] ) ) wp_send_json_error( 'Thiếu từ vựng' );
    $word = sanitize_text_field( $_GET['word'] );
    $meaning = wec_google_translate_word( $word );
    wp_send_json_success( array( 'word' => $word, 'meaning' => $meaning ) );
}
// Đăng ký cả 2 hook cho Translate
add_action( 'wp_ajax_wec_lookup_word', 'wec_handle_dictionary_lookup' );
add_action( 'wp_ajax_nopriv_wec_lookup_word', 'wec_handle_dictionary_lookup' );


/**
 * 2. Hàm LƯU TỪ VỰNG (SỬA LỖI 400 BAD REQUEST)
 */
function wec_handle_save_word() {
    // Kiểm tra đăng nhập tại đây (An toàn hơn và tránh lỗi 400)
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Vui lòng đăng nhập để lưu từ!' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wec_user_vocab';
    
    $user_id = get_current_user_id();
    $word    = isset($_POST['word']) ? sanitize_text_field( $_POST['word'] ) : '';
    $meaning = isset($_POST['meaning']) ? sanitize_text_field( $_POST['meaning'] ) : '';
    $video_id = isset($_POST['video_id']) ? intval( $_POST['video_id'] ) : 0;

    if ( empty($word) ) wp_send_json_error( 'Lỗi: Không có từ vựng.' );

    // Kiểm tra trùng
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d AND word = %s",
        $user_id, $word
    ) );

    if ( $exists ) {
        wp_send_json_success( 'Từ này đã lưu rồi.' );
    }

    // Insert
    $result = $wpdb->insert( 
        $table_name, 
        array( 
            'user_id' => $user_id,
            'word' => $word, 
            'meaning' => $meaning,
            'video_id' => $video_id
        ) 
    );

    if ( $result === false ) {
        wp_send_json_error( 'Lỗi DB: ' . $wpdb->last_error );
    }

    wp_send_json_success( 'Đã lưu thành công!' );
}
// QUAN TRỌNG: Đăng ký cho cả User đăng nhập và Khách (nopriv)
// Điều này giúp tránh lỗi 400 nếu server không nhận diện được session ajax
add_action( 'wp_ajax_wec_save_word', 'wec_handle_save_word' );
add_action( 'wp_ajax_nopriv_wec_save_word', 'wec_handle_save_word' );