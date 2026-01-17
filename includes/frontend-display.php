<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- HELPER FUNCTIONS (GIỮ NGUYÊN) ---
function wec_get_youtube_id( $url ) {
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if ( preg_match( $pattern, $url, $match ) ) return $match[1];
    return false;
}

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

// --- MAIN FUNCTION ---
function wec_add_video_player_to_content( $content ) {
    if ( ! is_singular( 'video_lesson' ) ) return $content;

    $post_id = get_the_ID();
    $video_url = get_post_meta( $post_id, 'wec_video_url', true );
    $sub_en_url = get_post_meta( $post_id, 'wec_subtitle_en', true );
    $sub_vi_url = get_post_meta( $post_id, 'wec_subtitle_vi', true );

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

    // 2. DICTIONARY POPUP
    $transcript_html = '<div id="wec-dict-popup" class="wec-dict-popup" style="display:none;">
                            <div class="wec-dict-header">
                                <span id="wec-dict-word">Word</span>
                                <span id="wec-dict-close">&times;</span>
                            </div>
                            <div id="wec-dict-body">Checking...</div>
                         </div>';

    // 3. TRANSCRIPT BOX (SMART MERGE LOGIC)
    if ( ! empty( $sub_en_url ) || ! empty( $sub_vi_url ) ) {
        
        $subs_en = !empty($sub_en_url) ? wec_parse_vtt( $sub_en_url ) : [];
        $subs_vi = !empty($sub_vi_url) ? wec_parse_vtt( $sub_vi_url ) : [];

        $transcript_html .= '<div class="wec-transcript-box">';
        
        // Toolbar
        $transcript_html .= '<div class="wec-transcript-header">
            <div class="wec-title">Transcript</div>
            <div class="wec-modes">
                <button class="wec-mode-btn active" data-mode="bilingual">Song ngữ</button>
                <button class="wec-mode-btn" data-mode="en">Tiếng Anh</button>
                <button class="wec-mode-btn" data-mode="vi">Tiếng Việt</button>
                <button class="wec-mode-btn" data-mode="hidden">Ẩn</button>
            </div>
        </div>';

        $transcript_html .= '<div id="wec-transcript-content" class="mode-bilingual">';
        
        // Chọn danh sách chuẩn để loop (ưu tiên EN)
        $base_subs = !empty($subs_en) ? $subs_en : $subs_vi;
        
        foreach ( $base_subs as $index => $sub ) {
            $start = $sub['start'];
            $end   = $sub['end'];
            $time_str = $sub['time_str'];
            
            // XỬ LÝ TIẾNG ANH (Tách từ)
            $text_en_html = '';
            if ( !empty($subs_en) ) {
                $raw_en = isset($subs_en[$index]) ? $subs_en[$index]['text'] : '';
                $words = explode( ' ', esc_html($raw_en) );
                foreach ( $words as $word ) {
                    if ( trim($word) !== '' ) {
                        $text_en_html .= '<span class="wec-word">' . $word . '</span> '; 
                    }
                }
            }

            // XỬ LÝ TIẾNG VIỆT (SMART MATCHING)
            $text_vi_html = '';
            if ( !empty($subs_vi) ) {
                $best_match_vi = '';
                $min_diff = 2.0; // Cho phép sai số tối đa 2 giây (Trước đây là 0.5)

                foreach($subs_vi as $vi_item) {
                    $diff = abs($vi_item['start'] - $start);
                    
                    // Tìm câu có thời gian gần nhất
                    if ( $diff < $min_diff ) {
                        $min_diff = $diff;
                        $best_match_vi = $vi_item['text'];
                    }
                }
                
                $text_vi_html = esc_html($best_match_vi);
            }

            // Render
            $transcript_html .= sprintf(
                '<div class="wec-transcript-line" data-start="%s" data-end="%s">
                    <div class="wec-time">[%s]</div> 
                    <div class="wec-content-wrap">
                        <div class="wec-sub-en">%s</div>
                        <div class="wec-sub-vi">%s</div>
                    </div>
                </div>',
                $start,
                $end,
                $time_str,
                $text_en_html,
                $text_vi_html
            );
        }
        
        $transcript_html .= '</div></div>';
    }

    return $player_html . $transcript_html . $content;
}
add_filter( 'the_content', 'wec_add_video_player_to_content' );