<?php

class SemLinksPluginSettingsAPI {
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
		add_action( 'rest_api_init', [ $this, 'register_ner_actions' ] );
	}

	public function register_ner_actions() {
		register_rest_route(
			$this->namespace,
			'/settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);

		register_rest_route(
			$this->namespace,
			'/settings/taxonomies',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings_taxonomies' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);

		register_rest_route(
			$this->namespace,
			'/settings/dictionaries',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings_dictionaries' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}

	/**
	 * Returns the semlinks-plugin settings
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @since   1.0.0
	 */
	public function get_settings() {
		$options = get_option( $this->plugin_name . '_options' );

		if ($options === false) {
			return rest_ensure_response([]);
		}

		// We don't want to expose the API key
		$filteredOptions = array_filter( $options, function ( $key ) {
			return $key !== SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY;
		},                               ARRAY_FILTER_USE_KEY );

		$allowedFeatures                     = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_ALLOWED_FEATURES_KEY );
		$filteredOptions["allowed_features"] = $allowedFeatures ?: [];

		$displayOptions = array_map( function ( $value ) {
			return is_bool( $value ) ? var_export( $value, true ) : $value;
		}, $filteredOptions );

		return rest_ensure_response( $displayOptions );
	}

	/**
	 * Returns the available dictionaries to select in settings
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function get_settings_dictionaries() {
		$nerService   = new SemLinksPluginNerService();
		$dictionaries = $nerService->get_csp_dictionary_list();

		return rest_ensure_response( $dictionaries );
	}
}