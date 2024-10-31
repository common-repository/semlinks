<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/includes
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginDeactivator {

	/**
	 * @since    1.0.0
	 */
	public static function deactivate() {
		require_once plugin_dir_path( __FILE__ ) . '../common/SemLinksPluginCapabilities.php';
		SemLinksPluginCapabilities::remove_capabilities_from_all_editable_roles();
	}

}
