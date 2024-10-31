<?php

/**
 * The API endpoints for the related posts feature
 *
 * This is used to register all API endpoints used by the plugin for the related posts feature.
 *
 *
 * @since      1.0.0
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/admin/api
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginRelatedPostsHooks {
	/**
	 * The MoreLikeThisService
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SemLinksPluginMoreLikeThisService $moreLikeThisService
	 */
	private $moreLikeThisService;

	/**
	 * @since    1.0.0
	 * @access   private
	 * @var      $loader
	 */
	private $loader;

	/**
	 * @param $moreLikeThisService
	 * @param $loader
	 */
	public function __construct( $moreLikeThisService, $loader ) {
		$this->moreLikeThisService = $moreLikeThisService;
		$this->loader              = $loader;
	}

	public function run() {
		// On Post upsert
		$this->loader->add_action( 'wp_insert_post', $this, 'on_insert_post', 10, 3 );
	}


	/**
	 * Hook on post upsert to synchronize with the CSP
	 *
	 * @return void
	 * @throws Exception
	 * @var WP_Post $post
	 * @var bool $update
	 *
	 * @var int $post_id
	 * @since   1.0.0
	 */
	public function on_insert_post( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// We want to synchronize only posts that are published
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// If the post just got created, we increment the sync count
		if ( ! $update ) {
			$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
			if ( ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] ) ) {
				$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] = 0;
			}
			$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] += 1;
			update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY, $options );
		}

		try {
			$this->moreLikeThisService->save_post( $post );
		} catch ( Exception $exception ) {
			$exceptionMessage = $exception->getMessage();
			error_log( "An exception occurred while synchronizing the post with the CSP : $exceptionMessage" );
		}
	}
}