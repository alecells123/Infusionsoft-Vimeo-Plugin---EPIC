<?php
// Debug helper for this file
function cfg_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $debug_info = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
            'data' => $data
        ]);
        error_log("=== INFUSIONSOFT CONFIG === " . $debug_info);
    }
}

// Get settings from WordPress options
$iv_settings = get_option('iv_settings', [
    'subdomain' => '',
    'api_key' => ''
]);

// Define constants if they're not already defined (allows for hardcoded values in wp-config.php)
if (!defined('INFUSIONSOFT_SUBDOMAIN')) {
    define('INFUSIONSOFT_SUBDOMAIN', $iv_settings['subdomain']);
}

if (!defined('INFUSIONSOFT_KEY')) {
    define('INFUSIONSOFT_KEY', $iv_settings['api_key']);
}

if (!defined('INFUSIONSFT_KEY')) {
    define('INFUSIONSFT_KEY', INFUSIONSOFT_KEY);
}

cfg_debug_log('Config loaded', [
    'subdomain_set' => defined('INFUSIONSOFT_SUBDOMAIN'),
    'subdomain' => INFUSIONSOFT_SUBDOMAIN,
    'key_set' => defined('INFUSIONSOFT_KEY'),
    'typo_key_set' => defined('INFUSIONSFT_KEY'),
    'key_length' => defined('INFUSIONSOFT_KEY') ? strlen(INFUSIONSOFT_KEY) : 0,
    'settings_source' => 'WordPress Options'
]);

$connInfo = array('connection:'.INFUSIONSOFT_SUBDOMAIN.':i:'.INFUSIONSOFT_KEY.':This is the connection for '.INFUSIONSOFT_SUBDOMAIN.'.infusionsoft.com');

?>