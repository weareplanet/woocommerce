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
 * Class WC_WeArePlanet_Customer_Document.
 * This class handles the customer document downloads
 *
 * @class WC_WeArePlanet_Customer_Document
 */
class WC_WeArePlanet_Customer_Document {

	/**
	 * Initialise.
	 */
	public static function init() {
		add_action(
			'woocommerce_view_order',
			array(
				__CLASS__,
				'render_download_buttons',
			),
			20,
			1
		);
		add_action(
			'init',
			array(
				__CLASS__,
				'download_document',
			)
		);
	}

	/**
	 * Render the download buttons.
	 *
	 * @param int $order_id order id.
	 */
	public static function render_download_buttons( $order_id ) {
		$order  = WC_Order_Factory::get_order( $order_id );
		$method = wc_get_payment_gateway_by_order( $order );
		if ( ! ( $method instanceof WC_WeArePlanet_Gateway ) ) {
			return;
		}
		$transaction_info = WC_WeArePlanet_Entity_Transaction_Info::load_by_order_id( $order->get_id() );
		if ( is_null( $transaction_info->get_id() ) ) {
			return;
		}
		$packing = false;
		$invoice = false;
		if ( get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_CUSTOMER_INVOICE ) == 'yes' && in_array(
			$transaction_info->get_state(),
			array(
				\WeArePlanet\Sdk\Model\TransactionState::COMPLETED,
				\WeArePlanet\Sdk\Model\TransactionState::FULFILL,
				\WeArePlanet\Sdk\Model\TransactionState::DECLINE,
			),
			true
		) ) {
			$invoice = true;
		}
		if ( get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_CUSTOMER_PACKING ) == 'yes'
			&& $transaction_info->get_state() == \WeArePlanet\Sdk\Model\TransactionState::FULFILL
		) {
			$packing = true;
		}
		if ( $invoice || $packing ) {
			?>
<section class="woocommerce-order-weareplanet-documents">
	<h2><?php esc_html_e( 'Order Documents', 'woo-weareplanet' ); ?></h2>
				<?php if ( $invoice ) : ?>
					<span><a
		href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'weareplanet_action' => 'download_invoice',
									'post'  => $order_id,
									'nonce' => wp_create_nonce( 'download_invoice' ),
								)
							)
						);
						?>
				"
		class="woocommerce-order-weareplanet-download woocommerce-order-weareplanet-download-invoice"><?php esc_html_e( 'Download Invoice', 'woo-weareplanet' ); ?></a></span>
				<?php endif; ?>
				<?php if ( $packing ) : ?>
				<span><a
		href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'weareplanet_action' => 'download_packing',
								'post'                         => $order_id,
								'nonce'                        => wp_create_nonce( 'download_packing' ),
							)
						)
					);
					?>
				"
		class="woocommerce-order-weareplanet-download woocommerce-order-weareplanet-download-packingslip"><?php esc_html_e( 'Download Packing Slip', 'woo-weareplanet' ); ?></a></span>
				<?php endif; ?>
				
</section>
			<?php
		}
	}

	/**
	 * Check if request is PDF action.
	 *
	 * @return bool
	 */
	private static function is_pdf_request() {
		return ( isset( $_GET['post'] ) && isset( $_GET['weareplanet_action'] ) && isset( $_GET['nonce'] ) );
	}

	/**
	 * Frontend pdf actions callback.
	 * Customers only have permission to view invoice, so invoice should be created by system/admin.
	 */
	public static function download_document() {
		if ( ! self::is_pdf_request() ) {
			return;
		}

		// verify nonce.
		$action = isset( $_GET['weareplanet_action'] ) ? sanitize_key( wp_unslash( $_GET['weareplanet_action'] ) ) : false;
		$nonce = isset( $_GET['nonce'] ) ? sanitize_key( wp_unslash( $_GET['nonce'] ) ) : false;
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( 'Invalid request.' );
		}

		if ( ! is_user_logged_in() ) {
			wp_die( 'Access denied' );
		}

		// verify woocommerce order.
		$post_id = isset( $_GET['post'] ) ? absint( sanitize_key( wp_unslash( $_GET['post'] ) ) ) : false;
		$order   = WC_Order_Factory::get_order( $post_id );
		if ( ! $order ) {
			wp_die( 'Order not found.' );
		}

		// check if user has ordered order.
		$user             = wp_get_current_user();
		$order_id         = $order->get_id();
		$customer_user_id = $order->get_customer_id();
		if ( $user->ID != $customer_user_id ) {
			wp_die( 'Access denied' );
		}
		try {

			switch ( $action ) {
				case 'download_invoice':
					if ( get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_CUSTOMER_INVOICE ) != 'yes' ) {
						wp_die( 'Access denied' );
					}
					WC_WeArePlanet_Download_Helper::download_invoice( $order_id );
					break;
				case 'download_packing':
					if ( get_option( WooCommerce_WeArePlanet::WEAREPLANET_CK_CUSTOMER_PACKING ) != 'yes' ) {
						wp_die( 'Access denied' );
					}
					WC_WeArePlanet_Download_Helper::download_packing_slip( $order_id );
					break;
			}
		} catch ( Exception $e ) {
			wc_add_notice( __( 'There was an error downloading the document.', 'woo-weareplanet' ), 'error' );
		}
		wp_redirect( wc_get_endpoint_url( 'my-account/view-order', $order_id, wc_get_page_permalink( 'my-account' ) ) ); //phpcs:ignore
		exit();
	}
}
WC_WeArePlanet_Customer_Document::init();
