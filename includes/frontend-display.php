<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- GIỮ NGUYÊN HÀM HELPER CŨ ---
function wec_get_youtube_id( $url ) {
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if ( preg_match( $pattern, $url, $match ) ) return $match[1];
    return false;
}

function wec_parse_vtt( $vtt_url ) {
    // Logic đọc file Local Path (Giữ nguyên như cũ)
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
    
    $subs = [];
    $current_sub = [];
    
    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) || $line === 'WEBVTT' || is_numeric($line) ) continue;

        if ( preg_match( '/(\d{2}:\d{2}:\d{2}\.\d{3}) --> (\d{2}:\d{2}:\d{2}\.\d{3})/', $line, $matches ) ) {
            if ( ! empty( $current_sub ) ) { $subs[] = $current_sub; $current_sub = []; }
            
            $start_parts = explode( ':', $matches[1] );
            $start = $start_parts[0] * 3600 + $start_parts[1] * 60 + (float)$start_parts[2];
            
            $end_parts = explode( ':', $matches[2] );
            $end = $end_parts[0] * 3600 + $end_parts[1] * 60 + (float)$end_parts[2];

            $current_sub['start'] = $start;
            $current_sub['end']   = $end;
            $current_sub['time_str'] = substr($matches[1], 0, 8); 
            $current_sub['text'] = '';
        } elseif ( isset( $current_sub['start'] ) ) {
            $current_sub['text'] .= ($current_sub['text'] === '' ? '' : ' ') . $line;
        }
    }
    if ( ! empty( $current_sub ) ) { $subs[] = $current_sub; }
    return $subs;
}

// --- CẬP NHẬT HÀM CHÍNH (THÊM POPUP & TÁCH TỪ) ---
function wec_add_video_player_to_content( $content ) {
    if ( ! is_singular( 'video_lesson' ) ) return $content;

    $post_id = get_the_ID();
    $video_url = get_post_meta( $post_id, 'wec_video_url', true );
    $subtitle_url = get_post_meta( $post_id, 'wec_subtitle_url', true );

    if ( empty( $video_url ) ) return $content;

    // 1. VIDEO PLAYER
    $player_html = '<div class="wec-video-wrapper" style="margin-bottom: 20px;">';
    $youtube_id = wec_get_youtube_id( $video_url );

    if ( $youtube_id ) {
        $player_html .= '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; border-radius: 8px;">';
        $player_html .= '<iframe id="wec-yt-iframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" src="https://www.youtube.com/embed/' . $youtube_id . '?enablejsapi=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        $player_html .= '</div>';
    } else {
        $player_html .= '<video id="wec-main-player" controls style="width: 100%; border-radius: 8px; background: #000;">';
        $player_html .= '<source src="' . esc_url( $video_url ) . '" type="video/mp4">';
        $player_html .= '</video>';
    }
    $player_html .= '</div>';

    // 2. DICTIONARY POPUP (MỚI)
    $transcript_html = '<div id="wec-dict-popup" class="wec-dict-popup" style="display:none;">
                            <div class="wec-dict-header">
                                <span id="wec-dict-word">Word</span>
                                <span id="wec-dict-close">&times;</span>
                            </div>
                            <div id="wec-dict-body">Đang tải nghĩa...</div>
                         </div>';

    // 3. TRANSCRIPT BOX
    if ( ! empty( $subtitle_url ) ) {
        $subs = wec_parse_vtt( $subtitle_url );
        if ( ! empty( $subs ) ) {
            $transcript_html .= '<div class="wec-transcript-box">';
            $transcript_html .= '<div class="wec-transcript-header">Lời thoại (Transcript)</div>';
            $transcript_html .= '<div id="wec-transcript-content">';
            
            foreach ( $subs as $sub ) {
                // LOGIC TÁCH TỪ (MỚI)
                $raw_text = esc_html( $sub['text'] );
                $words = explode( ' ', $raw_text );
                $processed_text = '';
                foreach ( $words as $word ) {
                    if ( trim($word) !== '' ) {
                        // Thêm class wec-word để JS bắt sự kiện
                        $processed_text .= '<span class="wec-word">' . $word . '</span> '; 
                    }
                }

                $transcript_html .= sprintf(
                    '<div class="wec-transcript-line" data-start="%s" data-end="%s">
                        <span class="wec-time">[%s]</span> 
                        <span class="wec-text">%s</span>
                    </div>',
                    $sub['start'],
                    $sub['end'],
                    $sub['time_str'],
                    $processed_text // Text đã tách
                );
            }
            $transcript_html .= '</div></div>';
        }
    }
    return $player_html . $transcript_html . $content;
}
add_filter( 'the_content', 'wec_add_video_player_to_content' );