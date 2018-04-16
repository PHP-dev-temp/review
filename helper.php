<?php

function saveReview(){
    $product_id = $_POST['product_id'];
    $image_id = $_POST['image_id'];
    $rating = $_POST['rating'];
    $key = $_POST['key'];
    $rev_title = $_POST['rev_title'];
    $rev_content = $_POST['rev_content'];
    $product_name = $_POST['product_name'];
    $taxonomy = 'bundle';

    // Create new Review
    if (!$product_id) {
        $product_id = '0';
        $image_id = '0';
        $rating = '0';
        $product_name = '';
        $taxonomy = 'global';
    }
    $postarr=array(
        'post_type' => 'reviews',
        'post_title'   => $rev_title,
        'post_content' => $rev_content,
        'post_status'  => 'private',
        'post_author'  => 1,
    );
    $post_id = wp_insert_post($postarr);
    wp_set_object_terms($post_id, $taxonomy, 'review_scope');

    // Add custom fields

    update_field('key', $key, $post_id);
    update_field('rating', $rating, $post_id);
    update_field('bundle_id', $product_id, $post_id);
    update_field('image_id', $image_id, $post_id);
    update_field('bundle_name', $product_name, $post_id);



    // Start output

    echo '<div class="popup-title">Thanks for review!</div>';

    die;
}
add_action('wp_ajax_save_review', 'saveReview');
add_action('wp_ajax_nopriv_save_review', 'saveReview');