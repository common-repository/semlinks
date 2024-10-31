<?php

namespace model;

class SemLinksPluginContextMetaEntity {

	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var string[]
	 * @since 2.0.0
	 * @access private
	 */
	private $modules;

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @param string $label
	 */
	public function set_label( $label ) {
		$this->label = $label;
	}

	/**
	 * @return string[]
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * @param string[] $modules
	 */
	public function set_modules( $modules ) {
		$this->modules = $modules;
	}

	/**
	 * @param $data
	 *
	 * @return SemLinksPluginContextMetaEntity
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function fromArray( $data ) {
		$contextMeta = new SemLinksPluginContextMetaEntity();
		if ( isset( $data['label'] ) ) {
			$contextMeta->set_label( $data['label'] );
		}
		if ( isset( $data['modules'] ) ) {
			// Must match the constants defined in SemLinksPluginConstants
			$contextMeta->set_modules( $data['modules'] );
		}

		return $contextMeta;
	}
}