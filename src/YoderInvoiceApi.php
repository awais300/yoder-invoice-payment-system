<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class YoderInvoiceApi
 * @package Yoder\YIPS
 */

class YoderInvoiceApi
{

	private $url = '';

	private $key = '';

	/**
	 * Instance to call certain functions globally within the plugin
	 *
	 * @var instance
	 */
	protected static $instance = null;

	/**
	 * Construct the plugin.
	 */
	public function __construct()
	{
	}

	/**
	 * Main instance
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @static
	 * @return self Main instance.
	 */
	public static function get_instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function request($args, $endpoint, $api_secret = null, $method = 'GET')
	{
		if (is_null($api_secret)) {
			$api_secret = $this->api_secret();
		}

		//Populate the correct endpoint for the API request
		$url                = "https://api.convertkit.com/v3/{$endpoint}?api_secret={$api_secret}";

		//Allow 3rd parties to alter the $args
		$args               = apply_filters('convertkit-call-args', $args, $endpoint, $method);

		//Populate the args for use in the wp_remote_request call
		$wp_args            = array('body' => $args);
		$wp_args['method']  = $method;
		$wp_args['timeout'] = 30;

		//Make the call and store the response in $res
		$res = wp_remote_request($url, $wp_args);

		//Check for success
		if (!is_wp_error($res) && ($res['response']['code'] == 200 || $res['response']['code'] == 201)) {
			return $res['body'];
		} else {
			return false;
		}
	}
}
