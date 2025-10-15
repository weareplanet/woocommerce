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
 * Webhook processor to handle manual task state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Webhook_Manual_Task_Strategy
 */
class WC_WeArePlanet_Webhook_Manual_Task extends WC_WeArePlanet_Webhook_Abstract {

	/**
	 * Updates the number of open manual tasks.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		wc_deprecated_function(
            __METHOD__,
            '3.0.12',
            'WC_WeArePlanet_Webhook_Manual_Task_Strategy::process'
        );
		$strategy = new WC_WeArePlanet_Webhook_Manual_Task_Strategy();
		$strategy->process( $request );
	}
}
