<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Đăng ký Metabox
function wec_add_video_meta_box() {
    add_meta_box(
        'wec_video_data',
        'Dữ liệu Video & Cài đặt VIP', // Đổi tên
        'wec_render_video_meta_box',
        'video_lesson',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'wec_add_video_meta_box' );

// 2. Render HTML
function wec_render_video_meta_box( $post ) {
    $video_url = get_post_meta( $post->ID, 'wec_video_url', true );
    $sub_en    = get_post_meta( $post->ID, 'wec_subtitle_en', true );
    $sub_vi    = get_post_meta( $post->ID, 'wec_subtitle_vi', true );
    $is_vip    = get_post_meta( $post->ID, 'wec_require_vip', true ); // Mới

    wp_nonce_field( 'wec_save_video_data', 'wec_video_nonce' );
    ?>
    
    <!-- CHECKBOX VIP (MỚI) -->
    <div style="background:#fff8e5; padding:10px; border:1px solid #eab308; margin-bottom:15px; border-radius:4px;">
        <label>
            <input type="checkbox" name="wec_require_vip" value="1" <?php checked( $is_vip, '1' ); ?> />
            <strong>Yêu cầu thành viên VIP mới được xem bài này?</strong>
        </label>
    </div>

    <div style="margin-top: 10px; padding-bottom: 15px; border-bottom: 1px dashed #ccc;">
        <label style="font-weight:bold; display:block; margin-bottom: 5px;">Link Video (Youtube / MP4):</label>
        <input type="text" name="wec_video_url" value="<?php echo esc_attr($video_url); ?>" style="width:100%; height:35px;" placeholder="Nhập link video..." />
    </div>

    <div style="margin-top: 15px;">
        <label style="font-weight:bold; display:block; color: #2271b1; margin-bottom: 5px;">1. Phụ đề Tiếng Anh (English .vtt):</label>
        <input type="text" name="wec_subtitle_en" value="<?php echo esc_attr($sub_en); ?>" style="width:100%; height:35px;" />
    </div>

    <div style="margin-top: 15px;">
        <label style="font-weight:bold; display:block; color: #d63638; margin-bottom: 5px;">2. Phụ đề Tiếng Việt (Vietnamese .vtt):</label>
        <input type="text" name="wec_subtitle_vi" value="<?php echo esc_attr($sub_vi); ?>" style="width:100%; height:35px;" />
    </div>
    <?php
}

// 3. Lưu dữ liệu
function wec_save_video_meta_box( $post_id ) {
    if ( ! isset( $_POST['wec_video_nonce'] ) || ! wp_verify_nonce( $_POST['wec_video_nonce'], 'wec_save_video_data' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if ( isset( $_POST['wec_video_url'] ) ) update_post_meta( $post_id, 'wec_video_url', sanitize_text_field( $_POST['wec_video_url'] ) );
    if ( isset( $_POST['wec_subtitle_en'] ) ) update_post_meta( $post_id, 'wec_subtitle_en', sanitize_text_field( $_POST['wec_subtitle_en'] ) );
    if ( isset( $_POST['wec_subtitle_vi'] ) ) update_post_meta( $post_id, 'wec_subtitle_vi', sanitize_text_field( $_POST['wec_subtitle_vi'] ) );
    
    // Lưu trạng thái VIP (Checkbox)
    $vip_status = isset( $_POST['wec_require_vip'] ) ? '1' : '0';
    update_post_meta( $post_id, 'wec_require_vip', $vip_status );
}
add_action( 'save_post', 'wec_save_video_meta_box' );