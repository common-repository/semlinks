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

$selectedValue = null;
if ( isset( $options[ $shortKey ] ) ) {
	$selectedValue = $options[ $shortKey ];
}

$dictionaries = [];
if ( ! empty( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ] ) ) {
	$service = new SemLinksPluginNerService();
	try {
		$dictionaries = array_merge( [ "" ], $service->get_csp_dictionary_list() );
	} catch ( Exception $e ) {
		$dictionaries = [];
	}
}
?>

<select name='<?php echo esc_attr( $this->plugin_name ) ?>_options[<?php echo esc_attr( $shortKey ) ?>]'
        id='<?php echo esc_attr( $idKey ) ?>'
        style="min-width: 90%;"
>
	<?php foreach ( $dictionaries as $dictionary ) { ?>
        <option
                value="<?php echo esc_attr( $dictionary ); ?>"
			<?php if ( $dictionary === $selectedValue )
				echo "selected" ?>
        >
			<?php echo esc_html( $dictionary ); ?>
        </option>
	<?php } ?>
</select>