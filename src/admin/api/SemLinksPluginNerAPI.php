<?php

use model\SemLinksPluginNamedEntity;

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
class SemLinksPluginNerAPI {
	/**
	 * The API version
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The API version.
	 */
	private $version;

	/**
	 * The name of the plugin
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The name of the plugin.
	 */
	private $plugin_name;

	/**
	 * The API namespace
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $namespace The API namespace.
	 */
	private $namespace;

	/**
	 * @param $plugin_name
	 */
	public function __construct( $plugin_name ) {
		$this->version     = '1';
		$this->plugin_name = $plugin_name;
		$this->namespace   = $plugin_name . '/v' . $this->version;
	}

	public function run() {
		// Endpoint for posts ner extraction
		add_action( 'rest_api_init', [ $this, 'register_ner_actions' ] );
	}

	public function register_ner_actions() {
		register_rest_route(
			$this->namespace,
			'/ner/extract',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'do_ner_request' ),
				'permission_callback' => function () {
					return current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_NER_ENTITIES );
				},
			]
		);

		register_rest_route(
			$this->namespace,
			'/dictionary/add-entity',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_tag_to_dictionary' ),
				'permission_callback' => function () {
					return (
						current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_NER_ENTITIES ) &&
						current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_NER_ADD_SUGGESTED_ENTITIES )
					);
				},
			]
		);
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @throws Exception
	 * @since   1.0.0
	 * @access  public
	 */
	public function do_ner_request( $request ) {
		$data = json_decode( $request->get_body(), true );

		$dataKeys = [ "content", "matcher" ];
		foreach ( $dataKeys as $data_key ) {
			if ( ! array_key_exists( $data_key, $data ) || ! $data[ $data_key ] ) {
				return rest_ensure_response( new WP_Error( 400, "Invalid format : $data_key is missing or empty" ) );
			}
		}

		$nerService = new SemLinksPluginNerService();
		try {
			// Decode the HTML entities to avoid having corrupt old articles and missing special chars in the entities
			$decodedContent = html_entity_decode( $data['content'] );
			$nerResponse    = $nerService->get_ner_response_for_content( $decodedContent, $data["matcher"] );
		} catch ( Exception $e ) {
			error_log( "An error occurred while processing the NER request : " . $e->getMessage() . " - " . $e->getTraceAsString() );

			return rest_ensure_response( new WP_Error( 500, "An error occurred." ) );
		}

		$overlapAsArray       = $this->postProcessEntities( $nerResponse->get_html_overlap_errors() );
		$entitiesAsArray      = $this->postProcessEntities( $nerResponse->get_entities(), true );
		$postProcessedArticle = $this->postProcessArticleContent( $nerResponse->get_article()['text'],
		                                                          $entitiesAsArray );

		return rest_ensure_response(
			[
				'entities' => $entitiesAsArray,
				'overlap'  => $overlapAsArray,
				'article'  => [
					'text' => $postProcessedArticle,
				],
			]
		);
	}

	/**
	 * @param $request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @throws Exception
	 */
	public function add_tag_to_dictionary( $request ) {
		$data = json_decode( $request->get_body(), true );

		if ( ! isset( $data["tagId"] ) ) {
			return rest_ensure_response( new WP_Error( 400, "Invalid format : tagId is missing" ) );
		}

		if ( ! isset( $data["entityType"] ) ) {
			$data["entityType"] = "TAG";
		}

		$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		if ( ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY ] )
		     || ! $dictionary = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY ] ) {
			return rest_ensure_response( new WP_Error( 500, "No dictionary configured" ) );
		}

		$tag        = get_term( $data["tagId"] );
		$nerService = new SemLinksPluginNerService();

		$nerService->add_tag_to_dictionary( $tag, $data["entityType"], $dictionary );

		return rest_ensure_response(
			[
				'id'  => $tag->term_id,
				'tag' => $tag->name,
			]
		);
	}

	/**
	 * Replaces the href attribute of the tag with the class 'semlinks-plugin-injected-entity' by the entity url
	 *
	 * @param $article string
	 * @param $entities array
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function postProcessArticleContent( $article, $entities ) {
		$indexedEntities = array_reduce(
			array_filter( $entities, function ( $entity ) {
				return $entity['id'] !== null && $entity['url'] !== '';
			} ),
			function ( $acc, $entity ) {
				$acc[ $entity['id'] ] = $entity;

				return $acc;
			},
			[]
		);

		$matches = [];
		preg_match_all( '/(<a class=\'semlinks-plugin-injected-entity\' data-entity-id=\'\d+\' href=\'[^\']+\'>)/',
		                $article,
		                $matches );

		$flattenMatches = array_reduce( $matches, function ( $acc, $match ) {
			return array_merge( $acc, $match );
		},                              [] );

		foreach ( $flattenMatches as $match ) {
			$entityId = intval( preg_replace( '/<a class=\'semlinks-plugin-injected-entity\' data-entity-id=\'(\d+)\' href=\'[^\']+\'>/',
			                                  '$1',
			                                  $match ) );
			
			if ( ! isset( $indexedEntities[ $entityId ] ) ) {
				continue;
			}

			$entity = $indexedEntities[ $entityId ];
			if ( empty( $entity['url'] ) ) {
				continue;
			}

			$article = preg_replace( '/<a (class=\'semlinks-plugin-injected-entity\' data-entity-id=\'' . $entityId . '\') href=\'[^\']+\'>/',
			                         '<a $1 href=\'' . $entity['url'] . '\'>',
			                         $article );
		}

		return $article;
	}

	/**
	 * Post process entities to add the WP generated url
	 *
	 * @param $entities
	 * @param $generate_urls
	 *
	 * @return array|array[]
	 *
	 * @since 2.0.0
	 * @access private
	 */
	private function postProcessEntities( $entities, $generate_urls = false ) {
		$tags = [];
		if ( $generate_urls && ! empty( $entities ) ) {
			$entityIds = array_filter(
				array_map(
					function ( $entity ) {
						return intval( $entity->get_id() );
					},
					$entities
				),
				function ( $id ) {
					return $id !== 0;
				}
			);

			$tags = get_tags(
				[
					'include' => array_values( $entityIds ),
					'get'     => 'all',
				]
			);

			/** @var WP_Term[] $tags */
			$tags = array_reduce(
				$tags,
				function ( $acc, $tag ) {
					$acc[ $tag->term_id ] = $tag;

					return $acc;
				},
				[]
			);
		}

		return array_map(
		/** @var SemLinksPluginNamedEntity $elem */
			function ( $elem ) use ( $tags ) {
				// $tags is empty if $generate_urls is false
				$tag = isset( $tags[ $elem->get_id() ] ) ? $tags[ $elem->get_id() ] : null;
				if ( ! is_null( $tag ) ) {
					$elem->set_url( get_tag_link( $tag ) );
				}

				return $elem->to_array();
			},
			$entities
		);
	}
}