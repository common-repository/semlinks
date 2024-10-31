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
$now     = new DateTime();

$nbPostsSynced = 0;
if ( isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] ) ) {
	$nbPostsSynced = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ];
}
$nbPosts = intval( wp_count_posts()->publish );

$isSyncPossible = false;
$isSyncOnGoing  = false;

$syncLastDate = new DateTime();
if ( isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] ) ) {
	try {
		$syncLastDate = new DateTime( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] );

		// We re-estimate the end date based on the last sync date
		$estimatedSyncEndDate = clone $syncLastDate;
		$estimatedSyncEndDate->add( DateInterval::createFromDateString( ( $nbPosts * 2 ) . ' seconds' ) );
		$isSyncOnGoing = ( $nbPosts > $nbPostsSynced ) && ( $now < $estimatedSyncEndDate );
	} catch ( Exception $e ) {
		$isSyncPossible = true;
	}
} else {
	$isSyncPossible = true;
}

$onGoingStyle = ''
?>

<div style="width: 100%;">
	<?php if ( $isSyncPossible ) { ?>
        <button
                id="semlinks-plugin-start-sync-button"
                class="button button-primary semlinks-plugin-start-transaction-button"
                style="margin-right: 2em;"
        >
			<?php esc_attr_e( 'Synchronize posts', 'semlinks' ) ?>
        </button>

        <span id="semlinks-plugin-sync-info"
              style="line-height: 30px;"><?php esc_attr_e( 'This action might take a long time to complete',
		                                                   'semlinks' ); ?></span>
	<?php } elseif ( $isSyncOnGoing ) { ?>
        <button
                id="semlinks-plugin-start-sync-button"
                class="button button-primary semlinks-plugin-start-transaction-button"
                style="margin-right: 2em;"
                disabled
        >
			<?php esc_attr_e( 'Loading...', 'semlinks' ) ?>
        </button>
	<?php } else { ?>
        <button
                id="semlinks-plugin-start-sync-button"
                class="button button-primary semlinks-plugin-start-transaction-button"
                style="margin-right: 2em; background-color: #4CAF50 !important; color: white !important"
                disabled
        >
			<?php esc_attr_e( 'Done', 'semlinks' ) ?>
        </button>
	<?php } ?>
</div>