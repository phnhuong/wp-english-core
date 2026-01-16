<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Đăng ký Metabox
function wec_add_video_meta_box() {
    add_meta_box(
        'wec_video_data',           // ID
        'Dữ liệu Video & Phụ đề',   // Title
        'wec_render_video_meta_box',// Callback function
        'video_lesson',             // Post Type
        'normal',                   // Context
        'high'                      // Priority
    );
}
add_action( 'add_meta_boxes', 'wec_add_video_meta_box' );

// 2. Render nội dung HTML
function wec_render_video_meta_box( $post ) {
    $video_url = get_post_meta( $post->ID, 'wec_video_url', true );
    $subtitle_url = get_post_meta( $post->ID, 'wec_subtitle_url', true );
    
    // Nonce field để bảo mật
    wp_nonce_field( 'wec_save_video_data', 'wec_video_nonce' );
    ?>
    
    <div style="margin-top: 10px;">
        <label style="font-weight:bold; display:block;">Link Video (MP4):</label>
        <input type="text" name="wec_video_url" value="<?php echo esc_attr($video_url); ?>" style="width:100%; height:35px;" placeholder="Nhập link video..." />
    </div>

    <div style="margin-top: 15px;">
        <label style="font-weight:bold; display:block;">Link Phụ đề (VTT):</label>
        <input type="text" name="wec_subtitle_url" value="<?php echo esc_attr($subtitle_url); ?>" style="width:100%; height:35px;" placeholder="Nhập link file .vtt..." />
    </div>
    <?php
}

// 3. Lưu dữ liệu
function wec_save_video_meta_box( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['wec_video_nonce'] ) || ! wp_verify_nonce( $_POST['wec_video_nonce'], 'wec_save_video_data' ) ) {
        return;
    }
    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Save Video URL
    if ( isset( $_POST['wec_video_url'] ) ) {
        update_post_meta( $post_id, 'wec_video_url', sanitize_text_field( $_POST['wec_video_url'] ) );
    }

    // Save Subtitle URL
    if ( isset( $_POST['wec_subtitle_url'] ) ) {
        update_post_meta( $post_id, 'wec_subtitle_url', sanitize_text_field( $_POST['wec_subtitle_url'] ) );
    }
}
add_action( 'save_post', 'wec_save_video_meta_box' );