<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Thêm trường "Ngày hết hạn VIP" vào trang hồ sơ User
 */
function wec_add_vip_fields( $user ) {
    $vip_date = get_user_meta( $user->ID, 'wec_vip_expire_date', true );
    ?>
    <h3>Thông tin Thành viên VIP</h3>
    <table class="form-table">
        <tr>
            <th><label for="wec_vip_expire_date">Ngày hết hạn VIP</label></th>
            <td>
                <input type="date" name="wec_vip_expire_date" id="wec_vip_expire_date" value="<?php echo esc_attr( $vip_date ); ?>" class="regular-text" />
                <p class="description">Định dạng: YYYY-MM-DD. Để trống nếu là thành viên thường.</p>
                <?php 
                if ( ! empty( $vip_date ) ) {
                    if ( strtotime( $vip_date ) >= time() ) {
                        echo '<span style="color:green; font-weight:bold;">Đang là VIP (Còn hạn)</span>';
                    } else {
                        echo '<span style="color:red; font-weight:bold;">Đã hết hạn VIP</span>';
                    }
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'wec_add_vip_fields' );
add_action( 'edit_user_profile', 'wec_add_vip_fields' );

/**
 * 2. Lưu trường "Ngày hết hạn VIP"
 */
function wec_save_vip_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    
    if ( isset( $_POST['wec_vip_expire_date'] ) ) {
        update_user_meta( $user_id, 'wec_vip_expire_date', sanitize_text_field( $_POST['wec_vip_expire_date'] ) );
    }
}
add_action( 'personal_options_update', 'wec_save_vip_fields' );
add_action( 'edit_user_profile_update', 'wec_save_vip_fields' );

/**
 * 3. Hiển thị cột "VIP" ra ngoài danh sách Users
 */
function wec_add_vip_column( $columns ) {
    $columns['wec_vip'] = 'Trạng thái VIP';
    return $columns;
}
add_filter( 'manage_users_columns', 'wec_add_vip_column' );

function wec_show_vip_column_content( $value, $column_name, $user_id ) {
    if ( 'wec_vip' == $column_name ) {
        $vip_date = get_user_meta( $user_id, 'wec_vip_expire_date', true );
        if ( ! empty( $vip_date ) ) {
            if ( strtotime( $vip_date ) >= time() ) {
                return '<span style="color:green; font-weight:bold;">VIP đến: ' . $vip_date . '</span>';
            } else {
                return '<span style="color:red;">Hết hạn: ' . $vip_date . '</span>';
            }
        }
        return '-';
    }
    return $value;
}
add_action( 'manage_users_custom_column', 'wec_show_vip_column_content', 10, 3 );

/**
 * 4. Hàm kiểm tra quyền VIP (Dùng cho code chặn video sau này)
 */
function wec_is_user_vip( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    if ( ! $user_id ) return false; // Chưa đăng nhập

    // Admin luôn là VIP
    if ( user_can( $user_id, 'manage_options' ) ) return true;

    $vip_date = get_user_meta( $user_id, 'wec_vip_expire_date', true );
    
    if ( ! empty( $vip_date ) && strtotime( $vip_date ) >= time() ) {
        return true;
    }
    return false;
}