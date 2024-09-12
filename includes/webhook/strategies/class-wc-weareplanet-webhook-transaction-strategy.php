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
 * Class WC_WeArePlanet_Webhook_Transaction_Strategy
 *
 * This class provides the implementation for processing transaction webhooks.
 * It includes methods for handling specific actions that need to be taken when
 * transaction-related webhook notifications are received, such as updating order
 * statuses, recording transaction logs, or triggering further business logic.
 */
class WC_WeArePlanet_Webhook_Transaction_Strategy extends WC_WeArePlanet_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_WeArePlanet_Service_Webhook::WEAREPLANET_TRANSACTION == $webhook_entity_id;
	}

	/**
	 * Process the webhook request.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request object.
	 * @return mixed The result of the processing.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		$order = $this->get_order( $request );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $request );
		}
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, WC_WeArePlanet_Webhook_Request $request ) {
		$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( $request->get_state() != $transaction_info->get_state() ) {
			switch ( $request->get_state() ) {
				case \WeArePlanet\Sdk\Model\TransactionState::CONFIRMED:
				case \WeArePlanet\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $request, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $request, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $request, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::FAILED:
					$this->failed( $request, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $request, $order );
					$this->fulfill( $request, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $request, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $request, $order );
					$this->waiting( $request, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
		}

		WC_WeArePlanet_Service_Transaction::instance()->update_transaction_info( $this->load_entity( $request ), $order );
	}

	/**
	 * Confirm.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function confirm( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_weareplanet_confirmed', true ) && ! $order->get_meta( '_weareplanet_authorized', true ) ) {
			do_action( 'wc_weareplanet_confirmed', $this->load_entity( $request ), $order );
			$order->add_meta_data( '_weareplanet_confirmed', 'true', true );
			$status = apply_filters( 'wc_weareplanet_confirmed_status', 'wearep-redirected', $order );
			$order->update_status( $status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param \WC_Order $order order.
	 */
	protected function authorize( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_weareplanet_authorized', true ) ) {
			do_action( 'wc_weareplanet_authorized', $this->load_entity( $request ), $order );
			$status = apply_filters( 'wc_weareplanet_authorized_status', 'on-hold', $order );
			$order->add_meta_data( '_weareplanet_authorized', 'true', true );
			$order->update_status( $status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		if ( ! $order->get_meta( '_weareplanet_manual_check', true ) ) {
			do_action( 'wc_weareplanet_completed', $this->load_entity( $request ), $order );
			$status = apply_filters( 'wc_weareplanet_completed_status', 'processing', $order );
			$order->update_status( $status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function decline( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_weareplanet_declined', $this->load_entity( $request ), $order );
		$status = apply_filters( 'wc_weareplanet_decline_status', 'cancelled', $order );
		$order->update_status( $status );
		WC_WeArePlanet_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function failed( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_weareplanet_failed', $this->load_entity( $request ), $order );
		if ( $order->get_status( 'edit' ) == 'pending' || $order->get_status( 'edit' ) == 'wearep-redirected' ) {
			$status = apply_filters( 'wc_weareplanet_failed_status', 'failed', $order );
			$order->update_status( $status );
			WC_WeArePlanet_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function fulfill( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		do_action( 'wc_weareplanet_fulfill', $this->load_entity( $request ), $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $request->get_entity_id() );
	}

	/**
	 * Voided.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function voided( WC_WeArePlanet_Webhook_Request $request, WC_Order $order ) {
		$status = apply_filters( 'wc_weareplanet_voided_status', 'cancelled', $order );
		$order->update_status( $status );
		do_action( 'wc_weareplanet_voided', $this->load_entity( $request ), $order );
	}
}
