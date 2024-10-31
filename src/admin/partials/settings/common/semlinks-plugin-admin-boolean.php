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
$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
if ( ! isset( $idKey ) ) {
	throw new Exception( 'idKey is not defined' );
}

if ( ! isset( $shortKey ) ) {
	throw new Exception( 'shortKey is not defined' );
}

if ( ! isset( $options[ $shortKey ] ) ) {
	$options[ $shortKey ] = '0';
}
?>

<input
        id='<?php echo esc_attr( $idKey ) ?>'
        name='<?php echo esc_attr( $this->plugin_name ) ?>_options[<?php echo esc_attr( $shortKey ) ?>]'
        type='checkbox'
        value="1"
	<?php checked( 1, $options[ $shortKey ] ) ?>
/>