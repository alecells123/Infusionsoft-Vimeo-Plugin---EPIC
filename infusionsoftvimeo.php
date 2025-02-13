<?php
/*
Plugin Name: Infusionsoft Vimeo for EPIC Entertainment
Plugin URI: https://wordpress.org/
Description: For tracking vimeo video process in infusionsoft.
Version: 1.1.0
Author: Wordpress; updated by Alec Ellsworth
Author URI: https://wordpress.org/
License: GPLv2 or later
Text Domain: infusionsoft-vimeo
*/

// Add after plugin header
define('IV_VERSION', '1.1.0');

// Add activation hook
register_activation_hook(__FILE__, 'iv_activate');

function iv_activate() {
	$old_version = get_option('iv_version', '0.0.0');
	
	if (version_compare($old_version, IV_VERSION, '<')) {
		// Perform any necessary upgrades here
		
		// Update version in database
		update_option('iv_version', IV_VERSION);
	}
}

// Add upgrade check on plugins loaded
add_action('plugins_loaded', 'iv_check_version');

function iv_check_version() {
	if (get_option('iv_version') !== IV_VERSION) {
		iv_activate();
	}
}

add_action('wp_enqueue_scripts', 'iv_enqueued_assets');

function iv_enqueued_assets() {
	wp_enqueue_script('jquery');
	
	wp_enqueue_script('vimeo-player-script', 'https://player.vimeo.com/api/player.js', array('jquery'), null, true);
	
	wp_enqueue_script('vimeo-infusionsoft-script', plugin_dir_url(__FILE__) . 'js/iv-script.js', array('jquery', 'vimeo-player-script'), '2.1', true);
	
	wp_localize_script('vimeo-infusionsoft-script', 'vimeo_ajax_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'contactid' => i4w_get_contact_field('Id'),
		'debug' => WP_DEBUG
	));
}

add_action('wp_ajax_vimeo_action', 'vimeo_action_callback');
add_action('wp_ajax_nopriv_vimeo_action', 'vimeo_action_callback');

function vimeo_action_callback() {
	global $i4w;
	
	// Get settings
	$options = get_option('iv_settings', []);
	
	if (empty($options['api_key'])) {
		wp_send_json_error(['message' => 'API key not configured']);
		return;
	}

	try {
		include(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');    
		$app = new ifiSDK;
		
		if($app->cfgCon("connection", $options['api_key'])) {
			$videoid = $_POST['videoid'];
			$percent = $_POST['percent'];
			$contactid = $_POST['contactid'];

			if($videoid && $percent && $contactid) {
				$vimeovideoids = array();
				$percent25tags = array();
				$percent50tags = array();
				$percent75tags = array();
				$percent100tags = array();

				for($i=1; $i<=25; $i++) {
					$vimeovideoids[$i] = get_option('iv_vimeo_id_'.$i);
					$percent25tags[$i] = get_option('iv_25_tag_'.$i);
					$percent50tags[$i] = get_option('iv_50_tag_'.$i);
					$percent75tags[$i] = get_option('iv_75_tag_'.$i);
					$percent100tags[$i] = get_option('iv_100_tag_'.$i);
				}

				$vimeoidkey = array_search($videoid, $vimeovideoids);

				if($vimeoidkey) {
					if($percent == 100) {
						$tagid = $percent100tags[$vimeoidkey];
					}
					if($percent == 75) {
						$tagid = $percent75tags[$vimeoidkey];
					}
					if($percent == 50) {
						$tagid = $percent50tags[$vimeoidkey];
					}
					if($percent == 25) {
						$tagid = $percent25tags[$vimeoidkey];
					}

					if($tagid) {
						$result['tagged'] = $app->grpAssign($contactid, $tagid);
						$result['tagid'] = $tagid;
					}
				}
			}
		}
	} catch (Exception $e) {
		wp_send_json_error(['message' => $e->getMessage()]);
		return;
	}
	
	echo json_encode($result);
	wp_die();
}

// Settings page
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
	$subdomain = isset($options['subdomain']) ? $options['subdomain'] : '';
	echo '<input type="text" name="iv_settings[subdomain]" value="' . esc_attr($subdomain) . '">';
}

function iv_api_key_render() {
	$options = get_option('iv_settings');
	$api_key = isset($options['api_key']) ? $options['api_key'] : '';
	echo '<input type="text" size="50" name="iv_settings[api_key]" value="' . esc_attr($api_key) . '">';
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