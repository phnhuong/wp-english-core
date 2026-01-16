<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function wec_register_video_cpt() {
    $labels = array(
        'name'          => 'Video Lessons',
        'singular_name' => 'Video Lesson',
        'menu_name'     => 'Bài học Video',
        'add_new'       => 'Thêm bài mới',
        'edit_item'     => 'Sửa Video Lesson',
        'all_items'     => 'Tất cả bài học',
    );

    $args = array(
        'labels'      => $labels,
        'public'      => true,
        'has_archive' => true,
        'menu_icon'   => 'dashicons-format-video',
        'supports'    => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest'=> true, // Hỗ trợ trình soạn thảo Block
    );

    register_post_type( 'video_lesson', $args );
}
add_action( 'init', 'wec_register_video_cpt' );

function wec_register_taxonomies() {
    register_taxonomy( 'english_level', 'video_lesson', array(
        'label'        => 'Trình độ',
        'hierarchical' => true,
        'show_in_rest' => true,
    ) );

    register_taxonomy( 'english_topic', 'video_lesson', array(
        'label'        => 'Chủ đề',
        'hierarchical' => true,
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'wec_register_taxonomies' );