<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode [wec_my_vocab]
 * Hiển thị danh sách từ vựng của user hiện tại
 */
function wec_shortcode_my_vocab() {
    // 1. Kiểm tra đăng nhập
    if ( ! is_user_logged_in() ) {
        return '<p class="wec-alert">Vui lòng <a href="' . wp_login_url() . '">đăng nhập</a> để xem từ vựng của bạn.</p>';
    }

    // 2. Lấy dữ liệu từ DB
    global $wpdb;
    $table_name = $wpdb->prefix . 'wec_user_vocab';
    $user_id = get_current_user_id();

    // Lấy danh sách từ mới nhất lên đầu
    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ) );

    if ( empty( $results ) ) {
        return '<p>Bạn chưa lưu từ vựng nào.</p>';
    }

    // 3. Render HTML
    ob_start(); // Bắt đầu bộ nhớ đệm để gom HTML
    ?>
    <div class="wec-vocab-list">
        <h3>Kho từ vựng của tôi (<?php echo count($results); ?> từ)</h3>
        
        <table class="wec-table">
            <thead>
                <tr>
                    <th>Từ vựng</th>
                    <th>Nghĩa</th>
                    <th>Video gốc</th>
                    <th>Ngày lưu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $results as $row ) : 
                    $video_link = get_permalink( $row->video_id );
                    $video_title = get_the_title( $row->video_id );
                ?>
                <tr>
                    <td><strong><?php echo esc_html( $row->word ); ?></strong></td>
                    <td><?php echo esc_html( $row->meaning ); ?></td>
                    <td>
                        <?php if ( $row->video_id > 0 ) : ?>
                            <a href="<?php echo esc_url( $video_link ); ?>"><?php echo esc_html( $video_title ); ?></a>
                        <?php else : ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td style="color:#666; font-size:13px;">
                        <?php echo date_i18n( get_option( 'date_format' ), strtotime( $row->created_at ) ); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <style>
        .wec-vocab-list { margin: 20px 0; font-family: sans-serif; }
        .wec-table { width: 100%; border-collapse: collapse; border: 1px solid #ddd; }
        .wec-table th, .wec-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .wec-table th { background: #f4f4f4; }
        .wec-table tr:nth-child(even) { background: #f9f9f9; }
        .wec-table tr:hover { background: #f1f1f1; }
        .wec-alert { padding: 15px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px; }
    </style>
    <?php
    return ob_get_clean(); // Trả về HTML đã gom
}
add_shortcode( 'wec_my_vocab', 'wec_shortcode_my_vocab' );