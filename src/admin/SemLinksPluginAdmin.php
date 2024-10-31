<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/admin
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPluginAdmin {

	/**
	 * The ID of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SemLinksPluginLoader $loader Maintains and registers all hooks for the plugin.
	 */
	private $loader;

	/**
	 * The MoreLikeService
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SemLinksPluginMoreLikeThisService $moreLikeThisService
	 */
	private $moreLikeThisService;

	/**
	 * The RelatedPostManager
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      SemLinksPluginRelatedPostManager $relatedPostsManager
	 */
	private $relatedPostManager;

	/**
	 * @param string $plugin_name
	 * @param string $version
	 * @param SemLinksPluginLoader $loader
	 * @param SemLinksPluginMoreLikeThisService $moreLikeThisService
	 * @param SemLinksPluginRelatedPostManager $relatedPostManager
	 */
	public function __construct(
		$plugin_name,
		$version,
		$loader,
		$moreLikeThisService,
		$relatedPostManager
	) {
		$this->plugin_name         = $plugin_name;
		$this->version             = $version;
		$this->loader              = $loader;
		$this->moreLikeThisService = $moreLikeThisService;
		$this->relatedPostManager  = $relatedPostManager;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/semlinks-plugin-admin.css',
			array(),
			$this->version,
			'all'
		);

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$scriptDependencies = require_once __DIR__ . '/../../build/index.asset.php';
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . '../../build/index.js',
			$scriptDependencies['dependencies'],
			$this->version,
			true
		);

		// We enable our own i18n tokens in the scripts
		wp_set_script_translations(
			$this->plugin_name,
			'semlinks',
			realpath( plugin_dir_path( __FILE__ ) . '/../languages/translated' )
		);

		// Exposes WPURLS object to the scripts
		wp_localize_script( $this->plugin_name, 'WPURLS', [ 'adminUrl' => get_admin_url() ] );
	}

	/**
	 * Registers the plugin menu entry
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function register_menu() {
		add_management_page(
			'SemLinks',
			'SemLinks',
			'manage_options',
			$this->plugin_name,
			[ $this, 'display_admin_page' ]
		);
	}

	/**
	 * Registers the plugin action links
	 *
	 * @param $links
	 * @param $plugin_file
	 *
	 * @return string[]
	 * @since 1.2.0
	 */
	public function customize_admin_action_links( $links, $plugin_file ) {
		if ( plugin_basename( SEMLINKS_PLUGIN_FILE ) !== $plugin_file ) {
			return $links;
		}

		$configuration_link = add_query_arg( [ 'page' => $this->plugin_name ], admin_url( "tools.php" ) );
		$settings_link      = array(
			'<a href="' . esc_url( $configuration_link ) . '">' . esc_html__( 'Settings', 'semlinks' ) . '</a>',
		);

		return array_merge( $settings_link, $links );
	}

	/**
	 * Will render the admin configuration page
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function register_admin_settings() {
		$semLinkPluginSettings = new SemLinksPluginAdminSettings( $this->plugin_name );
		$semLinkPluginSettings->register_admin_settings();
	}

	/**
	 * Will render custom meta boxes
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function register_meta_boxes() {
		if (
			! SemLinksPluginUtils::isApiKeyValid()
			|| ! SemLinksPluginUtils::isFeatureAllowed( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_RELATED_POSTS_FEATURE_NAME )
			|| ! current_user_can( SemLinksPluginCapabilities::SEMLINKS_PLUGIN_CAPABILITY_LOOKALIKE_DISCOVER )
		) {
			return;
		}
		add_meta_box(
			SemLinksPluginConstants::SEMLINKS_PLUGIN_RELATED_POSTS_META_BOX_ID,
			__( "Related posts", "semlinks" ),
			[ $this, 'display_related_posts_meta_box_content' ],
			"post"
		);
	}

	/**
	 * @param $old_value
	 * @param $value
	 * @param $option
	 *
	 * @return array|mixed
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function on_option_update( $value, $old_value, $option ) {
		// No new updates applied to the semlinks-plugin option
		if ( ! is_array( $value ) ) {
			return $old_value;
		}

		// If there's a sync date already saved, we don't want to overwrite it
		if ( isset( $old_value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] ) ) {
			$value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ] = $old_value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_INITIAL_SYNC_DATE ];
		}

		$newApiKey = $value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ];
		$oldApiKey = $old_value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ];
		if ( $newApiKey === $oldApiKey ) {
			$value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_VALID_KEY ] = $old_value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_VALID_KEY ];

			return $value;
		}

		// Checking all the sites for the same API key
		if ( is_multisite() ) {
			$sites      = get_sites();
			$allApiKeys = [];
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
				if ( is_array( $options ) && array_key_exists( SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY,
				                                               $options ) ) {
					$allApiKeys[ $site->blog_id ] = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ];
				}
				restore_current_blog();
			}

			if ( in_array( $newApiKey, $allApiKeys ) ) {
				$value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_VALID_KEY ] = false;

				return $value;
			}
		}

		$allowed_features = [];
		try {
			$csp_service = new SemLinksPluginCSPService();
			// get_meta will fail if the API key is invalid
			$meta = $csp_service->get_meta( $newApiKey );
			// Else it will contain the allowed features (among other infos)
			$allowed_features = $meta->get_context()->get_modules();
			$is_api_key_valid = $meta->isValid();
		} catch ( Exception $e ) {
			$is_api_key_valid = false;
			error_log( $e->getMessage() );
		}

		update_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_ALLOWED_FEATURES_KEY, $allowed_features );

		$value[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_VALID_KEY ] = $is_api_key_valid;

		return $value;
	}

	/**
	 * Loads all the endpoints (REST and Ajax)
	 *
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function load_ajax_endpoints() {
		$settingsAPI = new SemLinksPluginSettingsAPI( $this->plugin_name );
		$settingsAPI->run();

		$capabilitiesAPI = new SemLinksPluginCapabilitiesAPI( $this->plugin_name );
		$capabilitiesAPI->run();

		$syncAPI = new SemLinksPluginInitialSyncAPI( $this->plugin_name );
		$syncAPI->run();

		$syncAPI = new SemLinksPluginInitialSyncAPI( $this->plugin_name );
		$syncAPI->run();

		if ( ! SemLinksPluginUtils::isApiKeyValid() ) {
			return;
		}

		if ( SemLinksPluginUtils::isFeatureAllowed( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_RELATED_POSTS_FEATURE_NAME ) ) {

			$relatedPostsHook = new SemLinksPluginRelatedPostsHooks( $this->moreLikeThisService,
			                                                         $this->loader
			);

			$relatedPostsHook->run();

			$relatedPostsApi = new SemLinksPluginRelatedPostsAPI( $this->plugin_name, $this->loader, $this );
			$relatedPostsApi->run();
		}

		if ( SemLinksPluginUtils::isFeatureAllowed( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_NER_FEATURE_NAME ) ) {
			$nerApi = new SemLinksPluginNerAPI( $this->plugin_name );
			$nerApi->run();
		}
	}

	/**
	 * Will render the admin configuration page
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function display_admin_page() {
		include 'partials/semlinks-plugin-admin-display.php';
	}

	/**
	 * Will render the related-posts custom meta box
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function display_related_posts_meta_box_content( $post ) {
		include 'partials/meta-box/related-posts/meta-box-content.php';
	}
}
