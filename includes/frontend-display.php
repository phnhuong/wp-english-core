<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: Lấy ID Youtube từ URL
 */
function wec_get_youtube_id( $url ) {
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if ( preg_match( $pattern, $url, $match ) ) {
        return $match[1];
    }
    return false;
}

/**
 * Helper: Parse VTT (Đọc Local Path & Fix lỗi UTF-8)
 */
function wec_parse_vtt( $vtt_url ) {
    $upload_dir = wp_upload_dir();
    $base_url   = $upload_dir['baseurl'];
    $base_dir   = $upload_dir['basedir'];
    
    // Thay thế URL bằng đường dẫn thư mục để đọc local
    $vtt_path = str_replace( $base_url, $base_dir, $vtt_url );

    if ( file_exists( $vtt_path ) ) {
        $content = file_get_contents( $vtt_path );
    } else {
        $response = wp_remote_get( $vtt_url );
        if ( is_wp_error( $response ) ) return [];
        $content = wp_remote_retrieve_body( $response );
    }
    
    // Xử lý BOM
    $bom = pack('H*','EFBBBF');
    $content = preg_replace("/^$bom/", '', $content);
    
    // Chuẩn hóa xuống dòng
    $content = str_replace(array("\r\n", "\r"), "\n", $content);
    $lines = explode( "\n", $content );
    
    $subs = [];
    $current_sub = [];
    
    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) || $line === 'WEBVTT' || is_numeric($line) ) continue;

        if ( preg_match( '/(\d{2}:\d{2}:\d{2}\.\d{3}) --> (\d{2}:\d{2}:\d{2}\.\d{3})/', $line, $matches ) ) {
            if ( ! empty( $current_sub ) ) {
                $subs[] = $current_sub;
                $current_sub = [];
            }
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
    if ( ! empty( $current_sub ) ) {
        $subs[] = $current_sub;
    }
    return $subs;
}

/**
 * Main: Render Video + Transcript
 */
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

    // 2. TRANSCRIPT BOX
    $transcript_html = '';
    if ( ! empty( $subtitle_url ) ) {
        $subs = wec_parse_vtt( $subtitle_url );
        if ( ! empty( $subs ) ) {
            $transcript_html .= '<div class="wec-transcript-box">';
            $transcript_html .= '<div class="wec-transcript-header">Lời thoại (Transcript)</div>';
            $transcript_html .= '<div id="wec-transcript-content">';
            foreach ( $subs as $sub ) {
                $transcript_html .= sprintf(
                    '<div class="wec-transcript-line" data-start="%s" data-end="%s">
                        <span class="wec-time">[%s]</span> 
                        <span class="wec-text">%s</span>
                    </div>',
                    $sub['start'],
                    $sub['end'],
                    $sub['time_str'],
                    esc_html( $sub['text'] )
                );
            }
            $transcript_html .= '</div></div>';
        }
    }
    return $player_html . $transcript_html . $content;
}
add_filter( 'the_content', 'wec_add_video_player_to_content' );