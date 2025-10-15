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
 * Webhook processor to handle transaction void state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Webhook_Transaction_Void_Strategy
 */
class WC_WeArePlanet_Webhook_Transaction_Void extends WC_WeArePlanet_Webhook_Order_Related_Abstract {

	/**
	 * Canonical processor.
	 *
	 * @var WC_WeArePlanet_Webhook_Transaction_Void_Strategy
	 */
	private $strategy;

	/**
	 * Construct to initialize canonical processor.
	 *
	 */
	public function __construct() {
		$this->strategy = new WC_WeArePlanet_Webhook_Transaction_Void_Strategy();
	}

	/**
	 * Load entity.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return object|\WeArePlanet\Sdk\Model\TransactionVoid
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_WeArePlanet_Webhook_Transaction_Void_Strategy::load_entity'
        );
		return $this->strategy->load_entity( $request );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $void_transaction void transaction.
	 * @return int|string
	 */
	protected function get_order_id( $void_transaction ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_WeArePlanet_Webhook_Transaction_Void_Strategy::get_order_id'
        );
		return $this->strategy->get_order_id( $void_transaction );
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $void_transaction void transaction.
	 * @return int
	 */
	protected function get_transaction_id( $void_transaction ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionVoid $void_transaction */ //phpcs:ignore
		return $void_transaction->getLinkedTransaction();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $void_transaction void transaction.
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $void_transaction, $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_WeArePlanet_Webhook_Transaction_Void_Strategy::process_order_related_inner'
        );
        $this->strategy->bridge_process_order_related_inner( $order, $void_transaction, $request );
	}
}
