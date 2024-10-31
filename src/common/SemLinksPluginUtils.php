<?php

class SemLinksPluginUtils {

	/**
	 * @return boolean
	 * @throws Exception
	 * @since 2.0.0
	 * @access public
	 */
	public static function isApiKeyValid() {
		$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		if ( ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ] ) ) {
			return false;
		}

		$apiKey = $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ];
		if ( empty( $apiKey ) ) {
			return false;
		}

		$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		if ( ! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY ] ) ) {
			return false;
		}

		return boolval( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_API_KEY_VALID_KEY ] );
	}

	/**
	 * Returns true if the NER feature is correctly configured
	 *
	 * @return bool
	 */
	public static function isNerConfigured() {
		$options = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_OPTIONS_KEY );
		if (
			! isset( $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY ] )
			|| ! $options[ SemLinksPluginConstants::SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY ]
		) {
			return false;
		}

		return true;

	}

	/**
	 * @param $featureName
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function isFeatureAllowed( $featureName ) {
		$allowedFeatures = get_option( SemLinksPluginConstants::SEMLINKS_PLUGIN_API_ALLOWED_FEATURES_KEY );

		if ( ! is_array( $allowedFeatures ) ) {
			return false;
		}

		return in_array( $featureName, $allowedFeatures );
	}
}