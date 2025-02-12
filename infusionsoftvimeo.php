<?php
/*
Plugin Name: Infusionsoft Vimeo
Plugin URI: http://wordpress.org/
Description: For tracking vimeo video process in infusionsoft.
Author: Wordpress, altered for Service Access Key use by Alec Ellsworth
Version: 1.0
Author URI: http://wordpress.org/
*/

add_action( 'wp_enqueue_scripts', 'iv_enqueued_assets' );

function iv_enqueued_assets()
{
	// First ensure jQuery is loaded
	wp_enqueue_script('jquery');
	
	// Then load Vimeo player
	wp_enqueue_script('vimeo-player-script', 'https://player.vimeo.com/api/player.js', array('jquery'), null, true);
	
	// Finally load your custom script
	wp_enqueue_script('vimeo-infusionsoft-script', plugin_dir_url( __FILE__ ) . 'js/iv-script.js', array('jquery', 'vimeo-player-script'), '2.1', true);
	
	// Add error handling to your localized data
	wp_localize_script('vimeo-infusionsoft-script', 'vimeo_ajax_object', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'contactid' => i4w_get_contact_field('Id'),
		'debug' => WP_DEBUG
	));
}

add_action('wp_ajax_vimeo_action', 'vimeo_action_callback');
add_action('wp_ajax_nopriv_vimeo_action', 'vimeo_action_callback');

function vimeo_action_callback()
{
	global $i4w;
	$INFUSIONSOFT_SUBDOMAIN = get_option('iv_subdomain');
	$INFUSIONSFT_KEY        = get_option('iv_key');
	$result["tagged"] = 0;

	if($INFUSIONSOFT_SUBDOMAIN && $INFUSIONSFT_KEY)
	{
		define('INFUSIONSOFT_SUBDOMAIN', $INFUSIONSOFT_SUBDOMAIN);
		define('INFUSIONSFT_KEY', $INFUSIONSFT_KEY);

		include(plugin_dir_path( __FILE__ ).'infusionsoft/src/ifisdk.php');	
		$app = new ifiSDK;

		if($app->cfgCon("connection"))
		{
			$videoid   = $_POST['videoid'];
			$percent   = $_POST['percent'];
			$contactid = $_POST['contactid'];
			file_put_contents('getContactid.txt', $contactid);

			if($videoid && $percent && $contactid)
			{
				$vimeovideoids  = array();
				$percent25tags  = array();
				$percent50tags  = array();
				$percent75tags  = array();
				$percent100tags = array();

				for($i=1;$i<=25;$i++)
				{
					$vimeovideoids[$i]  = get_option('iv_vimeo_id_'.$i);
					$percent25tags[$i]  = get_option('iv_25_tag_'.$i);
					$percent50tags[$i]  = get_option('iv_50_tag_'.$i);
					$percent75tags[$i]  = get_option('iv_75_tag_'.$i);
					$percent100tags[$i] = get_option('iv_100_tag_'.$i);
				}

				$vimeoidkey = array_search($videoid, $vimeovideoids);

				if($vimeoidkey)
				{
					if($percent == 100)
					{
						$tagid = $percent100tags[$vimeoidkey];
					}
					if($percent == 75)
					{
						$tagid = $percent75tags[$vimeoidkey];
					}
					if($percent == 50)
					{
						$tagid = $percent50tags[$vimeoidkey];
					}
					if($percent == 25)
					{
						$tagid = $percent25tags[$vimeoidkey];
					}

					if($tagid)
					{
						$result['tagged']  = $app->grpAssign($contactid, $tagid);
						$result['tagid'] = $tagid;
					}
				}
			}
		}
	}
	
	echo json_encode($result);

	wp_die();
}

//admin functions
// create plugin settings menu
add_action('admin_menu', 'iv_create_menu');

function iv_create_menu()
{
	//create new top-level menu
	add_menu_page('Infusionsoft Vimeo Plugin Settings', 'Infusionsoft Vimeo Settings', 'administrator', __FILE__, 'iv_settings_page');

	//call register settings function
	add_action( 'admin_init', 'register_ivsettings' );
}

function register_ivsettings() {
	//register our settings
	register_setting( 'iv-settings-group', 'iv_subdomain' );
	register_setting( 'iv-settings-group', 'iv_key' );

	for($i=1;$i<=25;$i++)
	{
		register_setting( 'iv-settings-group', 'iv_vimeo_id_'.$i );
		register_setting( 'iv-settings-group', 'iv_25_tag_'.$i );
		register_setting( 'iv-settings-group', 'iv_50_tag_'.$i );
		register_setting( 'iv-settings-group', 'iv_75_tag_'.$i );
		register_setting( 'iv-settings-group', 'iv_100_tag_'.$i );
	}
}

function iv_settings_page() {
?>
<div class="wrap">
<h2>Infusionsoft Vimeo Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields('iv-settings-group'); ?>
    <?php do_settings_sections('iv-settings-group'); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Infusionsoft Subdomain</th>
        <td><input type="text" name="iv_subdomain" value="<?php echo esc_attr( get_option('iv_subdomain') ); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Infusionsoft Key</th>
        <td><input type="text" name="iv_key" value="<?php echo esc_attr( get_option('iv_key') ); ?>" /></td>
        </tr>

		<?php for($i=1;$i<=25;$i++):?>
			<tr valign="top"><td></td></tr>
			<tr valign="top"><td></td></tr>

			<tr valign="top">
			<th scope="row">Vimeo Video <?php echo $i;?> Id</th>
			<td><input type="text" name="iv_vimeo_id_<?php echo $i;?>" value="<?php echo esc_attr( get_option('iv_vimeo_id_'.$i) ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">Vimeo Video <?php echo $i;?> 25% Tag Id</th>
			<td><input type="text" name="iv_25_tag_<?php echo $i;?>" value="<?php echo esc_attr( get_option('iv_25_tag_'.$i) ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">Vimeo Video <?php echo $i;?> 50% Tag Id</th>
			<td><input type="text" name="iv_50_tag_<?php echo $i;?>" value="<?php echo esc_attr( get_option('iv_50_tag_'.$i) ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">Vimeo Video <?php echo $i;?> 75% Tag Id</th>
			<td><input type="text" name="iv_75_tag_<?php echo $i;?>" value="<?php echo esc_attr( get_option('iv_75_tag_'.$i) ); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">Vimeo Video <?php echo $i;?> 100% Tag Id</th>
			<td><input type="text" name="iv_100_tag_<?php echo $i;?>" value="<?php echo esc_attr( get_option('iv_100_tag_'.$i) ); ?>" /></td>
			</tr>
		<?php endfor;?>
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php } ?>

// Add settings menu
add_action('admin_menu', 'iv_add_admin_menu');
add_action('admin_init', 'iv_settings_init');

function iv_add_admin_menu() {
    add_options_page(
        'Infusionsoft Vimeo Settings', 
        'Infusionsoft Vimeo', 
        'manage_options', 
        'infusionsoft_vimeo', 
        'iv_options_page'
    );
}

function iv_settings_init() {
    register_setting('iv_settings', 'iv_settings');

    add_settings_section(
        'iv_settings_section', 
        'API Settings', 
        'iv_settings_section_callback', 
        'infusionsoft_vimeo'
    );

    add_settings_field(
        'iv_subdomain', 
        'Infusionsoft Subdomain', 
        'iv_subdomain_render', 
        'infusionsoft_vimeo', 
        'iv_settings_section'
    );

    add_settings_field(
        'iv_api_key', 
        'Service Account Key', 
        'iv_api_key_render', 
        'infusionsoft_vimeo', 
        'iv_settings_section'
    );
}

function iv_settings_section_callback() {
    echo 'Configure your Keap/Infusionsoft API settings';
}

function iv_subdomain_render() {
    $options = get_option('iv_settings');
    $subdomain = isset($options['subdomain']) ? $options['subdomain'] : 'lc299';
    ?>
    <input type='text' name='iv_settings[subdomain]' value='<?php echo esc_attr($subdomain); ?>'>
    <?php
}

function iv_api_key_render() {
    $options = get_option('iv_settings');
    $api_key = isset($options['api_key']) ? $options['api_key'] : 'KeapAK-a71db0d082bfb2f08503622b08592b3043342a7e28c6aee37c';
    ?>
    <input type='text' size='50' name='iv_settings[api_key]' value='<?php echo esc_attr($api_key); ?>'>
    <?php
}

// Add test connection button handler
add_action('wp_ajax_test_infusionsoft_connection', 'test_infusionsoft_connection_callback');

function test_infusionsoft_connection_callback() {
    try {
        require_once(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');
        $app = new ifiSDK;
        
        if($app->cfgCon("connection")) {
            // Try to make a simple API call
            $result = $app->dsGetSetting("Application", "enabled");
            if(strpos($result, 'ERROR') !== FALSE) {
                wp_send_json_error(['message' => 'Connection failed: ' . $result]);
            } else {
                wp_send_json_success(['message' => 'Successfully connected to Infusionsoft!']);
            }
        } else {
            wp_send_json_error(['message' => 'Failed to establish connection']);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
    }
    wp_die();
}

function iv_options_page() {
    ?>
    <div class="wrap">
        <h2>Infusionsoft Vimeo Settings</h2>
        <form action='options.php' method='post'>
            <?php
            settings_fields('iv_settings');
            do_settings_sections('infusionsoft_vimeo');
            submit_button();
            ?>
        </form>
        
        <hr>
        
        <h3>Test Connection</h3>
        <p>Click the button below to test your Infusionsoft connection:</p>
        <button id="test-infusionsoft-connection" class="button button-secondary">Test Connection</button>
        <div id="connection-result" style="margin-top: 10px;"></div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test-infusionsoft-connection').click(function(e) {
                e.preventDefault();
                var button = $(this);
                var resultDiv = $('#connection-result');
                
                button.prop('disabled', true);
                button.text('Testing...');
                resultDiv.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_infusionsoft_connection'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                        } else {
                            resultDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div class="notice notice-error"><p>Failed to test connection. Please check your settings and try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        button.text('Test Connection');
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}