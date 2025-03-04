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
 * WC WeArePlanet Admin Order Void class
 */
class WC_WeArePlanet_Admin_Order_Void {

	/**
	 * Init.
	 *
	 * @return void
	 */
	public static function init() {
		add_action(
			'woocommerce_order_item_add_line_buttons',
			array(
				__CLASS__,
				'render_execute_void_button',
			)
		);

		add_action(
			'wp_ajax_woocommerce_weareplanet_execute_void',
			array(
				__CLASS__,
				'execute_void',
			)
		);

		add_action(
			'weareplanet_five_minutes_cron',
			array(
				__CLASS__,
				'update_voids',
			)
		);

		add_action(
			'weareplanet_update_running_jobs',
			array(
				__CLASS__,
				'update_for_order',
			)
		);
	}

	/**
	 * Render execute void button.
	 *
	 * @param WC_Order $order order.
	 * @return void
	 */
	public static function render_execute_void_button( WC_Order $order ) {
		$gateway = wc_get_payment_gateway_by_order( $order );
		if ( $gateway instanceof WC_WeArePlanet_Gateway ) {
			$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
			if ( $transaction_info->get_state() === \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED ) {
				echo '<button type="button" class="button weareplanet-void-button action-weareplanet-void-cancel" style="display:none">' .
					esc_html__( 'Cancel', 'woo-weareplanet' ) . '</button>';
				echo '<button type="button" class="button button-primary weareplanet-void-button action-weareplanet-void-execute" style="display:none">' .
					esc_html__( 'Execute Void', 'woo-weareplanet' ) . '</button>';
				echo '<label for="restock_voided_items" style="display:none">' . esc_html__( 'Restock items', 'woo-weareplanet' ) . '</label>';
				echo '<input type="checkbox" id="restock_voided_items" name="restock_voided_items" checked="checked" style="display:none">';
			}
		}
	}

	/**
	 * Execute void.
	 *
	 * @return void
	 * @throws Exception Exception.
	 */
	public static function execute_void() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) { // phpcs:ignore
			wp_die( -1 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( sanitize_key( wp_unslash( $_POST['order_id'] ) ) ) : null;

		$restock_void_items = isset( $_POST['restock_voided_items'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['restock_voided_items'] ) );
		$current_void_id = null;
		try {
			WC_WeArePlanet_Helper::instance()->start_database_transaction();
			$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_order_id( $order_id );
			if ( ! $transaction_info->get_id() ) {
				throw new Exception( __( 'Could not load corresponding transaction', 'woo-weareplanet' ) );
			}

			WC_WeArePlanet_Helper::instance()->lock_by_transaction_id( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );
			$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id(),
				$transaction_info->get_space_id()
			);

			if ( $transaction_info->get_state() !== \WeArePlanet\Sdk\Model\TransactionState::AUTHORIZED ) {
				throw new Exception( __( 'The transaction is not in a state to be voided.', 'woo-weareplanet' ) );
			}

			if ( WC_WeArePlanet_Entity_Void_Job::count_running_void_for_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id()
			) > 0 ) {
				throw new Exception( __( 'Please wait until the existing void is processed.', 'woo-weareplanet' ) );
			}
			if ( WC_WeArePlanet_Entity_Completion_Job::count_running_completion_for_transaction(
				$transaction_info->get_space_id(),
				$transaction_info->get_transaction_id()
			) > 0 ) {
				throw new Exception( __( 'There is a completion in process. The order can not be voided.', 'woo-weareplanet' ) );
			}

			$void_job = new WC_WeArePlanet_Entity_Void_Job();
			$void_job->set_restock( $restock_void_items );
			$void_job->set_space_id( $transaction_info->get_space_id() );
			$void_job->set_transaction_id( $transaction_info->get_transaction_id() );
			$void_job->set_state( WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_CREATED );
			$void_job->set_order_id( $order_id );
			$void_job->save();
			$current_void_id = $void_job->get_id();
			WC_WeArePlanet_Helper::instance()->commit_database_transaction();
		} catch ( Exception $e ) {
			WC_WeArePlanet_Helper::instance()->rollback_database_transaction();
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
			return;
		}

		try {
			self::send_void( $current_void_id );
			wp_send_json_success(
				array(
					'message' => __( 'The transaction is updated automatically once the result is available.', 'woo-weareplanet' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Send void.
	 *
	 * @param mixed $void_job_id void job id.
	 * @return void
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 */
	protected static function send_void( $void_job_id ) {
		$void_job = WC_WeArePlanet_Entity_Void_Job::load_by_id( $void_job_id );
		WC_WeArePlanet_Helper::instance()->start_database_transaction();
		WC_WeArePlanet_Helper::instance()->lock_by_transaction_id( $void_job->get_space_id(), $void_job->get_transaction_id() );
		// Reload void job.
		$void_job = WC_WeArePlanet_Entity_Void_Job::load_by_id( $void_job_id );
		if ( $void_job->get_state() !== WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_CREATED ) {
			// Already sent in the meantime.
			WC_WeArePlanet_Helper::instance()->rollback_database_transaction();
			return;
		}
		try {
			$void_service = new \WeArePlanet\Sdk\Service\TransactionVoidService( WC_WeArePlanet_Helper::instance()->get_api_client() );

			$void = $void_service->voidOnline( $void_job->get_space_id(), $void_job->get_transaction_id() );
			$void_job->set_void_id( $void->getId() );
			$void_job->set_state( WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_SENT );
			$void_job->save();
			WC_WeArePlanet_Helper::instance()->commit_database_transaction();
		} catch ( \WeArePlanet\Sdk\ApiException $e ) {
			if ( $e->getResponseObject() instanceof \WeArePlanet\Sdk\Model\ClientError ) {
				$void_job->set_state( WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_DONE );
				$void_job->save();
				WC_WeArePlanet_Helper::instance()->commit_database_transaction();
			} else {
				$void_job->save();
				WC_WeArePlanet_Helper::instance()->commit_database_transaction();
				WooCommerce_WeArePlanet::instance()->log( 'Error sending void. ' . $e->getMessage(), WC_Log_Levels::INFO );
				throw $e;
			}
		} catch ( Exception $e ) {
			$void_job->save();
			WC_WeArePlanet_Helper::instance()->commit_database_transaction();
			WooCommerce_WeArePlanet::instance()->log( 'Error sending void. ' . $e->getMessage(), WC_Log_Levels::INFO );
			throw $e;
		}
	}

	/**
	 * Update for order.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	public static function update_for_order( WC_Order $order ) {
		$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		$void_job = WC_WeArePlanet_Entity_Void_Job::load_running_void_for_transaction( $transaction_info->get_space_id(), $transaction_info->get_transaction_id() );

		if ( $void_job->get_state() === WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_CREATED ) {
			self::send_void( $void_job->get_id() );
		}
	}

	/**
	 * Update voids.
	 *
	 * @return void
	 */
	public static function update_voids() {
		$to_process = WC_WeArePlanet_Entity_Void_Job::load_not_sent_job_ids();
		foreach ( $to_process as $id ) {
			try {
				self::send_void( $id );
			} catch ( Exception $e ) {
				/* translators: %d: id, %s: message */
				$message = sprintf( __( 'Error updating void job with id %1$d: %2$s', 'woo-weareplanet' ), $id, $e->getMessage() );
				WooCommerce_WeArePlanet::instance()->log( $message, WC_Log_Levels::ERROR );
			}
		}
	}
}
WC_WeArePlanet_Admin_Order_Void::init();
