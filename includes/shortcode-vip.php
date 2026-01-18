<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [wec_vip_form]
 * Hiển thị form xác nhận chuyển khoản
 */
function wec_shortcode_vip_payment_form() {
    if ( ! is_user_logged_in() ) {
        return '<p class="wec-alert">Vui lòng <a href="' . wp_login_url() . '">đăng nhập</a> để nâng cấp VIP.</p>';
    }

    $current_user = wp_get_current_user();
    $message = '';

    // Xử lý Form Submit
    if ( isset( $_POST['wec_vip_submit'] ) && wp_verify_nonce( $_POST['wec_vip_nonce'], 'wec_vip_action' ) ) {
        $trans_code = sanitize_text_field( $_POST['trans_code'] );
        $package    = sanitize_text_field( $_POST['vip_package'] );
        
        // Gửi Email cho Admin
        $to = get_option( 'admin_email' );
        $subject = '[VIP REQUEST] Yêu cầu kích hoạt từ: ' . $current_user->user_login;
        $body = "Người dùng: " . $current_user->display_name . " (ID: " . $current_user->ID . ")\n";
        $body .= "Email: " . $current_user->user_email . "\n";
        $body .= "Gói đăng ký: " . $package . "\n";
        $body .= "Mã giao dịch/Nội dung CK: " . $trans_code . "\n";
        $body .= "Thời gian: " . current_time( 'mysql' ) . "\n\n";
        $body .= "Link kích hoạt nhanh: " . admin_url( 'user-edit.php?user_id=' . $current_user->ID );

        wp_mail( $to, $subject, $body );

        $message = '<div class="wec-success">✅ Đã gửi yêu cầu thành công! Admin sẽ kích hoạt tài khoản của bạn trong vòng 30 phút.</div>';
    }

    ob_start();
    ?>
    <div class="wec-vip-page">
        <!-- PHẦN 1: THÔNG TIN CHUYỂN KHOẢN -->
        <div class="wec-pricing-box">
            <h2>💎 Nâng cấp Thành viên VIP</h2>
            <p>Mở khóa toàn bộ video, tra từ điển không giới hạn và luyện nghe nâng cao.</p>
            
            <div class="wec-bank-info">
                <h3>Thông tin chuyển khoản</h3>
                <p><strong>Ngân hàng:</strong> MB Bank (Quân Đội)</p>
                <p><strong>Số tài khoản:</strong> 0123 456 789</p>
                <p><strong>Chủ tài khoản:</strong> NGUYEN VAN A</p>
                <p><strong>Nội dung CK:</strong> VIP <?php echo $current_user->user_login; ?></p>
                <p><strong>Số tiền:</strong> 99.000đ / 1 Năm</p>
                <hr>
                <p style="font-size:14px; color:#666;">(Hoặc quét mã QR bên dưới nếu có)</p>
            </div>
        </div>

        <!-- PHẦN 2: FORM XÁC NHẬN -->
        <div class="wec-confirm-form">
            <h3>📝 Xác nhận đã chuyển khoản</h3>
            <?php echo $message; ?>
            <form method="post" action="">
                <?php wp_nonce_field( 'wec_vip_action', 'wec_vip_nonce' ); ?>
                
                <p>
                    <label>Gói đăng ký:</label>
                    <select name="vip_package" style="width:100%; padding:8px;">
                        <option value="1 Năm - 99k">VIP 1 Năm (99.000đ)</option>
                        <option value="Trọn đời - 299k">VIP Trọn đời (299.000đ)</option>
                    </select>
                </p>
                
                <p>
                    <label>Mã giao dịch / Nội dung CK:</label>
                    <input type="text" name="trans_code" required placeholder="Ví dụ: FT23059..." style="width:100%; padding:8px;">
                </p>

                <p>
                    <input type="submit" name="wec_vip_submit" value="Gửi yêu cầu kích hoạt" class="button-primary" style="width:100%; padding:10px; font-size:16px; background:#eab308; border:none; color:#000; font-weight:bold; cursor:pointer;">
                </p>
            </form>
        </div>
    </div>

    <style>
        .wec-vip-page { display: flex; gap: 30px; flex-wrap: wrap; margin-top: 20px; }
        .wec-pricing-box, .wec-confirm-form { flex: 1; min-width: 300px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        .wec-pricing-box { background: #f8fafc; border-top: 4px solid #2563eb; }
        .wec-confirm-form { border-top: 4px solid #eab308; }
        .wec-bank-info p { margin: 5px 0; }
        .wec-success { background: #d1fae5; color: #065f46; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wec_vip_form', 'wec_shortcode_vip_payment_form' );