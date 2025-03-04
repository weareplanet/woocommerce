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
 * Class WC_WeArePlanet_Webhook_Delivery_Indication_Strategy
 *
 * Handles strategy for processing delivery indication-related webhook requests.
 * This class extends the base webhook strategy to manage webhook requests specifically
 * dealing with delivery indications. It focuses on updating order states based on the delivery indication details
 * retrieved from the webhook data.
 */
class WC_WeArePlanet_Webhook_Delivery_Indication_Strategy extends WC_WeArePlanet_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_WeArePlanet_Service_Webhook::WEAREPLANET_DELIVERY_INDICATION == $webhook_entity_id;
	}

	/**
	 * Load the entity
	 *
	 * @inheritDoc
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$transaction_invoice_service = new \WeArePlanet\Sdk\Service\DeliveryIndicationService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $transaction_invoice_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get the order ID.
	 *
	 * @inheritDoc
	 * @param object $object The webhook request.
	 */
	protected function get_order_id( $object ) {
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $object */
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction(
			$object->getTransaction()->getLinkedSpaceId(),
			$object->getTransaction()->getId()
		)->get_order_id();
	}

	/**
	 * Processes the incoming webhook request pertaining to delivery indications.
	 *
	 * This method retrieves the delivery indication details from the API and updates the associated
	 * WooCommerce order based on the indication state.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		/* @var \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication */
		$delivery_indication = $this->load_entity( $request );
		$order = $this->get_order( $delivery_indication );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $delivery_indication, $request );
		}
	}

	/**
	 * Additional processing on the order based on the state of the delivery indication.
	 *
	 * @param WC_Order $order The WooCommerce order linked to the delivery indication.
	 * @param \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication The delivery indication object.
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, \WeArePlanet\Sdk\Model\DeliveryIndication $delivery_indication, WC_WeArePlanet_Webhook_Request $request ) {
		switch ( $request->get_state() ) {
			case \WeArePlanet\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review( $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Review and potentially update the order status based on manual review requirements.
	 *
	 * @param WC_Order $order The associated WooCommerce order.
	 * @return void
	 */
	protected function review( WC_Order $order ) {
		$order->add_meta_data( '_weareplanet_manual_check', true );
		$status = apply_filters( 'wc_weareplanet_manual_task_status', 'wearep-manual', $order );
		$status = apply_filters( 'weareplanet_order_update_status', $order, $status, __( 'A manual decision about whether to accept the payment is required.', 'woo-weareplanet' ) );
		$order->update_status( $status, __( 'A manual decision about whether to accept the payment is required.', 'woo-weareplanet' ) );
	}
}
