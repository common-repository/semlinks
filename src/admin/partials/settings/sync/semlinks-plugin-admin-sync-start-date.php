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
	$options[ $shortKey ] = '';
}

$nbPostsSynced = 0;
if ( isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] ) ) {
	$nbPostsSynced = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ];
}
$nbPosts = wp_count_posts()->publish;

$nbTagsSynced = 0;
if ( isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] ) ) {
	$nbTagsSynced = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ];
}

$nbTags = wp_count_terms( 'post_tag' );
?>

<input
        type="hidden"
        id="semlinks-plugin_sync_posts_progress"
        name="semlinks-plugin_sync_posts_progress"
        value="<?php echo esc_attr( $nbPostsSynced ) ?>"
>

<input
        type="hidden"
        id="semlinks-plugin_sync_posts_total"
        name="semlinks-plugin_sync_posts_total"
        value="<?php echo esc_attr( $nbPosts ) ?>"
>

<input
        type="hidden"
        id="semlinks-plugin_sync_tags_progress"
        name="semlinks-plugin_sync_tags_progress"
        value="<?php echo esc_attr( $nbTagsSynced ) ?>"
>

<input
        type="hidden"
        id="semlinks-plugin_sync_tags_total"
        name="semlinks-plugin_sync_tags_total"
        value="<?php echo esc_attr( $nbTags ) ?>"
>

<input
        id='<?php echo esc_attr( $idKey ) ?>'
        name='<?php echo esc_attr( $this->plugin_name ) ?>_options[<?php echo esc_attr( $shortKey ) ?>]'
        type='date'
        value='<?php echo esc_attr( $options[ $shortKey ] ); ?>'
        max='<?php if ( ! empty( $max ) ) {
			echo esc_attr( $max );
		} ?>'
/>