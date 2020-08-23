<?php
/**
 * Define the TF Our People plugin settings page
 */
add_action('admin_menu', 'tf_op_add_admin_menu');
add_action('admin_init', 'tf_op_add_settings_init');

function tf_op_add_admin_menu()
{
    add_options_page(
        'TF Our People Options', 
        'TF Our People Options', 
        'manage_options', 
        'tf_op_settings-api-page', 
        'tf_op_options_page'
    );
}

function tf_op_add_settings_init()
{
    register_setting(
        'tf_our_people_plugin', 
        'tf_op_settings', 
        'tf_op_validate'
    );
    add_settings_section(
        'tf_op_plugin_section',
        __(' ', 'wordpress'),
        'tf_op_settings_section_callback',
        'tf_our_people_plugin'
    );

    add_settings_field(
        'display_choice_field',
        __('Choose categories to allways include', 'wordpress'),
        'display_choice_render',
        'tf_our_people_plugin',
        'tf_op_plugin_section'
    );

    add_settings_field(
        'display_all_field',
        __('Test mode. Display all people', 'wordpress'),
        'display_all_people_render',
        'tf_our_people_plugin',
        'tf_op_plugin_section'
    );

    add_settings_field(
        'display_num_people_field',
        __('Number of people per page (even number)', 'wordpress'),
        'display_number_of_people_render',
        'tf_our_people_plugin',
        'tf_op_plugin_section'
    );

    add_settings_field(
        'display_op_slug_field',
        __('Name of Our People page slug', 'wordpress'),
        'display_op_slug_render',
        'tf_our_people_plugin',
        'tf_op_plugin_section'
    );

    add_settings_field(
        'display_php_slug_field',
        __('Name of php page for Our People page slug', 'wordpress'),
        'display_php_slug_render',
        'tf_our_people_plugin',
        'tf_op_plugin_section'
    );
}

function display_choice_render()
{
    $options = get_option('tf_op_settings');

    ?>
    <select name="tf_op_settings[display_choice_field]">
        <option value='1' <?php selected($options['display_choice_field'], '1'); ?>>
        Allways display Elders</option>
        <option value='2' <?php selected($options['display_choice_field'], '2'); ?>>
        Allways display Staff</option>
        <option value='3' <?php selected($options['display_choice_field'], '3'); ?>>
        Allways display Staff & Elders</option>
        <option value='4' <?php selected($options['display_choice_field'], '4'); ?>>
        No forced displays</option>
    </select>
         
    <?php
}

function display_all_people_render()
{
    $options = get_option('tf_op_settings');
    ?>
    <select name="tf_op_settings[display_all_field]">
        <option value='1' <?php selected($options['display_all_field'], 1); ?>>
        Display number of people per page (below)</option>
        <option value='2' <?php selected($options['display_all_field'], 2); ?>>
        Test mode. Display all people</option>
    </select>
    <?php
}

function display_number_of_people_render()
{
    $options = get_option('tf_op_settings');
    ?>
    <input type='text' name='tf_op_settings[display_num_people_field]' value='<?php echo $options['display_num_people_field']; ?>'>
    <?php
}

function display_op_slug_render()
{
    $options = get_option('tf_op_settings');
    ?>
    <input type='text' name="tf_op_settings[display_op_slug_field]" 
    value='<?php echo $options['display_op_slug_field']; ?>'>
    <?php
}

function display_php_slug_render()
{
    $options = get_option('tf_op_settings');
    ?>
    <input type='text' name="tf_op_settings[display_php_slug_field]" 
    value='<?php echo $options['display_php_slug_field']; ?>'>
    <?php
}

/**
 * Returns the default options for Our People.
 *
 */
function tf_op_get_default_options() 
{
    $default_op_options = array(
    'display_choice_field' => 3,
    'display_all_field' => 1,
    'display_num_people_field' => 12,
    'display_op_slug_field' => 'ourpeople',        
    'display_php_slug_field' => 'php-ourpeople'        
    );

    return apply_filters('tf_op_default_options', $default_op_options);
}

/**
 * Sanitize and validate form input. Accepts an array, return a sanitized array.
 *
 * @see tf_op_add_settings_init()
 *
 */
function tf_op_validate( $input ) 
{
    // error_log("validate input = " . print_r($input, true));
    $type = 'error';
    $output = $defaults = tf_op_get_default_options();

    // Validate the ['display_choice_field']
    if (is_numeric($input['display_choice_field']) && $input['display_choice_field'] >= 1 
        && $input['display_choice_field'] <= 4
    ) {
        $output['display_choice_field'] = $input['display_choice_field'];
    }

    // Validate the ['display_all_field']
    if (is_numeric($input['display_all_field']) && $input['display_all_field'] >= 1 
        && $input['display_all_field'] <= 2
    ) {
        $output['display_all_field'] = $input['display_all_field'];
    }

    // Validate the ['display_num_people_field']
    $message = '';
    $num = $input['display_num_people_field'];
    if (!is_numeric($num)) {
        $message = __('Number of people per page must be an integer');
    } else if ($num < 6) {
        $message = __('Number of people per page must be greater than 5');
    };

    if ($message == '') {
        $num = floor($num); // Round down decimals to an integer
        if ($num % 2 == 1) { // If odd, add one and notify user
            $num++; 
            $type = 'info';
            $message = __('Number of people per page must be even - rounded up');
            add_settings_error(
                'tf_op_settings',
                esc_attr('invalid_number_of_people'),   
                $message,
                $type
            );
        };
        $output['display_num_people_field'] = $num;
    } else {
        add_settings_error(
            'tf_op_settings',
            esc_attr('invalid_number_of_people'),
            $message,
            $type
        );
    }   

    // Validate the ['display_op_slug_field']
    $tempSlug = sanitize_title($input['display_op_slug_field']);
    if (strlen($tempSlug) > 2 ) {
        $output['display_op_slug_field'] = $tempSlug;
    } else {
        $message = __('Our People page slug must be 2 or more characters long and can only contain alpha & numeric and dash (-) & underscore (_) characters');
        add_settings_error(
            'tf_op_settings',
            esc_attr('invalid_slug'),
            $message,
            $type
        );
    }

    // Validate the ['display_php_slug_field']
    $tempSlug = sanitize_title($input['display_php_slug_field']);
    if (strlen($tempSlug) > 2 ) {
        $output['display_php_slug_field'] = $tempSlug;
    } else {
        $message = __('Our People php page slug must be 2 or more characters long and can only contain alpha & numeric and dash (-) & underscore (_) characters');
        add_settings_error(
            'tf_op_settings',
            esc_attr('invalid_slug'),
            $message,
            $type
        );
    }

    return apply_filters('tf_op_validate', $output, $input, $defaults);
}

function tf_op_settings_section_callback()
{
    echo __(' ', 'wordpress');
}

function tf_op_options_page() 
{
    ?>
<h1>TF Our People plugin </h1>
<p>Once the plugin is installed, create a DIVI page and add a "code" section and place the following in the section:<br>
<pre>
    &lt;div id="tf_spinner"&gt;&lt;div&gt;
    &lt;div id="our-people-top"&gt;&lt;/div&gt;
    [TFOurPeople]
    &lt;div id="our-people-bottom"&gt;&lt;/div&gt;
</pre>
Define the options you want for the display of the Our People page display below. <br>Put the "slug" name of the new page in the "Name of Our People page slug" field.
Define the options you want for the display of the Our People page display below. <br>Put the "slug" name of the old php page in the "Name of Our People php page slug" field. This is because there are times that ajax code fails(??) and it cause the code to revert to the php version. 
 
</p>
    <form action='options.php' method='post'>

        <h2>TF Our People Admin Page</h2>
        <!-- Our Section Title -->
        <?php
            settings_fields('tf_our_people_plugin');
            do_settings_sections('tf_our_people_plugin');
            submit_button();
        ?>
    </form>
    <?php
}