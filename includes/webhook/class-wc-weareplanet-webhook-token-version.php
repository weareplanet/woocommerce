<?php
/**
 *
 * WC_WeArePlanet_Webhook_Token_Version Class
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
 * Webhook processor to handle token version state transitions.
 */
class WC_WeArePlanet_Webhook_Token_Version extends WC_WeArePlanet_Webhook_Abstract {

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
		$token_service->update_token_version( $request->get_space_id(), $request->get_entity_id() );
	}
}
