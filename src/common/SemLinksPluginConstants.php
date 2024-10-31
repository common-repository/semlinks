<?php

class SemLinksPluginConstants {
	// Options and settings
	const SEMLINKS_PLUGIN_OPTIONS_KEY          = "semlinks-plugin_options";
	const SEMLINKS_PLUGIN_SETTINGS_API_KEY_KEY = "semlinks-plugin_setting_api_key";

	// SYNC
	const SEMLINKS_PLUGIN_SYNC_OPTIONS_KEY                                 = "semlinks-plugin_sync_options";
	const SEMLINKS_PLUGIN_RELATED_POSTS_SYNC_COUNT                         = "semlinks_plugin_posts_already_sync_count";
	const SEMLINKS_PLUGIN_TAGS_SYNC_COUNT                                  = "semlinks_plugin_tags_already_sync_count";
	const SEMLINKS_PLUGIN_INITIAL_SYNC_DATE                                = "semlinks_plugin_initial_sync_date";
	const SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_SHORT_KEY = "related_posts_sync_starting_date";
	const SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_KEY       = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_SYNC_START_DATE_SHORT_KEY;


	// API
	const SEMLINKS_PLUGIN_API_RELATED_POSTS_FEATURE_NAME = "LOOKALIKE";
	const SEMLINKS_PLUGIN_API_NER_FEATURE_NAME           = "NER";
	const SEMLINKS_PLUGIN_API_ALLOWED_FEATURES_KEY       = "semlinks-plugin_allowed_features";
	const SEMLINKS_PLUGIN_SETTINGS_API_KEY_VALID_KEY     = "is_api_key_valid";


	// Related posts
	const SEMLINKS_PLUGIN_RELATED_POSTS_META_BOX_ID                             = "semlinks-plugin-related-posts-meta-box";
	const SEMLINKS_PLUGIN_RELATED_POSTS_META_KEY                                = "semlinks-plugin-related-posts";
	const SEMLINKS_PLUGIN_RELATED_POSTS_OPTIONS_KEY                             = "semlinks-plugin_related_posts_options";
	const SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_NB_RESULTS_SHORT_KEY           = "related_posts_nb_results";
	const SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_NB_RESULTS_KEY                 = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_NB_RESULTS_SHORT_KEY;
	const SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_ACTIVATE_RETRO_LINKS_SHORT_KEY = "related_posts_activate_retro_links";
	const SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_ACTIVATE_RETRO_LINKS_KEY       = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_RELATED_POSTS_ACTIVATE_RETRO_LINKS_SHORT_KEY;

	// NER in posts
	const SEMLINKS_PLUGIN_NER_POST_META_KEY                                    = "named_entities";
	const SEMLINKS_PLUGIN_NER_OPTIONS_KEY                                      = "semlinks-plugin_ner_options";
	const SEMLINKS_PLUGIN_SETTINGS_NER_THRESHOLD_SHORT_KEY                     = "ner_threshold";
	const SEMLINKS_PLUGIN_SETTINGS_NER_THRESHOLD_KEY                           = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_NER_THRESHOLD_SHORT_KEY;
	const SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY                    = "ner_dictionary";
	const SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_KEY                          = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_NER_DICTIONARY_SHORT_KEY;
	const SEMLINKS_PLUGIN_SETTINGS_NER_URL_FORMAT_SHORT_KEY                    = "ner_url_format";
	const SEMLINKS_PLUGIN_SETTINGS_NER_URL_FORMAT_KEY                          = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_NER_URL_FORMAT_SHORT_KEY;
	const SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_AS_TAG_SHORT_KEY               = "ner_only_add_as_tag";
	const SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_AS_TAG_KEY                     = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_AS_TAG_SHORT_KEY;
	const SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_THE_FIRST_OCCURRENCE_SHORT_KEY = "ner_only_add_the_first_occurrence";
	const SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_THE_FIRST_OCCURRENCE_KEY       = "semlinks-plugin_setting_" . self::SEMLINKS_PLUGIN_SETTINGS_NER_ONLY_ADD_THE_FIRST_OCCURRENCE_SHORT_KEY;
}