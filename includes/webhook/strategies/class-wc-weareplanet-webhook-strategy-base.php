<?php
/**
 * WeArePlanet WooCommerce
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
 * Abstract class WC_WeArePlanet_Webhook_Strategy_Base
 *
 * Serves as a base class for all webhook strategy implementations. It provides common methods needed to process webhook requests,
 * such as loading entity data from the API, retrieving order details, and more.
 */
abstract class WC_WeArePlanet_Webhook_Strategy_Base implements WC_WeArePlanet_Webhook_Strategy_Interface {

	/**
	 * Loads the relevant entity from the API based on the webhook request.
	 *
	 * This method utilizes the TransactionService to fetch entity details (e.g., transaction data)
	 * based on the space and entity ID provided in the webhook request.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return object|\WeArePlanet\Sdk\Model\Transaction
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$transaction_service = new \WeArePlanet\Sdk\Service\TransactionService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $transaction_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get the WooCommerce order associated with the webhook request.
	 *
	 * This method uses the Order Factory to fetch the order based on the ID retrieved from the transaction linked to the webhook request.
	 *
	 * @param WC_WeArePlanet_Webhook_Request|mixed $object The webhook request or transaction that containing data needed to identify the order.
	 * @return \WC_Order The WooCommerce order object associated with the request.
	 */
	protected function get_order( $object ) {
		return WC_Order_Factory::get_order( $this->get_order_id( $object ) );
	}

	/**
	 * Extracts the order ID from a transaction.
	 *
	 * This method fetches the order ID by using the transaction information available in the webhook request.
	 * It is typically used to link the transaction data retrieved via API to a specific WooCommerce order.
	 *
	 * @param WC_WeArePlanet_Webhook_Request|mixed $object.
	 * @return int|string
	 */
	protected function get_order_id( $object ) {
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction( $object->get_space_id(), $object->get_entity_id() )->get_order_id();
	}
}