<?php
/**
 * WeArePlanet WooCommerce
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
 * Class WC_WeArePlanet_Webhook_Token_Version_Strategy
 * 
 * Handles the strategy for processing webhook requests related to token versions.
 * This class extends the base webhook strategy class to specifically manage webhook
 * requests that involve updates or changes to token versions. Token versions are crucial
 * for maintaining the integrity and version control of tokens used within the system.
 */
class WC_WeArePlanet_Webhook_Token_Version_Strategy extends WC_WeArePlanet_Webhook_Strategy_Base {
		
	/**
	 * @inheritDoc
	 */
	public function match( string $webhook_entity_id ) {
		return WC_WeArePlanet_Service_Webhook::WEAREPLANET_TOKEN_VERSION == $webhook_entity_id;
	}

	/**
	 * Processes the incoming webhook request associated with token versions.
	 *
	 * This method leverages the token service to update the version of a token identified by
	 * the space ID and entity ID provided in the webhook request. It ensures that the token version
	 * information is accurate and reflects any changes dictated by the incoming webhook data.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request.
	 * @return void 
	 * @throws Exception Throws an exception if there is a failure in updating the token version.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		$token_service = WC_WeArePlanet_Service_Token::instance();
		$token_service->update_token_version( $request->get_space_id(), $request->get_entity_id() );
	}
}
