<?php

class SemLinksPluginInitialSyncAPI {
	/**
	 * The API version
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The API version.
	 */
	private $version;

	/**
	 * The API namespace
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $namespace The API namespace.
	 */
	private $namespace;

	public function __construct( $plugin_name ) {
		$this->version   = '1';
		$this->namespace = $plugin_name . '/v' . $this->version;
	}

	public function run() {
		add_action( 'rest_api_init', [ $this, 'register_sync_actions' ] );
	}

	public function register_sync_actions() {
		register_rest_route(
			$this->namespace,
			'/initial-sync/start-sync',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start_synchronization' ),
				'permission_callback' => function () {
					return current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER );
				},
			]
		);
	}

	/**
	 * Will start the posts and tags synchronization process in the background
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 *
	 * @since   1.0.0
	 */
	public function start_synchronization() {
		// We mark the last sync date
		$options                                                               = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] = ( new DateTime() )->format( 'Y-m-d H:i:s' );
		update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY, $options );

		// Start the all the synchronizations as background processes
		$relatedPostsSyncId = as_enqueue_async_action( 'semlinks_plugin_synchronize_all_posts' );
		$tagsSyncId         = as_enqueue_async_action( 'semlinks_plugin_synchronize_all_tags' );

		return rest_ensure_response(
			[
				'success'            => true,
				'relatedPostsSyncId' => $relatedPostsSyncId,
				'tagsSyncId'         => $tagsSyncId,
			]
		);
	}
}