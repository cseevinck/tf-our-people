<?php
/**
 * WP [TFOurPeople] Shortcode processing here 
 */

add_shortcode('TFOurPeople', 'tf_include_op_code');
function tf_include_op_code()
{
    if (is_admin()) {
        error_log('tf_op: is_admin - early out of plugin code');
        return;
    }

    global $post;
    
    // Container for Our People display
    echo '<div class="our_people_list" id="people-list">';
    echo '</div>';

    // Linking to the admin-ajax.php file. Nonce check included for extra security. 
    // Note the "user_like" class for JS enabled clients.

    $b_nonce = wp_create_nonce('tf_op_nonce');

    $link = admin_url('admin-ajax.php?action=do_tf_our_people&post_id='.$post->ID.'&nonce='.$b_nonce);

    echo '<a class="user_like" id="for-auto-click" data-nonce="'.$b_nonce.'" data-post_id="'.$post->ID.'" href="'.$link.'"></a>';
}