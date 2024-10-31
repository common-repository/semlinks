<?php

use model\SemLinksPluginNamedEntity;
use model\SemLinksPluginNerResponse;

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
class SemLinksPluginNerService extends SemLinksPluginCSPService {

	private $baseNerEndpoint;

	public function __construct() {
		parent::__construct();

		$this->cspBaseUrl      .= '/api/beta';
		$this->baseNerEndpoint = $this->cspBaseUrl . '/article/name/extract';
	}

	/**
	 * Extracts named entities from a post and returns them.
	 *
	 * @throws Exception
	 * @var      WP_Post $post The post to save.
	 * @since    1.0.0
	 * @access   public
	 */
	public function extract_post_named_entities( $post ) {
		$entities = $this->get_ner_response_for_content( $post->post_content );

		if ( empty( $entities ) ) {
			return [];
		}

		return $entities;
	}

	/**
	 * Extracts named entities from a text, title and intro and returns them.
	 *
	 * @throws Exception
	 * @since    1.0.0
	 * @access   public
	 */
	public function get_ner_response_for_content( $content, $matcher = "csp_smart_matcher" ) {
		$options   = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		$threshold = ( $this->get_attribute_from_options_or_default( $options,
		                                                             $this->getThresholdOptionKey(),
		                                                             90 ) / 100 );

		$dictionary = $this->get_attribute_from_options_or_default( $options,
		                                                            $this->getDictionaryOptionKey(),
		                                                            null );

		$onlyTagFirstOccurrence = $this->get_attribute_from_options_or_default( $options,
		                                                                        $this->getOnlyFirstOccurrenceOptionKey(),
		                                                                        false );

		$currentRootUrl = get_site_url();
		// TODO For now, it is replaced later by a link generated in WP
		//  will be updated after https://contentside.atlassian.net/browse/API-721 is done
		$urlFormat = $this->get_attribute_from_options_or_default( $options,
		                                                           $this->getUrlFormatOptionKey(),
		                                                           "$currentRootUrl/tag/{{id}}" );
		$inlineTag = "<a class='semlinks-plugin-injected-entity' data-entity-id='{{id}}' href='{$urlFormat}'>{{quote}}</a>";

		if ( ! $dictionary ) {
			throw new Exception( "No dictionary configured" );
		}

		$nerEndpoint = $this->baseNerEndpoint . "?threshold=$threshold";

		$requestBody = [
			"html_overlap_errors" => "output",
			"inline_tagging"      => true,
			"extraction_engine"   => $matcher,
			"dictionaries"        => [ $dictionary ],
			"inline_tag"          => $inlineTag,
			"article"             => [
				"title"        => "",
				"introduction" => "",
				"text"         => $content,
			],
		];

		if ( $onlyTagFirstOccurrence ) {
			$requestBody["inline_tag_only_first"] = true;
		}

		$responseData = $this->do_request( $nerEndpoint, "POST", $requestBody );

		$entities = $this->map_entities( $responseData["result"] );

		$overlap = [];
		if ( isset( $responseData["html_overlap_errors"] ) ) {
			$overlap = $this->map_entities( $responseData["html_overlap_errors"] );
		}

		// Remove overlap from entities
		$entities = array_filter( $entities, function ( $entity ) use ( $overlap ) {
			return ! in_array( $entity, $overlap );
		} );

		return new SemLinksPluginNerResponse( $entities, $overlap, $responseData["article"] );
	}

	/**
	 * Save all the tags in the CSP dictionary
	 *
	 * @throws Exception
	 * @since    1.0.0
	 * @access   public
	 */
	public function save_all_tags() {
		$now           = new DateTime();
		$nextExecution = $now->modify( "+1 minutes" )->getTimestamp();
		try {
			// We reinitialize the sync counter
			$options                                                             = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
			$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] = 0;
			update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY, $options );

			// We first empty the dictionary
			$configuredDictionary = $this->get_attribute_from_options_or_default( get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY ),
			                                                                      $this->getDictionaryOptionKey(),
			                                                                      null );

			if ( ! $configuredDictionary ) {
				error_log( "No dictionary configured, could not sync the tags" );
				throw new Exception( "No dictionary configured, could not sync the tags" );
			}

			$dictionaryEndpoint = $this->cspBaseUrl . '/dictionaries/' . $configuredDictionary . '/entity';
			$requestBody        = [];
			$this->do_request( $dictionaryEndpoint, "POST", $requestBody );

			$offset = 0;
			do {
				$tags = get_terms(
					[
						"taxonomy"   => "post_tag",
						"offset"     => $offset,
						"hide_empty" => false,
						"number"     => 200,
					]
				);

				$tagIds = [];
				/** @var $tag WP_Term */
				array_walk( $tags, function ( $tag ) use ( &$tagIds ) {
					$tagIds[] = $tag->term_id;
				} );


				if ( count( $tagIds ) > 0 ) {
					// Save those tags asynchronously, 1 message every 10 seconds
					as_schedule_single_action( $nextExecution, 'semlinks_plugin_synchronize_tags', [ $tagIds ], [], '', false, 1 );
				}
				$nextExecution = (new DateTime("@$nextExecution"))->modify( "+1 minute" )->getTimestamp();

				$offset += 200;
			} while ( ! empty( $tags ) );
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
			error_log( $e->getTraceAsString() );
			throw $e;
		}
	}

	/**
	 * Save the tags with the given ids in the CSP dictionary
	 *
	 * @throws Exception
	 * @var      int[] $tagIds The tags to save.
	 * @since    1.0.0
	 * @access   public
	 */
	public function save_tags( $tagIds ) {
		try {
			if ( count( $tagIds ) === 0 ) {
				return;
			}

			$configuredDictionary = $this->get_attribute_from_options_or_default( get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY ),
			                                                                      $this->getDictionaryOptionKey(),
			                                                                      null );

			if ( ! $configuredDictionary ) {
				error_log( "No dictionary configured, could not sync the tags" );
				throw new Exception( "No dictionary configured, could not sync the tags" );
			}

			$tags = get_terms(
				[
					"taxonomy"   => "post_tag",
					"include"    => $tagIds,
					"hide_empty" => false,
					"number"     => 200,
				]
			);

			$dictionaryEndpoint = $this->cspBaseUrl . '/dictionaries/' . $configuredDictionary . '/entity?useLabelAsVariant=true';
			$requestBody        = [];
			foreach ( $tags as $tag ) {
				$requestBody[] = [
					"id"       => $tag->term_id,
					"type"     => "TAG",
					"label"    => $tag->name,
					"variants" => [],
				];
			}
			$this->do_request( $dictionaryEndpoint, "PUT", $requestBody );

			$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
			if ( ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] ) ) {
				$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] = 0;
			}
			$options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] = intval( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_TAGS_SYNC_COUNT ] )
			                                                                       + count( $tagIds );
			update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY, $options );
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
			error_log( $e->getTraceAsString() );
			throw $e;
		}
	}

	/**
	 * @param WP_Term $tag
	 * @param string $type
	 * @param string $dictionary
	 *
	 * @return void
	 * @throws Exception
	 */
	public function add_tag_to_dictionary( $tag, $type, $dictionary ) {
		$dictionaryEndpoint = $this->cspBaseUrl . '/dictionaries/' . $dictionary . '/entity/' . $tag->term_id;
		$requestBody        = [
			"type"  => $type,
			"label" => $tag->name,
		];

		return $this->do_request( $dictionaryEndpoint, "PUT", $requestBody );
	}

	/**
	 * Gets all the dictionaries available to the current Context
	 *
	 * @return string[] The list of dictionaries
	 * @throws Exception
	 * @since    1.0.0
	 * @access   public
	 */
	public function get_csp_dictionary_list() {
		if ( false === ( $dictionaries = wp_cache_get( 'semlinks_plugin_dictionaries', 'semlinks-plugin' ) ) ) {
			$dictionaryEndpoint = $this->cspBaseUrl . '/dictionaries';
			$dictionaries       = $this->do_request( $dictionaryEndpoint, "GET", [] );

			wp_cache_set( 'semlinks_plugin_dictionaries', $dictionaries, 'semlinks-plugin', HOUR_IN_SECONDS );
		}

		return $dictionaries;
	}

	/**
	 * Generates the URL for the entity.
	 * By default, the URL points to the entity's tag page,
	 * but it can be overwritten by the user's configuration.
	 *
	 * @param SemLinksPluginNamedEntity $entity
	 *
	 * @return string
	 * @throws Exception
	 * @since    1.0.0
	 * @access   public
	 */
	public function generate_entity_url( $entity ) {
		$entityAssociatedTags = get_terms(
			[
				'taxonomy'   => 'post_tag',
				'search'     => $entity->get_entity(),
				'hide_empty' => false,
			]
		);

		if ( empty( $entityAssociatedTags ) ) {
			return null;
		}

		if ( count( $entityAssociatedTags ) > 1 ) {
			return null;
		}

		$entityTag = $entityAssociatedTags[0];
		$termLink  = get_term_link( $entityTag );
		if ( is_wp_error( $termLink ) ) {
			return null;
		}

		return $termLink;
	}

	/**
	 * @param $entitiesAsArray
	 *
	 * @return array
	 * @throws Exception
	 */
	private function map_entities( $entitiesAsArray ) {
		$entities = [];
		array_walk( $entitiesAsArray, function ( $entity ) use ( &$entities ) {
			$entities[] = SemLinksPluginNamedEntity::from_array(
				[
					"entity"     => htmlentities( $entity["label"], ENT_NOQUOTES, "UTF-8" ),
					"type"       => $entity["type"],
					"score"      => $entity["score"],
					"start_char" => $entity["start_char"],
					"end_char"   => $entity["end_char"],
					"from"       => $entity["from"],
					"id"         => $entity["id"],
				]
			);
		} );

		return $entities;
	}

	private function getThresholdOptionKey() {
		return SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_THRESHOLD_SHORT_KEY;
	}

	private function getDictionaryOptionKey() {
		return SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY;
	}

	private function getOnlyFirstOccurrenceOptionKey() {
		return SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_THE_FIRST_OCCURRENCE_SHORT_KEY;
	}

	private function getUrlFormatOptionKey() {
		return SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_URL_FORMAT_SHORT_KEY;
	}
}