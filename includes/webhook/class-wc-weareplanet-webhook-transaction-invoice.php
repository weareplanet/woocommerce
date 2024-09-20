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
 * Webhook processor to handle transaction completion state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Webhook_Transaction_Invoice_Strategy
 */
class WC_WeArePlanet_Webhook_Transaction_Invoice extends WC_WeArePlanet_Webhook_Order_Related_Abstract {


	/**
	 * Load entity.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return object|\WeArePlanet\Sdk\Model\TransactionInvoice
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$transaction_invoice_service = new \WeArePlanet\Sdk\Service\TransactionInvoiceService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $transaction_invoice_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Load transaction.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return \WeArePlanet\Sdk\Model\Transaction
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_transaction( $transaction_invoice ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		$transaction_service = new \WeArePlanet\Sdk\Service\TransactionService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $transaction_service->read( $transaction_invoice->getLinkedSpaceId(), $transaction_invoice->getCompletion()->getLineItemVersion()->getTransaction()->getId() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return int|string
	 */
	protected function get_order_id( $transaction_invoice ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction( $transaction_invoice->getLinkedSpaceId(), $transaction_invoice->getCompletion()->getLineItemVersion()->getTransaction()->getId() )->get_order_id();
	}

	/**
	 * Get transaction invoice.
	 *
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return int
	 */
	protected function get_transaction_id( $transaction_invoice ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		return $transaction_invoice->getLinkedTransaction();
	}

	/**
	 * Process
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction_invoice transaction invoice.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction_invoice ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionInvoice $transaction_invoice */ //phpcs:ignore
		switch ( $transaction_invoice->getState() ) {
			case \WeArePlanet\Sdk\Model\TransactionInvoiceState::DERECOGNIZED:
				$order->add_order_note( esc_html__( 'Invoice Not Settled' ) );
				break;
			case \WeArePlanet\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE:
			case \WeArePlanet\Sdk\Model\TransactionInvoiceState::PAID:
				$order->add_order_note( esc_html__( 'Invoice Settled' ) );
				break;
			default:
				// Nothing to do.
				break;
		}
	}
}
