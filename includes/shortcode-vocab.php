<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode 1: [wec_my_vocab] - Xem danh sách
 */
function wec_shortcode_my_vocab() {
    if ( ! is_user_logged_in() ) return '<p>Vui lòng đăng nhập để xem từ vựng.</p>';

    global $wpdb;
    $table_name = $wpdb->prefix . 'wec_user_vocab';
    $user_id = get_current_user_id();
    $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id) );

    if ( empty( $results ) ) return '<p>Bạn chưa lưu từ vựng nào.</p>';

    ob_start();
    ?>
    <div class="wec-vocab-list">
        <h3>Kho từ vựng của tôi (<?php echo count($results); ?> từ)</h3>
        <table class="wec-table">
            <thead><tr><th>Từ vựng</th><th>Nghĩa</th><th>Video gốc</th><th>Ngày lưu</th></tr></thead>
            <tbody>
                <?php foreach ( $results as $row ) : 
                    $video_link = get_permalink( $row->video_id );
                ?>
                <tr>
                    <td><strong><?php echo esc_html( $row->word ); ?></strong></td>
                    <td><?php echo esc_html( $row->meaning ); ?></td>
                    <td><a href="<?php echo esc_url( $video_link ); ?>">Xem video</a></td>
                    <td><?php echo date_i18n( 'd/m/Y', strtotime( $row->created_at ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wec_my_vocab', 'wec_shortcode_my_vocab' );

/**
 * Shortcode 2: [wec_vocab_quiz] - Trắc nghiệm (MỚI)
 */
function wec_shortcode_vocab_quiz() {
    if ( ! is_user_logged_in() ) return '<p>Vui lòng đăng nhập để làm bài tập.</p>';

    global $wpdb;
    $table_name = $wpdb->prefix . 'wec_user_vocab';
    $user_id = get_current_user_id();
    
    // Lấy tối đa 20 từ ngẫu nhiên để làm Quiz
    $results = $wpdb->get_results( $wpdb->prepare("SELECT word, meaning FROM $table_name WHERE user_id = %d ORDER BY RAND() LIMIT 20", $user_id) );

    if ( count($results) < 4 ) {
        return '<p class="wec-alert">Bạn cần lưu ít nhất 4 từ vựng để bắt đầu làm trắc nghiệm!</p>';
    }

    // Chuẩn bị dữ liệu JSON cho JS
    $quiz_data = array();
    foreach ($results as $row) {
        $quiz_data[] = array('word' => $row->word, 'meaning' => $row->meaning);
    }

    ob_start();
    ?>
    <div id="wec-quiz-container">
        <div class="wec-quiz-header">
            <h3>Ôn tập Từ vựng</h3>
            <span id="wec-quiz-score">Điểm: 0</span>
        </div>
        <div id="wec-quiz-card">
            <div id="wec-quiz-question">Ready?</div>
            <div id="wec-quiz-options">
                <button id="wec-quiz-start-btn">Bắt đầu</button>
            </div>
            <div id="wec-quiz-feedback"></div>
        </div>
    </div>

    <!-- Truyền dữ liệu xuống biến JS -->
    <script>
        var wecQuizData = <?php echo json_encode($quiz_data); ?>;
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wec_vocab_quiz', 'wec_shortcode_vocab_quiz' );