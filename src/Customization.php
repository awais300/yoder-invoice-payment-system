<?php

namespace Yoder\YIPS;

defined( 'ABSPATH' ) || exit;

/**
 * Class Customization
 * @package Yoder\YIPS
 */

class Customization {

	private $version = "1.0.0";

	/**
	 * Instance to call certain functions globally within the plugin
	 *
	 * @var _instance
	 */
	protected static $_instance = null;
		
	/**
	* Construct the plugin.
	*/
	public function __construct() {
		
		add_action( 'init', array( $this, 'load_plugin' ), 0 );
		
    }

	/**
	 * Main Customization instance
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	* Determine which plugin to load.
	*/
	public function load_plugin() {
		$this->define_constants();
		$this->init_hooks();
	}

    /**
	 * Define WC Constants.
	 */
	private function define_constants() {
		// Path related defines
		$this->define( 'YIPS_CUST_PLUGIN_FILE', YIPS_CUST_PLUGIN_FILE );
		$this->define( 'YIPS_CUST_PLUGIN_BASENAME', plugin_basename( YIPS_CUST_PLUGIN_FILE ) );
		$this->define( 'YIPS_CUST_PLUGIN_DIR_PATH', untrailingslashit( plugin_dir_path( YIPS_CUST_PLUGIN_FILE ) ) );
		$this->define( 'YIPS_CUST_PLUGIN_DIR_URL', untrailingslashit( plugins_url( '/', YIPS_CUST_PLUGIN_FILE ) ) );
	}

	/**
     * Collection of hooks.
     */
    public function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init' ), 1 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles') );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );		
	}

	/**
	 * Localisation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'yips-customization', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
	}

	/**
     * Initialize the plugin.
     */
    public function init() {
    	new WPLogger();
    	new UserProfileFields();
    	new UserLogin();
    	new Invoice();
    }

	/**
     * Enqueue all styles.
     */
	public function enqueue_styles() {
		wp_enqueue_style('yips-customization-frontend', YIPS_CUST_PLUGIN_DIR_URL . '/assets/css/yips-customization-frontend.css', array(), null, 'all');
		wp_enqueue_style('yips-w3', YIPS_CUST_PLUGIN_DIR_URL . '/assets/css/w3.css', array(), null, 'all');
	}


	/**
     * Enqueue all scripts.
     */
	public function enqueue_scripts() {
		wp_enqueue_script( 'yips-customization-frontend', YIPS_CUST_PLUGIN_DIR_URL . '/assets/js/yips-customization-frontend.js', array( 'jquery' ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

}