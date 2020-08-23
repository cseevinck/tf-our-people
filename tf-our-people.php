<?php

/**
 * Plugin Name: TF Our People by CHS Consulting
 * Plugin URI: https://CHS-Webs.com/plugin/a-people
 * Description: Our People plugin (tf-our-people) for TF-Sandy.org (Uses Ajax to refresh). When installed, see plugin settings page for instructions.
 * Author: Corky Seevinck.
 * Author URI: https://CHS-Webs.com
 * Version: 1.1.0
 * Text Domain: chs-webs.com
 * Domain Path: /language.
 */

include_once WP_PLUGIN_DIR.'/tf-our-people/assets/shortcode-register.php';
include_once WP_PLUGIN_DIR.'/tf-our-people/assets/option-page-register.php';
include_once WP_PLUGIN_DIR.'/tf-our-people/assets/constants.php';

// define the actions for 2 hooks created, for logged in users and for logged out users
add_action('wp_ajax_do_tf_our_people', 'do_tf_our_people');
add_action('wp_ajax_nopriv_do_tf_our_people', 'do_tf_our_people');

/**
 * Function to be fired for all users
 */
function do_tf_our_people()
{
    $post_id = $_POST['post_id']; // get the post id from the AJAX data structure
    
    wp_enqueue_script("jquery");

    // nonce check for an extra layer of security, the function will exit if it fails
    if (!wp_verify_nonce($_REQUEST['nonce'], 'tf_op_nonce')) {
        exit('TF Our People plugin nonce mismatch');
    }

    // Get values from the plugin settings page
    $options = get_option('tf_op_settings');

    $display_all_field = $options['display_all_field']; // if = INCLUDE_ALL then display all
    $display_num_people_field = $options['display_num_people_field'];
    $display_choice_field = $options['display_choice_field'];

    // Here for code to build JSON from meta info for users

    wp_enqueue_script('jquery');

    global $wpdb;
    $order = 'user_nicename';
    $users = $wpdb->get_results("SELECT * FROM $wpdb->users ORDER BY $order"); // query users
    // error_log(print_r($users->user_email, true)); 

    // $post_meta = get_post_meta($post_id, '', false);
    // error_log(print_r($post_meta, true));

    // assign correct meta_field names
    
    // used by administrators to include/exclude people from the list
    $admin_meta_omit_from_ourpeople = 'tf_admin_force_exclude_from_our_people';
    $usr_meta_show_in_directory = 'tf_include_our_people';
    $usr_meta_nm_last = 'last_name';
    $usr_meta_nm_frst = 'first_name';
    $usr_meta_is_staff = 'tf_staff_our_people';
    $usr_meta_is_elder = 'tf_our_people_elder';
    $usr_meta_bio = 'tf_our_people_bio';
    $usr_meta_pict = 'pie_profile_pic_7';
    $usr_meta_oth_pict = 'profile_photo';
    $template_bio = 'This person does not have a bio in the system.';

    // comment out Gary's code that allows for defining meta names in post

    // $admin_meta_omit_from_ourpeople = get_post_meta($post_id, 'list_var_admin_omit_from_ourpeople', true);
    // if (strlen($admin_meta_omit_from_ourpeople) == 0) {
    //     $admin_meta_omit_from_ourpeople = 'tf_admin_force_exclude_from_our_people';
    // }

    // // used by user to include/exclude themselves from the list
    // $usr_meta_show_in_directory = get_post_meta($post_id, 'list_var_show_in_ourpeople', true);
    // if (strlen($usr_meta_show_in_directory) == 0) {
    //     $usr_meta_show_in_directory = 'tf_include_our_people';
    // }

    // $usr_meta_nm_last = get_post_meta($post_id, 'list_var_lastname', true);
    // if (strlen($usr_meta_nm_last) == 0) {
    //     $usr_meta_nm_last = 'last_name';
    // }

    // $usr_meta_nm_frst = get_post_meta($post_id, 'list_var_firstname', true);
    // if (strlen($usr_meta_nm_frst) == 0) {
    //     $usr_meta_nm_frst = 'first_name';
    // }

    // $usr_meta_is_staff = get_post_meta($post_id, 'list_var_is_staff', true);
    // if (strlen($usr_meta_is_staff) == 0) {
    //     $usr_meta_is_staff = 'tf_staff_our_people';
    // }

    // // TF_Staff_Elder
    // // In WP Admin pages: User -> User Meta Manager to maintain the field
    // // staff, elder, staff & elder
    // $usr_meta_is_elder = get_post_meta($post_id, 'list_var_is_elder', true);
    // if (strlen($usr_meta_is_elder) == 0) {
    //     $usr_meta_is_elder = 'tf_our_people_elder';
    // }

    // $usr_meta_bio = get_post_meta($post_id, 'list_var_bio', true);
    // if (strlen($usr_meta_bio) == 0) {
    //     $usr_meta_bio = 'tf_our_people_bio';
    // }

    // $usr_meta_pict = get_post_meta($post_id, 'list_var_pict', true);
    // if (strlen($usr_meta_pict) == 0) {
    //     $usr_meta_pict = 'pie_profile_pic_7';
    // }

    // $usr_meta_oth_pict = get_post_meta($post_id, 'list_var_other_pict', true);
    // if (strlen($usr_meta_oth_pict) == 0) {
    //     $usr_meta_oth_pict = 'profile_photo';
    // }

    //
    // start users' profile "loop" - created array will be $peopleArray
    //

    // Start building what will be the json sent through AJAX
    $peopleArray = [
        'displayAll' => $display_all_field,
        'displayNumber' => $display_num_people_field,
        'displayChoice' => $display_choice_field,
        'persons' => array(), // all the people who are not staff or elders
        'elders' => array(),  // all the elders
        'staffs' => array(),  // all the staff members 
        'leaders' => array(), // all elders and staff members 
    ];

    foreach ($users as $user) :

        $all_meta_for_user = get_user_meta($user->ID);
        $show_user = true;

        $has_include_str = false;
        if (isset($all_meta_for_user[$usr_meta_show_in_directory][0])) {
            $has_include_str = strpos(strtolower($all_meta_for_user[$usr_meta_show_in_directory][0]), 'yes');
        }

        $has_omit_str = false;
        if (isset($all_meta_for_user[$admin_meta_omit_from_ourpeople][0])) {
            $has_omit_str = strpos(strtolower($all_meta_for_user[$admin_meta_omit_from_ourpeople][0]), 'yes');
        }

        $bio_str_len = 0;
        if (isset($all_meta_for_user[$usr_meta_bio][0])) {
            $bio_str_len = strlen(trim($all_meta_for_user[$usr_meta_bio][0]));
        }

        if ($has_omit_str !== false || $has_include_str === false || $bio_str_len == 0) {
            $show_user = false;
        }

        // if this person should not be displayed - continue
        if (!$show_user && ($display_all_field != INCLUDE_ALL)) {
            // Ignore the hiding of people if settings want ALL displayed
            continue;
        }

        $is_staff = strpos(strtolower($all_meta_for_user[$usr_meta_is_staff][0]), 'yes');

        //
        // Get all the meta data for the person
        //
        $all_meta_for_user = get_user_meta($user->ID);

        $is_staff = strpos(strtolower($all_meta_for_user[$usr_meta_is_staff][0]), 'yes') > -1;
        $is_elder = strpos(strtolower($all_meta_for_user[$usr_meta_is_elder][0]), 'yes') > -1;
        $our_bio = trim($all_meta_for_user[$usr_meta_bio][0]);

        if (strlen($our_bio) == 0) {
            $our_bio = $template_bio;
        }

        $fullname = trim($all_meta_for_user[$usr_meta_nm_frst][0]).' '.trim($all_meta_for_user[$usr_meta_nm_last][0]);

        if (strlen($fullname) < 2) {
            // "fullname less than 2");
            $fullname = 'id:'.$user->user_login;
        }

        $oth_pict = null;
        if (isset($all_meta_for_user[$usr_meta_oth_pict][0])) {
            $oth_pict = $all_meta_for_user[$usr_meta_oth_pict][0];
        }
        $pictLink = op_getPictLink($all_meta_for_user[$usr_meta_pict][0], $oth_pict, $user->ID);

        //
        // build entry for person or leader
        //

        $newEntry = array(
                'name' => $fullname,
                'staff' => $is_staff,
                'elder' => $is_elder,
                'photo' => $pictLink,
                'bio' => $our_bio
            );
  
        if (!$is_staff && !$is_elder) { // regular person
            $peopleArray['persons'][] = $newEntry;
        } elseif (!$is_staff && $is_elder) { // elder only
            $peopleArray['elders'][] = $newEntry;
            $peopleArray['leaders'][] = $newEntry;
        } elseif ($is_staff && !$is_elder) { // staff only
            $peopleArray['staffs'][] = $newEntry;
            $peopleArray['leaders'][] = $newEntry;
        } else { // must be staff & elder
            $peopleArray['leaders'][] = $newEntry;
            $peopleArray['elders'][] = $newEntry;
            $peopleArray['staffs'][] = $newEntry;
        }

        endforeach; // end of the users' profile 'loop'

        // Check if action was fired via Ajax call. If yes, JS code will be triggered, 
        //       else the user is redirected to the post page

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $peopleArray = json_encode($peopleArray);

        if (!is_JSON($peopleArray)) {
            $error = json_last_error_msg();
            error_log("Not valid JSON string ($error)");
            die();
        }

        echo $peopleArray;
    } else {
        error_log("Did not come here with AJAX");
        header('Location: '.$_SERVER['HTTP_REFERER']);
    }

    // End scripts with a die() function - very important
    die();
}

/**
 * Function to ensure valid json data
 */
function is_JSON(...$args) 
{
    json_decode(...$args);
    return json_last_error() === JSON_ERROR_NONE;
}

// Fires after WordPress has finished loading, but before any headers are sent.
add_action('init', 'b_script_enqueuer');
function b_script_enqueuer() 
{
    // Register the JS file with a unique handle, file location, and an array of dependencies
    wp_register_script('tf_our_people', plugin_dir_url(__FILE__).'tf_our_people.js', array('jquery'));

    // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
    wp_localize_script('tf_our_people', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

    // enqueue jQuery library and the script you registered above
    wp_enqueue_script('jquery');
    wp_enqueue_script('tf_our_people');
}

/**
 * Enqueue css styles only if we are on the correct page
 */
function register_tf_op_style() 
{
    $options = get_option('tf_op_settings');
    $slug = $options['display_op_slug_field']; // get name of Our People slug from options

    if (is_page($slug)) {
        wp_enqueue_style('tf-our-people-css', plugin_dir_url(__FILE__).'/assets/tf-our-people.css');
    }
}
add_action('wp_enqueue_scripts', 'register_tf_op_style');

/**
 * Enqueue script that will generate an auto click to ensure that the page 
 * is populated on first load.
 */
function enqueue_auto_click_script() 
{
    wp_register_script('auto_click_script', plugin_dir_url(__FILE__).'/assets/tf-op-auto-click-script.js', array('jquery'), '1.1', true);

    wp_enqueue_script('auto_click_script', 9999);
}
add_action('wp', 'enqueue_auto_click_script');

/**
 * Plugin Add Settings Link to main plugin list
 * 
 */
add_filter('plugin_action_links_'.plugin_basename(__FILE__),'salcode_add_plugin_page_settings_link');
function salcode_add_plugin_page_settings_link( $links ) {
    $links[] = '<a href="' .
        admin_url('options-general.php?page=tf_op_settings-api-page') .'">' . __('Settings') . '</a>';
    return $links;
}