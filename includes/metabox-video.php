<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Đăng ký Metabox
function wec_add_video_meta_box() {
    add_meta_box(
        'wec_video_data',
        'Dữ liệu Video & Phụ đề Song ngữ', // Đổi tiêu đề
        'wec_render_video_meta_box',
        'video_lesson',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wec_add_video_meta_box' );

// 2. Render nội dung HTML
function wec_render_video_meta_box( $post ) {
    // Lấy dữ liệu 3 trường: Video, Sub EN, Sub VI
    $video_url = get_post_meta( $post->ID, 'wec_video_url', true );
    $sub_en    = get_post_meta( $post->ID, 'wec_subtitle_en', true ); // Mới
    $sub_vi    = get_post_meta( $post->ID, 'wec_subtitle_vi', true ); // Mới
    
    wp_nonce_field( 'wec_save_video_data', 'wec_video_nonce' );
    ?>
    
    <div style="margin-top: 10px; padding-bottom: 15px; border-bottom: 1px dashed #ccc;">
        <label style="font-weight:bold; display:block; margin-bottom: 5px;">Link Video (Youtube / MP4):</label>
        <input type="text" name="wec_video_url" value="<?php echo esc_attr($video_url); ?>" style="width:100%; height:35px;" placeholder="Nhập link video..." />
    </div>

    <div style="margin-top: 15px;">
        <label style="font-weight:bold; display:block; color: #2271b1; margin-bottom: 5px;">1. Phụ đề Tiếng Anh (English .vtt):</label>
        <input type="text" name="wec_subtitle_en" value="<?php echo esc_attr($sub_en); ?>" style="width:100%; height:35px;" placeholder="URL file sub tiếng Anh..." />
        <p class="description">Dùng để tách từ và tra từ điển.</p>
    </div>

    <div style="margin-top: 15px;">
        <label style="font-weight:bold; display:block; color: #d63638; margin-bottom: 5px;">2. Phụ đề Tiếng Việt (Vietnamese .vtt):</label>
        <input type="text" name="wec_subtitle_vi" value="<?php echo esc_attr($sub_vi); ?>" style="width:100%; height:35px;" placeholder="URL file sub tiếng Việt..." />
        <p class="description">Dùng để hiển thị nghĩa của câu.</p>
    </div>
    <?php
}

// 3. Lưu dữ liệu
function wec_save_video_meta_box( $post_id ) {
    if ( ! isset( $_POST['wec_video_nonce'] ) || ! wp_verify_nonce( $_POST['wec_video_nonce'], 'wec_save_video_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Save Video
    if ( isset( $_POST['wec_video_url'] ) ) {
        update_post_meta( $post_id, 'wec_video_url', sanitize_text_field( $_POST['wec_video_url'] ) );
    }

    // Save Sub EN
    if ( isset( $_POST['wec_subtitle_en'] ) ) {
        update_post_meta( $post_id, 'wec_subtitle_en', sanitize_text_field( $_POST['wec_subtitle_en'] ) );
    }

    // Save Sub VI
    if ( isset( $_POST['wec_subtitle_vi'] ) ) {
        update_post_meta( $post_id, 'wec_subtitle_vi', sanitize_text_field( $_POST['wec_subtitle_vi'] ) );
    }
}
add_action( 'save_post', 'wec_save_video_meta_box' );