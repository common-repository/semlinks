<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly // Silence is golden

/**
 * Displays the related articles and allows to delete them if wanted
 *
 *
 * @link       https://github.com/tschaeller
 * @since      1.0.0
 *
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/admin/partials/meta-box
 */

$relatedPostManager = new SemLinksPluginRelatedPostManager();
$relatedPosts       = $relatedPostManager->getRelatedPostsObjects( $post );
if (
	! SemLinksPluginUtils::isApiKeyValid()
	|| ! SemLinksPluginUtils::isFeatureAllowed( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_RELATED_POSTS_FEATURE_NAME )
	|| ! current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER )
) {
	return;
}
?>

<div class="wrap">
	<?php if ( ! empty( $relatedPosts ) ) { ?>
        <button
                class="button button-primary semlinks-plugin-meta-box-action-button semlinks-plugin-related-posts-meta-box-action-button semlinks-plugin-button-alert"
                data-action="semlinks_remove_all_related_posts"
                data-postId="<?php echo esc_attr( $post->ID ) ?>"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'semlinks-plugin_remove_all_related_posts' ) ) ?>"
                data-ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ) ?>"
        >
			<?php esc_attr_e( 'Remove all related posts', 'semlinks' ) ?>
        </button>
	<?php } ?>

    <table class='wp-list-table widefat fixed pages sortable semlinks-plugin-related-posts-table'>
        <thead>
        <tr>
            <th scope='col' id='relatedPost'><?php esc_attr_e( 'Related post', 'semlinks' ) ?></th>
            <th scope='col' id='select'><?php esc_attr_e( 'Select', 'semlinks' ) ?></th>
        <tr>
        </thead>
        <tbody>
		<?php
		if ( empty( $relatedPosts ) ) {
			echo "<tr>";
			echo "<td colspan='2'>";
			esc_attr_e( 'No related post found', 'semlinks' );
			echo "</td>";
			echo "</tr>";
		}
		/** @var WP_Post $related_post */
		foreach ( $relatedPosts as $related_post ) {
			$postAdminUrl = get_admin_url() . "post.php?post={$related_post->ID}&amp;action=edit";
			echo "<tr>";

			echo "<td>";
			echo "<strong><a href='" . esc_url( $postAdminUrl ) . "' target='_blank'>" . esc_html( $related_post->post_title ) . "</a></strong>";
			echo "</td>";

			echo "<td>";
			echo "<span
                    id='semlinks-plugin-remove-related-post-button-" . esc_attr( $related_post->ID ) . "'
                    class='dashicons dashicons-dismiss semlinks-plugin-remove-related-post-button semlinks-plugin-tooltip-parent'
                    data-action='semlinks_remove_related_post'
                    data-postId='" . esc_attr( $post->ID ) . "'
                    data-relatedPost='" . esc_attr( $related_post->ID ) . "'
                    data-nonce='" . esc_attr( wp_create_nonce( 'semlinks-plugin_remove_related_post' ) ) . "'
                    data-ajaxurl='" . esc_url( admin_url( 'admin-ajax.php' ) ) . "'
                    >
                        <span class='semlinks-plugin-tooltip-text'>" . esc_attr__( 'Remove from related posts list',
			                                                                  'semlinks' ) . "</span>
                    </span>
                ";
			echo "</td>";

			echo "</tr>";
		}
		?>
        </tbody>
    </table>
</div>