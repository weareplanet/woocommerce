<?php
/**
 *
 * WC_WeArePlanet_Webhook_Delivery_Indication Class
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
 * Webhook processor to handle delivery indication state transitions.
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Webhook_Delivery_Indication_Strategy
 */
class WC_WeArePlanet_Webhook_Delivery_Indication extends WC_WeArePlanet_Webhook_Order_Related_Abstract {


	/**
	 * Load entity.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return object|\WeArePlanet\Sdk\Model\DeliveryIndication DeliveryIndication.
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$delivery_indication_service = new \WeArePlanet\Sdk\Service\DeliveryIndicationService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $delivery_indication_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int|string
	 */
	protected function get_order_id( $delivery_indication ) {
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication */
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction( $delivery_indication->getTransaction()->getLinkedSpaceId(), $delivery_indication->getTransaction()->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $delivery_indication delivery indication.
	 * @return int
	 */
	protected function get_transaction_id( $delivery_indication ) {
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication */
		return $delivery_indication->getLinkedTransaction();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed    $delivery_indication delivery indication.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $delivery_indication ) {
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication */
		switch ( $delivery_indication->getState() ) {
			case \WeArePlanet\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review( $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Review.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function review( WC_Order $order ) {
		$status = apply_filters( 'wc_weareplanet_manual_task_status', 'wearep-manual', $order );
		$order->add_meta_data( '_weareplanet_manual_check', true );
		$order->update_status( $status, __( 'A manual decision about whether to accept the payment is required.', 'woo-weareplanet' ) );
	}
}
