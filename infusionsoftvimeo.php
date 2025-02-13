<?php
/*
Plugin Name: Infusionsoft Vimeo for EPIC Entertainment
Plugin URI: https://wordpress.org/
Description: For tracking vimeo video process in infusionsoft.
Version: 1.1.1
Author: Wordpress; updated by Alec Ellsworth
Author URI: https://wordpress.org/
License: GPLv2 or later
Text Domain: infusionsoft-vimeo
*/

// Add after plugin header
define('IV_VERSION', '1.1.1');

// Add activation hook
register_activation_hook(__FILE__, 'iv_activate');

function iv_activate() {
	$old_version = get_option('iv_version', '0.0.0');
	
	if (version_compare($old_version, IV_VERSION, '<')) {
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
	$result["tagged"] = false;
	
	$options = get_option('iv_settings');
	
	if (empty($options)) {
		echo json_encode($result);
		wp_die();
	}

	include(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');    
	$app = new ifiSDK;

	if($app->cfgCon("connection")) {
		$videoid = $_POST['videoid'];
		$percent = $_POST['percent'];
		$contactid = $_POST['contactid'];

		if($videoid && $percent && $contactid) {
			// Find which video number matches this video ID
			$video_number = 0;
			for($i = 1; $i <= 5; $i++) {
				if($options['video_' . $i . '_id'] == $videoid) {
					$video_number = $i;
					break;
				}
			}

			if($video_number > 0) {
				if($percent == 100) {
					$tagid = $options['video_' . $video_number . '_100_tag'];
				}
				if($percent == 75) {
					$tagid = $options['video_' . $video_number . '_75_tag'];
				}
				if($percent == 50) {
					$tagid = $options['video_' . $video_number . '_50_tag'];
				}
				if($percent == 25) {
					$tagid = $options['video_' . $video_number . '_25_tag'];
				}

				if($tagid) {
					$result['tagged'] = (bool)$app->grpAssign($contactid, $tagid);
					$result['tagid'] = $tagid;
				}
			}
		}
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

	// API Settings Section
	add_settings_section(
		'iv_api_section',
		'API Settings',
		'iv_api_section_callback',
		'infusionsoft_vimeo'
	);

	add_settings_field(
		'iv_subdomain',
		'Subdomain',
		'iv_subdomain_render',
		'infusionsoft_vimeo',
		'iv_api_section'
	);

	add_settings_field(
		'iv_api_key',
		'API Key',
		'iv_api_key_render',
		'infusionsoft_vimeo',
		'iv_api_section'
	);

	// Video Settings Section
	add_settings_section(
		'iv_video_section',
		'Video Settings',
		'iv_video_section_callback',
		'infusionsoft_vimeo'
	);

	// Add fields for all 5 videos
	for($i = 1; $i <= 5; $i++) {
		add_settings_field(
			'video_' . $i,
			'Video ' . $i,
			'iv_video_fields_render',
			'infusionsoft_vimeo',
			'iv_video_section',
			['video_number' => $i]
		);
	}
}

function iv_api_section_callback() {
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

function iv_video_section_callback() {
	echo 'Configure your 5 Vimeo videos and their corresponding Infusionsoft tags:';
}

function iv_video_fields_render($args) {
	$options = get_option('iv_settings');
	$n = $args['video_number'];
	?>
	<div class="video-settings" style="margin-bottom: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
		<h4>Video <?php echo $n; ?></h4>
		<p>
			<label style="display: inline-block; width: 100px;">Vimeo ID:</label>
			<input type="text" name="iv_settings[video_<?php echo $n; ?>_id]" 
				   value="<?php echo isset($options['video_' . $n . '_id']) ? esc_attr($options['video_' . $n . '_id']) : ''; ?>"
				   style="width: 200px;">
		</p>
		<p>
			<label style="display: inline-block; width: 100px;">25% Tag:</label>
			<input type="text" name="iv_settings[video_<?php echo $n; ?>_25_tag]" 
				   value="<?php echo isset($options['video_' . $n . '_25_tag']) ? esc_attr($options['video_' . $n . '_25_tag']) : ''; ?>"
				   style="width: 200px;">
		</p>
		<p>
			<label style="display: inline-block; width: 100px;">50% Tag:</label>
			<input type="text" name="iv_settings[video_<?php echo $n; ?>_50_tag]" 
				   value="<?php echo isset($options['video_' . $n . '_50_tag']) ? esc_attr($options['video_' . $n . '_50_tag']) : ''; ?>"
				   style="width: 200px;">
		</p>
		<p>
			<label style="display: inline-block; width: 100px;">75% Tag:</label>
			<input type="text" name="iv_settings[video_<?php echo $n; ?>_75_tag]" 
				   value="<?php echo isset($options['video_' . $n . '_75_tag']) ? esc_attr($options['video_' . $n . '_75_tag']) : ''; ?>"
				   style="width: 200px;">
		</p>
		<p>
			<label style="display: inline-block; width: 100px;">100% Tag:</label>
			<input type="text" name="iv_settings[video_<?php echo $n; ?>_100_tag]" 
				   value="<?php echo isset($options['video_' . $n . '_100_tag']) ? esc_attr($options['video_' . $n . '_100_tag']) : ''; ?>"
				   style="width: 200px;">
		</p>
	</div>
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