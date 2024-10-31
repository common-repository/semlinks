<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 * For now the creation of new capabilities specific to the plugin.
 *
 * @since      1.0.0
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/includes
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginActivator {

	/**
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( __FILE__ ) . '../common/SemLinksPluginCapabilities.php';
		SemLinksPluginCapabilities::add_capabilities_to_default_roles();
	}

}
