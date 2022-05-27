<?php

namespace Yoder\YIPS;

defined( 'ABSPATH' ) || exit;

/**
 * Class TemplateLoader
 * @package Yoder\YIPS
 */

class TemplateLoader {

	/**
	 * Instance to call certain functions globally within the plugin.
	 *
	 * @var instance
	 */
	protected static $instance = null;

	/**
	 * Ensures only one instance is loaded or can be loaded.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads a template.
	 * @param  string $template_name
	 * @param  array $args
	 * @param  string $template_path
	 * @param  bool $echo
	 *
	*/
	public function get_template( $template_name, $args = array(), $template_path, $echo = false ) {
		$output = null;

		$template_path = $template_path . $template_name;

		if ( file_exists( $template_path ) ) {
			extract( $args ); // @codingStandardsIgnoreLine required for template.

			ob_start();
			include $template_path;
			$output = ob_get_clean();
		} else {
			throw new \Exception( __( 'Specified path does not exist' ) );
		}

		if ( $echo ) {
			print $output;
		} else {
			return $output;
		}
	}
}
