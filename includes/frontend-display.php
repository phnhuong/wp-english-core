<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. HELPER: LẤY ID YOUTUBE
function wec_get_youtube_id( $url ) {
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if ( preg_match( $pattern, $url, $match ) ) return $match[1];
    return false;
}

// 2. HELPER: ĐỌC FILE VTT
function wec_parse_vtt( $vtt_url ) {
    $upload_dir = wp_upload_dir();
    $vtt_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $vtt_url );
    if ( file_exists( $vtt_path ) ) {
        $content = file_get_contents( $vtt_path );
    } else {
        $response = wp_remote_get( $vtt_url );
        if ( is_wp_error( $response ) ) return [];
        $content = wp_remote_retrieve_body( $response );
    }
    $bom = pack('H*','EFBBBF');
    $content = preg_replace("/^$bom/", '', $content);
    $content = str_replace(array("\r\n", "\r"), "\n", $content);
    $lines = explode( "\n", $content );
    $subs = []; $current_sub = [];
    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) || $line === 'WEBVTT' || is_numeric($line) ) continue;
        if ( preg_match( '/(\d{2}:\d{2}:\d{2}\.\d{3}) --> (\d{2}:\d{2}:\d{2}\.\d{3})/', $line, $matches ) ) {
            if ( ! empty( $current_sub ) ) { $subs[] = $current_sub; $current_sub = []; }
            $start_parts = explode( ':', $matches[1] );
            $start = $start_parts[0] * 3600 + $start_parts[1] * 60 + (float)$start_parts[2];
            $end_parts = explode( ':', $matches[2] );
            $end = $end_parts[0] * 3600 + $end_parts[1] * 60 + (float)$end_parts[2];
            $current_sub['start'] = $start; $current_sub['end'] = $end; $current_sub['time_str'] = substr($matches[1], 0, 8); $current_sub['text'] = '';
        } elseif ( isset( $current_sub['start'] ) ) {
            $current_sub['text'] .= ($current_sub['text'] === '' ? '' : ' ') . $line;
        }
    }
    if ( ! empty( $current_sub ) ) { $subs[] = $current_sub; }
    return $subs;
}

// 3. MAIN FUNCTION: HIỂN THỊ PLAYER VÀ TOOLBAR
function wec_add_video_player_to_content( $content ) {
    if ( ! is_singular( 'video_lesson' ) ) return $content;

    $post_id = get_the_ID();
    
    // --- CHECK VIP ---
    $is_vip_content = get_post_meta( $post_id, 'wec_require_vip', true );
    if ( $is_vip_content === '1' ) {
        $user_has_access = function_exists('wec_is_user_vip') ? wec_is_user_vip() : current_user_can('manage_options');
        if ( ! $user_has_access ) {
            return '<div class="wec-video-container-full" style="background:#111; color:#fff; padding:50px; text-align:center;">
                <h2>🔒 Nội dung VIP</h2><p>Vui lòng nâng cấp tài khoản.</p>
                <a href="' . site_url('/nang-cap-vip') . '" style="background:#eab308; color:#000; padding:10px 20px; border-radius:5px; text-decoration:none;">Nâng cấp ngay</a>
            </div><div style="opacity:0.3; pointer-events:none;">' . $content . '</div>';
        }
    }

    $video_url = get_post_meta( $post_id, 'wec_video_url', true );
    $sub_en_url = get_post_meta( $post_id, 'wec_subtitle_en', true );
    $sub_vi_url = get_post_meta( $post_id, 'wec_subtitle_vi', true );

    if ( empty( $video_url ) ) return $content;

    // --- HTML START ---
    $html = '<div class="wec-video-container-full">';

    // CỘT 1: VIDEO PLAYER
    $html .= '<div class="wec-col-video"><div class="wec-video-wrapper">';
    $youtube_id = wec_get_youtube_id( $video_url );
    if ( $youtube_id ) {
        $html .= '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000;">
            <iframe id="wec-yt-iframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" src="https://www.youtube.com/embed/' . $youtube_id . '?enablejsapi=1&rel=0" frameborder="0" allowfullscreen></iframe>
        </div>';
    } else {
        $html .= '<video id="wec-main-player" controls style="width: 100%; display:block; background: #000;"><source src="' . esc_url( $video_url ) . '" type="video/mp4"></video>';
    }
    $html .= '</div></div>';

    // CỘT 2: TRANSCRIPT VÀ TOOLBAR
    $html .= '<div class="wec-col-transcript">';
    $html .= '<div id="wec-dict-popup" class="wec-dict-popup" style="display:none;"><div class="wec-dict-header"><span id="wec-dict-word">Word</span> <span id="wec-dict-close">&times;</span></div><div id="wec-dict-body">...</div></div>';

    if ( ! empty( $sub_en_url ) || ! empty( $sub_vi_url ) ) {
        $subs_en = !empty($sub_en_url) ? wec_parse_vtt( $sub_en_url ) : [];
        $subs_vi = !empty($sub_vi_url) ? wec_parse_vtt( $sub_vi_url ) : [];

        $html .= '<div class="wec-transcript-box">';
        
        // --- ĐÂY LÀ PHẦN BẠN ĐANG THIẾU ---
        $html .= '<!-- TOOLBAR DEBUG -->
        <div class="wec-transcript-header">
            <div class="wec-title">Transcript</div>
            <div class="wec-modes">
                <button class="wec-mode-btn active" data-mode="bilingual">Song ngữ</button>
                <button class="wec-mode-btn" data-mode="en">EN</button>
                <button class="wec-mode-btn" data-mode="vi">VI</button>
                <button class="wec-mode-btn" data-mode="hidden">Ẩn</button>
                <button class="wec-mode-btn" data-mode="dictation" style="border-color:#eab308; color:#a16207;">⚡ Luyện</button>
            </div>
        </div>
        <!-- END TOOLBAR -->';

        $html .= '<div id="wec-transcript-content" class="mode-bilingual">';
        
        // Loop render dòng thoại
        $base_subs = !empty($subs_en) ? $subs_en : $subs_vi;
        foreach ( $base_subs as $index => $sub ) {
            $start = $sub['start']; $end = $sub['end']; $time_str = $sub['time_str'];
            
            $text_en = '';
            if(!empty($subs_en)) {
                $raw = isset($subs_en[$index]) ? $subs_en[$index]['text'] : '';
                foreach(explode(' ', esc_html($raw)) as $w) { if(trim($w)!=='') $text_en .= '<span class="wec-word">'.$w.'</span> '; }
            }
            
            $text_vi = '';
            if(!empty($subs_vi)) {
                $best = ''; $min = 2.0;
                foreach($subs_vi as $v) { $d = abs($v['start']-$start); if($d<$min){$min=$d; $best=$v['text'];} }
                $text_vi = esc_html($best);
            }

            $html .= sprintf(
                '<div class="wec-transcript-line" data-start="%s" data-end="%s">
                    <div class="wec-time">[%s]</div>
                    <div class="wec-content-wrap"><div class="wec-sub-en">%s</div><div class="wec-sub-vi">%s</div></div>
                </div>',
                $start, $end, $time_str, $text_en, $text_vi
            );
        }
        $html .= '</div></div>'; // Close box
    }
    $html .= '</div>'; // Close col-transcript
    $html .= '</div>'; // Close container

    return $html . '<div style="margin-top:30px; clear:both;">' . $content . '</div>';
}
add_filter( 'the_content', 'wec_add_video_player_to_content' );