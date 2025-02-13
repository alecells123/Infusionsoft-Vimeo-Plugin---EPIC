<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin settings
delete_option('iv_settings');

// Delete all vimeo ID and tag settings
for($i=1; $i<=25; $i++) {
    delete_option('iv_vimeo_id_'.$i);
    delete_option('iv_25_tag_'.$i);
    delete_option('iv_50_tag_'.$i);
    delete_option('iv_75_tag_'.$i);
    delete_option('iv_100_tag_'.$i);
}

// Delete version info
delete_option('iv_version');

// Clean up any transients we might have created
delete_transient('iv_api_connection_test');

// Clear any scheduled hooks
wp_clear_scheduled_hook('iv_daily_cleanup'); 