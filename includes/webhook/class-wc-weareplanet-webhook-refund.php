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
 * Webhook processor to handle refund state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Service_Refund
 */
class WC_WeArePlanet_Webhook_Refund extends WC_WeArePlanet_Webhook_Order_Related_Abstract {


	/**
	 * Load entity.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return object|\WeArePlanet\Sdk\Model\Refund
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	protected function load_entity( WC_WeArePlanet_Webhook_Request $request ) {
		$refund_service = new \WeArePlanet\Sdk\Service\RefundService( WC_WeArePlanet_Helper::instance()->get_api_client() );
		return $refund_service->read( $request->get_space_id(), $request->get_entity_id() );
	}

	/**
	 * Get order id.
	 *
	 * @param mixed $refund refund.
	 * @return int|string
	 */
	protected function get_order_id( $refund ) {
		/* @var \WeArePlanet\Sdk\Model\Refund $refund */ //phpcs:ignore
		return WC_WeArePlanet_Entity_Transaction_Info::load_by_transaction( $refund->getTransaction()->getLinkedSpaceId(), $refund->getTransaction()->getId() )->get_order_id();
	}

	/**
	 * Get transaction id.
	 *
	 * @param mixed $refund refund.
	 * @return int
	 */
	protected function get_transaction_id( $refund ) {
		/* @var \WeArePlanet\Sdk\Model\Refund $refund */ //phpcs:ignore
		return $refund->getTransaction()->getId();
	}

	/**
	 * Process order related inner.
	 *
	 * @param WC_Order $order order.
	 * @param mixed $refund refund.
	 * @return void
	 */
	protected function process_order_related_inner( WC_Order $order, $refund ) {
		/* @var \WeArePlanet\Sdk\Model\Refund $refund */ //phpcs:ignore
		switch ( $refund->getState() ) {
			case \WeArePlanet\Sdk\Model\RefundState::FAILED:
				// fallback.
				$this->failed( $refund, $order );
				break;
			case \WeArePlanet\Sdk\Model\RefundState::SUCCESSFUL:
				$this->refunded( $refund, $order );
				// Nothing to do.
			default:
				// Nothing to do.
				break;
		}
	}

	/**
	 * Failed.
	 *
	 * @param \WeArePlanet\Sdk\Model\Refund $refund refund.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function failed( \WeArePlanet\Sdk\Model\Refund $refund, WC_Order $order ) {
		$refund_job = WC_WeArePlanet_Entity_Refund_Job::load_by_external_id( $refund->getLinkedSpaceId(), $refund->getExternalId() );
		if ( $refund_job->get_id() ) {
			$refund_job->set_state( WC_WeArePlanet_Entity_Refund_Job::WEAREPLANET_STATE_FAILURE );
			if ( $refund->getFailureReason() != null ) {
				$refund_job->set_failure_reason( $refund->getFailureReason()->getDescription() );
			}
			$refund_job->save();
			$refunds = $order->get_refunds();
			foreach ( $refunds as $wc_refund ) {
				if ( $wc_refund->get_meta( '_weareplanet_refund_job_id', true ) == $refund_job->get_id() ) {
					$wc_refund->set_status( 'failed' );
					$wc_refund->save();
					break;
				}
			}
		}
	}

	/**
	 * Refunded.
	 *
	 * @param \WeArePlanet\Sdk\Model\Refund $refund refund.
	 * @param WC_Order $order order.
	 * @return void
	 * @throws Exception Exception.
	 */
	protected function refunded( \WeArePlanet\Sdk\Model\Refund $refund, WC_Order $order ) {
		$refund_job = WC_WeArePlanet_Entity_Refund_Job::load_by_external_id( $refund->getLinkedSpaceId(), $refund->getExternalId() );

		if ( $refund_job->get_id() ) {
			$refund_job->set_state( WC_WeArePlanet_Entity_Refund_Job::WEAREPLANET_STATE_SUCCESS );
			$refund_job->save();
			$refunds = $order->get_refunds();
			foreach ( $refunds as $wc_refund ) {
				if ( $wc_refund->get_meta( '_weareplanet_refund_job_id', true ) == $refund_job->get_id() ) {
					$wc_refund->set_status( 'completed' );
					$wc_refund->save();
					break;
				}
			}
		}
	}
}
