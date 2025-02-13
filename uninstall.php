<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete API credentials
delete_option('iv_subdomain');
delete_option('iv_key');

// Delete version info
delete_option('iv_version');

// Clean up any transients we might have created
delete_transient('iv_api_connection_test');

// Clear any scheduled hooks
wp_clear_scheduled_hook('iv_daily_cleanup');
