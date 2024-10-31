<?php

/**
 * The CSP specific methods needed to get moreLikeThis results.
 *
 * Defines the cspBaseUrl depending on the app environment and
 * exposes multiple methods to use the CSP's moreLikeThis feature.
 *
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/commun/CSP
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginMoreLikeThisService extends SemLinksPluginCSPService {
	private $relatedPostManager;

	public function __construct() {
		parent::__construct();

		$this->cspBaseUrl         .= '/api/beta/article';
		$this->relatedPostManager = new SemLinksPluginRelatedPostManager();
	}

	/**
	 * Save a post in the CSP content repository
	 *
	 * @throws Exception
	 * @var      WP_Post $post The post to save.
	 * @since    1.0.0
	 * @access   public
	 */
	public function save_post( $post ) {
		$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		$this->check_post_type_and_api_key( $post, $options );

		$postUpsertEndpoint = $this->cspBaseUrl . "/{$post->ID}";

		$postAsArray = [
			"title"        => $post->post_title,
			"introduction" => $post->post_excerpt,
			"text"         => wp_strip_all_tags( $post->post_content ),
			"meta"         => [
				"post_url"          => get_post_permalink( $post ),
				"post_id"           => $post->ID,
				"post_published_at" => ( new DateTime( $post->post_date ) )->format( 'Y-m-d\TH:i:s\Z' ),
				"post_updated_at"   => ( new DateTime( $post->post_modified ) )->format( 'Y-m-d\TH:i:s\Z' ),
			],
		];

		$this->do_request( $postUpsertEndpoint, "PUT", $postAsArray );
	}

	/**
	 * Save the posts with the given ids in the CSP content repository
	 *
	 * @throws Exception
	 * @var      int[] $postIds The posts to save.
	 * @since    1.0.0
	 * @access   public
	 */
	public function save_posts( $postIds ) {
		try {

			if ( count( $postIds ) === 0 ) {
				return;
			}

			$posts = get_posts(
				[
					"numberposts" => 50,
					"include"     => $postIds,
				]
			);
			foreach ( $posts as $post ) {
				$this->save_post( $post );
			}

			$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
			if ( ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] ) ) {
				$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] = 0;
			}
			$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] = intval( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] )
			                                                                                + count( $postIds );
			update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY, $options );
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
			error_log( $e->getTraceAsString() );
			throw $e;
		}
	}

	/**
	 * Save all the posts in the CSP content repository
	 *
	 * @throws Exception
	 * @since    1.0.0
	 * @access   public
	 */
	public function save_all_posts() {
		$now           = new DateTime();
		$nextExecution = $now->modify( "+10 minutes" )->getTimestamp();
		try {
			// We reinitialize the sync counter
			$options                                                                      = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
			$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT ] = 0;
			update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY, $options );

			$startDate = isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_SHORT_KEY ] )
				? $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_SHORT_KEY ]
				: null;

			$postdate = null;
			if ( $startDate ) {
				$postdate = ( new DateTime( $startDate ) )->format( 'Y-m-d' );
			}

			$offset = 0;
			do {
				$params = [
					"numberposts" => 50,
					"offset"      => $offset,
				];

				if ( $postdate ) {
					$params["date_query"] = [
						"after"     => $postdate,
						"inclusive" => true,
					];
				}

				$posts = get_posts(
					$params
				);

				$postIds = [];
				array_walk( $posts, function ( $post ) use ( &$postIds ) {
					$postIds[] = $post->ID;
				} );

				if ( count( $postIds ) > 0 ) {
					// Save those posts asynchronously
					as_schedule_single_action( $nextExecution, 'semlinks_plugin_synchronize_posts', [ $postIds ], [], '', false, 10 );
				}
				$nextExecution = (new DateTime("@$nextExecution"))->modify( "+1 minute" )->getTimestamp();

				$offset += 50;
			} while ( ! empty( $posts ) );
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
			error_log( $e->getTraceAsString() );
			throw $e;
		}
	}

	/**
	 * Discovers the Posts that look alike in CSP content repository
	 *
	 * @throws Exception
	 * @var      WP_Post $post The post to save.
	 * @since    1.0.0
	 * @access   public
	 */
	public function discover_post_look_alike( $post, $save = false ) {
		$ids = $this->discover_look_alike_for_content_title_and_intro( $post->ID,
		                                                               $post->post_content,
		                                                               $post->post_title,
		                                                               $post->post_excerpt ?: $post->post_title
		);

		if ( $save ) {
			$this->relatedPostManager->saveRelatedPosts( $post, $ids );
		}

		if ( empty( $ids ) ) {
			return [];
		}

		return $this->relatedPostManager->getRelatedPostsObjects( $post );
	}

	/**
	 * Discovers the Posts that look alike in CSP content repository
	 *
	 * @throws Exception
	 * @since    1.0.0
	 * @access   public
	 */
	public function discover_look_alike_for_content_title_and_intro( $originPostId, $content, $title, $introduction ) {
		$options              = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		$postDiscoverEndpoint = $this->cspBaseUrl . "/lookalike/discover";

		$filter = [
			[
				"meta.post_id" => [
					"operator" => "!=",
					"value"    => $originPostId,
				],
			],
		];
		if ( isset ( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_SHORT_KEY ] ) &&
		     null !== ( $syncStartDate = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_SHORT_KEY ] ) &&
		     isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] ) &&
		     null !== ( $lastSyncDate = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] )
		) {
			$syncStartDateInstance = DateTime::createFromFormat( "Y-m-d", $syncStartDate );
			$lastSyncDateInstance  = DateTime::createFromFormat( "Y-m-d", $lastSyncDate );
			if ( $syncStartDate && $lastSyncDateInstance ) {
				$interval   = $syncStartDateInstance->diff( $lastSyncDateInstance );
				$filterDate = ( new DateTime() )->sub( $interval );
				$filter[]   = [
					"meta.post_published_at" => [
						"operator" => ">=",
						"value"    => $filterDate->format( 'Y-m-d\TH:i:s\Z' ),
					],
				];
			}
		}


		$postAsArray = [
			"article" => [
				"title"        => $title,
				"introduction" => $introduction,
				"text"         => wp_strip_all_tags( $content ),
			],
			"size"    => $this->get_attribute_from_options_or_default(
				$options,
				SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_NB_RESULTS_SHORT_KEY,
				10 ),
			"filter"  => $filter,
			"return"  => [
				"meta.post_url",
				"meta.post_id",
				"meta.post_published_at",
			],
		];

		$responseData = $this->do_request( $postDiscoverEndpoint, "POST", $postAsArray );

		$ids = [];
		array_walk( $responseData["result"], function ( $hit ) use ( &$ids ) {
			$ids[] = $hit["id"];
		} );

		return $ids;
	}

	/**
	 * Discovers the Posts that look alike in CSP content repository
	 *
	 * @throws Exception
	 * @var      WP_Post $post The post to save.
	 * @var      array $options The options of the plugin.
	 * @since    1.0.0
	 * @access   private
	 */
	private function check_post_type_and_api_key( $post, $options ) {
		if ( ! $post instanceof WP_Post ) {
			throw new Exception( "Expected WP_Post got : " . esc_attr( gettype( $post ) ) . "\n In MoreLikeThisService::save_post()" );
		}

		if ( ! $options || ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ] ) || ! ( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ] ) ) {
			throw new Exception( "The API Key is not defined." );
		}
	}
}