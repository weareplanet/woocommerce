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
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction The webhook request object.
	 * @return mixed The result of the processing.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		$order = $this->get_order( $request );
		$entity = $this->load_entity( $request );
		if ( false != $order && $order->get_id() ) {
			$this->process_order_related_inner( $order, $entity );
			if ($request->get_state() === \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED) {
				do_action( 'weareplanet_transaction_authorized_send_email', $order->get_id() );
			}
		}
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $transaction transaction.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function process_order_related_inner( WC_Order $order, $transaction ) {
		if ( strpos( $order->get_payment_method(), 'weareplanet' ) === false ) {
			return;
		}

		$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$transaction_state = $transaction->getState();
		if ( $transaction_state != $transaction_info->get_state() ) {
			switch ( $transaction_state ) {
				case \WeArePlanet\Sdk\Model\TransactionState::CONFIRMED:
				case \WeArePlanet\Sdk\Model\TransactionState::PROCESSING:
					$this->confirm( $transaction, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED:
					$this->authorize( $transaction, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::DECLINE:
					$this->decline( $transaction, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::FAILED:
					$this->failed( $transaction, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::FULFILL:
					$this->authorize( $transaction, $order );
					$this->fulfill( $transaction, $order );
					WC_WeArePlanet_Helper::set_virtual_zero_total_orders_to_complete( $order );
					WC_WeArePlanet_Helper::update_order_status_for_preorder_if_needed( $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::VOIDED:
					$this->voided( $transaction, $order );
					break;
				case \WeArePlanet\Sdk\Model\TransactionState::COMPLETED:
					$this->authorize( $transaction, $order );
					$this->waiting( $transaction, $order );
					break;
				default:
					// Nothing to do.
					break;
			}
			WC_WeArePlanet_Service_Transaction::instance()->update_transaction_info( $transaction, $order );
		}
	}

	/**
	 * Confirm.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function confirm( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_weareplanet_confirmed', true ) && ! $order->get_meta( '_weareplanet_authorized', true ) ) {
			do_action( 'wc_weareplanet_confirmed', $transaction, $order );
			$order->add_meta_data( '_weareplanet_confirmed', 'true', true );
			$default_status = apply_filters( 'wc_weareplanet_confirmed_status', 'wearep-redirected', $order );
			apply_filters( 'weareplanet_order_update_status', $order, \WeArePlanet\Sdk\Model\TransactionState::CONFIRMED, $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
		}
	}

	/**
	 * Authorize.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param \WC_Order $order order.
	 */
	protected function authorize( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_weareplanet_authorized', true ) ) {
			do_action( 'wc_weareplanet_authorized', $transaction, $order );
			$order->add_meta_data( '_weareplanet_authorized', 'true', true );
			$default_status = apply_filters( 'wc_weareplanet_authorized_status', 'on-hold', $order );
			apply_filters( 'weareplanet_order_update_status', $order, \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED, $default_status );
			wc_maybe_reduce_stock_levels( $order->get_id() );
			if ( isset( WC()->cart ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Waiting.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function waiting( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->get_meta( '_weareplanet_manual_check', true ) ) {
			do_action( 'wc_weareplanet_completed', $transaction, $order );
			$default_status = apply_filters( 'wc_weareplanet_completed_status', 'processing', $order );
			apply_filters( 'weareplanet_order_update_status', $order, \WeArePlanet\Sdk\Model\TransactionState::COMPLETED, $default_status );
		}
	}

	/**
	 * Decline.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function decline( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_weareplanet_declined', $transaction, $order );
		$default_status = apply_filters( 'wc_weareplanet_decline_status', 'cancelled', $order );
		apply_filters( 'weareplanet_order_update_status', $order, \WeArePlanet\Sdk\Model\TransactionState::DECLINE, $default_status );
		WC_WeArePlanet_Helper::instance()->maybe_restock_items_for_order( $order );
	}

	/**
	 * Failed.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function failed( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		if ( ! $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			return;
		}

		do_action( 'wc_weareplanet_failed', $transaction, $order );
		$valid_order_statuses = array(
			// Default pending status.
			'pending',
			// Custom order statuses mapped.
			apply_filters( 'weareplanet_wc_status_for_transaction', 'confirmed' ),
			apply_filters( 'weareplanet_wc_status_for_transaction', 'failed' )
		);
		if ( in_array( $order->get_status( 'edit' ), $valid_order_statuses ) ) {
			$default_status = apply_filters( 'wc_weareplanet_failed_status', 'failed', $order );
			apply_filters( 'weareplanet_order_update_status', $order, \WeArePlanet\Sdk\Model\TransactionState::FAILED, $default_status, );
			WC_WeArePlanet_Helper::instance()->maybe_restock_items_for_order( $order );
		}
	}

	/**
	 * Fulfill.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function fulfill( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		do_action( 'wc_weareplanet_fulfill', $transaction, $order );
		// Sets the status to procesing or complete depending on items.
		$order->payment_complete( $transaction->getId() );
	}

	/**
	 * Voided.
	 *
	 * @param \WeArePlanet\Sdk\Model\Transaction $transaction transaction.
	 * @param WC_Order $order order.
	 * @return void
	 */
	protected function voided( \WeArePlanet\Sdk\Model\Transaction $transaction, WC_Order $order ) {
		$default_status = apply_filters( 'wc_weareplanet_voided_status', 'cancelled', $order );
		apply_filters( 'weareplanet_order_update_status', $order, \WeArePlanet\Sdk\Model\TransactionState::VOIDED, $default_status );
		do_action( 'wc_weareplanet_voided', $transaction, $order );
	}
}
