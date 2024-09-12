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
 * Class WC_WeArePlanet_Webhook_Refund_Strategy
 *
 * Handles strategy for processing transaction invoice-related webhook requests.
 * This class extends the base webhook strategy to manage webhook requests specifically
 * dealing with transaction invoices. It focuses on updating order states based on the invoice details
 * retrieved from the webhook data.
 */
class WC_WeArePlanet_Webhook_Transaction_Invoice_Strategy extends WC_WeArePlanet_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_WeArePlanet_Service_Webhook::WEAREPLANET_TRANSACTION_INVOICE == $webhook_entity_id;
	}

	/**
	 * Load the entity.
	 *
	 * @inheritDoc
	 * @param WC_WeArePlanet_Webhook_Request $request webhook request.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$transaction_invoice_service = new \WeArePlanet\Sdk\Service\TransactionInvoiceService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $transaction_invoice_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get the order ID from the object.
	 *
	 * @inheritDoc
	 * @param object $object transaction entity object.
	 */
	protected function get_order_id( $object ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionInvoice $object */
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction(
			$object->getLinkedSpaceId(),
			$object->getCompletion()->getLineItemVersion()->getTransaction()->getId()
		)->get_order_id();
	}

	/**
	 * Processes the incoming webhook request pertaining to transaction invoices.
	 *
	 * This method retrieves the transaction invoice details from the API and updates the associated
	 * WooCommerce order based on the state of the invoice.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice */
		$transaction_invoice = $this->load_entity( $request );
		$order = $this->get_order( $transaction_invoice );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $transaction_invoice, $request );
		}
	}

	/**
	 * Additional processing on the order based on the state of the transaction invoice.
	 *
	 * @param WC_Order $order The WooCommerce order linked to the invoice.
	 * @param \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice The transaction invoice object.
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request object.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice, WC_WeArePlanet_Webhook_Request $request ) {
		switch ( $request->get_state() ) {
			case \WeArePlanet\Sdk\Model\TransactionInvoiceState::DERECOGNIZED:
				$order->add_order_note( __( 'Invoice Not Settled' ) );
				break;
			case \WeArePlanet\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE:
			case \WeArePlanet\Sdk\Model\TransactionInvoiceState::PAID:
				$order->add_order_note( __( 'Invoice Settled' ) );
				break;
			default:
				// Nothing to do.
				break;
		}
	}
}
