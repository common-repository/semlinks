<?php

namespace model;

class SemLinksPluginKeyMetaEntity {
	/**
	 * @var SemLinksPluginContextMetaEntity
	 * @since 2.0.0
	 * @access private
	 */
	private $context;

	/**
	 * @var boolean
	 * @since 2.0.0
	 * @access private
	 */
	private $isValid;

	/**
	 * @return SemLinksPluginContextMetaEntity
	 */
	public function get_context() {
		return $this->context;
	}

	/**
	 * @param SemLinksPluginContextMetaEntity $context
	 */
	public function set_context( $context ) {
		$this->context = $context;
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * @param bool $isValid
	 */
	public function set_is_valid( $isValid ) {
		$this->isValid = $isValid;
	}

	/**
	 * @param $data
	 *
	 * @return SemLinksPluginKeyMetaEntity
	 * @since 2.0.0
	 * @access public
	 * @static
	 */
	public static function fromArray( $data ) {
		$keyMeta = new SemLinksPluginKeyMetaEntity();
		if ( isset( $data['context'] ) ) {
			$keyMeta->set_context( SemLinksPluginContextMetaEntity::fromArray( $data['context'] ) );
		}
		if ( isset( $data['isValid'] ) ) {
			$keyMeta->set_is_valid( boolval( $data['isValid'] ) );
		}

		return $keyMeta;
	}
}