<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Shortcode Form Đăng nhập [wec_login_form]
 */
function wec_shortcode_login_form() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        return '<div class="wec-auth-box logged-in">
            <p>Xin chào, <strong>' . esc_html( $current_user->display_name ) . '</strong>!</p>
            <p>
                <a href="' . admin_url( 'profile.php' ) . '" class="button">Hồ sơ cá nhân</a>
                <a href="' . wp_logout_url( get_permalink() ) . '" class="button button-secondary">Đăng xuất</a>
            </p>
        </div>';
    }

    $args = array(
        'echo'           => false,
        'redirect'       => home_url(), 
        'form_id'        => 'wec-login-form',
        'label_username' => 'Tên đăng nhập hoặc Email',
        'label_password' => 'Mật khẩu',
        'label_remember' => 'Ghi nhớ đăng nhập',
        'label_log_in'   => 'Đăng nhập ngay',
        'remember'       => true
    );
    
    return '<div class="wec-auth-box">' . wp_login_form( $args ) . '<p style="margin-top:10px;"><a href="' . wp_registration_url() . '">Chưa có tài khoản? Đăng ký ngay</a></p></div>';
}
add_shortcode( 'wec_login_form', 'wec_shortcode_login_form' );

/**
 * 2. Tự động thêm link Đăng nhập/Đăng xuất vào Menu chính
 */
function wec_add_login_logout_menu( $items, $args ) {
    // Chỉ thêm vào menu chính (thường là 'primary')
    // Nếu theme bạn dùng tên khác (ví dụ 'main-menu'), hãy đổi lại
    if( $args->theme_location == 'primary' || $args->theme_location == 'menu-1' ) {
        
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            // Link Thoát
            $items .= '<li class="menu-item wec-menu-user"><a href="#">👤 ' . $current_user->display_name . '</a>
                        <ul class="sub-menu">
                            <li><a href="/kho-tu-vung-cua-toi">Kho từ vựng</a></li>
                            <li><a href="' . wp_logout_url( home_url() ) . '">Đăng xuất</a></li>
                        </ul>
                       </li>';
        } else {
            // Link Đăng nhập
            $login_page = site_url( '/dang-nhap' ); // Đường dẫn trang login mình sẽ tạo
            $items .= '<li class="menu-item"><a href="' . $login_page . '">🔐 Đăng nhập</a></li>';
            $items .= '<li class="menu-item highlight"><a href="' . wp_registration_url() . '">Đăng ký</a></li>';
        }
    }
    return $items;
}
add_filter( 'wp_nav_menu_items', 'wec_add_login_logout_menu', 10, 2 );

/**
 * 3. Style CSS cho Form (Inline cho tiện)
 */
function wec_auth_style() {
    echo '<style>
        .wec-auth-box { max-width: 400px; margin: 30px auto; padding: 30px; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .wec-auth-box label { display: block; margin-bottom: 5px; font-weight: 600; }
        .wec-auth-box input[type="text"], .wec-auth-box input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        .wec-auth-box input[type="submit"] { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .wec-auth-box input[type="submit"]:hover { background: #1d4ed8; }
        /* Menu Highlight */
        .menu-item.highlight a { color: #eab308 !important; font-weight: bold; }
    </style>';
}
add_action( 'wp_head', 'wec_auth_style' );