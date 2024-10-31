<?php

/**
 * The API endpoints for the related posts toolbar actions
 *
 * This is used to register all API endpoints used by the plugin for the related posts toolbar actions.
 *
 * @since      1.0.0
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/admin/api
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginRelatedPostsAPI {
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

	/**
	 * @var SemLinksPluginMoreLikeThisService $moreLikeThisService
	 * @since 1.0.0
	 * @access private
	 */
	private $moreLikeThisService;

	/**
	 * @var SemLinksPluginRelatedPostManager $relatedPostManager
	 * @since 1.0.0
	 * @access private
	 */
	private $relatedPostManager;

	/**
	 * @since    1.0.0
	 * @access   private
	 * @var      $loader
	 */
	private $loader;

	/**
	 * The semLinkPluginAdmin
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SemLinksPluginAdmin $semLinkPluginAdmin
	 */
	private $semLinkPluginAdmin;

	public function __construct( $plugin_name, $loader, $semLinkPluginAdmin ) {
		$this->version             = '1';
		$this->namespace           = $plugin_name . '/v' . $this->version;
		$this->moreLikeThisService = new SemLinksPluginMoreLikeThisService();
		$this->relatedPostManager  = new SemLinksPluginRelatedPostManager();
		$this->loader              = $loader;
		$this->semLinkPluginAdmin  = $semLinkPluginAdmin;
	}

	public function run() {
		add_action( 'rest_api_init', [ $this, 'register_related_posts_actions' ] );


		// Adds the semlinks_remove_related_post ajax handler
		$this->loader->add_action( 'wp_ajax_semlinks_remove_related_post', $this, 'remove_related_post' );
		// Endpoint for posts look alike removal
		$this->loader->add_action( 'wp_ajax_semlinks_remove_all_related_posts', $this, 'remove_all_related_posts' );
	}

	public function register_related_posts_actions() {
		register_rest_route(
			$this->namespace,
			'/related-posts',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_related_posts_list' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);

		register_rest_route(
			$this->namespace,
			'/related-posts/remove-post',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'remove_related_post' ),
				'permission_callback' => function () {
					return current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER );
				},
			]
		);

		register_rest_route(
			$this->namespace,
			'/related-posts/nb-already-synced',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_nb_synced_posts' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);

		register_rest_route(
			$this->namespace,
			'/related-posts/save-related-posts',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_related_posts' ),
				'permission_callback' => function () {
					return current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER );
				},
			]
		);
	}

	/**
	 * Will remove the given post from the related posts list
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function remove_related_post() {
		if (
			! isset( $_REQUEST['nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ),
			                   'semlinks-plugin_remove_related_post' ) ||
			! current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER )
		) {
			wp_send_json_error( "Permission denied.", 403 );

			return;
		}

		if ( ! isset( $_REQUEST['postId'] ) || null === ( $postId = sanitize_title( $_REQUEST['postId'] ) ) ) {
			wp_send_json_error( "Missing parameter : postId", 400 );

			return;
		}

		if ( ! isset( $_REQUEST['relatedPost'] ) || null === ( $relatedPost = sanitize_title( $_REQUEST['relatedPost'] ) ) ) {
			wp_send_json_error( "Missing parameter : relatedPost", 400 );

			return;
		}

		$post = get_post( $postId );
		if ( ! $post ) {
			wp_send_json_error( "Post {$postId} not found.", 404 );

			return;
		}

		$posts = $this->relatedPostManager->removeRelatedPost( $post, $relatedPost );

		ob_start();
		$this->semLinkPluginAdmin->display_related_posts_meta_box_content( $post );
		$metaBoxContent = ob_get_clean();

		wp_send_json_success( [ 'posts' => $posts, 'metaBoxContent' => $metaBoxContent ] );
	}

	/**
	 * Will remove all related posts from the given post
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function remove_all_related_posts() {
		if (
			! isset( $_REQUEST['nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ),
			                   'semlinks-plugin_remove_all_related_posts' ) ||
			! current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER )
		) {
			wp_send_json_error( "Permission denied.", 403 );

			return;
		}

		if ( ! isset( $_REQUEST['postId'] ) || null === ( $postId = sanitize_title( $_REQUEST['postId'] ) ) ) {
			wp_send_json_error( "Missing parameter : postId", 400 );

			return;
		}

		$post = get_post( $postId );
		if ( ! $post ) {
			wp_send_json_error( "Post {$postId} not found.", 404 );

			return;
		}

		$posts = $this->relatedPostManager->removeAllRelatedPost( $post );

		ob_start();
		$this->semLinkPluginAdmin->display_related_posts_meta_box_content( $post );
		$metaBoxContent = ob_get_clean();

		wp_send_json_success( [ 'posts' => $posts, 'metaBoxContent' => $metaBoxContent ] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @since   1.0.0
	 * @access  public
	 */
	public function get_related_posts_list( $request ) {
		$data     = json_decode( $request->get_body(), true );
		$dataKeys = [
			"postId"       => "required",
			"title"        => "required",
			"introduction" => "required",
			"content"      => "required",
		];
		$error    = $this->validate_request_data( $data, $dataKeys );
		if ( $error ) {
			return $error;
		}

		try {
			$ids = $this->moreLikeThisService->discover_look_alike_for_content_title_and_intro( sanitize_title( $data["postId"] ),
			                                                                                    sanitize_title( $data["content"] ),
			                                                                                    sanitize_title( $data["title"] ),
			                                                                                    sanitize_title( $data["introduction"] ) );
		} catch ( Exception $exception ) {
			return rest_ensure_response(
				new WP_Error( 500,
				              "An error occurred while fetching the related posts : {$exception->getMessage()}"
				)
			);
		}

		$posts = [];
		if ( count( $ids ) > 0 ) {
			$posts = get_posts( [ "include" => $ids, "suppress_filters" => false ] );
		}

		$permalinks = [];
		/** @var WP_Post $post */
		foreach ( $posts as $post ) {
			$permalinks[ $post->ID ] = get_permalink( $post );
		}

		return rest_ensure_response( [ "posts" => $posts, "permalinks" => $permalinks ] );
	}

	/**
	 * Saves the list of related posts for a given post
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function save_related_posts( $request ) {
		$options            = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		$activateRetroLinks = isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_ACTIVATE_RETRO_LINKS_SHORT_KEY ] )
		                      && $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_ACTIVATE_RETRO_LINKS_SHORT_KEY ];
		$data               = json_decode( $request->get_body(), true );
		$dataKeys           = [ "postId" => "required", "posts" => "optional" ];
		$error              = $this->validate_request_data( $data, $dataKeys );
		if ( $error ) {
			return $error;
		}

		$postId = sanitize_title( $data["postId"] );
		// Posts are sent as an array of ids
		$posts = $data["posts"];

		$post = get_post( $postId );
		if ( ! $post ) {
			return rest_ensure_response( new WP_Error( 404, "Post {$postId} not found." ) );
		}

		$this->relatedPostManager->saveRelatedPosts( $post, $posts );

		if ( $activateRetroLinks ) {
			foreach ( $posts as $relatedPostId ) {
				$relatedPost = get_post( $relatedPostId );
				if ( $relatedPost ) {
					$this->relatedPostManager->addRelatedPost( $relatedPost, $post->ID );
				}
			}
		}

		ob_start();
		$this->semLinkPluginAdmin->display_related_posts_meta_box_content( $post );
		$metaBoxContent = ob_get_clean();

		return rest_ensure_response( [ 'success' => true, 'posts' => $posts, 'metaBoxContent' => $metaBoxContent ] );
	}

	/**
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @since   1.0.0
	 * @access  public
	 */
	public function get_nb_synced_posts() {
		$options       = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		$nbPostsSynced = 0;
		if ( isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] ) ) {
			$nbPostsSynced = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ];
		}

		$nbTagsSynced = 0;
		if ( isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] ) ) {
			$nbTagsSynced = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ];
		}

		return rest_ensure_response( [ "nbPostSynced" => $nbPostsSynced, "nbTagSynced" => $nbTagsSynced ] );
	}

	/**
	 * @param $data
	 * @param $data_keys
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response|null
	 */
	private function validate_request_data( $data, $data_keys ) {
		foreach ( $data_keys as $data_key => $data_value ) {
			if ( ! array_key_exists( $data_key, $data ) ) {
				return rest_ensure_response( new WP_Error( 400, "Invalid format : $data_key is missing" ) );
			}

			if ( $data_value !== "optional" && ! $data[ $data_key ] ) {
				return rest_ensure_response( new WP_Error( 400, "Invalid format : $data_value is empty" ) );
			}
		}

		return null;
	}
}