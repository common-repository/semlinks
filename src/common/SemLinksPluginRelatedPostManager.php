<?php

class SemLinksPluginRelatedPostManager {

	/**
	 * @param WP_Post $post
	 * @param int[] $ids
	 *
	 * @return void
	 */
	public function saveRelatedPosts( $post, $ids ) {
		update_post_meta( $post->ID,
		                  SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_META_KEY,
		                  wp_json_encode( $ids ) );
	}

	/**
	 * Adds a new related post to the list of related posts
	 *
	 * @param $post
	 * @param $id
	 *
	 * @return void
	 */
	public function addRelatedPost( $post, $id ) {
		$idsInJSON = get_post_meta( $post->ID, SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_META_KEY, true );

		$ids = json_decode( $idsInJSON, true );

		if ( empty( $ids ) ) {
			$ids = [];
		}

		if ( ! in_array( $id, $ids ) ) {
			$ids[] = $id;
		}

		$this->saveRelatedPosts( $post, $ids );
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function getRelatedPostsObjects( $post ) {
		$idsInJSON = get_post_meta( $post->ID, SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_META_KEY, true );

		$ids = json_decode( $idsInJSON, true );

		if ( empty( $ids ) ) {
			return [];
		}

		return get_posts( [ "include" => $ids, "suppress_filters" => false ] );
	}

	/**
	 * @param WP_Post $post
	 * @param int $relatedPost
	 *
	 * @return array
	 */
	public function removeRelatedPost( $post, $relatedPost ) {
		$idsInJSON = get_post_meta( $post->ID, SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_META_KEY, true );

		$ids = json_decode( $idsInJSON, true );

		if ( empty( $ids ) ) {
			return [];
		}

		$ids = array_filter( $ids, function ( $elem ) use ( $relatedPost ) {
			return intval($elem) !== intval($relatedPost);
		} );

		$this->saveRelatedPosts( $post, $ids );

		if ( empty( $ids ) ) {
			return [];
		}

		return get_posts( [ "include" => $ids, "suppress_filters" => false ] );
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function removeAllRelatedPost( $post ) {
		$ids = [];

		$this->saveRelatedPosts( $post, $ids );

		return [];
	}
}