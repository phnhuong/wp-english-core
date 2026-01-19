<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Ẩn Admin Bar
 */
function wec_disable_admin_bar() {
    if ( ! current_user_can( 'manage_options' ) ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'wec_disable_admin_bar' );

/**
 * 2. Đăng ký Menu Location
 */
function wec_register_my_menus() {
    register_nav_menus( array(
        'wec_header_menu' => 'English Pro Header Menu'
    ) );
}
add_action( 'init', 'wec_register_my_menus' );

/**
 * 3. Chèn Header Custom
 */
function wec_inject_custom_header() {
    $home_url = home_url();
    $vocab_url = site_url( '/kho-tu-vung-cua-toi' );
    $login_url = site_url( '/dang-nhap' );
    
    // User Area
    $user_html = '';
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $avatar = get_avatar( $current_user->ID, 32 );
        $user_html = '
            <div class="wec-user-menu">
                <span class="wec-user-name">Chào, ' . esc_html( $current_user->display_name ) . '</span>
                ' . $avatar . '
                <div class="wec-dropdown">
                    <a href="' . $vocab_url . '">📚 Kho từ vựng</a>
                    <a href="' . wp_logout_url( $home_url ) . '">👋 Đăng xuất</a>
                </div>
            </div>
        ';
    } else {
        $user_html = '<a href="' . $login_url . '" class="wec-btn-login">Đăng nhập</a>';
    }

    echo '
    <div id="wec-main-header">
        <div class="wec-container">
            <a href="' . $home_url . '" class="wec-logo">
                <span style="font-size:24px;">🎓</span> ENGLISH PRO
            </a>
            
            <nav class="wec-nav">';
                
                // Hiển thị Menu động từ WordPress
                if ( has_nav_menu( 'wec_header_menu' ) ) {
                    wp_nav_menu( array(
                        'theme_location' => 'wec_header_menu',
                        'container'      => false,
                        'menu_class'     => 'wec-menu-ul',
                        'fallback_cb'    => false
                    ) );
                } else {
                    // Menu mặc định nếu Admin chưa cài đặt
                    echo '<a href="' . $home_url . '">Trang chủ</a>';
                    echo '<a href="' . $home_url . '/video_lesson">Bài học</a>'; // Link chuẩn
                    echo '<a href="' . site_url('/nang-cap-vip') . '" style="color:#fde047;">★ Nâng cấp VIP</a>';
                }

    echo '  </nav>

            ' . $user_html . '
        </div>
    </div>
    <div style="height: 70px;"></div>
    ';
}
add_action( 'wp_body_open', 'wec_inject_custom_header' );
add_action( 'wp_footer', 'wec_inject_custom_header', 0 ); 

/**
 * 4. CSS
 */
function wec_inject_global_css() {
    ?>
    <style>
        :root { --wec-blue: #0ea5e9; --wec-blue-dark: #0284c7; --wec-text: #334155; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; color: var(--wec-text); margin: 0; }
        .site-header, #masthead, header.wp-block-template-part { display: none !important; }

        #wec-main-header {
            background: linear-gradient(135deg, var(--wec-blue), var(--wec-blue-dark));
            color: #fff; height: 60px; position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; align-items: center;
        }
        .wec-container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .wec-logo { font-size: 20px; font-weight: 800; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }

        /* Style cho Menu động */
        .wec-nav { display: flex; align-items: center; }
        .wec-menu-ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 20px; }
        .wec-menu-ul li { margin: 0; }
        .wec-menu-ul a, .wec-nav > a { color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 500; font-size: 15px; transition: color 0.2s; display: block; }
        .wec-menu-ul a:hover, .wec-nav > a:hover { color: #fff; text-shadow: 0 0 5px rgba(255,255,255,0.5); }
        
        /* Highlight menu item (nếu muốn) */
        .wec-menu-ul .vip-link a { color: #fde047; font-weight: bold; }

        .wec-user-menu { position: relative; display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .wec-user-menu img { border-radius: 50%; border: 2px solid #fff; }
        .wec-user-name { font-weight: 500; font-size: 14px; }
        .wec-dropdown { display: none; position: absolute; top: 100%; right: 0; background: #fff; color: #333; min-width: 180px; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); padding: 10px 0; margin-top: 10px; }
        .wec-user-menu:hover .wec-dropdown { display: block; }
        .wec-dropdown a { display: block; padding: 8px 20px; color: #333; text-decoration: none; }
        .wec-dropdown a:hover { background: #f1f5f9; color: var(--wec-blue); }
        .wec-btn-login { background: #fff; color: var(--wec-blue); padding: 6px 15px; border-radius: 20px; font-weight: bold; text-decoration: none; font-size: 14px; }
        
        @media (max-width: 768px) { .wec-nav { display: none; } .wec-user-name { display: none; } }
        .wec-mode-btn.active, #wec-btn-save, #wec-quiz-start-btn { background-color: var(--wec-blue) !important; border-color: var(--wec-blue) !important; }
        .wec-transcript-line.active { border-left-color: var(--wec-blue) !important; }
    </style>
    <?php
}
add_action( 'wp_head', 'wec_inject_global_css' );