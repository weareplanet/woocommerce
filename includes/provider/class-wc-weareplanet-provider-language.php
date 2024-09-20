<?php
/**
 * Plugin Name: WeArePlanet
 * Author: Planet Merchant Services Ltd
 * Text Domain: weareplanet
 * Domain Path: /languages/
 *
 * WeArePlanet
 * This plugin will add support for all WeArePlanet payments methods and connect the WeArePlanet servers to your WooCommerce webshop (https://www.weareplanet.com/).
 *
 * @category Class
 * @package  WeArePlanet
 * @author   Planet Merchant Services Ltd (https://www.weareplanet.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provider of language information from the gateway.
 */
class WC_WeArePlanet_Provider_Language extends WC_WeArePlanet_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_weareplanet_languages' );
	}

	/**
	 * Returns the language by the given code.
	 *
	 * @param string $code code.
	 * @return \WeArePlanet\Sdk\Model\RestLanguage
	 */
	public function find( $code ) { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::find( $code );
	}

	/**
	 * Returns the primary language in the given group.
	 *
	 * @param string $code code.
	 * @return \WeArePlanet\Sdk\Model\RestLanguage
	 */
	public function find_primary( $code ) {
		$code = substr( $code, 0, 2 );
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $code && $language->getPrimaryOfGroup() ) {
				return $language;
			}
		}

		return false;
	}

	/**
	 * Find by iso code.
	 *
	 * @param mixed $iso iso.
	 * @return false|\WeArePlanet\Sdk\Model\RestLanguage
	 */
	public function find_by_iso_code( $iso ) {
		foreach ( $this->get_all() as $language ) {
			if ( $language->getIso2Code() == $iso || $language->getIso3Code() == $iso ) {
				return $language;
			}
		}
		return false;
	}

	/**
	 * Returns a list of language.
	 *
	 * @return \WeArePlanet\Sdk\Model\RestLanguage[]
	 */
	public function get_all() { //phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\WeArePlanet\Sdk\Model\RestLanguage[]
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$language_service = new \WeArePlanet\Sdk\Service\LanguageService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $language_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return string
	 */
	protected function get_id( $entry ) {
		/* @var \WeArePlanet\Sdk\Model\RestLanguage $entry */ //phpcs:ignore
		return $entry->getIetfCode();
	}
}
