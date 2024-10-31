<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    SemLinks_Plugin
 * @subpackage SemLinks_Plugin/includes
 * @author     Thibault Schaeller <thibault.schaeller-ext@contentside.com>
 */
class SemLinksPlugin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      SemLinksPluginLoader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SEMLINKS_PLUGIN_VERSION' ) ) {
			$this->version = SEMLINKS_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'semlinks-plugin';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->expose_globals();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - SemLinks_Plugin_Loader. Orchestrates the hooks of the plugin.
	 * - SemLinks_Plugin_i18n. Defines internationalization functionality.
	 * - SemLinks_Plugin_Admin. Defines all hooks for the admin area.
	 * - SemLinks_Plugin_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/SemLinksPluginLoader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/SemLinksPluginI18n.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/SemLinksPluginUtils.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/SemLinksPluginAdminSettings.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/SemLinksPluginAdmin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/SemLinksPluginPublic.php';

		/**
		 * We require the action-scheduler library to synchronize the posts with the CSP content repository
		 * in the background
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '../vendor/woocommerce/action-scheduler/action-scheduler.php';

		/**
		 * Requires the data model
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/model/SemLinksPluginNamedEntity.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/model/SemLinksPluginNerResponse.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/model/SemLinksPluginContextMetaEntity.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/model/SemLinksPluginKeyMetaEntity.php';

		/**
		 * Requires the services
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/SemLinksPluginSettingsDisplayManager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/CSP/SemLinksPluginCSPService.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/CSP/SemLinksPluginMoreLikeThisService.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/CSP/SemLinksPluginNerService.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/SemLinksPluginCapabilities.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/SemLinksPluginConstants.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/SemLinksPluginRelatedPostManager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/hooks/SemLinksPluginRelatedPostsHooks.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api/SemLinksPluginRelatedPostsAPI.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api/SemLinksPluginInitialSyncAPI.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api/SemLinksPluginNerAPI.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api/SemLinksPluginSettingsAPI.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/api/SemLinksPluginCapabilitiesAPI.php';

		$this->loader = new SemLinksPluginLoader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the SemLinks_Plugin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new SemLinksPluginI18N( $this->plugin_name );

		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$moreLikeThisService = new SemLinksPluginMoreLikeThisService();
		$relatedPostsManager = new SemLinksPluginRelatedPostManager();
		$nerService          = new SemLinksPluginNerService();

		$plugin_admin = new SemLinksPluginAdmin( $this->get_plugin_name(),
		                                         $this->get_version(),
		                                         $this->loader,
		                                         $moreLikeThisService,
		                                         $relatedPostsManager
		);

		$plugin_admin->load_ajax_endpoints();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );


		// We register the pre_update/update/add option settings hooks
		$this->loader->add_action(
			'pre_update_option_' . SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY,
			$plugin_admin,
			'on_option_update',
			10,
			3
		);

		// Register the menu entry
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_menu' );

		// Register the action links for the plugin page
		$this->loader->add_action( 'plugin_action_links',
		                           $plugin_admin,
		                           'customize_admin_action_links',
		                           10,
		                           2 );

		// Will register the setting fields for the plugin
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_admin_settings' );

		// Adds the custom meta box to display the look alike results
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'register_meta_boxes');

		// Async actions
		$this->loader->add_action( 'semlinks_plugin_synchronize_all_posts', $moreLikeThisService, 'save_all_posts' );
		$this->loader->add_action( 'semlinks_plugin_synchronize_posts', $moreLikeThisService, 'save_posts' );

		$this->loader->add_action( 'semlinks_plugin_synchronize_all_tags', $nerService, 'save_all_tags' );
		$this->loader->add_action( 'semlinks_plugin_synchronize_tags', $nerService, 'save_tags' );
	}

	/**
	 * Register all the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new SemLinksPluginPublic( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Registers shortcodes
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
	}

	/**
	 * Exposes global functions such as get_csp_related_posts
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function expose_globals() {
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'global/semlinks-plugin-globals.php';
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    SemLinksPluginLoader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}
}
