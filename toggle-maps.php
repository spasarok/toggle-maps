<?php
/* Plugin Name: Toggle Maps */

function toggle_maps_register_scripts(){
    wp_register_style( 'toggle_maps', plugins_url('/toggle-maps.css', __FILE__));
    if(get_option('toggle_maps_google_key'))
        wp_register_script('google_maps', 'http://maps.googleapis.com/maps/api/js?key='.get_option('toggle_maps_google_key'));
    else
        wp_register_script('google_maps', 'http://maps.googleapis.com/maps/api/js');
    wp_register_script( 'toggle_maps', plugins_url('/toggle-maps.js', __FILE__));
}
add_action('wp_enqueue_scripts', 'toggle_maps_register_scripts');

// Create toggle maps shortcode and enqueue scripts
function toggle_maps_handler($atts){
    $post = get_post($atts['id']);

    // Check if we're mapping a single location
    if($post->post_type == 'toggle_maps_location')
        $single = true;
    else if($post->post_type == 'toggle_maps_map')
        $single = false;
    else
        return;

    $terms = wp_get_post_terms($post->ID, 'toggle_maps_category');
    $term_slugs = [];
    $term_names = [];
    foreach($terms as $term){
        $term_slugs[$term->name] = $term->slug;
        $term_names[$term->slug] = $term->name;
    }

    if(get_page_by_path('list-view'))
        $list_view = get_template_directory_uri().'/list-view.php';
    else
        $list_view = false;

    if(get_option('toggle_maps_dev_port'))
        $dev_port = get_option('toggle_maps_dev_port');
    else
        $dev_port = null;

    $map_vals = array(
        'single' => $single,
        'id' => $post->ID,
        'terms' => $terms,
        'termSlugs' => $term_slugs,
        'termNames' => $term_names,
        'listView' => $list_view,
        'devPort' => $dev_port
    );

    // Localize toggle maps script
    wp_enqueue_script('jquery');
    wp_enqueue_style( 'toggle_maps');
    wp_enqueue_script('google_maps');
    wp_enqueue_script( 'toggle_maps');
    wp_localize_script( 'toggle_maps', 'toggleMaps', $map_vals );

    return '<div id="toggle-map"></div>';
}
add_shortcode('toggle-maps', 'toggle_maps_handler');

// Create toggle maps shortcode and enqueue scripts
function toggle_maps_list_handler($atts){
    $post = get_post($atts['id']);

    // Check if we're mapping a single location
    if($post->post_type == 'toggle_maps_map')
        $single = false;
    else
        return;

    $terms = wp_get_post_terms($post->ID, 'toggle_maps_category');
    $term_slugs = [];
    $term_names = [];
    foreach($terms as $term){
        $term_slugs[$term->name] = $term->slug;
        $term_names[$term->slug] = $term->name;
    }

    if(get_page_by_path('list-view'))
        $list_view = get_template_directory_uri().'/list-view.php';
    else
        $list_view = false;

    if(get_option('toggle_maps_dev_port'))
        $dev_port = get_option('toggle_maps_dev_port');
    else
        $dev_port = null;

    $map_vals = array(
        'single' => $single,
        'id' => $post->ID,
        'terms' => $terms,
        'termSlugs' => $term_slugs,
        'termNames' => $term_names,
        'listView' => $list_view,
        'devPort' => $dev_port
    );

    // Localize toggle maps script
    wp_enqueue_script('jquery');
    wp_enqueue_style( 'toggle_maps');
    wp_enqueue_script('google_maps');
    wp_enqueue_script( 'toggle_maps');
    wp_localize_script( 'toggle_maps', 'toggleMaps', $map_vals );

    return '<div id="toggle-map"></div>';
}
add_shortcode('toggle-maps-list', 'toggle_maps_list_handler');

// Create toggle map post type
function create_toggle_maps_maps() {
    register_post_type( 'toggle_maps_map',
        array(
            'labels' => array(
                'name' => __( 'Toggle Maps' ),
                'singular_name' => __( 'Toggle Map' )
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true
        )
    );
}
add_action( 'init', 'create_toggle_maps_maps' );

// Create toggle location post type
function create_toggle_maps_locations() {

    $rewrite = array('slug' => 'locations');
    if(get_option('toggle_maps_rewrite'))
        $rewrite = array('slug' => get_option('toggle_maps_rewrite'));

    register_post_type( 'toggle_maps_location',
        array(
            'labels' => array(
                'name' => __( 'Toggle Locations' ),
                'singular_name' => __( 'Toggle Location' )
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'rewrite' => $rewrite,
        )
    );
}
add_action( 'init', 'create_toggle_maps_locations' );

function toggle_maps_admin_style () {
    global $post_type;
    if( 'toggle_maps_location' == $post_type )
        wp_enqueue_style('toggle_maps', plugins_url('/toggle-maps-admin.css', __FILE__ ));
}
add_action( 'admin_print_scripts-post-new.php', 'toggle_maps_admin_style' );
add_action( 'admin_print_scripts-post.php', 'toggle_maps_admin_style' );

// Create custom plugin settings menu
function toggle_maps_create_menu() {
    add_submenu_page(
        'options-general.php',
        'Toggle Maps',
        'Toggle Maps',
        'administrator',
        'toggle_maps_options_page',
        'toggle_maps_options_page'
    );
    //add_menu_page('Toggle Maps Settings', 'Toggle Maps Settings', 'administrator', __FILE__, 'toggle_maps_options_page' , plugins_url('/images/icon.png', __FILE__) );
    add_action( 'admin_init', 'toggle_maps_register_options' );
}
add_action('admin_menu', 'toggle_maps_create_menu');

// Register settings
function toggle_maps_register_options() {
    register_setting( 'toggle_maps_options_group', 'toggle_maps_google_key' );
    register_setting( 'toggle_maps_options_group', 'toggle_maps_dev_port' );
    register_setting( 'toggle_maps_options_group', 'toggle_maps_rewrite' );
}

// Create options page
function toggle_maps_options_page() {
    ?>
    <div class="wrap">
        <h2>Toggle Maps Options</h2>

        <form method="post" action="options.php">
            <?php settings_fields( 'toggle_maps_options_group' ); ?>
            <?php do_settings_sections( 'toggle_maps_options_group' ); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Google API Key</th>
                    <td><input type="text" class="large-text" name="toggle_maps_google_key" value="<?php echo esc_attr( get_option('toggle_maps_google_key') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Slug Rewrite</th>
                    <td><input type="text" class="large-text" name="toggle_maps_rewrite" value="<?php echo esc_attr( get_option('toggle_maps_rewrite') ); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Dev Port or Directory</th>
                    <td><input type="text" class="large-text" name="toggle_maps_dev_port" value="<?php echo esc_attr( get_option('toggle_maps_dev_port') ); ?>" /></td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

// Create toggle location category taxonomy
function create_toggle_maps_categories(){
    $labels = array(
        'name'              => _x( 'Toggle Categories', 'taxonomy general name' ),
        'singular_name'     => _x( 'Toggle Category', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Toggle Categories' ),
        'all_items'         => __( 'All Toggle Categories' ),
        'parent_item'       => __( 'Parent Toggle Category' ),
        'parent_item_colon' => __( 'Parent Toggle Category:' ),
        'edit_item'         => __( 'Edit Toggle Category' ),
        'update_item'       => __( 'Update Toggle Category' ),
        'add_new_item'      => __( 'Add New Toggle Category' ),
        'new_item_name'     => __( 'New Toggle Category Name' ),
        'menu_name'         => __( 'Toggle Category' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'map-categories' ),
    );

    register_taxonomy( 'toggle_maps_category', array('toggle_maps_map', 'toggle_maps_location'), $args );
}
add_action('init', 'create_toggle_maps_categories');

// Add the meta fields for locations
function add_toggle_maps_location_meta () {
    add_meta_box ( 'toggle-maps-location-meta', 'Toggle Maps', 'toggle_maps_location_meta_callback', 'toggle_maps_location', 'normal');
}
add_action ( 'add_meta_boxes', 'add_toggle_maps_location_meta' );

// Add meta fields for maps
function add_toggle_maps_map_meta () {
    add_meta_box ( 'toggle-maps-map-meta', 'Toggle Maps', 'toggle_maps_map_meta_callback', 'toggle_maps_map', 'normal');
}
add_action ( 'add_meta_boxes', 'add_toggle_maps_map_meta' );

// Initialize meta boxes with their saved values
function toggle_maps_location_meta_callback () {
    global $post;

    $address = get_post_meta($post->ID, 'toggle_maps_address', true);
    echo '<label>Address</label>';
    echo '<input type="text" name="toggle_maps_address" value="' . $address . '" class="widefat" />';

    $phone = get_post_meta($post->ID, 'toggle_maps_phone', true);
    echo '<label>Phone</label>';
    echo '<input type="text" name="toggle_maps_phone" value="' . $phone . '" class="widefat" />';

    $website_link = get_post_meta($post->ID, 'toggle_maps_website_link', true);
    echo '<label>Website Link</label>';
    echo '<input type="text" name="toggle_maps_website_link" value="' . $website_link . '" class="widefat" />';

    $latitude = get_post_meta($post->ID, 'toggle_maps_latitude', true);
    echo '<label>Latitude</label>';
    echo '<input type="text" name="toggle_maps_latitude" value="' . $latitude . '" class="widefat" />';

    $longitude = get_post_meta($post->ID, 'toggle_maps_longitude', true);
    echo '<label>Longitude</label>';
    echo '<input type="text" name="toggle_maps_longitude" value="' . $longitude . '" class="widefat" />';

    $zoom = get_post_meta($post->ID, 'toggle_maps_zoom', true);
    echo '<label>Zoom</label>';
    echo '<input type="text" name="toggle_maps_zoom" value="' . $zoom . '" class="widefat" />';

    echo '<label>Shortcode</label>';
    echo '<p>[toggle-maps id="'.$post->ID.'"]</p>';
}

// Initialize meta boxes with their saved values
function toggle_maps_map_meta_callback () {
    global $post;

    $latitude = get_post_meta($post->ID, 'toggle_maps_latitude', true);
    echo '<label>Center Latitude</label>';
    echo '<input type="text" name="toggle_maps_latitude" value="' . $latitude . '" class="widefat" />';

    $longitude = get_post_meta($post->ID, 'toggle_maps_longitude', true);
    echo '<label>Center Longitude</label>';
    echo '<input type="text" name="toggle_maps_longitude" value="' . $longitude . '" class="widefat" />';

    $zoom = get_post_meta($post->ID, 'toggle_maps_zoom', true);
    echo '<label>Zoom</label>';
    echo '<input type="text" name="toggle_maps_zoom" value="' . $zoom . '" class="widefat" />';

    echo '<label>Shortcode</label>';
    echo '<p>[toggle-maps id='.$post->ID.']</p>';
}

// Save toggle maps meta
function toggle_maps_save_meta() {
    global $post;

    if($post->post_type == 'toggle_maps_location'){
        $meta['toggle_maps_address'] = $_POST['toggle_maps_address'];
        $meta['toggle_maps_phone'] = $_POST['toggle_maps_phone'];
        $meta['toggle_maps_website_link'] = $_POST['toggle_maps_website_link'];
        $meta['toggle_maps_latitude'] = $_POST['toggle_maps_latitude'];
        $meta['toggle_maps_longitude'] = $_POST['toggle_maps_longitude'];
        $meta['toggle_maps_zoom'] = $_POST['toggle_maps_zoom'];
        if(!$meta['toggle_maps_zoom'])
            $meta['toggle_maps_zoom'] = 14;

        foreach ($meta as $key => $value) {

            if(get_post_meta($post->ID, $key, FALSE))
                update_post_meta($post->ID, $key, $value);
            else
                add_post_meta($post->ID, $key, $value);

            if(!$value)
                delete_post_meta($post->ID, $key);
        }
    }

    else if($post->post_type == 'toggle_maps_map'){
        $meta['toggle_maps_latitude'] = $_POST['toggle_maps_latitude'];
        $meta['toggle_maps_longitude'] = $_POST['toggle_maps_longitude'];
        $meta['toggle_maps_zoom'] = $_POST['toggle_maps_zoom'];

        foreach ($meta as $key => $value) {

            if(get_post_meta($post->ID, $key, FALSE))
                update_post_meta($post->ID, $key, $value);
            else
                add_post_meta($post->ID, $key, $value);

            if(!$value)
                delete_post_meta($post->ID, $key);
        }
    }
}
add_action('save_post', 'toggle_maps_save_meta');

// Add toggle maps meta to API
function add_toggle_maps_data() {

    // Location meta
    register_api_field(
        'toggle_maps_location',
        'toggle_maps_address',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Location address',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_location',
        'toggle_maps_phone',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Location phone',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_location',
        'toggle_maps_website_link',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Location website',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_location',
        'toggle_maps_latitude',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Location latitude',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_location',
        'toggle_maps_longitude',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Location longitude',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_location',
        'toggle_maps_zoom',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Location zoom',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );

    // Maps meta
    register_api_field(
        'toggle_maps_map',
        'toggle_maps_latitude',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Map center latitude',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_map',
        'toggle_maps_longitude',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Map center longitude',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
    register_api_field(
        'toggle_maps_map',
        'toggle_maps_zoom',
        array(
            'get_callback' => 'toggle_maps_get_field',
            'update_callback' => null,
            'schema' => array(
                'description' => 'Map zoom',
                'type' => 'string',
                'context' => array('view', 'edit')
            )
        )
    );
}
add_action('rest_api_init', 'add_toggle_maps_data');

// Callback to display meta in API
function toggle_maps_get_field($post, $field_name, $request) {
    return get_post_meta($post['id'], $field_name, true);
}

