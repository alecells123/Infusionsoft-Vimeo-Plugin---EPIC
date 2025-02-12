<?php
/*
Plugin Name: Infusionsoft Developers Plugin
Plugin URI: http://wordpress.org/plugins/infusionsoft-for-developers/
Description: This plugin is primarily designed for developers adding Infusionsoft API hooks for use in WP. It only provides a basic feature set for the average WordPress user.
Version: 0.2
Author: Infusionsoft
Author URI: http://infusionsoft.com
License: 		GPLv2 or later
License URI: 	http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2014 Infusionsoft (email : info@infusionsoft.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Include WordPress libraries to handle XML-RPC
require_once ABSPATH . '/wp-includes/class-IXR.php';
require_once ABSPATH . '/wp-includes/class-wp-http-ixr-client.php';

class Infusionsoft {
	public $api_key;
	public $error = FALSE;
	public $subdomain;
	private $debug = false;

	private function debug_log($message, $data = null) {
		if ($this->debug && defined('WP_DEBUG') && WP_DEBUG) {
			error_log('=== INFUSIONSOFT WRAPPER DEBUG ===');
			error_log("Message: $message");
			if ($data) error_log('Data: ' . print_r($data, true));
			error_log('================================');
		}
	}

	public function __construct($subdomain = NULL, $api_key = NULL) {
		$this->debug = (defined('WP_DEBUG') && WP_DEBUG);
		
		// Try to get settings from WordPress options if not provided
		if (empty($subdomain) || empty($api_key)) {
			$iv_settings = get_option('iv_settings', []);
			$subdomain = $subdomain ?: ($iv_settings['subdomain'] ?? null);
			$api_key = $api_key ?: ($iv_settings['api_key'] ?? null);
			
			$this->debug_log('Using settings from WordPress options', [
				'subdomain_found' => !empty($subdomain),
				'api_key_found' => !empty($api_key)
			]);
		}

		$this->subdomain = $subdomain;
		$this->api_key = $api_key;

		if (empty($this->subdomain) || empty($this->api_key)) {
			$this->error = new WP_Error(
				'invalid-request', 
				__('You must provide a subdomain and API key for your Infusionsoft application.', 'infusionsoftwp')
			);
			$this->debug_log('Construction failed - missing credentials');
		} else {
			$this->debug_log('Successfully constructed Infusionsoft wrapper', [
				'subdomain' => $this->subdomain,
				'api_key_length' => strlen($this->api_key)
			]);
		}
	}

	public function __call($name, $arguments) {
		// Make sure no error already exists
		if ($this->error) {
			$this->debug_log('Call failed - existing error condition');
			return $this->error;
		}

		// Get the full method name with the service and method
		$method = ucfirst($name) . 'Service' . '.' . array_shift($arguments);
		
		$this->debug_log('Preparing API call', [
			'method' => $method,
			'argument_count' => count($arguments)
		]);

		// Initialize the client with new endpoint
		$client = new WP_HTTP_IXR_Client('https://api.infusionsoft.com/crm/xmlrpc');
		
		// Add Bearer token authentication
		$client->headers['Authorization'] = 'Bearer ' . $this->api_key;

		// Prepare arguments (no need to include API key in params anymore)
		$call_arguments = array_merge(array($method), $arguments);

		$this->debug_log('Sending request', [
			'endpoint' => 'https://api.infusionsoft.com/crm/xmlrpc',
			'method' => $method
		]);

		// Call the function and return any error that happens
		if (!call_user_func_array(array($client, 'query'), $call_arguments)) {
			$error = $client->getErrorMessage();
			$this->debug_log('API call failed', [
				'error' => $error
			]);
			return new WP_Error('invalid-request', $error);
		}

		$response = $client->getResponse();
		$this->debug_log('API call successful', [
			'response_type' => gettype($response),
			'response_length' => is_string($response) ? strlen($response) : count($response)
		]);

		// Pass the response directly to the user
		return $response;
	}

	/**
	 * Test the connection to Infusionsoft
	 * @return bool|WP_Error
	 */
	public function testConnection() {
		try {
			$this->debug_log('Testing connection');
			
			// Try to make a simple API call
			$result = $this->data('echo', 'test');
			
			if ($result === 'test') {
				$this->debug_log('Connection test successful');
				return true;
			} else {
				$this->debug_log('Connection test failed - unexpected response', [
					'response' => $result
				]);
				return new WP_Error('test-failed', 'Connection test failed - unexpected response');
			}
		} catch (Exception $e) {
			$this->debug_log('Connection test failed with exception', [
				'error' => $e->getMessage()
			]);
			return new WP_Error('test-failed', $e->getMessage());
		}
	}
}