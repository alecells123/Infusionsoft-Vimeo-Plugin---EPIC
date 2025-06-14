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
	$result = array();
	$result["tagged"] = false;
	$result["error"] = "";
	$result["debug"] = array();
	
	// Get plugin settings
	$options = get_option('iv_settings');
	
	if (empty($options)) {
		$result["error"] = "Plugin settings not configured";
		echo json_encode($result);
		wp_die();
	}

	// Validate required data
	$videoid = sanitize_text_field($_POST['videoid'] ?? '');
	$percent = intval($_POST['percent'] ?? 0);
	$contactid = intval($_POST['contactid'] ?? 0);
	
	$result["debug"]["received_data"] = array(
		'videoid' => $videoid,
		'percent' => $percent,
		'contactid' => $contactid
	);

	if(empty($videoid) || empty($percent) || empty($contactid)) {
		$result["error"] = "Missing required data: videoid, percent, or contactid";
		echo json_encode($result);
		wp_die();
	}

	// Include the Infusionsoft SDK
	include_once(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');
	
	try {
		$app = new ifiSDK;
		
		// Test connection first
		if(!$app->cfgCon("connection")) {
			$result["error"] = "Failed to connect to Infusionsoft API";
			echo json_encode($result);
			wp_die();
		}
		
		$result["debug"]["api_connected"] = true;

		// Find which video number matches this video ID
		$video_number = 0;
		for($i = 1; $i <= 5; $i++) {
			if(isset($options['video_' . $i . '_id']) && $options['video_' . $i . '_id'] == $videoid) {
				$video_number = $i;
				break;
			}
		}
		
		$result["debug"]["video_number"] = $video_number;

		if($video_number == 0) {
			$result["error"] = "Video ID not found in settings: " . $videoid;
			echo json_encode($result);
			wp_die();
		}

		// Get the appropriate tag name based on percentage
		$tag_name = '';
		if($percent == 75) {
			// FOCUS: This is the critical 75% functionality
			$tag_name = "Watched 75% of Video {$video_number}";
			$result["debug"]["tag_type"] = "75%";
		} elseif($percent == 100) {
			$tag_name = "Watched 100% of Video {$video_number}";
			$result["debug"]["tag_type"] = "100%";
		} elseif($percent == 50) {
			$tag_name = "Watched 50% of Video {$video_number}";
			$result["debug"]["tag_type"] = "50%";
		} elseif($percent == 25) {
			$tag_name = "Watched 25% of Video {$video_number}";
			$result["debug"]["tag_type"] = "25%";
		}

		$result["debug"]["tag_name"] = $tag_name;

		if(empty($tag_name)) {
			$result["error"] = "No tag name determined for video {$video_number} at {$percent}%";
			echo json_encode($result);
			wp_die();
		}

		// Assign the tag to the contact using tag name (SDK will find the ID)
		$tag_result = $app->grpAssign($contactid, $tag_name);
		
		$result["debug"]["tag_assignment_result"] = $tag_result;
		
		if($tag_result) {
			$result['tagged'] = true;
			$result['tag_name'] = $tag_name;
			$result["debug"]["success"] = "Tag '{$tag_name}' assigned to contact {$contactid} for video {$video_number} at {$percent}%";
		} else {
			$result["error"] = "Failed to assign tag '{$tag_name}' to contact {$contactid}";
		}

	} catch (Exception $e) {
		$result["error"] = "Exception: " . $e->getMessage();
		error_log("Infusionsoft Vimeo Plugin Error: " . $e->getMessage());
	}
	
	// Log the result for debugging
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log("Vimeo Action Result: " . json_encode($result));
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
	echo '<p>Configure your 5 Vimeo video IDs. Tags will be automatically created as "Watched 75% of Video 1", etc.</p>';
}

function iv_video_fields_render($args) {
	$options = get_option('iv_settings');
	$n = $args['video_number'];
	?>
	<div class="video-settings" style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
		<h4>Video <?php echo $n; ?></h4>
		<p>
			<label style="display: inline-block; width: 100px;">Vimeo ID:</label>
			<input type="text" name="iv_settings[video_<?php echo $n; ?>_id]" 
				   value="<?php echo isset($options['video_' . $n . '_id']) ? esc_attr($options['video_' . $n . '_id']) : ''; ?>"
				   style="width: 200px;"
				   placeholder="e.g. 123456789">
		</p>
		<p style="color: #666; font-size: 12px; margin-left: 100px;">
			Tags will be: "Watched 25% of Video <?php echo $n; ?>", "Watched 50% of Video <?php echo $n; ?>", "Watched 75% of Video <?php echo $n; ?>", "Watched 100% of Video <?php echo $n; ?>"
		</p>
	</div>
	<?php
}

// Add test connection button handler
add_action('wp_ajax_test_infusionsoft_connection', 'test_infusionsoft_connection_callback');

function test_infusionsoft_connection_callback() {
	try {
		$options = get_option('iv_settings', []);
		
		// Show what settings we have
		$debug_info = [
			'has_api_key' => !empty($options['api_key']),
			'api_key_length' => isset($options['api_key']) ? strlen($options['api_key']) : 0,
			'api_key_starts_with' => isset($options['api_key']) ? substr($options['api_key'], 0, 10) . '...' : 'N/A',
			'has_subdomain' => !empty($options['subdomain']),
			'subdomain' => $options['subdomain'] ?? 'N/A'
		];
		
		require_once(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');
		$app = new ifiSDK;
		
		// Try the connection test with better error handling
		try {
			$connection_result = $app->cfgCon("connection");
			$debug_info['connection_result'] = $connection_result;
			$debug_info['connection_result_type'] = gettype($connection_result);
			
			if($connection_result === true) {
				wp_send_json_success([
					'message' => 'Successfully connected to Infusionsoft!',
					'debug' => $debug_info
				]);
			} else {
				// Try to understand what went wrong
				$debug_info['connection_failed'] = true;
				wp_send_json_error([
					'message' => 'Connection test returned: ' . var_export($connection_result, true),
					'debug' => $debug_info
				]);
			}
		} catch (Exception $e) {
			$debug_info['connection_exception'] = $e->getMessage();
			wp_send_json_error([
				'message' => 'Connection exception: ' . $e->getMessage(),
				'debug' => $debug_info
			]);
		}
	} catch (Exception $e) {
		wp_send_json_error([
			'message' => 'Error: ' . $e->getMessage(),
			'debug' => $debug_info ?? ['error' => 'Failed before debug info collected']
		]);
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

		<hr>
		
		<h3>Test 75% Video Tagging</h3>
		<p>Test the critical 75% video tagging functionality:</p>
		<table class="form-table">
			<tr>
				<th><label for="test-video-id">Video ID:</label></th>
				<td><input type="text" id="test-video-id" placeholder="Enter Vimeo video ID" style="width: 200px;"></td>
			</tr>
			<tr>
				<th><label for="test-contact-id">Contact ID:</label></th>
				<td><input type="number" id="test-contact-id" placeholder="Enter contact ID" style="width: 200px;"></td>
			</tr>
		</table>
		<p>
			<button id="test-75-percent-tagging" class="button button-primary">🎯 Test 75% Tagging</button>
		</p>
		<div id="tagging-test-result" style="margin-top: 10px;"></div>

		<hr>
		
		<h3>Test Simple Contact Update</h3>
		<p>Test if we can actually modify contact data in Keap:</p>
		<table class="form-table">
			<tr>
				<th><label for="update-contact-id">Contact ID:</label></th>
				<td><input type="number" id="update-contact-id" placeholder="Enter contact ID to test update" style="width: 200px;"></td>
			</tr>
		</table>
		<p>
			<button id="test-contact-update" class="button button-secondary">🔧 Test Contact Update</button>
		</p>
		<div id="contact-update-result" style="margin-top: 10px;"></div>



		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Test connection
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
						console.log('Connection test response:', response);
						if (response.success) {
							var debugInfo = response.data.debug ? '<pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-size: 12px;">' + JSON.stringify(response.data.debug, null, 2) + '</pre>' : '';
							resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p>' + debugInfo + '</div>');
						} else {
							var debugInfo = response.data.debug ? '<pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-size: 12px;">' + JSON.stringify(response.data.debug, null, 2) + '</pre>' : '';
							resultDiv.html('<div class="notice notice-error"><p>' + response.data.message + '</p>' + debugInfo + '</div>');
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
			
			// Test 75% tagging
			$('#test-75-percent-tagging').click(function(e) {
				e.preventDefault();
				var button = $(this);
				var resultDiv = $('#tagging-test-result');
				var videoId = $('#test-video-id').val().trim();
				var contactId = $('#test-contact-id').val().trim();
				
				if (!videoId || !contactId) {
					resultDiv.html('<div class="notice notice-error"><p>Please enter both Video ID and Contact ID</p></div>');
					return;
				}
				
				button.prop('disabled', true);
				button.text('Testing 75% Tagging...');
				resultDiv.html('<div class="notice notice-info"><p>Testing tag assignment...</p></div>');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'test_75_percent_tagging',
						test_video_id: videoId,
						test_contact_id: contactId
					},
					success: function(response) {
						console.log('75% tagging test response:', response);
						
						if (typeof response === 'string') {
							try {
								response = JSON.parse(response);
							} catch(e) {
								// Response might already be JSON
							}
						}
						
						if (response.success) {
							var debugInfo = response.data.debug ? '<pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-size: 12px;">' + JSON.stringify(response.data.debug, null, 2) + '</pre>' : '';
							resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p>' + debugInfo + '</div>');
						} else {
							var debugInfo = response.data && response.data.debug ? '<pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-size: 12px;">' + JSON.stringify(response.data.debug, null, 2) + '</pre>' : '';
							var errorMsg = response.data ? response.data.message : (response.message || 'Unknown error');
							resultDiv.html('<div class="notice notice-error"><p>' + errorMsg + '</p>' + debugInfo + '</div>');
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX Error:', xhr.responseText);
						resultDiv.html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p><pre>' + xhr.responseText + '</pre></div>');
					},
					complete: function() {
						button.prop('disabled', false);
						button.text('🎯 Test 75% Tagging');
					}
				});
			});
			
			// Test contact update
			$('#test-contact-update').click(function(e) {
				e.preventDefault();
				var button = $(this);
				var resultDiv = $('#contact-update-result');
				var contactId = $('#update-contact-id').val().trim();
				
				if (!contactId) {
					resultDiv.html('<div class="notice notice-error"><p>Please enter a Contact ID</p></div>');
					return;
				}
				
				button.prop('disabled', true);
				button.text('Testing Contact Update...');
				resultDiv.html('<div class="notice notice-info"><p>Testing contact update...</p></div>');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'test_simple_contact_update',
						test_contact_id: contactId
					},
					success: function(response) {
						console.log('Contact update test response:', response);
						
						if (typeof response === 'string') {
							try {
								response = JSON.parse(response);
							} catch(e) {
								// Response might already be JSON
							}
						}
						
						if (response.success) {
							var debugInfo = response.data.debug ? '<pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-size: 12px;">' + JSON.stringify(response.data.debug, null, 2) + '</pre>' : '';
							resultDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p>' + debugInfo + '</div>');
						} else {
							var debugInfo = response.data && response.data.debug ? '<pre style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-size: 12px;">' + JSON.stringify(response.data.debug, null, 2) + '</pre>' : '';
							var errorMsg = response.data ? response.data.message : (response.message || 'Unknown error');
							resultDiv.html('<div class="notice notice-error"><p>' + errorMsg + '</p>' + debugInfo + '</div>');
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX Error:', xhr.responseText);
						resultDiv.html('<div class="notice notice-error"><p>AJAX Error: ' + error + '</p><pre>' + xhr.responseText + '</pre></div>');
					},
					complete: function() {
						button.prop('disabled', false);
						button.text('🔧 Test Contact Update');
					}
				});
			});
			

		});
		</script>
	</div>
	<?php
}

// Add test 75% functionality handler
add_action('wp_ajax_test_75_percent_tagging', 'test_75_percent_tagging_callback');

function test_75_percent_tagging_callback() {
	$options = get_option('iv_settings');
	
	if (empty($options)) {
		wp_send_json_error(['message' => 'Plugin settings not configured']);
		return;
	}
	
	// Get test parameters
	$test_video_id = sanitize_text_field($_POST['test_video_id'] ?? '');
	$test_contact_id = intval($_POST['test_contact_id'] ?? 0);
	
	if (empty($test_video_id) || empty($test_contact_id)) {
		wp_send_json_error(['message' => 'Please provide both video ID and contact ID for testing']);
		return;
	}
	
	try {
		// Store original POST data
		$original_post = $_POST;
		
		// Simulate the 75% request
		$_POST['videoid'] = $test_video_id;
		$_POST['percent'] = 75;
		$_POST['contactid'] = $test_contact_id;
		
		// Include the SDK
		include_once(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');
		
		$result = array();
		$result["tagged"] = false;
		$result["error"] = "";
		$result["debug"] = array();
		
		$app = new ifiSDK;
		
		// Test connection first
		if(!$app->cfgCon("connection")) {
			wp_send_json_error([
				'message' => 'Failed to connect to Infusionsoft API',
				'debug' => ['connection_failed' => true]
			]);
			return;
		}
		
		// Find which video number matches this video ID
		$video_number = 0;
		for($i = 1; $i <= 5; $i++) {
			if(isset($options['video_' . $i . '_id']) && $options['video_' . $i . '_id'] == $test_video_id) {
				$video_number = $i;
				break;
			}
		}
		
		if($video_number == 0) {
			wp_send_json_error([
				'message' => "Video ID '{$test_video_id}' not found in settings",
				'debug' => ['video_number' => $video_number, 'settings' => $options]
			]);
			return;
		}
		
		// Get the 75% tag name (automatically generated)
		$tag_name = "Watched 75% of Video {$video_number}";
		
		if(empty($tag_name)) {
			wp_send_json_error([
				'message' => "No 75% tag name generated for video {$video_number}",
				'debug' => ['video_number' => $video_number, 'tag_name' => $tag_name]
			]);
			return;
		}
		
		// First, let's test if the contact exists
		$contact_test = $app->loadCon($test_contact_id, ['Id', 'FirstName', 'LastName', 'Email']);
		
		if (!$contact_test || empty($contact_test)) {
			wp_send_json_error([
									'message' => "FAILED: Contact ID {$test_contact_id} does not exist in Infusionsoft",
					'debug' => [
						'contact_id' => $test_contact_id,
						'contact_exists' => false
					]
			]);
			return;
		}
		
		// Now assign the tag to the contact using tag name
		$tag_result = $app->grpAssign($test_contact_id, $tag_name);
		
		// Restore original POST data
		$_POST = $original_post;
		
		if($tag_result === true || $tag_result === 1 || is_numeric($tag_result)) {
			wp_send_json_success([
				'message' => 'SUCCESS! Tag assignment API call completed. Check Infusionsoft to verify the tag was actually applied.',
				'tag_name' => $tag_name,
				'debug' => [
					'contact_id' => $test_contact_id,
					'tag_name' => $tag_name,
					'tag_assignment_result' => $tag_result,
					'result_type' => gettype($tag_result)
				]
			]);
		} else {
			wp_send_json_error([
				'message' => 'FAILED: Tag assignment returned unexpected result',
				'debug' => [
					'contact_id' => $test_contact_id,
					'tag_name' => $tag_name,
					'tag_assignment_result' => $tag_result,
					'result_type' => gettype($tag_result)
				]
			]);
		}
		
	} catch (Exception $e) {
		wp_send_json_error([
			'message' => 'Exception: ' . $e->getMessage(),
			'debug' => ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
		]);
	}
}

// Add test simple contact update handler
add_action('wp_ajax_test_simple_contact_update', 'test_simple_contact_update_callback');

function test_simple_contact_update_callback() {
	try {
		$test_contact_id = intval($_POST['test_contact_id'] ?? 0);
		
		if (empty($test_contact_id)) {
			wp_send_json_error(['message' => 'Please provide a contact ID for testing']);
			return;
		}
		
		require_once(plugin_dir_path(__FILE__) . 'infusionsoft/src/ifisdk.php');
		$app = new ifiSDK;
		
		// First test connection
		if(!$app->cfgCon("connection")) {
			wp_send_json_error([
				'message' => 'Failed to connect to Infusionsoft API',
				'debug' => ['connection_failed' => true]
			]);
			return;
		}
		
		// Load the contact first to see what we're working with
		$original_contact = $app->loadCon($test_contact_id, ['Id', 'FirstName', 'LastName', 'Email', 'JobTitle']);
		
		if (!$original_contact || empty($original_contact)) {
			wp_send_json_error([
				'message' => "Contact ID {$test_contact_id} does not exist in Infusionsoft",
				'debug' => ['contact_load_result' => $original_contact]
			]);
			return;
		}
		
		// Simple update - just set JobTitle to "It works!"
		$update_data = ['JobTitle' => 'It works!'];
		
		// Perform the update
		$update_result = $app->updateCon($test_contact_id, $update_data);
		
		// Load the contact again to verify the update worked (including JobTitle)
		$updated_contact = $app->loadCon($test_contact_id, ['Id', 'FirstName', 'LastName', 'Email', 'JobTitle']);
		
		if($update_result === true || $update_result === 1 || is_numeric($update_result)) {
			wp_send_json_success([
				'message' => 'SUCCESS! Contact update API call completed. We can modify contacts in Keap.',
				'debug' => [
					'contact_id' => $test_contact_id,
					'original_contact' => $original_contact,
					'update_data' => $update_data,
					'update_result' => $update_result,
					'updated_contact' => $updated_contact,
					'result_type' => gettype($update_result)
				]
			]);
		} else {
			wp_send_json_error([
				'message' => 'FAILED: Contact update returned unexpected result',
				'debug' => [
					'contact_id' => $test_contact_id,
					'original_contact' => $original_contact,
					'update_data' => $update_data,
					'update_result' => $update_result,
					'result_type' => gettype($update_result)
				]
			]);
		}
		
	} catch (Exception $e) {
		wp_send_json_error([
			'message' => 'Exception: ' . $e->getMessage(),
			'debug' => ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
		]);
	}
	
	wp_die();
}

