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
 * Handles the strategy for processing webhook requests related to manual tasks.
 *
 * This class extends the base webhook strategy class and is tailored specifically for handling
 * webhooks that deal with manual task updates. These tasks could involve manual interventions required
 * for certain operations within the system, which are triggered by external webhook events.
 */
class WC_WeArePlanet_Webhook_Manual_Task_Strategy extends WC_WeArePlanet_Webhook_Strategy_Base {

	/**
	 * Match function.
	 *
	 * @inheritDoc
	 * @param string $webhook_entity_id The webhook entity id.
	 */
	public function match( string $webhook_entity_id ) {
		return WC_WeArePlanet_Service_Webhook::WEAREPLANET_MANUAL_TASK == $webhook_entity_id;
	}

	/**
	 * Processes the incoming webhook request that pertains to manual tasks.
	 *
	 * This method activates the manual task service to handle updates based on the data provided
	 * in the webhook request. It could involve marking tasks as completed, updating their status, or
	 * initiating sub-processes required as part of the task resolution.
	 *
	 * @param WC_WeArePlanet_Webhook_Request $request The webhook request object containing all necessary data.
	 * @return void The method does not return a value but updates the state of manual tasks based on the webhook data.
	 * @throws Exception Throws an exception if there is a failure in processing the manual task updates.
	 */
	public function process( WC_WeArePlanet_Webhook_Request $request ) {
		$manual_task_service = WC_WeArePlanet_Service_Manual_Task::instance();
		$manual_task_service->update();
	}
}
