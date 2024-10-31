<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/includes
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginI18N {

	private $plugin_name;

	/**
	 * @param $plugin_name
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'semlinks',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/translated'
		);
	}


}
