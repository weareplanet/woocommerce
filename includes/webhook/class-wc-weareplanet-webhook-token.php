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
 * Webhook processor to handle token state transitions.
 *
 * @deprecated 3.0.12 No longer used by internal code and not recommended.
 * @see WC_WeArePlanet_Webhook_Token_Strategy
 */
class WC_WeArePlanet_Webhook_Token extends WC_WeArePlanet_Webhook_Abstract {

	/**
	 * Process.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request request.
	 * @return void
	 * @throws \WeArePlanet\Sdk\ApiException ApiException.
	 * @throws \WeArePlanet\Sdk\Http\ConnectionException ConnectionException.
	 * @throws \WeArePlanet\Sdk\VersioningException VersioningException.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		$token_service = WC_WeArePlanet_Service_Token::instance();
		$token_service->update_token( $request->get_space_id(), $request->get_entity_id() );
	}
}
