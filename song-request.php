<?php
/*
Plugin Name: Song Request
Plugin URI: https://soyoutuber.com/song-request
Description: Plugin para solicitar canciones en la emisora de radio.
Version: 0.0.3
Author: SOYoutuber
Author URI: https://soyoutuber.com/
Text Domain: song-request
*/
// Agregar una página de configuración para el plugin
function song_request_settings_page() {
    add_options_page(
        'Song Request Settings',
        'Song Request',
        'manage_options',
        'song-request-settings',
        'song_request_settings_page_callback'
    );
}
add_action('admin_menu', 'song_request_settings_page');

// Callback para la página de configuración del plugin
function song_request_settings_page_callback() {
    ?>
    <div class="wrap">
        <h1>Song Request Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('song_request_settings');
            do_settings_sections('song-request-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar las opciones de configuración
function song_request_register_settings() {
    add_settings_section(
        'song_request_api_settings',
        'RadioBOSS API Settings',
        'song_request_api_settings_callback',
        'song-request-settings'
    );

    add_settings_field(
        'rb_server',
        'RadioBOSS Server',
        'rb_server_callback',
        'song-request-settings',
        'song_request_api_settings'
    );

    add_settings_field(
        'rb_port',
        'RadioBOSS Port',
        'rb_port_callback',
        'song-request-settings',
        'song_request_api_settings'
    );

    add_settings_field(
        'rb_password',
        'RadioBOSS API Password',
        'rb_password_callback',
        'song-request-settings',
        'song_request_api_settings'
    );

    add_settings_field(
        'rb_library',
        'RadioBOSS Music Library',
        'rb_library_callback',
        'song-request-settings',
        'song_request_api_settings'
    );

    register_setting('song_request_settings', 'song_request_options');
}
add_action('admin_init', 'song_request_register_settings');

// Callback para la sección de configuración de la API de RadioBOSS
function song_request_api_settings_callback() {
    echo 'Configure the connection details for RadioBOSS API:';
}

// Callback para el campo de configuración "RadioBOSS Server"
function rb_server_callback() {
    $options = get_option('song_request_options');
    $rb_server = isset($options['rb_server']) ? $options['rb_server'] : '';
    echo '<input type="text" name="song_request_options[rb_server]" value="' . esc_attr($rb_server) . '" />';
}

// Callback para el campo de configuración "RadioBOSS Port"
function rb_port_callback() {
    $options = get_option('song_request_options');
    $rb_port = isset($options['rb_port']) ? $options['rb_port'] : '';
    echo '<input type="text" name="song_request_options[rb_port]" value="' . esc_attr($rb_port) . '" />';
}

// Callback para el campo de configuración "RadioBOSS API Password"
function rb_password_callback() {
    $options = get_option('song_request_options');
    $rb_password = isset($options['rb_password']) ? $options['rb_password'] : '';
    echo '<input type="password" name="song_request_options
    [rb_password]" value="' . esc_attr($rb_password) . '" />';
}

// Callback para el campo de configuración "RadioBOSS Music Library"
function rb_library_callback() {
$options = get_option('song_request_options');
$rb_library = isset($options['rb_library']) ? $options['rb_library'] : '';
echo '<input type="text" name="song_request_options[rb_library]" value="' . esc_attr($rb_library) . '" />';
}

// Agregar los estilos y scripts del plugin
function song_request_enqueue_scripts() {
wp_enqueue_style('song-request-style', plugin_dir_url(FILE) . 'css/style.css');
wp_enqueue_script('song-request-script', plugin_dir_url(FILE) . 'js/script.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'song_request_enqueue_scripts');

// Agregar shortcode para el formulario
function song_request_form_shortcode($atts) {
ob_start();
?>
<form method="post" action="">
<input type="hidden" name="type" value="request">
<label>Artist<input size="30" name="artist"></label>
<label>Title<input size="30" name="title"></label>
<label>Message<textarea cols="30" rows="3" name="message"></textarea></label>
<button>Request a song</button>
</form>
<?php
return ob_get_clean();
}
add_shortcode('song_request_form', 'song_request_form_shortcode');

// Lógica para procesar el formulario y enviar la solicitud a RadioBOSS
function song_request_process_form() {
if (isset($_POST['type']) && $_POST['type'] === 'request') {
$options = get_option('song_request_options');
$rb_server = isset($options['rb_server']) ? $options['rb_server'] : '';
$rb_port = isset($options['rb_port']) ? $options['rb_port'] : '';
$rb_password = isset($options['rb_password']) ? $options['rb_password'] : '';
$rb_library = isset($options['rb_library']) ? $options['rb_library'] : '';
$artist = mb_strtolower(trim($_POST['artist']));
$title = mb_strtolower(trim($_POST['title']));

if (empty($artist) && empty($title)) {
    echo 'No artist or title entered.';
    return;
}

$rb_api = "http://$rb_server:$rb_port?pass=$rb_password";

$library_raw = file_get_contents("$rb_api&action=library&filename=" . urlencode($rb_library));
if ($library_raw === false) {
    echo 'Song request failed: unable to load music library.';
    return;
}

$xml = simplexml_load_string($library_raw);
if ($xml === false) {
    echo 'Song request failed: unable to parse music library XML data.';
    return;
}

$fn = false;

foreach ($xml as $x) {
    if ($x->getName() !== 'Track') {
        continue;
    }

    $found = (empty($artist) || (mb_strtolower((string)$x['artist']) === $artist)) &&
             (empty($title) || (mb_strtolower((string)$x['title']) === $title));

    if ($found) {
        $fn = (string)$x['filename'];
        break;
    }
}

if ($fn !== false) {
    $msg = isset($_POST['message']) ? $_POST['message'] : '';
    $res = file_get_contents("$rb_api&action=songrequest&filename=" . urlencode($fn) . '&message=' . urlencode($msg));
    if ($res === 'OK') {
        echo 'Song requested successfully!';
    } else {
        echo 'An error occurred while adding song request.';
    }
} else {
    echo 'Requested song not found in the music library.';
}
}
}
add_action('init', 'song_request_process_form');
// Agregar el formulario de solicitud como widget
class Song_Request_Form_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'song_request_form_widget',
            'Song Request Form',
            array('description' => 'Displays the Song Request Form')
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        echo do_shortcode('[get_song_request_form]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}
add_action('widgets_init', function() {
    register_widget('Song_Request_Form_Widget');
});
// Agregar el formulario de solicitud como bloque
function song_request_block_render() {
    ob_start();
    echo '<div class="song-request-block">';
    echo do_shortcode('[get_song_request_form]');
    echo '</div>';
    return ob_get_clean();
}

function song_request_block_init() {
    $args = array(
        'name'            => 'Song Request',
        'title'           => 'Song Request',
        'render_callback' => 'song_request_block_render',
        'category'        => 'widgets',
        'icon'            => 'admin-comments',
        'keywords'        => array( 'song', 'request', 'music' ),
    );
    register_block_type( 'song-request/song-request-block', $args );
}
add_action( 'init', 'song_request_block_init' );
// Obtener el formulario de solicitud como contenido procesado por el shortcode
function get_song_request_form() {
    ob_start();
    song_request_form_shortcode();
    return ob_get_clean();
}
add_shortcode('get_song_request_form', 'get_song_request_form');

// Agregar la función para renderizar el formulario de solicitud como bloque de Gutenberg
function render_song_request_form_block($attributes) {
    return get_song_request_form();
}
register_block_type('song-request/song-request-form', array(
    'render_callback' => 'render_song_request_form_block',
));
