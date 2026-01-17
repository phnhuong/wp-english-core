<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Hàm gọi Google Translate (Mẹo dùng API miễn phí)
 */
function wec_google_translate_word( $word ) {
    // Chuẩn hóa từ: Xóa khoảng trắng, mã hóa URL
    $word = urlencode( trim( $word ) );
    
    // Gọi API public của Google (client=gtx)
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q={$word}";

    $response = wp_remote_get( $url );
    
    if ( is_wp_error( $response ) ) {
        return 'Lỗi kết nối';
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // Google trả về JSON dạng: [[[ "Xin chào", "Hello", ... ]]]
    // Chúng ta lấy phần tử đầu tiên
    if ( isset( $data[0][0][0] ) ) {
        return $data[0][0][0];
    }
    
    return 'Không tìm thấy nghĩa';
}

/**
 * 2. Xử lý Ajax Request từ Frontend gửi lên
 */
function wec_handle_dictionary_lookup() {
    // Kiểm tra dữ liệu đầu vào
    if ( ! isset( $_GET['word'] ) ) {
        wp_send_json_error( 'Thiếu từ vựng' );
    }

    $word = sanitize_text_field( $_GET['word'] );
    
    // Gọi hàm dịch
    $meaning = wec_google_translate_word( $word );
    
    // Trả kết quả về cho JS
    wp_send_json_success( array(
        'word' => $word,
        'meaning' => $meaning
    ) );
}

// Đăng ký Action để WordPress biết hàm này xử lý Ajax
add_action( 'wp_ajax_wec_lookup_word', 'wec_handle_dictionary_lookup' );        // Cho Admin/User đăng nhập
add_action( 'wp_ajax_nopriv_wec_lookup_word', 'wec_handle_dictionary_lookup' ); // Cho khách vãng lai