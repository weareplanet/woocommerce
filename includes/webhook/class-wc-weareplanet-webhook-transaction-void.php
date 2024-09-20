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
	 * Load entity.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return object|\WeArePlanet\Sdk\Model\TransactionVoid
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$void_service = new \WeArePlanet\Sdk\Service\TransactionVoidService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $void_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $void_transaction void transaction.
	 * @return int|string
	 */
	protected function get_order_id( $void_transaction ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionVoid $void */ //phpcs:ignore
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction( $void_transaction->getTransaction()->getLinkedSpaceId(), $void_transaction->getTransaction()->getId() )->get_order_id();
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
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $void_transaction ) {
		/* @var \WeArePlanet\Sdk\Model\TransactionVoid $void_transaction */ //phpcs:ignore
		switch ( $void_transaction->getState() ) {
			case \WeArePlanet\Sdk\Model\TransactionVoidState::FAILED:
				$this->failed( $void_transaction, $order );
				break;
			case \WeArePlanet\Sdk\Model\TransactionVoidState::SUCCESSFUL:
				$this->success( $void_transaction, $order );
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Success.
	 *
	 * @param \WeArePlanet\Sdk\Model\TransactionVoid $void_transaction void.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function success( \WeArePlanet\Sdk\Model\TransactionVoid $void_transaction, WC_Order $order ) {
		$void_job = WC_WeArePlanet_Entity_Void_Job::load_by_void( $void_transaction->getLinkedSpaceId(), $void_transaction->getId() );
		if ( ! $void_job->get_id() ) {
			// We have no void job with this id -> the server could not store the id of the void after sending the request. (e.g. connection issue or crash).
			// We only have on running void which was not yet processed successfully and use it as it should be the one the webhook is for.
			$void_job = WC_WeArePlanet_Entity_Void_Job::load_running_void_for_transaction( $void_transaction->getLinkedSpaceId(), $void_transaction->getLinkedTransaction() );
			if ( ! $void_job->get_id() ) {
				// void not initiated in shop backend ignore.
				return;
			}
			$void_job->set_void_id( $void_transaction->getId() );
		}
		$void_job->set_state( WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_DONE );

		if ( $void_job->get_restock() ) {
			WC_WeArePlanet_Helper::instance()->maybe_restock_items_for_order( $order );
		}
		$void_job->save();
	}

	/**
	 * Failed.
	 *
	 * @param \WeArePlanet\Sdk\Model\TransactionVoid $void_transaction void.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function failed( \WeArePlanet\Sdk\Model\TransactionVoid $void_transaction, WC_Order $order ) {
		$void_job = WC_WeArePlanet_Entity_Void_Job::load_by_void( $void_transaction->getLinkedSpaceId(), $void_transaction->getId() );
		if ( ! $void_job->get_id() ) {
			// We have no void job with this id -> the server could not store the id of the void after sending the request. (e.g. connection issue or crash)
			// We only have on running void which was not yet processed successfully and use it as it should be the one the webhook is for.
			$void_job = WC_WeArePlanet_Entity_Void_Job::load_running_void_for_transaction( $void_transaction->getLinkedSpaceId(), $void_transaction->getLinkedTransaction() );
			if ( ! $void_job->get_id() ) {
				// void not initiated in shop backend ignore.
				return;
			}
			$void_job->set_void_id( $void_transaction->getId() );
		}
		if ( $void_job->getFailureReason() != null ) {
			$void_job->set_failure_reason( $void_transaction->getFailureReason()->getDescription() );
		}
		$void_job->set_state( WC_WeArePlanet_Entity_Void_Job::WEAREPLANET_STATE_DONE );
		$void_job->save();
	}
}
