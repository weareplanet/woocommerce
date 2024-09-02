<?php
/**
 *
 * WC_WeArePlanet_Webhook_Method_Configuration Class
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
	exit();
}
/**
 * Webhook processor to handle payment method configuration state transitions.
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Webhook_Method_Configuration_Strategy
 */
class WC_WeArePlanet_Webhook_Method_Configuration extends WC_WeArePlanet_Webhook_Abstract {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		$payment_method_configuration_service = WC_WeArePlanet_Service_Method_Configuration::instance();
		$payment_method_configuration_service->synchronize();
	}
}
