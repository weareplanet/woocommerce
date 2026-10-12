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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_WeArePlanet_Zero_Gateway.
 * This class handles the zero payment gateway of WeArePlanet
 *
 * @class WC_WeArePlanet_Zero_Gateway
 */
class WC_WeArePlanet_Zero_Gateway extends WC_Payment_Gateway {

	public const ZERO_PAYMENT_CONF_ID = 999999;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id = 'weareplanet_zero';
		$this->method_title = __( 'No Payment Required', 'woo-weareplanet' );
		$this->method_description = __( 'Used when order total is zero.', 'woo-weareplanet' );
		$this->title = __( 'No Payment Required', 'woo-weareplanet' );
		$this->enabled = 'yes';
		$this->has_fields = false;

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled = $this->get_option( 'enabled' );
		$this->title = $this->get_option( 'title' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		add_filter(
			'woocommerce_available_payment_gateways',
			array( $this, 'hide_gateways_for_zero_order_total' )
		);
	}

	/**
	 * Initialize form fields for the gateway settings.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woo-weareplanet' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable No Payment Required Gateway', 'woo-weareplanet' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'woo-weareplanet' ),
				'type'        => 'text',
				'description' => __( 'This controls the title seen by the customer during checkout.', 'woo-weareplanet' ),
				'default'     => __( 'No Payment Required', 'woo-weareplanet' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Check if the gateway is available for the current order.
	 *
	 * @return bool True if available, false otherwise.
	 */
	public function is_available() {
		if ( isset( $_GET['pay_for_order'] ) && boolval( $_GET['pay_for_order'] ) === true ) {// phpcs:disable WordPress.Security.NonceVerification.Recommended -- $pay_for_order is a woocommerce read-only URL param. Value is only used as a bool flag.
			$order_id = get_query_var( 'order-pay' );
			$order = wc_get_order( $order_id );

			if ( $order instanceof WC_Order && floatval( $order->get_total() ) == 0 ) {
				return true;
			}

			return false;
		}
		return ( WC()->cart && WC()->cart->total == 0 ) && 'yes' === $this->enabled;
	}

	/**
	 * Get the payment configuration ID for the zero payment gateway.
	 *
	 * @return int The payment configuration ID.
	 */
	public function get_payment_configuration_id() {
		// This can be a fake or static ID just to satisfy the interface.
		return self::ZERO_PAYMENT_CONF_ID;
	}

	/**
	 * Process the payment for the order.
	 *
	 * @param int $order_id The ID of the order being processed.
	 * @return array An array containing the result and redirect URL.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->payment_complete();
		$order->add_order_note( __( 'Order completed automatically – no payment needed.', 'woo-weareplanet' ) );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Hide other payment gateways when the order total is zero.
	 *
	 * @param array $available_gateways The available payment gateways.
	 * @return array The modified list of available payment gateways.
	 */
	public function hide_gateways_for_zero_order_total( $available_gateways ) {
		if ( is_admin() ) {
			return $available_gateways;
		}

		if ( isset( $_GET['pay_for_order'] ) && boolval( $_GET['pay_for_order'] ) === true ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $pay_for_order is a woocommerce read-only URL param. Value is only used as a bool flag.
			$order_id = get_query_var( 'order-pay' );
			$order = wc_get_order( $order_id );

			if ( $order instanceof WC_Order && floatval( $order->get_total() ) > 0 ) {
				return $available_gateways;
			}
		}

		$has_subscription = self::cart_has_subscription();
		if ( WC()->cart && WC()->cart->total == 0 && ! $has_subscription ) {
			foreach ( $available_gateways as $gateway_id => $gateway ) {
				if ( 'weareplanet_zero' !== $gateway_id ) {
					unset( $available_gateways[ $gateway_id ] );
				}
			}
		}

		return $available_gateways;
	}

	/**
	 * Check if the cart contains any subscription products.
	 *
	 * @return bool True if the cart has a subscription, false otherwise.
	 */
	public static function cart_has_subscription() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$subscription_classes = array(
			'WC_Subscription_Product',
			'WC_Product_Subscription',
			'WC_Product_Subscription_Variation',
		);

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			foreach ( $subscription_classes as $class ) {
				if ( $product instanceof $class ) {
					return true;
				}
			}
		}

		return false;
	}
}
