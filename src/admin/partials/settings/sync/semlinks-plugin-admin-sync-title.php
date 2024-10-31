<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly // Silence is golden

/**
 * @link       https://github.com/tschaeller
 * @since      1.0.0
 *
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/admin/partials
 */

function render() {
	ob_start();
	echo "<h3 class='semlinks-plugin-no-margin'>";
	esc_attr_e( 'Initialization', 'semlinks' );
	echo "</h3>";
	return ob_get_clean();
}
