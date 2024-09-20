<?php
/**
 *
 * WC_WeArePlanet_Provider_Payment_Method Class
 *
 * WeArePlanet
 * This plugin will add support for all WeArePlanet payments methods and connect the WeArePlanet servers to your WooCommerce webshop (https://www.weareplanet.com/).
 *
 * @category Class
 * @package  WeArePlanet
 * @author   Planet Merchant Services Ltd (https://www.weareplanet.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
/**
 * Provider of payment method information from the gateway.
 */
class WC_WeArePlanet_Provider_Payment_Method extends WC_WeArePlanet_Provider_Abstract {

	/**
	 * Construct.
	 */
	protected function __construct() {
		parent::__construct( 'wc_weareplanet_payment_methods' );
	}

	/**
	 * Returns the payment method by the given id.
	 *
	 * @param int $id id.
	 * @return \WeArePlanet\Sdk\Model\PaymentMethod
	 */
	public function find( $id ) {
		return parent::find( $id );
	}

	/**
	 * Returns a list of payment methods.
	 *
	 * @return \WeArePlanet\Sdk\Model\PaymentMethod[]
	 */
	public function get_all() {
		return parent::get_all();
	}

	/**
	 * Fetch data.
	 *
	 * @return array|\WeArePlanet\Sdk\Model\PaymentMethod[]
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function fetch_data() {
		$method_service = new \WeArePlanet\Sdk\Service\PaymentMethodService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $method_service->all();
	}

	/**
	 * Get id.
	 *
	 * @param mixed $entry entry.
	 * @return int|string
	 */
	protected function get_id( $entry ) {
		/* @var \WeArePlanet\Sdk\Model\PaymentMethod $entry */
		return $entry->getId();
	}
}
